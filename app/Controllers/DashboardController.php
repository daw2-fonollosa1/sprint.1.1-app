<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Models/DemoData.php';
require_once dirname(__DIR__) . '/Models/StudentDashboardData.php';

class DashboardController
{
    // Each array key is a short section identifier used in the URL, such as ?page=projects.
    // Its title and description are the Catalan text shown by the shared placeholder view.
    private const PAGES = [
        'dashboard' => [
            'title' => 'Tauler',
            'description' => 'Resum de l’activitat i de les dades de tota l’aplicació.',
        ],
        'projects' => [
            'title' => 'Projectes',
            'description' => 'Consulta els projectes assignats i obri’n els detalls.',
        ],
        'project-details' => [
            'title' => 'Detalls del projecte',
            'description' => 'Consulta l’alumnat i els ítems avaluables associats al projecte.',
        ],
        'users' => [
            'title' => 'Usuaris',
            'description' => 'Aquí es gestionaran els comptes de professorat i alumnat.',
        ],
        'evaluable-items' => [
            'title' => 'Ítems avaluables',
            'description' => 'Aquí es gestionaran els ítems que es poden assignar als projectes.',
        ],
        'grades' => [
            'title' => 'Qualificacions',
            'description' => 'Aquí es consultaran o introduiran les qualificacions segons el rol seleccionat.',
        ],
        'students' => [
            'title' => 'Alumnat',
            'description' => 'Consulta l’alumnat associat als teus projectes.',
        ],
    ];

    // This table controls which navigation links are displayed in the prototype for each role.
    // It is presentation data only; changing a URL does not create real role-based security.
    private const ROLES = [
        'admin' => [
            'label' => 'Administrador',
            'pages' => ['dashboard', 'projects', 'users', 'evaluable-items', 'grades'],
        ],
        'teacher' => [
            'label' => 'Professor/a',
            'pages' => ['dashboard', 'projects', 'project-details', 'students', 'grades'],
        ],
        'student' => [
            'label' => 'Alumne/a',
            'pages' => ['dashboard', 'projects', 'project-details', 'grades'],
        ],
    ];

