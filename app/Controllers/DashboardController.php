<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/RoleAuthorization.php';
require_once __DIR__ . '/ReadOperationsController.php';
require_once dirname(__DIR__) . '/Models/UserModel.php';

/** Selects a role page and provides it with database data. */
class DashboardController
{
    // Each route key names one menu and its matching database role. The session is
    // checked against databaseRole before the controller loads any role-specific data.
    private const ROLES = [
        'admin' => [
            'label' => 'Administrador',
            'databaseRole' => 'administrator',
            'pages' => ['dashboard', 'projects', 'users', 'evaluable-items', 'grades'],
        ],
        'teacher' => [
            'label' => 'Professor/a',
            'databaseRole' => 'teacher',
            'pages' => ['dashboard', 'projects', 'project-details', 'students', 'grades'],
        ],
        'student' => [
            'label' => 'Alumne/a',
            'databaseRole' => 'student',
            'pages' => ['dashboard', 'projects', 'project-details', 'grades'],
        ],
    ];

    // Keep page titles and navigation labels together so the views do not invent routes.
    private const PAGE_TITLES = [
        'dashboard' => 'Tauler',
        'projects' => 'Projectes',
        'project-details' => 'Detalls del projecte',
        'users' => 'Usuaris',
        'evaluable-items' => 'Ítems avaluables',
        'grades' => 'Qualificacions',
        'students' => 'Alumnat',
    ];

