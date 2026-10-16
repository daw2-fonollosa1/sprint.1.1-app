<?php

declare(strict_types=1);

// Start the server-side session before any controller reads the logged-in account.
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Use one session token for login and logout forms to reject forged POST requests.
if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// This file is the single public entry point: the web server sends page requests here.
// PHP reads the "page" query parameter so it can choose which controller should build the response.
$requestedPage = $_GET['page'] ?? 'login';
// Query parameters can be arrays if a URL is specially formed, so accept only a plain string.
// If no usable value was provided, show the login page as the default.
$page = is_string($requestedPage) ? $requestedPage : 'login';

if ($page === 'login' || $page === 'logout') {
    // Keep both authentication actions in the same small controller.
    require_once dirname(__DIR__) . '/app/Controllers/LoginController.php';
    $controller = new LoginController();

    if ($page === 'logout') {
        $controller->logout();
    }

    $controller->index();
    exit;
}

// Every other page value is handled by the role-aware dashboard controller.
require_once dirname(__DIR__) . '/app/Controllers/DashboardController.php';
$controller = new DashboardController();
// The controller reads the requested section and includes the shared application layout.
$controller->index();