    public function index(): void
    {
        // Read all shared example records once. The data class keeps their definitions
        // together, while this controller decides which role receives each part.
        $demoData = DemoData::all();

        // Read the role from the URL. Use the admin preview when it is missing or unknown,
        // so the page always has a valid role menu to display.
        $requestedRole = $_GET['role'] ?? 'admin';
        $currentRoleKey = is_string($requestedRole) && isset(self::ROLES[$requestedRole])
            ? $requestedRole
            : 'admin';
        $currentRole = self::ROLES[$currentRoleKey];

        // The selected page is also a URL value, so check its type before using it as an array key.
        $requestedPage = $_GET['page'] ?? 'dashboard';
        $pageKey = is_string($requestedPage) ? $requestedPage : 'dashboard';
        $isPageAvailable = in_array($pageKey, $currentRole['pages'], true);

        // A section must be listed for the selected preview role. Unknown or unavailable sections
        // get an HTTP 404 response and a friendly page message instead of an undefined array lookup.
        if (!$isPageAvailable) {
            http_response_code(404);
            $pageKey = 'dashboard';
            $page = [
                'title' => 'Pàgina no trobada',
                'description' => 'La secció no existeix o no forma part d’aquesta previsualització.',
            ];
        } else {
            $page = self::PAGES[$pageKey];
        }

        // Convert this role's section keys into link data used by navigation.php.
        $navigationItems = [];

        foreach ($currentRole['pages'] as $availablePageKey) {
            // Project details are opened from a project row, so they are not a top-level menu link.
            if ($availablePageKey === 'project-details') {
                continue;
            }

            $navigationItems[] = [
                'key' => $availablePageKey,
                'title' => self::PAGES[$availablePageKey]['title'],
            ];
        }

        // Build the labels for the role-preview dropdown shown in the shared navigation.
        $roleOptions = [];

        foreach (self::ROLES as $roleKey => $roleData) {
            $roleOptions[$roleKey] = $roleData['label'];
        }

        // Start with the shared placeholder, then select a dedicated view for each implemented role.
        $pageView = '/pages/placeholder.php';
        $adminDemoData = null;
        $adminUserPageData = null;
        $createOperationsData = null;
        $teacherDemoData = null;
        $studentDemoData = null;

        if ($currentRoleKey === 'admin') {
            $adminDemoData = [
                'users' => $demoData['users'],
                'projects' => $demoData['projects'],
                'items' => $demoData['items'],
            ];

            // The user-management section is the one administrator page backed by MariaDB.
            // Its controller receives POST actions and returns fresh users plus any feedback
            // so the same shared layout can render the result in the dedicated PHP view.
            if ($pageKey === 'users') {
                require_once dirname(__DIR__) . '/Controllers/AdminUsersController.php';
                $adminUserPageData = (new AdminUsersController())->handle();
            }

            // These administrator pages use database-backed create-only forms. The
            // controller also prepares the small lists needed by project assignments.
            if (in_array($pageKey, ['projects', 'evaluable-items'], true)) {
                require_once dirname(__DIR__) . '/Controllers/CreateOperationsController.php';
                $createOperationsData = (new CreateOperationsController())->handle($pageKey, $currentRoleKey);
            }

            // Use a simple switch so it is clear which administrator URL loads each view.
            switch ($pageKey) {
                case 'dashboard':
                    $pageView = '/pages/admin/dashboard.php';
                    break;
                case 'users':
                    $pageView = '/pages/admin/users.php';
                    break;
                case 'projects':
                    $pageView = '/pages/admin/projects.php';
                    break;
                case 'evaluable-items':
                    $pageView = '/pages/admin/evaluable_items.php';
                    break;
                default:
                    $pageView = '/pages/placeholder.php';
            }
        }

        if ($currentRoleKey === 'teacher') {
            // The prototype login has one sample teacher; real account lookup will replace this ID later.
            $teacherId = 'teacher-1';
            $teacherProjects = array_values(array_filter(
                $demoData['projects'],
                static fn (array $project): bool => in_array($teacherId, $project['teacherIds'], true),
            ));
            $teacherProjectIds = array_column($teacherProjects, 'id');
            $requestedProjectId = $_GET['project_id'] ?? '';
            $selectedProjectId = is_string($requestedProjectId) && in_array($requestedProjectId, $teacherProjectIds, true)
                ? $requestedProjectId
                : ($teacherProjects[0]['id'] ?? '');
            $selectedProject = null;

            foreach ($teacherProjects as $project) {
                if ($project['id'] === $selectedProjectId) {
                    $selectedProject = $project;
                    break;
                }
            }

            $selectedStudentId = '';
            $requestedStudentId = $_GET['student_id'] ?? '';
            if ($selectedProject !== null && is_string($requestedStudentId) && in_array($requestedStudentId, $selectedProject['studentIds'], true)) {
                $selectedStudentId = $requestedStudentId;
            }

            $selectedItemId = '';
            $requestedItemId = $_GET['item_id'] ?? '';
            if ($selectedProject !== null && is_string($requestedItemId) && in_array($requestedItemId, $selectedProject['itemIds'], true)) {
                $selectedItemId = $requestedItemId;
            }

            $teacherDemoData = [
                'teacherId' => $teacherId,
                'users' => $demoData['users'],
                'projects' => $teacherProjects,
                'items' => $demoData['items'],
                'grades' => $demoData['grades'],
            ];

            // Project creation and grade entry use the existing PDO setup. The
            // controller checks server-side session roles before every database write.
            if (in_array($pageKey, ['projects', 'grades'], true)) {
                require_once dirname(__DIR__) . '/Controllers/CreateOperationsController.php';
                $createOperationsData = (new CreateOperationsController())->handle($pageKey, $currentRoleKey);
            }

            // Each teacher section is a separate PHP view rendered by the shared layout.
            switch ($pageKey) {
                case 'dashboard':
                    $pageView = '/pages/teacher/dashboard.php';
                    break;
                case 'projects':
                    $pageView = '/pages/teacher/projects.php';
                    break;
                case 'project-details':
                    $pageView = '/pages/teacher/project_details.php';
                    break;
                case 'students':
                    $pageView = '/pages/teacher/students.php';
                    break;
                case 'grades':
                    $pageView = '/pages/teacher/grades.php';
                    break;
                default:
                    $pageView = '/pages/placeholder.php';
            }
        }

        if ($currentRoleKey === 'student') {
            // This preview always uses the sample student Marc. The model prepares only
            // his project and grade data before the layout includes a student view.
            $requestedProjectId = $_GET['project_id'] ?? null;
            $studentData = StudentDashboardData::build(
                'student-1',
                $demoData,
                is_string($requestedProjectId) ? $requestedProjectId : null,
            );

            // A missing or unassigned project must not render a different student's details.
            if ($pageKey === 'project-details' && $studentData['selectedProject'] === null) {
                http_response_code(404);
            }

            $studentDemoData = [
                'student' => $studentData['student'],
                'projects' => $studentData['projects'],
                'gradeRows' => $studentData['gradeRows'],
            ];
            $selectedProject = $studentData['selectedProject'];
            $studentGradeRows = $studentData['gradeRows'];
            $visibleStudentGradeRows = $studentData['visibleGradeRows'];
            $selectedGradeProjectId = $studentData['selectedGradeProjectId'];
            $gradedItemCount = $studentData['gradedItemCount'];
            $pendingItemCount = $studentData['pendingItemCount'];

            // Keep student navigation server-rendered by choosing a PHP view for the page.
            switch ($pageKey) {
                case 'dashboard':
                    $pageView = '/pages/student/dashboard.php';
                    break;
                case 'projects':
                    $pageView = '/pages/student/projects.php';
                    break;
                case 'project-details':
                    $pageView = '/pages/student/project_details.php';
                    break;
                case 'grades':
                    $pageView = '/pages/student/grades.php';
                    break;
                default:
                    $pageView = '/pages/placeholder.php';
            }
        }

        // PHP includes the layout in this scope, so the layout and child views can read
        // these variables without another request or JavaScript-based page routing.
        require dirname(__DIR__) . '/Views/layouts/main.php';
    }
}