    public function index(): void
    {
        $currentRoleKey = $this->requestedRole();
        $currentRole = self::ROLES[$currentRoleKey];
        $pageKey = $this->requestedPage();
        $pageWasFound = in_array($pageKey, $currentRole['pages'], true);

        if (!$pageWasFound) {
            http_response_code(404);
            $pageKey = 'dashboard';
            $page = [
                'title' => 'Pàgina no trobada',
                'description' => 'La secció no existeix o no està disponible per a aquest rol.',
            ];
        } else {
            $page = [
                'title' => self::PAGE_TITLES[$pageKey],
                'description' => '',
            ];
        }

        $navigationItems = $this->navigationItems($currentRole['pages']);
        $pageView = $this->viewFor($currentRoleKey, $pageKey);

        // Start with empty values so each view has a predictable shape.
        $adminDashboardData = ['projects' => [], 'teachers' => [], 'students' => [], 'items' => [], 'gradeCount' => 0];
        $adminUserPageData = null;
        $createOperationsData = null;
        $updateOperationsData = null;
        $deleteOperationsData = null;
        $teacherData = [
            'projects' => [],
            'students' => [],
            'summary' => ['gradeCount' => 0, 'pendingGradeCount' => 0],
        ];
        $selectedProject = null;
        $studentData = $this->emptyStudentData();
        $studentGradeRows = [];
        $visibleStudentGradeRows = [];
        $selectedGradeProjectId = '';
        $assignedItemCount = 0;
        $gradedItemCount = 0;
        $pendingItemCount = 0;
        $readAccessError = '';
        $readDatabaseError = '';

        // The URL chooses a route key, but the session role decides whether it can open.
        // Read records only when that session account still exists in MariaDB.
        $sessionUser = RoleAuthorization::currentUser();

        if ($sessionUser === null) {
            http_response_code(401);
            $readAccessError = 'Cal iniciar una sessió autenticada per consultar les dades de l’aplicació.';
        } elseif ($sessionUser['role'] !== $currentRole['databaseRole']) {
            http_response_code(403);
            $readAccessError = 'El compte autenticat no té accés a la secció seleccionada.';
        } else {
            try {
                $connection = Database::getConnection();
                $users = new UserModel($connection);
                $databaseUser = $users->findByIdAndRole($sessionUser['id'], $currentRole['databaseRole']);

                if ($databaseUser === null) {
                    http_response_code(403);
                    $readAccessError = 'No s’ha pogut verificar el compte autenticat a la base de dades.';
                } else {
                    // Keep create, update, and delete requests in their own controllers.
                    // A delete POST must not be mistaken for an unknown create action.
                    $createState = null;
                    $isUpdatePost = $this->isUpdatePost();
                    $isDeletePost = $this->isDeletePost();
                    if ($this->isCreateFormPage($currentRoleKey, $pageKey) && !$isUpdatePost && !$isDeletePost) {
                        require_once __DIR__ . '/CreateOperationsController.php';
                        $createState = (new CreateOperationsController())->handle($pageKey, $currentRoleKey);
                    }

                    $updateState = null;
                    if ($this->isUpdateFormPage($currentRoleKey, $pageKey)) {
                        require_once __DIR__ . '/UpdateOperationsController.php';
                        $updateState = (new UpdateOperationsController())->handle($pageKey, $currentRoleKey);
                    }

                    $deleteState = null;
                    if ($this->isDeleteFormPage($currentRoleKey, $pageKey)) {
                        require_once __DIR__ . '/DeleteOperationsController.php';
                        $deleteState = (new DeleteOperationsController())->handle(
                            $pageKey,
                            $currentRoleKey,
                            $sessionUser['id'],
                        );
                    }

                    // One controller prepares the records displayed by the current page.
                    $readData = (new ReadOperationsController())->handle(
                        $connection,
                        $currentRoleKey,
                        $pageKey,
                        $sessionUser['id'],
                        $this->requestedProjectId(),
                    );

                    $adminDashboardData = $readData['adminDashboardData'];
                    $teacherData = $readData['teacherData'];
                    $selectedProject = $readData['selectedProject'];
                    $studentData = $readData['studentData'];

                    if ($this->isCreateFormPage($currentRoleKey, $pageKey)) {
                        // Existing views use one array for their read lists and create feedback.
                        $createOperationsData = array_merge(
                            $readData['formData'],
                            $createState ?? $this->emptyCreateState(),
                        );
                    }

                    if ($updateState !== null) {
                        // Update forms receive the same database choices plus their own messages.
                        $updateOperationsData = array_merge($readData['formData'], $updateState);
                    }

                    if ($deleteState !== null) {
                        // Delete forms use the same database lists and display their own result.
                        $deleteOperationsData = array_merge($readData['formData'], $deleteState);
                    }

                    if ($currentRoleKey === 'admin' && $pageKey === 'users') {
                        require_once __DIR__ . '/AdminUsersController.php';
                        $adminUserPageData = (new AdminUsersController())->handle($readData['managedUsers']);
                    }

                    $studentGradeRows = $studentData['gradeRows'];
                    $visibleStudentGradeRows = $studentData['visibleGradeRows'];
                    $selectedGradeProjectId = $studentData['selectedGradeProjectId'];
                    $assignedItemCount = $studentData['assignedItemCount'];
                    $gradedItemCount = $studentData['gradedItemCount'];
                    $pendingItemCount = $studentData['pendingItemCount'];

                    if ($currentRoleKey === 'student' && $studentData['student'] === null) {
                        http_response_code(403);
                        $readAccessError = 'El compte autenticat no és un alumne vàlid.';
                    }

                    if ($pageKey === 'project-details' && $selectedProject === null && $currentRoleKey === 'teacher') {
                        http_response_code(404);
                    }

                    if ($pageKey === 'project-details' && $studentData['selectedProject'] === null && $currentRoleKey === 'student') {
                        http_response_code(404);
                    }
                }
            } catch (Throwable $error) {
                // Keep SQL errors in the server log and show only a safe Catalan message.
                error_log('Application read failed: ' . $error->getMessage());
                $readDatabaseError = 'No s’han pogut carregar les dades. Revisa la connexió amb la base de dades.';
            }
        }

        // Include the selected PHP view inside the shared layout; navigation stays server-rendered.
        require dirname(__DIR__) . '/Views/layouts/main.php';
    }

    /** Identify pages whose existing PHP forms can create database records. */
    private function isCreateFormPage(string $roleKey, string $pageKey): bool
    {
        return ($roleKey === 'admin' && in_array($pageKey, ['projects', 'evaluable-items'], true))
            || ($roleKey === 'teacher' && in_array($pageKey, ['projects', 'grades'], true));
    }

