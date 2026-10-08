<?php

declare(strict_types=1);

// Start the request in the controller; keep this public entry point minimal.
require_once dirname(__DIR__) . '/app/Controllers/DashboardController.php';

$controller = new DashboardController();
$controller->index();
