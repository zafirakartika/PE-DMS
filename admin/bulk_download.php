<?php
require_once '../config/config.php';

if (!isLoggedIn()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['document_ids'])) {
    $documentIds = $_POST['document_ids'];

    if (empty($documentIds) || !is_array($documentIds)) {
        die('Invalid request');
    }

    // Get document details
    $placeholders = str_repeat('?,', count($documentIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT id, title, filename FROM documents WHERE id IN ($placeholders)");
    $stmt->execute($documentIds);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($documents)) {
        die('No documents found');
    }

    if (count($documents) === 1) {
        // Single file download
        $doc = $documents[0];
        $filePath = '../uploads/' . $doc['filename'];

        if (file_exists($filePath)) {
            // Update download count
            $updateStmt = $pdo->prepare("UPDATE documents SET download_count = download_count + 1 WHERE id = ?");
            $updateStmt->execute([$doc['id']]);

            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'download', ?, ?)");
            $logStmt->execute([$_SESSION['user_id'], "Downloaded document: " . $doc['title'], $_SERVER['REMOTE_ADDR']]);

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($doc['filename']) . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            die('File not found');
        }
    } else {
        // Multiple files - create ZIP
        $zipName = 'documents_' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = sys_get_temp_dir() . '/' . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) {
            die('Could not create ZIP file');
        }

        $downloadedCount = 0;
        foreach ($documents as $doc) {
            $filePath = '../uploads/' . $doc['filename'];
            if (file_exists($filePath)) {
                $zip->addFile($filePath, $doc['title'] . '.' . pathinfo($doc['filename'], PATHINFO_EXTENSION));
                $downloadedCount++;

                // Update download count
                $updateStmt = $pdo->prepare("UPDATE documents SET download_count = download_count + 1 WHERE id = ?");
                $updateStmt->execute([$doc['id']]);
            }
        }

        $zip->close();

        if ($downloadedCount > 0) {
            // Log activity
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'bulk_download', ?, ?)");
            $logStmt->execute([$_SESSION['user_id'], "Bulk downloaded $downloadedCount documents", $_SERVER['REMOTE_ADDR']]);

            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $zipName . '"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
            unlink($zipPath); // Delete temp file
            exit;
        } else {
            unlink($zipPath);
            die('No files could be downloaded');
        }
    }
} else {
    die('Invalid request method');
}
?>
