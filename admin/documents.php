<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../index.php');
}

// Get search and filter parameters
$searchQuery = $_GET['search'] ?? '';
$folderId = $_GET['folder'] ?? '';

// Build query with search and filter
$sql = "
    SELECT d.*, f.name as folder_name, p.name as project_name, dt.name as document_type, u.full_name as uploader_name
    FROM documents d
    LEFT JOIN folders f ON d.folder_id = f.id
    LEFT JOIN projects p ON d.project_id = p.id
    LEFT JOIN document_types dt ON d.document_type_id = dt.id
    LEFT JOIN users u ON d.uploaded_by = u.id
    WHERE 1=1
";

$params = [];

if ($searchQuery) {
    $sql .= " AND (d.title LIKE ? OR d.description LIKE ? OR d.tags LIKE ? OR d.filename LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($folderId) {
    $sql .= " AND d.folder_id = ?";
    $params[] = $folderId;
}

$sql .= " ORDER BY d.uploaded_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll();

// Get all folders for filter dropdown
$folders = $pdo->query("SELECT * FROM folders ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Documents - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/modern-style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: #f9fafb;
            font-size: 14px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .page-header h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        /* Compact Search Bar */
        .search-container {
            background: white;
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .search-form {
            display: flex;
            gap: 0.6rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 0.4rem 0.6rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.8rem;
        }

        .search-select {
            padding: 0.4rem 2rem 0.4rem 0.6rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.8rem;
            background: white;
            cursor: pointer;
        }

        .btn {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #0284c7;
            color: white;
        }

        .btn-primary:hover {
            background: #0369a1;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        /* Info Badge */
        .search-info {
            background: #f0f9ff;
            color: #0369a1;
            padding: 0.5rem 0.8rem;
            border-radius: 6px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.75rem;
        }

        /* Bulk Actions Container */
        .bulk-actions-container {
            background: #f0f9ff;
            border: 1px solid #e0f2fe;
            border-radius: 8px;
            padding: 0.8rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* Alerts - Compact */
        .alert {
            padding: 0.6rem 0.8rem;
            border-radius: 6px;
            margin-bottom: 0.75rem;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Modern Card-Based Document List */
        .documents-grid {
            display: grid;
            gap: 0.75rem;
        }

        .doc-card {
            background: white;
            border-radius: 8px;
            padding: 0.8rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            display: flex;
            gap: 0.8rem;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }

        .doc-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
            transform: translateY(-1px);
            border-left-color: #0284c7;
        }

        .doc-icon-large {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .doc-icon-large.pdf { background: #fee2e2; color: #dc2626; }
        .doc-icon-large.word { background: #dbeafe; color: #2563eb; }
        .doc-icon-large.excel { background: #d1fae5; color: #16a34a; }
        .doc-icon-large.ppt { background: #fed7aa; color: #ea580c; }

        .doc-content {
            flex: 1;
            min-width: 0;
        }

        .doc-title-large {
            font-size: 0.9rem;
            font-weight: 600;
            color: #111827;
            margin: 0 0 0.3rem 0;
        }

        .doc-title-large a {
            color: #111827;
            text-decoration: none;
        }

        .doc-title-large a:hover {
            color: #0284c7;
        }

        .doc-meta-row {
            font-size: 0.7rem;
            line-height: 1.5;
            margin-bottom: 0.3rem;
            color: #6b7280;
        }

        .doc-meta-item {
            display: inline;
            white-space: nowrap;
        }

        .doc-meta-item .label {
            font-weight: 500;
            color: #6b7280;
        }

        .doc-meta-item .value {
            color: #374151;
            font-weight: 400;
        }

        .doc-meta-item::after {
            content: " • ";
            color: #d1d5db;
            margin: 0 0.25rem;
        }

        .doc-meta-item:last-child::after {
            content: "";
            margin: 0;
        }

        .doc-actions {
            display: flex;
            gap: 0.375rem;
            flex-wrap: nowrap;
        }

        .btn-action {
            padding: 0.35rem 0.65rem;
            border-radius: 5px;
            font-size: 0.7rem;
            font-weight: 500;
            text-decoration: none;
            border: 1px solid #e5e7eb;
            background: white;
            color: #374151;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            white-space: nowrap;
        }

        .btn-action:hover {
            background: #f9fafb;
            border-color: #0284c7;
            color: #0284c7;
        }

        .btn-action.btn-danger {
            color: #dc2626;
        }

        .btn-action.btn-danger:hover {
            background: #fee2e2;
            border-color: #dc2626;
        }

        /* Empty State */
        .empty-state {
            background: white;
            border-radius: 8px;
            padding: 3rem 2rem;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 3rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #6b7280;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        /* Actions Menu Styles */
        .actions-menu-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .actions-menu {
            position: relative;
        }

        .btn-actions-menu {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 0.5rem;
            cursor: pointer;
            color: #6b7280;
            transition: all 0.2s;
            font-size: 0.875rem;
        }

        .btn-actions-menu:hover {
            background: #f9fafb;
            border-color: #d1d5db;
            color: #374151;
        }

        .actions-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 100;
            min-width: 160px;
            margin-top: 0.25rem;
        }

        .dropdown-item {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            background: none;
            border: none;
            text-align: left;
            cursor: pointer;
            color: #374151;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .dropdown-item:hover {
            background: #f9fafb;
            color: #0284c7;
        }

        .dropdown-item i {
            margin-right: 0.5rem;
            width: 16px;
        }

        .bulk-buttons {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: nowrap;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }

        .selected-count {
            font-size: 0.75rem;
            color: #6b7280;
            font-weight: 500;
        }

        .doc-checkbox {
            margin-right: 0.75rem;
            flex-shrink: 0;
            display: none;
        }

        .doc-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .doc-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .doc-icon-large {
                align-self: center;
            }

            .doc-content {
                width: 100%;
            }

            .doc-meta-row {
                width: 100%;
            }

            .doc-actions {
                width: 100%;
            }

            .search-form {
                flex-direction: column;
                align-items: stretch;
            }

            .search-input,
            .search-select {
                width: 100%;
            }

            .bulk-actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .bulk-buttons {
                width: 100%;
                justify-content: space-between;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/modern-admin-header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Manage Documents</h1>
            <a href="upload_document.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Upload Document
            </a>
        </div>

        <!-- Search and Filter Bar -->
        <div class="search-container">
            <form action="" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search by title, description, tags, or filename..."
                       value="<?php echo clean($searchQuery); ?>" class="search-input">
                <select name="folder" class="search-select" id="folderFilter">
                    <option value="">All Folders</option>
                    <?php foreach ($folders as $folder): ?>
                        <option value="<?php echo $folder['id']; ?>" <?php echo $folderId == $folder['id'] ? 'selected' : ''; ?>>
                            <?php echo clean($folder['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if ($searchQuery || $folderId): ?>
                    <a href="documents.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
                <?php if (!empty($documents)): ?>
                    <div class="actions-menu">
                        <button type="button" class="btn-actions-menu" id="actionsMenuBtn" title="Actions">
                            <i class="fas fa-ellipsis-v"></i> Action
                        </button>
                        <div class="actions-dropdown" id="actionsDropdown" style="display: none;">
                            <button type="button" class="dropdown-item" id="selectAllBtn">
                                <i class="fas fa-check-square"></i> Select All
                            </button>
                            <button type="button" class="dropdown-item" id="deselectAllBtn">
                                <i class="fas fa-square"></i> Deselect All
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <?php if (!empty($documents)): ?>
            <div class="bulk-actions-container" id="bulkButtons" style="display: none;">
                <div class="bulk-buttons">
                    <button type="button" class="btn btn-secondary btn-sm" id="bulkDownloadBtn" onclick="bulkDownload()">
                        <i class="fas fa-download"></i> Download
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" onclick="bulkDelete()">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="exitSelectBtn" onclick="exitSelectMode()">
                        <i class="fas fa-times"></i> Exit Select
                    </button>
                    <span class="selected-count" id="selectedCount">0 selected</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($searchQuery || $folderId): ?>
            <div class="search-info">
                <i class="fas fa-info-circle"></i>
                Found <?php echo count($documents); ?> document(s)
                <?php if ($searchQuery): ?>matching "<?php echo clean($searchQuery); ?>"<?php endif; ?>
                <?php if ($folderId):
                    $folderName = array_filter($folders, fn($f) => $f['id'] == $folderId);
                    if ($folderName): ?> in folder "<?php echo clean(array_values($folderName)[0]['name']); ?>"<?php endif;
                endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php
                if ($_GET['success'] === 'uploaded') echo 'Document uploaded successfully';
                elseif ($_GET['success'] === 'updated') echo 'Document updated successfully';
                elseif ($_GET['success'] === 'deleted') echo 'Document deleted successfully';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php
                if ($_GET['error'] === 'upload_failed') echo 'Failed to upload document';
                elseif ($_GET['error'] === 'delete_failed') echo 'Failed to delete document';
                ?>
            </div>
        <?php endif; ?>

        <?php if (empty($documents)): ?>
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <p>No documents found</p>
                <a href="upload_document.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Upload Your First Document
                </a>
            </div>
        <?php else: ?>
            <div class="documents-grid">
                <?php foreach ($documents as $doc):
                    $ext = strtolower(pathinfo($doc['filename'], PATHINFO_EXTENSION));
                    $iconClass = ($ext === 'pdf') ? 'pdf' :
                                (($ext === 'doc' || $ext === 'docx') ? 'word' :
                                (($ext === 'xls' || $ext === 'xlsx') ? 'excel' : 'ppt'));
                    $iconName = ($ext === 'pdf') ? 'file-pdf' :
                               (($ext === 'doc' || $ext === 'docx') ? 'file-word' :
                               (($ext === 'xls' || $ext === 'xlsx') ? 'file-excel' : 'file-powerpoint'));
                ?>
                    <div class="doc-card">
                        <div class="doc-checkbox">
                            <input type="checkbox" class="doc-select" value="<?php echo $doc['id']; ?>" id="doc-<?php echo $doc['id']; ?>">
                            <label for="doc-<?php echo $doc['id']; ?>"></label>
                        </div>
                        <div class="doc-icon-large <?php echo $iconClass; ?>">
                            <i class="fas fa-<?php echo $iconName; ?>"></i>
                        </div>
                        <div class="doc-content">
                            <h3 class="doc-title-large">
                                <a href="view_document.php?id=<?php echo $doc['id']; ?>" title="<?php echo clean($doc['title']); ?>">
                                    <?php echo clean($doc['title']); ?>
                                </a>
                            </h3>
                            <div class="doc-meta-row">
                                <span class="doc-meta-item">
                                    <span class="label">Folder:</span>
                                    <span class="value"><?php echo clean($doc['folder_name'] ?? 'No folder'); ?></span>
                                </span>
                                <span class="doc-meta-item">
                                    <span class="label">Project:</span>
                                    <span class="value"><?php echo clean($doc['project_name'] ?? 'No project'); ?></span>
                                </span>
                                <span class="doc-meta-item">
                                    <span class="label">Type:</span>
                                    <span class="value"><?php echo clean($doc['document_type'] ?? 'N/A'); ?></span>
                                </span>
                                <span class="doc-meta-item">
                                    <span class="label">By:</span>
                                    <span class="value"><?php echo clean($doc['uploader_name']); ?></span>
                                </span>
                                <span class="doc-meta-item">
                                    <span class="label">Date:</span>
                                    <span class="value"><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></span>
                                </span>
                                <span class="doc-meta-item">
                                    <span class="label">Size:</span>
                                    <span class="value"><?php echo formatFileSize($doc['file_size']); ?></span>
                                </span>
                                <span class="doc-meta-item">
                                    <span class="label">Downloads:</span>
                                    <span class="value"><?php echo number_format($doc['download_count']); ?></span>
                                </span>
                                <?php if ($doc['tags']): ?>
                                    <span class="doc-meta-item">
                                        <span class="label">Tags:</span>
                                        <span class="value"><?php echo clean($doc['tags']); ?></span>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="doc-actions">
                                <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="btn-action" title="View">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="edit_document.php?id=<?php echo $doc['id']; ?>" class="btn-action" title="Edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="delete_document.php?id=<?php echo $doc['id']; ?>"
                                   class="btn-action btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this document?')"
                                   title="Delete">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-submit form when folder dropdown changes
        document.getElementById('folderFilter').addEventListener('change', function() {
            this.closest('form').submit();
        });

        // Three dots menu functionality
        const actionsMenuBtn = document.getElementById('actionsMenuBtn');
        const actionsDropdown = document.getElementById('actionsDropdown');
        const selectAllBtn = document.getElementById('selectAllBtn');
        const deselectAllBtn = document.getElementById('deselectAllBtn');
        const docCheckboxes = document.querySelectorAll('.doc-select');
        const docCheckboxContainers = document.querySelectorAll('.doc-checkbox');
        const bulkButtons = document.getElementById('bulkButtons');
        const selectedCount = document.getElementById('selectedCount');

        let isSelectMode = false;

        // Toggle dropdown menu
        actionsMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isVisible = actionsDropdown.style.display === 'block';
            actionsDropdown.style.display = isVisible ? 'none' : 'block';
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!actionsMenuBtn.contains(e.target) && !actionsDropdown.contains(e.target)) {
                actionsDropdown.style.display = 'none';
            }
        });

        // Select all
        selectAllBtn.addEventListener('click', function() {
            enterSelectMode();
            docCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            updateBulkActions();
            actionsDropdown.style.display = 'none';
        });

        // Deselect all
        deselectAllBtn.addEventListener('click', function() {
            docCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateBulkActions();
            actionsDropdown.style.display = 'none';
        });

        // Enter select mode function
        function enterSelectMode() {
            isSelectMode = true;
            docCheckboxContainers.forEach(container => {
                container.style.display = 'block';
            });
            bulkButtons.style.display = 'flex';
            actionsMenuBtn.title = 'Select Options';
        }

        // Exit select mode function
        function exitSelectMode() {
            isSelectMode = false;
            docCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            docCheckboxContainers.forEach(container => {
                container.style.display = 'none';
            });
            bulkButtons.style.display = 'none';
            actionsMenuBtn.title = 'Actions';
        }

        // Individual checkbox events
        docCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkActions);
        });

        function updateBulkActions() {
            if (!isSelectMode) return;

            const checkedBoxes = document.querySelectorAll('.doc-select:checked');
            const count = checkedBoxes.length;

            if (count > 0) {
                selectedCount.textContent = count + ' selected';
            } else {
                selectedCount.textContent = '0 selected';
            }
        }

        // Bulk download function
        function bulkDownload() {
            const selectedIds = Array.from(document.querySelectorAll('.doc-select:checked')).map(cb => cb.value);

            if (selectedIds.length === 0) {
                alert('Please select documents to download.');
                return;
            }

            if (selectedIds.length === 1) {
                // Single file download
                window.location.href = `download.php?id=${selectedIds[0]}`;
            } else {
                // Multiple files - create zip
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'bulk_download.php';
                form.target = '_blank';

                selectedIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'document_ids[]';
                    input.value = id;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            }
        }

        // Bulk delete function
        function bulkDelete() {
            const selectedIds = Array.from(document.querySelectorAll('.doc-select:checked')).map(cb => cb.value);

            if (selectedIds.length === 0) {
                alert('Please select documents to delete.');
                return;
            }

            if (confirm(`Are you sure you want to delete ${selectedIds.length} document(s)? This action cannot be undone.`)) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'bulk_delete_documents.php';

                selectedIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'document_ids[]';
                    input.value = id;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            }
        }

        // Initialize - hide checkboxes by default
        docCheckboxContainers.forEach(container => {
            container.style.display = 'none';
        });
    </script>
</body>
</html>
