<?php

declare(strict_types=1);

// Read the local .env file when it exists. Shell-provided environment variables take priority.
$environmentValues = [];
$environmentFile = dirname(__DIR__) . '/.env';

if (is_file($environmentFile)) {
    $parsedValues = @parse_ini_file($environmentFile, false, INI_SCANNER_RAW);

    if (!is_array($parsedValues)) {
        throw new RuntimeException('The local environment file could not be read.');
    }

    $environmentValues = $parsedValues;
}

$readSetting = static function (string $name) use ($environmentValues): string {
    $systemValue = getenv($name);

    if ($systemValue !== false) {
        return $systemValue;
    }

    return (string) ($environmentValues[$name] ?? '');
};

return [
    'host' => $readSetting('DB_HOST'),
    'port' => $readSetting('DB_PORT'),
    'database' => $readSetting('DB_NAME'),
    'username' => $readSetting('DB_USER'),
    'password' => $readSetting('DB_PASSWORD'),
];
