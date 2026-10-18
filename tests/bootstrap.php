<?php

declare(strict_types=1);

// Tests must use an explicitly configured database whose name ends in _test.
$testSettings = [
    'host' => getenv('TEST_DB_HOST'),
    'port' => getenv('TEST_DB_PORT'),
    'database' => getenv('TEST_DB_NAME'),
    'username' => getenv('TEST_DB_USER'),
    'password' => getenv('TEST_DB_PASSWORD'),
];

foreach (['host', 'port', 'database', 'username', 'password'] as $setting) {
    if ($testSettings[$setting] === false) {
        throw new RuntimeException('Set all TEST_DB_* environment variables before running the tests.');
    }
}

foreach (['host', 'database', 'username'] as $setting) {
    if ($testSettings[$setting] === '') {
        throw new RuntimeException('TEST_DB_HOST, TEST_DB_NAME, and TEST_DB_USER cannot be empty.');
    }
}

// Only allow a simple, clearly marked test database name. This also prevents a
// typo from pointing the test suite at the regular development database.
if (!preg_match('/^[A-Za-z0-9_]+_test$/', (string) $testSettings['database'])) {
    throw new RuntimeException('TEST_DB_NAME must use only letters, numbers, and underscores and end in _test.');
}

// Read the normal application settings before replacing DB_* with test settings.
// Refuse to continue if both configurations point at the same database.
$projectRoot = dirname(__DIR__);
$developmentSettings = require $projectRoot . '/config/database.php';
if (strcasecmp($developmentSettings['database'], (string) $testSettings['database']) === 0) {
    throw new RuntimeException('The test database must be different from the configured application database.');
}

$port = filter_var(
    $testSettings['port'],
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 65535]],
);
if (!is_int($port)) {
    throw new RuntimeException('TEST_DB_PORT must be a number between 1 and 65535.');
}

// Database::getConnection() is the same PDO factory used by the application.
// Map the separate test settings to its regular DB_* names for this test process.
putenv('DB_HOST=' . $testSettings['host']);
putenv('DB_PORT=' . $port);
putenv('DB_NAME=' . $testSettings['database']);
putenv('DB_USER=' . $testSettings['username']);
putenv('DB_PASSWORD=' . $testSettings['password']);

require_once $projectRoot . '/app/Core/Database.php';

try {
    $connection = Database::getConnection();
} catch (Throwable $error) {
    // Do not print DSNs, usernames, passwords, or low-level database diagnostics.
    throw new RuntimeException('Could not connect to the configured test database. Check its settings and MariaDB service.');
}

if ($connection->query('SELECT DATABASE()')->fetchColumn() !== $testSettings['database']) {
    throw new RuntimeException('The PDO connection did not select the configured test database.');
}

// Stop with a clear setup message if the database schema has not been imported.
$tableCount = (int) $connection->query(
    "SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE()
       AND table_name IN ('users', 'projects', 'evaluable_items', 'teacher_projects',
                          'student_projects', 'project_evaluable_items', 'grades')",
)->fetchColumn();

if ($tableCount !== 7) {
    throw new RuntimeException('Import database/schema.sql into the dedicated test database before running tests.');
}

require_once __DIR__ . '/Integration/DatabaseTestCase.php';
