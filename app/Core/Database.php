<?php

declare(strict_types=1);

/**
 * Creates and reuses the application's PDO connection to MariaDB.
 */
class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $settings = require dirname(__DIR__, 2) . '/config/database.php';

        if ($settings['host'] === '' || $settings['database'] === '' || $settings['username'] === '') {
            throw new RuntimeException('Database settings are incomplete.');
        }

        $port = filter_var(
            $settings['port'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 65535]],
        );

        if ($port === false) {
            throw new RuntimeException('The database port must be between 1 and 65535.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $settings['host'],
            $port,
            $settings['database'],
        );

        // Keep one connection for this PHP request and make PDO raise catchable errors.
        self::$connection = new PDO($dsn, $settings['username'], $settings['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$connection;
    }
}
