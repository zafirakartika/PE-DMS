<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../index.php');
}

// Get search parameter
$searchQuery = $_GET['search'] ?? '';

// Build query with search
$sql = "
    SELECT f.*,
           COUNT(d.id) as document_count,
           u.full_name as creator_name
    FROM folders f
    LEFT JOIN documents d ON f.id = d.folder_id
    LEFT JOIN users u ON f.created_by = u.id
";

$params = [];

if ($searchQuery) {
    $sql .= " WHERE (f.name LIKE ? OR f.description LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params = [$searchTerm, $searchTerm];
}

$sql .= " GROUP BY f.id ORDER BY f.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$folders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Folders - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/modern-style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f9fafb; font-size: 14px; }
        .container { max-width: 1400px; margin: 0 auto; padding: 1rem; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .page-header h1 { font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0; }

        .search-container { background: white; padding: 0.8rem; border-radius: 8px; margin-bottom: 0.75rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .search-form { display: flex; gap: 0.6rem; }
        .search-input { flex: 1; padding: 0.4rem 0.6rem; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.8rem; }
        .btn { padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.8rem; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.2s; }
        .btn-primary { background: #0284c7; color: white; }
        .btn-primary:hover { background: #0369a1; }
        .btn-secondary { background: #f3f4f6; color: #374151; }
        .btn-secondary:hover { background: #e5e7eb; }

        .search-info { background: #f0f9ff; color: #0369a1; padding: 0.5rem 0.8rem; border-radius: 6px; font-size: 0.8rem; display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.75rem; }

        /* Bulk Actions Container */
        .bulk-actions-container {
            background: #f0f9ff;
            border: 1px solid #e0f2fe;
            border-radius: 8px;
            padding: 0.8rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .alert { padding: 0.6rem 0.8rem; border-radius: 6px; margin-bottom: 0.75rem; font-size: 0.8rem; display: flex; align-items: center; gap: 0.4rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }

        .folders-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.75rem; }
        .folder-card { background: white; border-radius: 8px; padding: 0.9rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); transition: all 0.2s; display: flex; flex-direction: column; }
        .folder-card:hover { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); transform: translateY(-2px); }

        .folder-header { display: flex; align-items: center; gap: 0.8rem; margin-bottom: 0.6rem; }
        .folder-icon { width: 56px; height: 56px; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.75rem; flex-shrink: 0; }
        .folder-title { flex: 1; min-width: 0; }
        .folder-title h3 { font-size: 1rem; font-weight: 600; color: #111827; margin: 0 0 0.2rem 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .folder-count { font-size: 0.7rem; color: #6b7280; display: flex; align-items: center; gap: 0.3rem; }

        .folder-description { font-size: 0.8rem; color: #6b7280; margin-bottom: 0.75rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        .folder-meta { display: flex; align-items: center; justify-content: space-between; padding-top: 0.6rem; border-top: 1px solid #f3f4f6; margin-bottom: 0.6rem; }
        .folder-creator { font-size: 0.7rem; color: #9ca3af; display: flex; align-items: center; gap: 0.3rem; }

        .folder-actions { display: flex; gap: 0.4rem; }
        .btn-action { flex: 1; padding: 0.4rem 0.6rem; border-radius: 6px; font-size: 0.7rem; font-weight: 500; text-decoration: none; text-align: center; border: 1px solid #e5e7eb; background: white; color: #374151; transition: all 0.2s; }
        .btn-action:hover { background: #f9fafb; border-color: #0284c7; color: #0284c7; }
        .btn-action.btn-danger { color: #dc2626; }
        .btn-action.btn-danger:hover { background: #fee2e2; border-color: #dc2626; }
        .btn-action.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }

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

        .folder-checkbox {
            margin-bottom: 0.75rem;
        }

        .folder-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            margin-right: 0.5rem;
        }

        .empty-state { background: white; border-radius: 8px; padding: 2rem 1.5rem; text-align: center; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .empty-state i { font-size: 2.5rem; color: #d1d5db; margin-bottom: 0.75rem; }
        .empty-state p { color: #6b7280; margin-bottom: 1rem; font-size: 0.8rem; }
    </style>
</head>
<body>
    <?php include '../includes/modern-admin-header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Manage Folders</h1>
            <a href="add_folder.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Folder
            </a>
        </div>

        <div class="search-container">
            <form action="" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search folders by name or description..."
                       value="<?php echo clean($searchQuery); ?>" class="search-input">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if ($searchQuery): ?>
                    <a href="folders.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
                <?php if (!empty($folders)): ?>
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

        <?php if (!empty($folders)): ?>
            <div class="bulk-actions-container" id="bulkButtons" style="display: none;">
                <div class="bulk-buttons">
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

        <?php if ($searchQuery): ?>
            <div class="search-info">
                <i class="fas fa-info-circle"></i>
                Found <?php echo count($folders); ?> folder(s) matching "<?php echo clean($searchQuery); ?>"
            </div>
        <?php endif; ?>



        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php
                if ($_GET['success'] === 'created') echo 'Folder created successfully';
                elseif ($_GET['success'] === 'updated') echo 'Folder updated successfully';
                elseif ($_GET['success'] === 'deleted') echo 'Folder deleted successfully';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php
                if ($_GET['error'] === 'has_documents') echo 'Cannot delete folder that contains documents';
                elseif ($_GET['error'] === 'delete_failed') echo 'Failed to delete folder';
                ?>
            </div>
        <?php endif; ?>

        <?php if (empty($folders)): ?>
            <div class="empty-state">
                <i class="fas fa-folder"></i>
                <p>No folders found</p>
                <a href="add_folder.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Your First Folder
                </a>
            </div>
        <?php else: ?>
            <div class="folders-grid">
                <?php foreach ($folders as $folder): ?>
                    <div class="folder-card">
                        <div class="folder-checkbox">
                            <input type="checkbox" class="folder-select" value="<?php echo $folder['id']; ?>" id="folder-<?php echo $folder['id']; ?>">
                            <label for="folder-<?php echo $folder['id']; ?>"></label>
                        </div>
                        <div class="folder-header">
                            <div class="folder-icon">
                                <i class="fas fa-folder"></i>
                            </div>
                            <div class="folder-title">
                                <h3><?php echo clean($folder['name']); ?></h3>
                                <div class="folder-count">
                                    <i class="fas fa-file"></i>
                                    <?php echo $folder['document_count']; ?> document<?php echo $folder['document_count'] != 1 ? 's' : ''; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($folder['description']): ?>
                            <div class="folder-description">
                                <?php echo clean($folder['description']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="folder-meta">
                            <div class="folder-creator">
                                <i class="fas fa-user"></i>
                                Created by <?php echo clean($folder['creator_name'] ?? 'N/A'); ?>
                            </div>
                        </div>

                        <div class="folder-actions">
                            <a href="edit_folder.php?id=<?php echo $folder['id']; ?>" class="btn-action">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <?php if ($folder['document_count'] == 0): ?>
                                <a href="delete_folder.php?id=<?php echo $folder['id']; ?>"
                                   class="btn-action btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this folder?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            <?php else: ?>
                                <span class="btn-action disabled" title="Cannot delete folder with documents">
                                    <i class="fas fa-lock"></i> Locked
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Three dots menu functionality
        const actionsMenuBtn = document.getElementById('actionsMenuBtn');
        const actionsDropdown = document.getElementById('actionsDropdown');
        const selectAllBtn = document.getElementById('selectAllBtn');
        const deselectAllBtn = document.getElementById('deselectAllBtn');
        const folderCheckboxes = document.querySelectorAll('.folder-select');
        const folderCheckboxContainers = document.querySelectorAll('.folder-checkbox');
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
            folderCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            updateBulkActions();
            actionsDropdown.style.display = 'none';
        });

        // Deselect all
        deselectAllBtn.addEventListener('click', function() {
            folderCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateBulkActions();
            actionsDropdown.style.display = 'none';
        });

        // Enter select mode function
        function enterSelectMode() {
            if (isSelectMode) return;
            isSelectMode = true;
            folderCheckboxContainers.forEach(container => {
                container.style.display = 'block';
            });
            bulkButtons.style.display = 'flex';
            actionsMenuBtn.title = 'Select Options';
        }

        // Exit select mode function
        function exitSelectMode() {
            isSelectMode = false;
            folderCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            folderCheckboxContainers.forEach(container => {
                container.style.display = 'none';
            });
            bulkButtons.style.display = 'none';
            actionsMenuBtn.title = 'Actions';
        }

        // Individual checkbox events
        folderCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkActions);
        });

        function updateBulkActions() {
            if (!isSelectMode) return;

            const checkedBoxes = document.querySelectorAll('.folder-select:checked');
            const count = checkedBoxes.length;

            if (count > 0) {
                selectedCount.textContent = count + ' selected';
            } else {
                selectedCount.textContent = '0 selected';
            }
        }

        // Bulk delete function
        function bulkDelete() {
            const selectedIds = Array.from(document.querySelectorAll('.folder-select:checked')).map(cb => cb.value);

            if (selectedIds.length === 0) {
                alert('Please select folders to delete.');
                return;
            }

            if (confirm(`Are you sure you want to delete ${selectedIds.length} folder(s)? This action cannot be undone.`)) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'bulk_delete_folders.php';

                selectedIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'folder_ids[]';
                    input.value = id;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            }
        }

        // Initialize - hide checkboxes by default
        folderCheckboxContainers.forEach(container => {
            container.style.display = 'none';
        });
    </script>
</body>
</html>
