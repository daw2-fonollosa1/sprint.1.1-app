<?php

declare(strict_types=1);

// This file is the single public entry point: the web server sends page requests here.
// PHP reads the "page" query parameter so it can choose which controller should build the response.
$requestedPage = $_GET['page'] ?? 'login';
// Query parameters can be arrays if a URL is specially formed, so accept only a plain string.
// If no usable value was provided, show the login page as the default.
$page = is_string($requestedPage) ? $requestedPage : 'login';

if ($page === 'login') {
    // Load the login controller only when the visitor asked for the login page.
    require_once dirname(__DIR__) . '/app/Controllers/LoginController.php';
    // The controller checks the submitted form and selects the view to render.
    $controller = new LoginController();
    $controller->index();
    // Stop here so this request cannot also render the dashboard layout below.
    exit;
}

// Every other page value is handled by the role-aware dashboard prototype controller.
require_once dirname(__DIR__) . '/app/Controllers/DashboardController.php';
$controller = new DashboardController();
// The controller reads the requested section and includes the shared application layout.
$controller->index();
