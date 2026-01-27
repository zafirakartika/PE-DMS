<?php
/**
 * Environment Helper
 * Loads and parses .env file for configuration
 */

function loadEnv($filePath = null) {
    if ($filePath === null) {
        $filePath = __DIR__ . '/../../.env';
    }

    // If .env doesn't exist, use defaults from .env.example
    if (!file_exists($filePath)) {
        $filePath = __DIR__ . '/../../.env.example';
    }

    if (!file_exists($filePath)) {
        return false;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse key=value pairs
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            $value = trim($value, '"\'');

            // Set as environment variable
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    return true;
}

function env($key, $default = null) {
    $value = getenv($key);

    if ($value === false) {
        return $default;
    }

    // Convert string representations to proper types
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
        case 'empty':
        case '(empty)':
            return '';
    }

    return $value;
}
