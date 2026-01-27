<?php
require_once 'config/config.php';
require_once 'core/helpers/functions.php';

// PUBLIC ACCESS - No login required for viewing/downloading documents

$documentId = $_GET['id'] ?? null;
$isView = isset($_GET['view']);

if (!$documentId) {
    die('Invalid document ID');
}

// Get document details
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$documentId]);
$document = $stmt->fetch();

if (!$document) {
    die('Document not found');
}

$filePath = UPLOAD_DIR . $document['file_path'];

if (!file_exists($filePath)) {
    die('File not found on server');
}

// Update download count if not just viewing
if (!$isView) {
    $updateStmt = $pdo->prepare("UPDATE documents SET download_count = download_count + 1 WHERE id = ?");
    $updateStmt->execute([$documentId]);

    // Log activity (only if user is logged in)
    if (isLoggedIn()) {
        $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, document_id, details, ip_address) VALUES (?, 'download', ?, ?, ?)");
        $logStmt->execute([$_SESSION['user_id'], $documentId, "Downloaded: " . $document['title'], $_SERVER['REMOTE_ADDR']]);
    } else {
        // Log anonymous download
        $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, document_id, details, ip_address) VALUES (NULL, 'download', ?, ?, ?)");
        $logStmt->execute([$documentId, "Public download: " . $document['title'], $_SERVER['REMOTE_ADDR']]);
    }
}

// Determine MIME type based on file extension
$extension = strtolower(pathinfo($document['filename'], PATHINFO_EXTENSION));
$contentType = getMimeType($extension);

// Set headers for file download or viewing
header('Content-Type: ' . $contentType);
header('Content-Length: ' . filesize($filePath));

if ($isView) {
    header('Content-Disposition: inline; filename="' . $document['filename'] . '"');
} else {
    header('Content-Disposition: attachment; filename="' . $document['filename'] . '"');
}

header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Output file
readfile($filePath);
exit;
?>
