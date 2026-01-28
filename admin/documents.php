<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../index.php');
}

// Get search and filter parameters
$searchQuery = $_GET['search'] ?? '';
$folderId = $_GET['folder'] ?? '';

// Build query
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
        body { background: #f9fafb; font-size: 14px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .container { max-width: 1400px; margin: 0 auto; padding: 1rem; }
        
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .page-header h1 { font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0; }

        .search-container { background: white; padding: 0.8rem; border-radius: 8px; margin-bottom: 0.75rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .search-form { display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap; }
        .search-input { flex: 1; min-width: 200px; padding: 0.4rem 0.6rem; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.8rem; }
        .search-select { padding: 0.4rem 2rem 0.4rem 0.6rem; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.8rem; background: white; cursor: pointer; }

        .btn { padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.8rem; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.2s; }
        .btn-primary { background: #0284c7; color: white; }
        .btn-primary:hover { background: #0369a1; }
        .btn-secondary { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }
        .btn-secondary:hover { background: #e5e7eb; }

        .search-info { background: #f0f9ff; color: #0369a1; padding: 0.5rem 0.8rem; border-radius: 6px; font-size: 0.8rem; display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.75rem; }
        
        .bulk-actions-container { background: #f0f9ff; border: 1px solid #e0f2fe; border-radius: 8px; padding: 0.8rem; margin-bottom: 0.75rem; }
        .bulk-buttons { display: flex; align-items: center; gap: 1rem; }
        .btn-sm { padding: 0.35rem 0.6rem; font-size: 0.75rem; }

        .alert { padding: 0.6rem 0.8rem; border-radius: 6px; margin-bottom: 0.75rem; font-size: 0.8rem; display: flex; align-items: center; gap: 0.4rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }

        /* Documents Grid */
        .documents-grid { display: grid; gap: 0.75rem; }
        .doc-card { background: white; border-radius: 8px; padding: 0.8rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); display: flex; gap: 0.8rem; transition: all 0.2s; border-left: 3px solid transparent; }
        .doc-card:hover { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); transform: translateY(-1px); border-left-color: #0284c7; }

        .doc-icon-large { width: 36px; height: 36px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
        .doc-icon-large.pdf { background: #fee2e2; color: #dc2626; }
        .doc-icon-large.word { background: #dbeafe; color: #2563eb; }
        .doc-icon-large.excel { background: #d1fae5; color: #16a34a; }
        .doc-icon-large.ppt { background: #fed7aa; color: #ea580c; }

        .doc-content { flex: 1; min-width: 0; }
        .doc-title-large { font-size: 0.9rem; font-weight: 600; color: #111827; margin: 0 0 0.3rem 0; }
        .doc-title-large a { color: #111827; text-decoration: none; }
        .doc-title-large a:hover { color: #0284c7; }

        .doc-meta-row { font-size: 0.7rem; color: #6b7280; margin-bottom: 0.3rem; }
        .doc-meta-item::after { content: " • "; color: #d1d5db; margin: 0 0.25rem; }
        .doc-meta-item:last-child::after { content: ""; }
        .label { font-weight: 500; }

        .doc-actions { display: flex; gap: 0.4rem; }
        .btn-action { padding: 0.3rem 0.6rem; border-radius: 5px; font-size: 0.7rem; font-weight: 500; text-decoration: none; border: 1px solid #e5e7eb; background: white; color: #374151; transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.25rem; }
        .btn-action:hover { background: #f9fafb; border-color: #0284c7; color: #0284c7; }
        .btn-action.btn-danger { color: #dc2626; }
        .btn-action.btn-danger:hover { background: #fee2e2; border-color: #dc2626; }

        .actions-menu { position: relative; margin-left: auto; }
        .btn-actions-menu { background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 0.4rem 0.6rem; cursor: pointer; color: #6b7280; font-size: 0.8rem; }
        .actions-dropdown { position: absolute; top: 100%; right: 0; background: white; border: 1px solid #e5e7eb; border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); z-index: 100; min-width: 140px; margin-top: 0.25rem; display: none; }
        .dropdown-item { display: block; width: 100%; padding: 0.6rem 1rem; background: none; border: none; text-align: left; cursor: pointer; color: #374151; font-size: 0.8rem; }
        .dropdown-item:hover { background: #f9fafb; color: #0284c7; }

        .doc-checkbox { margin-right: 0.5rem; display: none; }
        .empty-state { background: white; border-radius: 8px; padding: 2rem 1.5rem; text-align: center; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .empty-state p { color: #6b7280; margin-bottom: 1rem; font-size: 0.9rem; }
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

        <div class="search-container">
            <form action="" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search..." value="<?php echo clean($searchQuery); ?>" class="search-input">
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
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="actions-dropdown" id="actionsDropdown">
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
                    <button type="button" class="btn btn-secondary btn-sm" id="exitSelectBtn" onclick="exitSelectMode()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <span class="selected-count" id="selectedCount" style="font-size:0.8rem; color:#6b7280;">0 selected</span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($searchQuery || $folderId): ?>
            <div class="search-info">
                <i class="fas fa-info-circle"></i>
                Found <?php echo count($documents); ?> document(s)
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
                <i class="fas fa-file-alt" style="font-size: 2.5rem; color: #d1d5db; margin-bottom: 0.75rem;"></i>
                <p>No documents found</p>
                <a href="upload_document.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Upload Your First Document
                </a>
            </div>
        <?php else: ?>
            <div class="documents-grid">
                <?php foreach ($documents as $doc):
                    $ext = strtolower(pathinfo($doc['filename'], PATHINFO_EXTENSION));
                    $iconClass = ($ext === 'pdf') ? 'pdf' : (($ext === 'doc' || $ext === 'docx') ? 'word' : (($ext === 'xls' || $ext === 'xlsx') ? 'excel' : 'ppt'));
                    $iconName = ($ext === 'pdf') ? 'file-pdf' : (($ext === 'doc' || $ext === 'docx') ? 'file-word' : (($ext === 'xls' || $ext === 'xlsx') ? 'file-excel' : 'file-powerpoint'));
                ?>
                    <div class="doc-card">
                        <div class="doc-checkbox">
                            <input type="checkbox" class="doc-select" value="<?php echo $doc['id']; ?>">
                        </div>
                        <div class="doc-icon-large <?php echo $iconClass; ?>">
                            <i class="fas fa-<?php echo $iconName; ?>"></i>
                        </div>
                        <div class="doc-content">
                            <h3 class="doc-title-large">
                                <a href="view_document.php?id=<?php echo $doc['id']; ?>"><?php echo clean($doc['title']); ?></a>
                            </h3>
                            <div class="doc-meta-row">
                                <span class="doc-meta-item"><span class="label">Folder:</span> <?php echo clean($doc['folder_name'] ?? '-'); ?></span>
                                <span class="doc-meta-item"><span class="label">Date:</span> <?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></span>
                                <span class="doc-meta-item"><span class="label">Size:</span> <?php echo formatFileSize($doc['file_size']); ?></span>
                            </div>
                            <div class="doc-actions">
                                <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="btn-action"><i class="fas fa-eye"></i> View</a>
                                <a href="edit_document.php?id=<?php echo $doc['id']; ?>" class="btn-action"><i class="fas fa-edit"></i> Edit</a>
                                <a href="delete_document.php?id=<?php echo $doc['id']; ?>" class="btn-action btn-danger" onclick="return confirm('Delete document?')"><i class="fas fa-trash"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('folderFilter').addEventListener('change', function() { this.closest('form').submit(); });
        const actionsMenuBtn = document.getElementById('actionsMenuBtn');
        const actionsDropdown = document.getElementById('actionsDropdown');
        const selectAllBtn = document.getElementById('selectAllBtn');
        const deselectAllBtn = document.getElementById('deselectAllBtn');
        const docCheckboxes = document.querySelectorAll('.doc-select');
        const docCheckboxContainers = document.querySelectorAll('.doc-checkbox');
        const bulkButtons = document.getElementById('bulkButtons');
        const selectedCount = document.getElementById('selectedCount');
        let isSelectMode = false;

        if(actionsMenuBtn) {
            actionsMenuBtn.addEventListener('click', function(e) { e.stopPropagation(); actionsDropdown.style.display = actionsDropdown.style.display === 'block' ? 'none' : 'block'; });
            document.addEventListener('click', function(e) { if (!actionsMenuBtn.contains(e.target)) actionsDropdown.style.display = 'none'; });
        }

        if(selectAllBtn) selectAllBtn.addEventListener('click', function() { enterSelectMode(); docCheckboxes.forEach(cb => cb.checked = true); updateBulkActions(); });
        if(deselectAllBtn) deselectAllBtn.addEventListener('click', function() { docCheckboxes.forEach(cb => cb.checked = false); updateBulkActions(); });

        function enterSelectMode() { isSelectMode = true; docCheckboxContainers.forEach(c => c.style.display = 'block'); bulkButtons.style.display = 'flex'; }
        function exitSelectMode() { isSelectMode = false; docCheckboxes.forEach(cb => cb.checked = false); docCheckboxContainers.forEach(c => c.style.display = 'none'); bulkButtons.style.display = 'none'; }
        docCheckboxes.forEach(cb => cb.addEventListener('change', updateBulkActions));
        function updateBulkActions() { if (!isSelectMode) return; selectedCount.textContent = document.querySelectorAll('.doc-select:checked').length + ' selected'; }
        
        function bulkDownload() {
            const ids = Array.from(document.querySelectorAll('.doc-select:checked')).map(cb => cb.value);
            if (ids.length === 0) return alert('Select documents.');
            if (ids.length === 1) window.location.href = `download.php?id=${ids[0]}`;
            else {
                const form = document.createElement('form'); form.method = 'POST'; form.action = 'bulk_download.php'; form.target = '_blank';
                ids.forEach(id => { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'document_ids[]'; input.value = id; form.appendChild(input); });
                document.body.appendChild(form); form.submit(); document.body.removeChild(form);
            }
        }

        function bulkDelete() {
            const ids = Array.from(document.querySelectorAll('.doc-select:checked')).map(cb => cb.value);
            if (ids.length === 0) return alert('Select documents.');
            if (confirm(`Delete ${ids.length} document(s)?`)) {
                const form = document.createElement('form'); form.method = 'POST'; form.action = 'bulk_delete_documents.php';
                ids.forEach(id => { const input = document.createElement('input'); input.type = 'hidden'; input.name = 'document_ids[]'; input.value = id; form.appendChild(input); });
                document.body.appendChild(form); form.submit();
            }
        }
    </script>
</body>
</html>