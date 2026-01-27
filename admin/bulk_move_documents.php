<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['document_ids']) && isset($_POST['target_folder_id'])) {
    $documentIds = $_POST['document_ids'];
    $targetFolderId = (int)$_POST['target_folder_id'];

    if (empty($documentIds) || !is_array($documentIds)) {
        redirect('documents.php?error=invalid_request');
    }

    try {
        $pdo->beginTransaction();

        // Verify target folder exists
        $folderStmt = $pdo->prepare("SELECT id, name FROM folders WHERE id = ?");
        $folderStmt->execute([$targetFolderId]);
        $targetFolder = $folderStmt->fetch();

        if (!$targetFolder) {
            $pdo->rollBack();
            redirect('documents.php?error=folder_not_found');
        }

        // Update documents with new folder
        $placeholders = str_repeat('?,', count($documentIds) - 1) . '?';
        $updateStmt = $pdo->prepare("UPDATE documents SET folder_id = ? WHERE id IN ($placeholders)");
        $updateStmt->execute(array_merge([$targetFolderId], $documentIds));

        // Log activity
        $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'bulk_move', ?, ?)");
        $logStmt->execute([$_SESSION['user_id'], "Bulk moved " . count($documentIds) . " documents to folder: " . $targetFolder['name'], $_SERVER['REMOTE_ADDR']]);

        $pdo->commit();

        redirect('documents.php?success=bulk_moved');

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Bulk document move error: " . $e->getMessage());
        redirect('documents.php?error=move_failed');
    }
} else {
    redirect('documents.php?error=invalid_request');
}
?>
