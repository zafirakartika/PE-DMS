<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../index.php');
}

$folderId = $_GET['id'] ?? null;
if (!$folderId) {
    redirect('folders.php');
}

// Check if folder has documents
$stmt = $pdo->prepare("SELECT COUNT(*) as doc_count FROM documents WHERE folder_id = ?");
$stmt->execute([$folderId]);
$result = $stmt->fetch();

if ($result['doc_count'] > 0) {
    redirect('folders.php?error=has_documents');
}

// Delete folder
$deleteStmt = $pdo->prepare("DELETE FROM folders WHERE id = ?");
$deleteStmt->execute([$folderId]);

redirect('folders.php?success=deleted');
?>
