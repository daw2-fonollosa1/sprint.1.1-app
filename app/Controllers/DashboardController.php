<?php

declare(strict_types=1);

class DashboardController
{
    // Page copy is kept separate from the page keys used in URLs.
    private const PAGES = [
        'dashboard' => [
            'title' => 'Tauler',
            'description' => 'Un resum de l’activitat de l’aplicació apareixerà en aquesta secció.',
        ],
        'projects' => [
            'title' => 'Projectes',
            'description' => 'Aquí es mostraran els projectes i les opcions disponibles per al rol seleccionat.',
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
    ];

    // These role menus are for the interface preview only, not access control.
    private const ROLES = [
        'admin' => [
            'label' => 'Administrador',
            'pages' => ['dashboard', 'projects', 'users', 'evaluable-items', 'grades'],
        ],
        'teacher' => [
            'label' => 'Professor/a',
            'pages' => ['dashboard', 'projects', 'grades'],
        ],
        'student' => [
            'label' => 'Alumne/a',
            'pages' => ['dashboard', 'projects', 'grades'],
        ],
    ];

    public function index(): void
    {
        // Read query parameters and fall back to a known role and page.
        $requestedRole = $_GET['role'] ?? 'admin';
        $currentRoleKey = is_string($requestedRole) && isset(self::ROLES[$requestedRole])
            ? $requestedRole
            : 'admin';
        $currentRole = self::ROLES[$currentRoleKey];

        $requestedPage = $_GET['page'] ?? 'dashboard';
        $pageKey = is_string($requestedPage) ? $requestedPage : 'dashboard';
        $isPageAvailable = in_array($pageKey, $currentRole['pages'], true);

        // Return a not-found response when the preview role has no such section.
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

        // Prepare the data that the shared navigation and layout will render.
        $navigationItems = [];

        foreach ($currentRole['pages'] as $availablePageKey) {
            $navigationItems[] = [
                'key' => $availablePageKey,
                'title' => self::PAGES[$availablePageKey]['title'],
            ];
        }

        $roleOptions = [];

        foreach (self::ROLES as $roleKey => $roleData) {
            $roleOptions[$roleKey] = $roleData['label'];
        }

        // Views receive the prepared data from this controller scope.
        require dirname(__DIR__) . '/Views/layouts/main.php';
    }
}
