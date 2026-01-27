<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../index.php');
}

// Get all folders for dropdown
$folders = $pdo->query("SELECT * FROM folders ORDER BY name")->fetchAll();

// Get all projects for dropdown
$projects = $pdo->query("SELECT * FROM projects ORDER BY name")->fetchAll();

// Get all document types for dropdown
$documentTypes = $pdo->query("SELECT * FROM document_types ORDER BY order_index")->fetchAll();

// Pre-fill project if passed in URL
$prefilledProjectId = $_GET['project_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean($_POST['title']);
    $description = clean($_POST['description']);
    $tags = clean($_POST['tags']);
    $folder_id = $_POST['folder_id'] ?: null;
    $project_id = $_POST['project_id'] ?: null;
    $document_type_id = $_POST['document_type_id'] ?: null;

    // Validate document type is provided
    if (!$document_type_id) {
        redirect('upload_document.php?error=no_type');
    }

    // Handle file upload
    if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['document'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Validate file type
        if (!in_array($fileExtension, ALLOWED_EXTENSIONS)) {
            redirect('upload_document.php?error=invalid_type');
        }

        // Validate file size
        if ($file['size'] > MAX_FILE_SIZE) {
            redirect('upload_document.php?error=file_too_large');
        }

        // Generate unique filename (preserve original extension)
        $newFilename = uniqid() . '_' . time() . '.' . $fileExtension;
        $uploadPath = UPLOAD_DIR . $newFilename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            // Extract text from PDF for search (only for PDF files)
            $pdfText = '';
            if ($fileExtension === 'pdf') {
                try {
                    // Using simple PDF text extraction
                    $pdfContent = file_get_contents($uploadPath);
                    if (preg_match_all('/\(([^\)]+)\)/i', $pdfContent, $matches)) {
                        $pdfText = implode(' ', $matches[1]);
                    }
                } catch (Exception $e) {
                    // If extraction fails, continue without text content
                }
            }

            // Insert document into database
            $stmt = $pdo->prepare("
                INSERT INTO documents (title, filename, file_path, file_size, folder_id, project_id, document_type_id, description, tags, pdf_text_content, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $title,
                $file['name'],
                $newFilename,
                $file['size'],
                $folder_id,
                $project_id,
                $document_type_id,
                $description,
                $tags,
                $pdfText,
                $_SESSION['user_id']
            ]);

            // Log activity
            $documentId = $pdo->lastInsertId();
            $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, document_id, details, ip_address) VALUES (?, 'upload', ?, ?, ?)");
            $logStmt->execute([$_SESSION['user_id'], $documentId, "Uploaded: $title", $_SERVER['REMOTE_ADDR']]);

            if ($project_id) {
                redirect('view_project.php?id=' . $project_id . '&success=uploaded');
            } else {
                redirect('documents.php?success=uploaded');
            }
        } else {
            redirect('upload_document.php?error=upload_failed');
        }
    } else {
        redirect('upload_document.php?error=no_file');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Document - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/modern-style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f9fafb; font-size: 14px; }
        .container { max-width: 700px; margin: 0 auto; padding: 1rem; }
        .page-header { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem; }
        .page-header h1 { font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0; }
        .page-header i { font-size: 1.5rem; color: #0284c7; }

        .alert { padding: 0.6rem 0.8rem; border-radius: 6px; margin-bottom: 0.75rem; font-size: 0.8rem; display: flex; align-items: center; gap: 0.4rem; }
        .alert-error { background: #fee2e2; color: #991b1b; }

        .form-container { background: white; border-radius: 8px; padding: 1.1rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .form-group { margin-bottom: 0.9rem; }

        label { display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem; }
        input[type="text"], input[type="file"], select, textarea { width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.8rem; color: #111827; transition: all 0.2s; }
        input[type="text"]:focus, select:focus, textarea:focus { outline: none; border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1); }
        textarea { resize: vertical; min-height: 70px; font-family: inherit; }
        select { cursor: pointer; }

        input[type="file"] { padding: 0.5rem 0.6rem; cursor: pointer; }
        input[type="file"]::file-selector-button { padding: 0.3rem 0.6rem; border: 1px solid #e5e7eb; border-radius: 4px; background: #f9fafb; color: #374151; font-size: 0.7rem; font-weight: 500; cursor: pointer; margin-right: 0.6rem; }
        input[type="file"]::file-selector-button:hover { background: #f3f4f6; }

        small { display: block; margin-top: 0.3rem; font-size: 0.7rem; color: #6b7280; }

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
            <i class="fas fa-cloud-upload-alt"></i>
            <h1>Upload Document</h1>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php
                if ($_GET['error'] === 'invalid_type') echo 'Invalid file type. Allowed types: ' . implode(', ', ALLOWED_EXTENSIONS);
                elseif ($_GET['error'] === 'file_too_large') echo 'File is too large. Maximum size is ' . formatFileSize(MAX_FILE_SIZE);
                elseif ($_GET['error'] === 'upload_failed') echo 'Failed to upload file';
                elseif ($_GET['error'] === 'no_file') echo 'Please select a file to upload';
                elseif ($_GET['error'] === 'no_type') echo 'Please select a document type';
                ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form action="" method="POST" enctype="multipart/form-data" class="document-form">
                <div class="form-group">
                    <label for="title">Document Title *</label>
                    <input type="text" id="title" name="title" placeholder="Enter document title" required autofocus>
                </div>

                <div class="form-group">
                    <label for="folder_id">Folder</label>
                    <select id="folder_id" name="folder_id">
                        <option value="">-- Select Folder (Optional) --</option>
                        <?php foreach ($folders as $folder): ?>
                            <option value="<?php echo $folder['id']; ?>">
                                <?php echo clean($folder['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="project_id">Project *</label>
                    <select id="project_id" name="project_id" required>
                        <option value="">-- Select Project --</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>" <?php echo ($prefilledProjectId == $project['id']) ? 'selected' : ''; ?>>
                                <?php echo clean($project['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="document_type_id">Document Type *</label>
                    <select id="document_type_id" name="document_type_id" required>
                        <option value="">-- Select Document Type --</option>
                        <?php foreach ($documentTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>">
                                <?php echo clean($type['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" placeholder="Enter document description (optional)"></textarea>
                </div>

                <div class="form-group">
                    <label for="tags">Tags (comma separated)</label>
                    <input type="text" id="tags" name="tags" placeholder="e.g. invoice, 2024, important">
                </div>

                <div class="form-group">
                    <label for="document">Document File *</label>
                    <input type="file" id="document" name="document"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx" required>
                    <small>Allowed types: PDF, Word, Excel, PowerPoint | Maximum size: <?php echo formatFileSize(MAX_FILE_SIZE); ?></small>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload Document
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
