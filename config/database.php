<?php

declare(strict_types=1);

// Read local settings from .env when it exists. This file is outside public/, so
// the browser cannot request the database username or password directly.
// Environment variables supplied by the shell or hosting service take priority.
$environmentValues = [];
$environmentFile = dirname(__DIR__) . '/.env';

if (is_file($environmentFile)) {
    // INI_SCANNER_RAW keeps values as written instead of interpreting them as typed values.
    // The @ prevents PHP's parser warning from being printed to the page; the check below
    // turns an unreadable file into a controlled exception for the application to handle.
    $parsedValues = @parse_ini_file($environmentFile, false, INI_SCANNER_RAW);

    if (!is_array($parsedValues)) {
        throw new RuntimeException('The local environment file could not be read.');
    }

    $environmentValues = $parsedValues;
}

// Use one small helper for every setting so all values follow the same priority rule:
// system environment first, local .env second, and an empty string if neither exists.
$readSetting = static function (string $name) use ($environmentValues): string {
    $systemValue = getenv($name);

    if ($systemValue !== false) {
        return $systemValue;
    }

    return (string) ($environmentValues[$name] ?? '');
};

// Return plain configuration values to Database::getConnection(); this file does not
// connect to MariaDB itself and does not expose the settings to a PHP view.
return [
    'host' => $readSetting('DB_HOST'),
    'port' => $readSetting('DB_PORT'),
    'database' => $readSetting('DB_NAME'),
    'username' => $readSetting('DB_USER'),
    'password' => $readSetting('DB_PASSWORD'),
];