    /** Identify the pages that contain the update forms handled by UpdateOperationsController. */
    private function isUpdateFormPage(string $roleKey, string $pageKey): bool
    {
        return ($roleKey === 'admin' && in_array($pageKey, ['projects', 'evaluable-items'], true))
            || ($roleKey === 'teacher' && in_array($pageKey, ['projects', 'grades'], true));
    }

    /** Identify pages that already display records with delete controls. */
    private function isDeleteFormPage(string $roleKey, string $pageKey): bool
    {
        return ($roleKey === 'admin' && in_array($pageKey, ['projects', 'evaluable-items', 'grades'], true))
            || ($roleKey === 'teacher' && $pageKey === 'grades');
    }

    /** Distinguish an update POST from the create forms that share the same pages. */
    private function isUpdatePost(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return false;
        }

        $action = $_POST['action'] ?? '';

        return is_string($action) && str_starts_with($action, 'update_');
    }

    /** Keep delete POST requests out of the existing create and update handlers. */
    private function isDeletePost(): bool
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return false;
        }

        $action = $_POST['action'] ?? '';

        return is_string($action) && str_starts_with($action, 'delete_');
    }

    /** Provide empty create values when this response came from an update form. */
    private function emptyCreateState(): array
    {
        return [
            'feedback' => '',
            'accessError' => '',
            'databaseError' => '',
            'validationErrors' => [],
            'submitted' => [],
        ];
    }

    /** Read the page role key; the session role is checked separately before data is loaded. */
    private function requestedRole(): string
    {
        $role = $_GET['role'] ?? 'admin';

        return is_string($role) && isset(self::ROLES[$role]) ? $role : 'admin';
    }

    /** Return the requested page key, or the dashboard as a safe default. */
    private function requestedPage(): string
    {
        $page = $_GET['page'] ?? 'dashboard';

        return is_string($page) ? $page : 'dashboard';
    }

    /** Read a project filter only when it is a simple string value. */
    private function requestedProjectId(): ?string
    {
        $projectId = $_GET['project_id'] ?? null;

        // Keep the selected project after an invalid teacher grade submission.
        if ($projectId === null && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $projectId = $_POST['project_id'] ?? null;
        }

        return is_string($projectId) ? $projectId : null;
    }

    /** Build the current role's menu and leave project details as a contextual link. */
    private function navigationItems(array $pageKeys): array
    {
        $items = [];

        foreach ($pageKeys as $pageKey) {
            if ($pageKey !== 'project-details') {
                $items[] = ['key' => $pageKey, 'title' => self::PAGE_TITLES[$pageKey]];
            }
        }

        return $items;
    }

    /** Map a role and page to the PHP template that renders it. */
    private function viewFor(string $role, string $page): string
    {
        $views = [
            'admin' => [
                'dashboard' => '/pages/admin/dashboard.php',
                'projects' => '/pages/admin/projects.php',
                'users' => '/pages/admin/users.php',
                'evaluable-items' => '/pages/admin/evaluable_items.php',
                'grades' => '/pages/admin/grades.php',
            ],
            'teacher' => [
                'dashboard' => '/pages/teacher/dashboard.php',
                'projects' => '/pages/teacher/projects.php',
                'project-details' => '/pages/teacher/project_details.php',
                'students' => '/pages/teacher/students.php',
                'grades' => '/pages/teacher/grades.php',
            ],
            'student' => [
                'dashboard' => '/pages/student/dashboard.php',
                'projects' => '/pages/student/projects.php',
                'project-details' => '/pages/student/project_details.php',
                'grades' => '/pages/student/grades.php',
            ],
        ];

        return $views[$role][$page] ?? '/pages/placeholder.php';
    }

    /** Keep student view variables empty while an unauthenticated request is blocked. */
    private function emptyStudentData(): array
    {
        return [
            'student' => null,
            'projects' => [],
            'gradeRows' => [],
            'visibleGradeRows' => [],
            'filterError' => '',
            'selectedProject' => null,
            'selectedGradeProjectId' => '',
            'assignedItemCount' => 0,
            'gradedItemCount' => 0,
            'pendingItemCount' => 0,
        ];
    }
}
