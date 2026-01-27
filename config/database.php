<?php
/**
 * Database Configuration and Connection
 */

require_once __DIR__ . '/../core/helpers/env_helper.php';

// Load environment variables
loadEnv();

// Database Configuration
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'dms_db'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // Log error in production, display in development
    if (env('APP_ENV') === 'production') {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please contact the administrator.");
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}

return $pdo;
