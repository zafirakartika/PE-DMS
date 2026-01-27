<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'dms_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default XAMPP password is empty

// Application Settings
define('SITE_NAME', 'Production Engineering DMS');
define('COMPANY_NAME', 'PT. Astra Daihatsu Motor');
define('DEPARTMENT', 'Production Engineering');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx']);

// Session settings
define('SESSION_TIMEOUT', 3600); // 1 hour

// Timezone
date_default_timezone_set('Asia/Kuala_Lumpur'); // Adjust to your timezone

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    die("Database connection failed: " . $e->getMessage());
}

// Start session
session_start();

// Helper function to check if admin is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check if user is admin (kept for compatibility)
function isSuperAdmin() {
    return isLoggedIn();
}

// Helper function to redirect
function redirect($url) {
    header("Location: $url");
    exit;
}

// Helper function to sanitize input
function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
?>
