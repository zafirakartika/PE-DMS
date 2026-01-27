<?php
require_once 'config/config.php';

// Get document ID - PUBLIC ACCESS (no login required)
$docId = $_GET['id'] ?? 0;

// Fetch document details
$stmt = $pdo->prepare("
    SELECT d.*, f.name as folder_name, p.name as project_name, dt.name as document_type, u.full_name as uploader_name
    FROM documents d
    LEFT JOIN folders f ON d.folder_id = f.id
    LEFT JOIN projects p ON d.project_id = p.id
    LEFT JOIN document_types dt ON d.document_type_id = dt.id
    LEFT JOIN users u ON d.uploaded_by = u.id
    WHERE d.id = ?
");
$stmt->execute([$docId]);
$document = $stmt->fetch();

if (!$document) {
    die('Document not found');
}

// Note: Download count is incremented in download.php when user actually downloads
// NOT when just viewing the document details
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo clean($document['title']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/x-icon" href="assets/images/daihatsu-logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/modern-style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f9fafb; font-size: 14px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }

        .header {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            min-height: 70px;
            gap: 1.5rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-image {
            width: auto;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .logo-image img {
            height: 100%;
            width: auto;
            max-width: 150px;
            object-fit: contain;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }

        .logo-main {
            font-size: 1.1rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            letter-spacing: -0.01em;
            line-height: 1.2;
        }

        .logo-subtitle {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 400;
            letter-spacing: 0.02em;
            line-height: 1.2;
        }

        .header-spacer {
            flex: 1;
        }

        .nav {
            display: flex;
            gap: 1rem;
        }

        .nav a {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.25s;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .nav a:hover {
            background: white;
            color: #0284c7;
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
        }

        .container { max-width: 1200px; margin: 0 auto; padding: 1rem; }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            background: white;
            padding: 1.25rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .page-actions { display: flex; gap: 0.5rem; }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-primary { background: #0284c7; color: white; }
        .btn-primary:hover { background: #0369a1; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(2, 132, 199, 0.3); }

        .file-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .file-icon.pdf { background: #fee2e2; color: #dc2626; }
        .file-icon.word { background: #dbeafe; color: #2563eb; }
        .file-icon.excel { background: #d1fae5; color: #16a34a; }
        .file-icon.powerpoint { background: #fed7aa; color: #ea580c; }

        .document-details {
            background: white;
            border-radius: 8px;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .detail-item { display: flex; flex-direction: column; gap: 0.25rem; }
        .detail-item.full-width { grid-column: 1 / -1; margin-top: 0.5rem; padding-top: 1rem; border-top: 1px solid #f3f4f6; }
        .detail-item label { font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: 0.025em; }
        .detail-item span, .detail-item p { font-size: 0.875rem; color: #111827; }
        .detail-item p { margin: 0; line-height: 1.6; }

        .tags { display: flex; flex-wrap: wrap; gap: 0.375rem; }
        .tag { background: #f0f9ff; color: #0369a1; padding: 0.25rem 0.625rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500; }

        .preview-container { background: white; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .preview-container h2 { font-size: 1.125rem; font-weight: 600; color: #111827; margin: 0 0 1rem 0; display: flex; align-items: center; gap: 0.5rem; }

        .pdf-viewer { background: #f9fafb; border-radius: 6px; overflow: hidden; }
        .pdf-viewer embed { display: block; border: none; }

        .download-prompt { text-align: center; padding: 3rem 2rem; }
        .download-prompt i { font-size: 4rem; margin-bottom: 1rem; }
        .download-prompt i.fa-file-word { color: #2563eb; }
        .download-prompt i.fa-file-excel { color: #16a34a; }
        .download-prompt i.fa-file-powerpoint { color: #ea580c; }
        .download-prompt p { color: #6b7280; margin-bottom: 1.5rem; font-size: 0.875rem; }
        .download-prompt .btn-primary { font-size: 1rem; padding: 0.75rem 1.5rem; }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .page-actions {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="logo">
                <div class="logo-image">
                    <img src="assets/images/daihatsu-logo.png" alt="Document Management System" onerror="this.parentElement.innerHTML='<i class=\'fas fa-industry\' style=\'font-size:32px;color:#0284c7;\'></i>'">
                </div>
                <div class="logo-text">
                    <div class="logo-main">
                        Document Management System
                    </div>
                    <div class="logo-subtitle">
                        Production Engineering Casting
                    </div>
                </div>
            </div>
            <div class="header-spacer"></div>
            <nav class="nav">
                <a href="index.php">
                    <i class="fas fa-arrow-left" style="margin-right: 0.25rem;"></i>
                    Back to Documents
                </a>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php
        // Get file extension and icon info
        $extension = strtolower(pathinfo($document['filename'], PATHINFO_EXTENSION));
        $isPDF = ($extension === 'pdf');

        $iconClass = ($extension === 'pdf') ? 'pdf' :
                    (($extension === 'doc' || $extension === 'docx') ? 'word' :
                    (($extension === 'xls' || $extension === 'xlsx') ? 'excel' : 'powerpoint'));

        $iconName = ($extension === 'pdf') ? 'file-pdf' :
                   (($extension === 'doc' || $extension === 'docx') ? 'file-word' :
                   (($extension === 'xls' || $extension === 'xlsx') ? 'file-excel' : 'file-powerpoint'));
        ?>

        <div class="page-header">
            <h1>
                <div class="file-icon <?php echo $iconClass; ?>">
                    <i class="fas fa-<?php echo $iconName; ?>"></i>
                </div>
                <?php echo clean($document['title']); ?>
            </h1>
            <div class="page-actions">
                <a href="download.php?id=<?php echo $document['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
        </div>

        <div class="document-details">
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Folder</label>
                    <span><?php echo clean($document['folder_name'] ?? 'No folder'); ?></span>
                </div>
                <div class="detail-item">
                    <label>Project</label>
                    <span><?php echo clean($document['project_name'] ?? 'No project'); ?></span>
                </div>
                <div class="detail-item">
                    <label>Document Type</label>
                    <span><?php echo clean($document['document_type'] ?? 'N/A'); ?></span>
                </div>
                <div class="detail-item">
                    <label>Uploaded By</label>
                    <span><?php echo clean($document['uploader_name']); ?></span>
                </div>
                <div class="detail-item">
                    <label>Upload Date</label>
                    <span><?php echo date('M d, Y H:i', strtotime($document['uploaded_at'])); ?></span>
                </div>
                <div class="detail-item">
                    <label>File Size</label>
                    <span><?php echo formatFileSize($document['file_size']); ?></span>
                </div>
                <div class="detail-item">
                    <label>Downloads</label>
                    <span><?php echo number_format($document['download_count']); ?></span>
                </div>
                <div class="detail-item">
                    <label>File Type</label>
                    <span><?php echo strtoupper($extension); ?></span>
                </div>
                <?php if ($document['tags']): ?>
                    <div class="detail-item">
                        <label>Tags</label>
                        <span class="tags">
                            <?php
                            $tags = explode(',', $document['tags']);
                            foreach ($tags as $tag) {
                                echo '<span class="tag">' . clean(trim($tag)) . '</span>';
                            }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($document['description']): ?>
                <div class="detail-item full-width">
                    <label>Description</label>
                    <p><?php echo nl2br(clean($document['description'])); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="preview-container">
            <h2>
                <?php if ($isPDF): ?>
                    <i class="fas fa-eye"></i> Document Preview
                <?php else: ?>
                    <i class="fas fa-download"></i> Download Required
                <?php endif; ?>
            </h2>

            <?php if ($isPDF): ?>
                <div class="pdf-viewer">
                    <iframe src="download.php?id=<?php echo $document['id']; ?>&view=1"
                            type="application/pdf"
                            width="100%"
                            height="800px"
                            style="border: none;">
                    </iframe>
                </div>
            <?php else: ?>
                <div class="download-prompt">
                    <i class="fas fa-<?php echo $iconName; ?>"></i>
                    <p>Preview not available for this file type. Please download to view.</p>
                    <a href="download.php?id=<?php echo $document['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download <?php echo strtoupper($extension); ?> File
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
