<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../index.php');
}

$projectId = $_GET['id'] ?? null;

if (!$projectId) {
    redirect('projects.php');
}

// Get project details
$stmt = $pdo->prepare("SELECT p.*, f.name as folder_name FROM projects p LEFT JOIN folders f ON p.folder_id = f.id WHERE p.id = ?");
$stmt->execute([$projectId]);
$project = $stmt->fetch();

if (!$project) {
    redirect('projects.php?error=not_found');
}

// Get all document types in order
$docTypes = $pdo->query("SELECT * FROM document_types ORDER BY order_index")->fetchAll();

// Get documents grouped by type
$documents = [];
foreach ($docTypes as $type) {
    $stmt = $pdo->prepare("
        SELECT d.*, dt.name as type_name, u.full_name as uploader_name
        FROM documents d
        LEFT JOIN document_types dt ON d.document_type_id = dt.id
        LEFT JOIN users u ON d.uploaded_by = u.id
        WHERE d.project_id = ? AND d.document_type_id = ?
        ORDER BY d.uploaded_at DESC
    ");
    $stmt->execute([$projectId, $type['id']]);
    $typeDocuments = $stmt->fetchAll();
    
    if (!empty($typeDocuments)) {
        $documents[$type['id']] = [
            'type' => $type,
            'files' => $typeDocuments
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Documents - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/modern-style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #f0f4ff 0%, #f9fafb 100%); font-size: 14px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }

        .page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 2rem; gap: 1rem; flex-wrap: wrap; }
        .page-header h1 { font-size: 2rem; font-weight: 800; color: #111827; margin: 0; letter-spacing: -0.5px; }
        .page-header a { padding: 0.65rem 1.25rem; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: white; text-decoration: none; border-radius: 8px; display: inline-flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 600; transition: all 0.3s; box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3); }
        .page-header a:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(2, 132, 199, 0.4); }

        .project-info { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); border: 1px solid #f0f0f0; border-left: 4px solid #0284c7; }
        .info-row { display: flex; align-items: center; gap: 2rem; margin-bottom: 1rem; }
        .info-row:last-child { margin-bottom: 0; }
        .info-label { font-weight: 700; color: #6b7280; min-width: 100px; font-size: 0.9rem; letter-spacing: 0.3px; }
        .info-value { color: #111827; font-weight: 500; }

        .document-section { margin-bottom: 2.5rem; }
        .section-title { font-size: 1.1rem; font-weight: 700; color: white; padding: 1rem 1.25rem; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border-radius: 10px 10px 0 0; display: flex; align-items: center; gap: 0.75rem; letter-spacing: 0.3px; box-shadow: 0 2px 6px rgba(2, 132, 199, 0.2); }
        .section-title .doc-index { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: rgba(255, 255, 255, 0.25); border-radius: 6px; font-size: 0.8rem; font-weight: 800; }

        .documents-list { background: white; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 10px 10px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); }
        .document-item { display: flex; align-items: center; justify-content: space-between; padding: 1.25rem; border-bottom: 1px solid #f3f4f6; transition: all 0.3s; }
        .document-item:last-child { border-bottom: none; }
        .document-item:hover { background: #f9fafb; }

        .document-info { flex: 1; }
        .document-title { font-weight: 700; color: #111827; margin-bottom: 0.5rem; word-break: break-word; font-size: 0.95rem; letter-spacing: -0.2px; }
        .document-meta { font-size: 0.8rem; color: #9ca3af; display: flex; align-items: center; gap: 1.25rem; font-weight: 500; }
        .meta-item { display: flex; align-items: center; gap: 0.35rem; }

        .document-actions { display: flex; gap: 0.5rem; }
        .btn-action { padding: 0.5rem 0.9rem; border-radius: 7px; font-size: 0.75rem; text-decoration: none; border: 1.5px solid #e5e7eb; background: white; color: #0284c7; transition: all 0.3s; font-weight: 600; letter-spacing: 0.2px; cursor: pointer; }
        .btn-action:hover { background: #f0f9ff; border-color: #0284c7; transform: translateY(-1px); }
        .btn-action.btn-danger { color: #dc2626; border-color: #fecaca; }
        .btn-action.btn-danger:hover { background: #fee2e2; border-color: #dc2626; }

        .empty-type { background: white; border: 1px solid #f0f0f0; border-radius: 10px; padding: 3rem 2rem; text-align: center; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); }
        .empty-type p { color: #9ca3af; font-size: 0.9rem; margin-bottom: 1rem; font-weight: 500; }
    </style>

        .project-info { background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .info-row { display: flex; align-items: center; gap: 2rem; margin-bottom: 0.75rem; }
        .info-row:last-child { margin-bottom: 0; }
        .info-label { font-weight: 600; color: #6b7280; width: 100px; }
        .info-value { color: #111827; }

        .document-section { margin-bottom: 2rem; }
        .section-title { font-size: 1.125rem; font-weight: 600; color: #111827; padding: 1rem; background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%); color: white; border-radius: 6px 6px 0 0; display: flex; align-items: center; gap: 0.5rem; }
        .section-title .doc-index { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; background: rgba(255, 255, 255, 0.2); border-radius: 50%; font-size: 0.75rem; font-weight: 700; margin-right: 0.5rem; }

        .documents-list { background: white; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 6px 6px; overflow: hidden; }
        .document-item { display: flex; align-items: center; justify-content: space-between; padding: 1rem; border-bottom: 1px solid #f3f4f6; transition: all 0.2s; }
        .document-item:last-child { border-bottom: none; }
        .document-item:hover { background: #f9fafb; }

        .document-info { flex: 1; }
        .document-title { font-weight: 600; color: #111827; margin-bottom: 0.25rem; word-break: break-word; }
        .document-meta { font-size: 0.75rem; color: #9ca3af; display: flex; align-items: center; gap: 1rem; }
        .meta-item { display: flex; align-items: center; gap: 0.25rem; }

        .document-actions { display: flex; gap: 0.5rem; }
        .btn-action { padding: 0.375rem 0.75rem; border-radius: 4px; font-size: 0.75rem; text-decoration: none; border: 1px solid #e5e7eb; background: white; color: #0284c7; transition: all 0.2s; }
        .btn-action:hover { background: #f0f9ff; border-color: #0284c7; }
        .btn-action.btn-danger { color: #dc2626; }
        .btn-action.btn-danger:hover { background: #fee2e2; border-color: #dc2626; }

        .empty-type { background: white; border: 1px dashed #d1d5db; border-radius: 6px; padding: 2rem; text-align: center; }
        .empty-type p { color: #9ca3af; font-size: 0.875rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <?php include '../includes/modern-admin-header.php'; ?>

    <div class="container">
        <div class="page-header">
            <div>
                <a href="projects.php" style="color: #6b7280; text-decoration: none; font-size: 0.875rem; display: inline-block; margin-bottom: 0.5rem;">← Projects</a>
                <h1 style="margin: 0; font-size: 1.5rem;"><?php echo clean($project['name']); ?></h1>
            </div>
            <a href="upload_document.php?project_id=<?php echo $projectId; ?>" class="btn btn-primary" style="padding: 0.5rem 1.25rem; border-radius: 6px; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.5rem; text-decoration: none; background: #0284c7; color: white;">
                Upload Document
            </a>
        </div>

        <div class="project-info">
            <?php if ($project['folder_name']): ?>
                <div class="info-row">
                    <span class="info-label">Folder:</span>
                    <span class="info-value"><?php echo clean($project['folder_name']); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($project['description']): ?>
                <div class="info-row">
                    <span class="info-label">Description:</span>
                    <span class="info-value"><?php echo clean($project['description']); ?></span>
                </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-label">Created:</span>
                <span class="info-value"><?php echo date('d M Y H:i', strtotime($project['created_at'])); ?></span>
            </div>
        </div>

        <div style="margin-top: 2rem;">
            <?php if (empty($documents)): ?>
                    <div class="empty-type" style="border: none; background: white; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                    <p>No documents uploaded yet</p>
                    <p style="color: #6b7280; font-size: 0.8rem; margin-bottom: 1.5rem;">Start by uploading your first document</p>
                    <a href="upload_document.php?project_id=<?php echo $projectId; ?>" class="btn btn-primary" style="padding: 0.5rem 1.25rem; border-radius: 6px; font-size: 0.875rem; display: inline-flex; align-items: center; gap: 0.5rem;">
                        Upload Document
                    </a>
                </div>
            <?php else: ?>
                <div style="background: white; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
                    <p style="margin: 0; color: #6b7280; font-size: 0.875rem;"><strong>Note:</strong> Documents are organized in order (1-11). Missing types will not appear.</p>
                </div>
                <?php 
                $docTypeIndex = 1;
                foreach ($docTypes as $type):
                    if (isset($documents[$type['id']])): 
                        $section = $documents[$type['id']];
                ?>
                    <div class="document-section">
                        <div class="section-title">
                            <span class="doc-index"><?php echo $docTypeIndex; ?></span>
                            <?php echo clean($section['type']['name']); ?>
                        </div>
                        <div class="documents-list">
                            <?php foreach ($section['files'] as $doc): ?>
                                <div class="document-item">
                                    <div class="document-info">
                                        <div class="document-title">
                                            <?php echo clean($doc['title']); ?>
                                        </div>
                                        <div class="document-meta">
                                            <span class="meta-item">
                                                <?php echo clean($doc['uploader_name'] ?? 'N/A'); ?>
                                            </span>
                                            <span class="meta-item">
                                                <?php echo date('d M Y', strtotime($doc['uploaded_at'])); ?>
                                            </span>
                                            <span class="meta-item">
                                                <?php echo $doc['download_count']; ?> downloads
                                            </span>
                                        </div>
                                    </div>
                                    <div class="document-actions">
                                        <a href="../download.php?id=<?php echo $doc['id']; ?>" class="btn-action">
                                            Download
                                        </a>
                                        <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="btn-action">
                                            View
                                        </a>
                                        <a href="edit_document.php?id=<?php echo $doc['id']; ?>" class="btn-action">
                                            Edit
                                        </a>
                                        <a href="delete_document.php?id=<?php echo $doc['id']; ?>" class="btn-action btn-danger" onclick="return confirm('Delete this document?');">
                                            Delete
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php $docTypeIndex++; ?>
                <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
