<?php

declare(strict_types=1);

class DashboardController
{
    // Each array key is a short section identifier used in the URL, such as ?page=projects.
    // Its title and description are the Catalan text shown by the shared placeholder view.
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

    // This table controls which navigation links are displayed in the prototype for each role.
    // It is presentation data only; changing a URL does not create real role-based security.
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

        // PHP includes the layout in this scope, so the layout and child views can read
        // these variables without another request or JavaScript-based page routing.
        require dirname(__DIR__) . '/Views/layouts/main.php';
    }
}
