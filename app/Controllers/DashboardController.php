<?php

declare(strict_types=1);

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
            'pages' => ['dashboard', 'projects', 'grades'],
        ],
    ];

    // These example records are sent to the page as a starting point for the admin demo.
    // Browser-side edits only change this page's copy and are not saved after navigation.
    private const DEMO_USERS = [
        ['id' => 'teacher-1', 'name' => 'Marta Vila Puig', 'username' => 'mvila', 'role' => 'teacher', 'group' => ''],
        ['id' => 'teacher-2', 'name' => 'Joan Marcet Soler', 'username' => 'jmarcet', 'role' => 'teacher', 'group' => ''],
        ['id' => 'student-1', 'name' => 'Marc Gómez Ribas', 'username' => 'mgomez', 'role' => 'student', 'group' => 'DAM2A'],
        ['id' => 'student-2', 'name' => 'Laura Pons Bosch', 'username' => 'lpons', 'role' => 'student', 'group' => 'DAM2B'],
    ];

    private const DEMO_ITEMS = [
        ['id' => 'item-1', 'code' => 'IT-001', 'name' => 'Documentació', 'description' => 'Qualitat i claredat de la documentació del projecte.'],
        ['id' => 'item-2', 'code' => 'IT-002', 'name' => 'Proves', 'description' => 'Execució i resultats de les proves del projecte.'],
    ];

    private const DEMO_PROJECTS = [
        [
            'id' => 'project-1',
            'code' => 'PRJ-001',
            'name' => 'Aplicació de gestió',
            'description' => 'Prototip d’una aplicació web de gestió acadèmica.',
            'group' => 'DAM2A',
            'status' => 'Actiu',
            'teacherIds' => ['teacher-1', 'teacher-2'],
            'studentIds' => ['student-1'],
            'itemIds' => ['item-1', 'item-2'],
        ],
        [
            'id' => 'project-2',
            'code' => 'PRJ-002',
            'name' => 'Catàleg digital',
            'description' => 'Disseny d’un catàleg digital amb cerca i filtres.',
            'group' => 'DAM2B',
            'status' => 'Actiu',
            'teacherIds' => ['teacher-1', 'teacher-2'],
            'studentIds' => ['student-2'],
            'itemIds' => ['item-2'],
        ],
    ];

    // Each record represents one student and one evaluable item inside a project.
    // The value map stores each teacher's individual grade before the mean is calculated.
    private const DEMO_GRADES = [
        [
            'projectId' => 'project-1',
            'studentId' => 'student-1',
            'itemId' => 'item-1',
            'teacherGrades' => ['teacher-1' => 8.5, 'teacher-2' => 9.0],
        ],
        [
            'projectId' => 'project-1',
            'studentId' => 'student-1',
            'itemId' => 'item-2',
            'teacherGrades' => ['teacher-2' => 7.5],
        ],
        [
            'projectId' => 'project-2',
            'studentId' => 'student-2',
            'itemId' => 'item-2',
            'teacherGrades' => ['teacher-1' => 8.0],
        ],
    ];

    public function index(): void
    {
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

        // Select a dedicated administrator view where one exists; other role pages keep the shared placeholder.
        $pageView = '/pages/placeholder.php';
        $adminDemoData = null;
        $teacherDemoData = null;

        if ($currentRoleKey === 'admin') {
            $adminDemoData = [
                'users' => self::DEMO_USERS,
                'projects' => self::DEMO_PROJECTS,
                'items' => self::DEMO_ITEMS,
            ];

            $pageView = match ($pageKey) {
                'dashboard' => '/pages/admin/dashboard.php',
                'users' => '/pages/admin/users.php',
                'projects' => '/pages/admin/projects.php',
                'evaluable-items' => '/pages/admin/evaluable_items.php',
                default => '/pages/placeholder.php',
            };
        }

        if ($currentRoleKey === 'teacher') {
            // The prototype login has one sample teacher; real account lookup will replace this ID later.
            $teacherId = 'teacher-1';
            $teacherProjects = array_values(array_filter(
                self::DEMO_PROJECTS,
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
                'users' => self::DEMO_USERS,
                'projects' => $teacherProjects,
                'items' => self::DEMO_ITEMS,
                'grades' => self::DEMO_GRADES,
            ];

            $pageView = match ($pageKey) {
                'dashboard' => '/pages/teacher/dashboard.php',
                'projects' => '/pages/teacher/projects.php',
                'project-details' => '/pages/teacher/project_details.php',
                'students' => '/pages/teacher/students.php',
                'grades' => '/pages/teacher/grades.php',
                default => '/pages/placeholder.php',
            };
        }

        // PHP includes the layout in this scope, so the layout and child views can read
        // these variables without another request or JavaScript-based page routing.
        require dirname(__DIR__) . '/Views/layouts/main.php';
    }
}
