<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../index.php');
}

$documentId = $_GET['id'] ?? null;
if (!$documentId) {
    redirect('documents.php');
}

// Get document details
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$documentId]);
$document = $stmt->fetch();

if (!$document) {
    redirect('documents.php?error=not_found');
}

// Delete physical file
$filePath = UPLOAD_DIR . $document['file_path'];
if (file_exists($filePath)) {
    unlink($filePath);
}

// Log activity before deletion
$logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'delete', ?, ?)");
$logStmt->execute([$_SESSION['user_id'], "Deleted: " . $document['title'], $_SERVER['REMOTE_ADDR']]);

// Delete from database
$deleteStmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
$deleteStmt->execute([$documentId]);

redirect('documents.php?success=deleted');
?>
