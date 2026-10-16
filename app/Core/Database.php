<?php

declare(strict_types=1);

/**
 * Creates and reuses the application's PDO connection to MariaDB.
 * UserModel receives this connection and uses it for prepared SQL statements.
 */
class Database
{
    // Store the connection for this PHP request so separate model calls do not open
    // multiple database connections unnecessarily.
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        // The connection has already been created earlier in this request.
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        // The settings file reads the local .env file and then lets real environment
        // variables override it. Credentials stay outside the public/ web directory.
        $settings = require dirname(__DIR__, 2) . '/config/database.php';

        // Fail before building the DSN if the developer has not configured the basics.
        if ($settings['host'] === '' || $settings['database'] === '' || $settings['username'] === '') {
            throw new RuntimeException('Database settings are incomplete.');
        }

        // Validate the port as an integer in MariaDB's TCP port range before using it.
        $port = filter_var(
            $settings['port'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 65535]],
        );

        if ($port === false) {
            throw new RuntimeException('The database port must be between 1 and 65535.');
        }

        // DSN means Data Source Name: it tells PDO which database server, schema,
        // and character encoding to use. The password is passed separately below.
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $settings['host'],
            $port,
            $settings['database'],
        );

        // Keep one connection for this PHP request. Exception mode lets controllers
        // catch database failures, associative fetches return named columns, and
        // native prepares keep user values separate from SQL commands.
        self::$connection = new PDO($dsn, $settings['username'], $settings['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return self::$connection;
    }
}
