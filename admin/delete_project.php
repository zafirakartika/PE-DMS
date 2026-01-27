<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../index.php');
}

$projectId = $_GET['id'] ?? null;

if (!$projectId) {
    redirect('projects.php');
}

// Check if project has documents
$stmt = $pdo->prepare("SELECT COUNT(*) as doc_count FROM documents WHERE project_id = ?");
$stmt->execute([$projectId]);
$result = $stmt->fetch();

if ($result['doc_count'] > 0) {
    redirect('projects.php?error=has_documents');
}

// Delete the project
$stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
if ($stmt->execute([$projectId])) {
    redirect('projects.php?success=deleted');
} else {
    redirect('projects.php?error=delete_failed');
}
