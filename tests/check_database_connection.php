<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Core/Database.php';

try {
    // A small query confirms that the connection can communicate with the selected database.
    Database::getConnection()->query('SELECT 1')->fetchColumn();

    echo "Database connection successful.\n";
    exit(0);
} catch (Throwable) {
    // Keep credentials, DSN details, and PHP exception traces out of the command output.
    fwrite(
        STDERR,
        "Database connection failed. Check MariaDB, the PDO MySQL driver, and your local settings.\n",
    );
    exit(1);
}
