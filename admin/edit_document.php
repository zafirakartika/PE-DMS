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

// Get all folders
$folders = $pdo->query("SELECT * FROM folders ORDER BY name")->fetchAll();

// Get all projects
$projects = $pdo->query("SELECT * FROM projects ORDER BY name")->fetchAll();

// Get all document types
$documentTypes = $pdo->query("SELECT * FROM document_types ORDER BY order_index")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean($_POST['title']);
    $description = clean($_POST['description']);
    $tags = clean($_POST['tags']);
    $folder_id = $_POST['folder_id'] ?: null;
    $project_id = $_POST['project_id'] ?: null;
    $document_type_id = $_POST['document_type_id'] ?: null;

    $updateStmt = $pdo->prepare("
        UPDATE documents
        SET title = ?, description = ?, tags = ?, folder_id = ?, project_id = ?, document_type_id = ?
        WHERE id = ?
    ");

    $updateStmt->execute([$title, $description, $tags, $folder_id, $project_id, $document_type_id, $documentId]);

    // Log activity
    $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, document_id, details, ip_address) VALUES (?, 'update', ?, ?, ?)");
    $logStmt->execute([$_SESSION['user_id'], $documentId, "Updated: $title", $_SERVER['REMOTE_ADDR']]);

    redirect('documents.php?success=updated');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Document - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/modern-style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f9fafb; font-size: 14px; }
        .container { max-width: 700px; margin: 0 auto; padding: 1rem; }
        .page-header { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem; }
        .page-header h1 { font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0; }
        .page-header i { font-size: 1.5rem; color: #0284c7; }

        .form-container { background: white; border-radius: 8px; padding: 1.1rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .form-group { margin-bottom: 0.9rem; }

        label { display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem; }
        input[type="text"], select, textarea { width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.8rem; color: #111827; transition: all 0.2s; }
        input[type="text"]:focus, select:focus, textarea:focus { outline: none; border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1); }
        textarea { resize: vertical; min-height: 70px; font-family: inherit; }
        select { cursor: pointer; }

        .info-box { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 6px; padding: 0.8rem; margin-bottom: 0.9rem; }
        .info-box p { margin: 0.3rem 0; font-size: 0.8rem; color: #0369a1; display: flex; align-items: center; gap: 0.4rem; }
        .info-box strong { font-weight: 600; color: #0c4a6e; }

        .form-actions { display: flex; gap: 0.6rem; padding-top: 0.5rem; border-top: 1px solid #f3f4f6; }
        .btn { padding: 0.4rem 1rem; border-radius: 6px; font-size: 0.8rem; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.2s; }
        .btn-primary { background: #0284c7; color: white; }
        .btn-primary:hover { background: #0369a1; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(2, 132, 199, 0.3); }
        .btn-secondary { background: #f3f4f6; color: #374151; }
        .btn-secondary:hover { background: #e5e7eb; }
    </style>
</head>
<body>
    <?php include '../includes/modern-admin-header.php'; ?>

    <div class="container">
        <div class="page-header">
            <i class="fas fa-edit"></i>
            <h1>Edit Document</h1>
        </div>

        <div class="form-container">
            <form action="" method="POST" class="document-form">
                <div class="form-group">
                    <label for="title">Document Title *</label>
                    <input type="text" id="title" name="title" value="<?php echo clean($document['title']); ?>" placeholder="Enter document title" required autofocus>
                </div>

                <div class="form-group">
                    <label for="folder_id">Folder</label>
                    <select id="folder_id" name="folder_id">
                        <option value="">-- Select Folder --</option>
                        <?php foreach ($folders as $folder): ?>
                            <option value="<?php echo $folder['id']; ?>"
                                <?php echo $document['folder_id'] == $folder['id'] ? 'selected' : ''; ?>>
                                <?php echo clean($folder['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="project_id">Project</label>
                    <select id="project_id" name="project_id">
                        <option value="">-- Select Project --</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>"
                                <?php echo $document['project_id'] == $project['id'] ? 'selected' : ''; ?>>
                                <?php echo clean($project['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="document_type_id">Document Type</label>
                    <select id="document_type_id" name="document_type_id">
                        <option value="">-- Select Document Type --</option>
                        <?php foreach ($documentTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>"
                                <?php echo $document['document_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo clean($type['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" placeholder="Enter document description (optional)"><?php echo clean($document['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="tags">Tags (comma separated)</label>
                    <input type="text" id="tags" name="tags" value="<?php echo clean($document['tags']); ?>" placeholder="e.g. invoice, 2024, important">
                </div>

                <div class="info-box">
                    <p><i class="fas fa-file"></i> <strong>Current File:</strong> <?php echo clean($document['filename']); ?></p>
                    <p><i class="fas fa-hdd"></i> <strong>File Size:</strong> <?php echo formatFileSize($document['file_size']); ?></p>
                    <p><i class="fas fa-clock"></i> <strong>Uploaded:</strong> <?php echo date('M d, Y H:i', strtotime($document['uploaded_at'])); ?></p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Document
                    </button>
                    <a href="documents.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
