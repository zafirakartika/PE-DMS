<?php
/**
 * Application Configuration
 */

require_once __DIR__ . '/../core/helpers/env_helper.php';

// Load environment variables
loadEnv();

// Application Settings
define('SITE_NAME', env('SITE_NAME', 'Production Engineering DMS'));
define('COMPANY_NAME', env('COMPANY_NAME', 'PT. Astra Daihatsu Motor'));
define('DEPARTMENT', env('DEPARTMENT', 'Production Engineering'));

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', (int)env('MAX_FILE_SIZE', 52428800)); // 50MB default
define('ALLOWED_EXTENSIONS', explode(',', env('ALLOWED_EXTENSIONS', 'pdf')));

// Session Settings
define('SESSION_TIMEOUT', (int)env('SESSION_TIMEOUT', 3600)); // 1 hour

// Timezone
date_default_timezone_set(env('TIMEZONE', 'Asia/Kuala_Lumpur'));

// Environment
define('APP_ENV', env('APP_ENV', 'development'));

// Error Reporting
$errorReporting = (int)env('ERROR_REPORTING', 1);
$displayErrors = (int)env('DISPLAY_ERRORS', 1);

if (APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting($errorReporting ? E_ALL : 0);
    ini_set('display_errors', $displayErrors);
}

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Start session
session_start();
