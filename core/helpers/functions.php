<?php
/**
 * Global Helper Functions
 */

// Only define these functions if they don't already exist
if (!function_exists('isLoggedIn')) {
    /**
     * Check if admin is logged in
     */
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('isSuperAdmin')) {
    /**
     * Check if user is admin (kept for compatibility)
     */
    function isSuperAdmin() {
        return isLoggedIn();
    }
}

if (!function_exists('redirect')) {
    /**
     * Redirect to a URL
     */
    function redirect($url) {
        header("Location: $url");
        exit;
    }
}

if (!function_exists('clean')) {
    /**
     * Sanitize input data
     */
    function clean($data) {
        if (is_array($data)) {
            return array_map('clean', $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('formatFileSize')) {
    /**
     * Format file size in human-readable format
     */
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
}

/**
 * Get current timestamp
 */
function now() {
    return date('Y-m-d H:i:s');
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

/**
 * Log activity
 */
function logActivity($pdo, $userId, $action, $description) {
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, description, ip_address, created_at)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $action, $description, getClientIP(), now()]);
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowedExtensions = null, $maxSize = null) {
    if ($allowedExtensions === null) {
        $allowedExtensions = ALLOWED_EXTENSIONS;
    }
    if ($maxSize === null) {
        $maxSize = MAX_FILE_SIZE;
    }

    $errors = [];

    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        $errors[] = "No file uploaded";
        return $errors;
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "File upload error: " . $file['error'];
        return $errors;
    }

    // Check file size
    if ($file['size'] > $maxSize) {
        $errors[] = "File size exceeds maximum allowed size of " . formatFileSize($maxSize);
    }

    // Check file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        $errors[] = "File type not allowed. Allowed types: " . implode(', ', $allowedExtensions);
    }

    return $errors;
}

/**
 * Generate unique filename
 */
function generateUniqueFilename($originalFilename) {
    $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
    return uniqid('', true) . '_' . time() . '.' . $extension;
}

/**
 * Get file type information (icon and label)
 */
function getFileTypeInfo($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    $fileTypes = [
        'pdf' => [
            'icon' => 'file-pdf',
            'label' => 'PDF Document',
            'color' => '#dc2626'
        ],
        'doc' => [
            'icon' => 'file-word',
            'label' => 'Word Document',
            'color' => '#2563eb'
        ],
        'docx' => [
            'icon' => 'file-word',
            'label' => 'Word Document',
            'color' => '#2563eb'
        ],
        'xls' => [
            'icon' => 'file-excel',
            'label' => 'Excel Spreadsheet',
            'color' => '#16a34a'
        ],
        'xlsx' => [
            'icon' => 'file-excel',
            'label' => 'Excel Spreadsheet',
            'color' => '#16a34a'
        ],
        'ppt' => [
            'icon' => 'file-powerpoint',
            'label' => 'PowerPoint Presentation',
            'color' => '#ea580c'
        ],
        'pptx' => [
            'icon' => 'file-powerpoint',
            'label' => 'PowerPoint Presentation',
            'color' => '#ea580c'
        ]
    ];

    return $fileTypes[$extension] ?? [
        'icon' => 'file-alt',
        'label' => 'Document',
        'color' => '#6b7280'
    ];
}

/**
 * Get MIME type for file extension
 */
function getMimeType($extension) {
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
    ];

    return $mimeTypes[$extension] ?? 'application/octet-stream';
}
