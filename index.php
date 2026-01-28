<?php
require_once 'config/config.php';

$searchQuery = $_GET['search'] ?? '';
$folderId = $_GET['folder'] ?? '';
$view = $_GET['view'] ?? 'table'; 

$sql = "
    SELECT d.*, f.name as folder_name, u.full_name as uploader_name
    FROM documents d
    LEFT JOIN folders f ON d.folder_id = f.id
    LEFT JOIN users u ON d.uploaded_by = u.id
    WHERE 1=1
";

$params = [];

if ($searchQuery) {
    $searchTerm = '%' . $searchQuery . '%';
    $sql .= " AND (d.title LIKE ? OR d.description LIKE ? OR d.tags LIKE ? OR d.pdf_text_content LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($folderId) {
    $sql .= " AND d.folder_id = ?";
    $params[] = $folderId;
}

$sql .= " ORDER BY d.uploaded_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll();

// Get folders with document counts
$folders = $pdo->query("
    SELECT f.*, COUNT(d.id) as document_count
    FROM folders f
    LEFT JOIN documents d ON f.id = d.folder_id
    GROUP BY f.id
    ORDER BY f.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/x-icon" href="assets/images/daihatsu-logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --primary-light: #7dd3fc;
            --sky-50: #f0f9ff;
            --sky-100: #e0f2fe;
            --sky-500: #0ea5e9;
            --sky-600: #0284c7;
            --sky-700: #0369a1;
            --bg: #f8fafc;
            --surface: #ffffff;
            --border: #e0f2fe;
            --text: #0c4a6e;
            --text-secondary: #64748b;
            --hover: #e0f2fe;
            --accent: #f97316;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
            line-height: 1.5;
        }

        .header {
            background: linear-gradient(135deg, var(--sky-600) 0%, var(--sky-700) 100%);
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
            min-height: 56px;
            gap: 1.5rem;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo-image {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 6px;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
            flex-shrink: 0;
        }

        .logo-image img {
            width: 100%;
            height: 100%;
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

        .logo-main i {
            font-size: 1.2rem;
            color: var(--primary-light);
        }

        .logo-subtitle {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 400;
            letter-spacing: 0.02em;
            line-height: 1.2;
        }

        .nav {
            display: flex;
            gap: 1rem;
        }

        .header-spacer {
            flex: 1;
        }

        .nav a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            padding: 0.4rem 0;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }

        .nav a:hover, .nav a.active {
            color: white;
            border-bottom-color: var(--primary-light);
        }

        .admin-btn {
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

        .admin-btn:hover {
            background: white;
            color: var(--sky-600);
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem;
        }

        .toolbar {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.875rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .toolbar-row {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 300px;
            padding: 0.5rem 0.75rem 0.5rem 2.25rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.875rem;
            background: var(--surface);
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: 0.75rem center;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        select, .btn {
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.875rem;
            background: var(--surface);
            cursor: pointer;
            font-weight: 500;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .view-toggle {
            display: flex;
            background: var(--bg);
            border-radius: 6px;
            padding: 0.25rem;
            gap: 0.25rem;
        }

        .view-toggle a {
            padding: 0.375rem 0.75rem;
            border-radius: 4px;
            text-decoration: none;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.813rem;
        }

        .view-toggle a.active {
            background: var(--surface);
            color: var(--primary);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .results-info {
            padding: 0.75rem 1rem;
            background: var(--primary);
            color: white;
            border-radius: 6px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        /* Folder Section */
        .folders-section {
            margin-bottom: 1.5rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .section-header h2 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .folders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 0.75rem;
        }

        .folder-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.875rem;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            display: block;
        }

        .folder-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.15);
            transform: translateY(-2px);
        }

        .folder-card-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            margin-bottom: 0.625rem;
        }

        .folder-card-name {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text);
            margin-bottom: 0.25rem;
        }

        .folder-card-count {
            font-size: 0.75rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Table View */
        .table-view {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }

        .table-view table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-view th {
            background: var(--bg);
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border);
        }

        .table-view td {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid var(--border);
            font-size: 0.875rem;
        }

        .table-view tr:last-child td {
            border-bottom: none;
        }

        .table-view tr:hover {
            background: var(--hover);
        }

        .doc-title {
            font-weight: 600;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .doc-title a {
            color: var(--text);
            text-decoration: none;
        }

        .doc-title a:hover {
            color: var(--primary);
        }

        .doc-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .doc-description {
            color: var(--text-secondary);
            font-size: 0.813rem;
            margin-top: 0.25rem;
            max-width: 400px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
        }

        .tag {
            background: var(--bg);
            color: var(--text-secondary);
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            padding: 0.375rem 0.625rem;
            border: 1px solid var(--border);
            border-radius: 4px;
            background: var(--surface);
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .action-btn:hover {
            background: var(--bg);
            color: var(--primary);
            border-color: var(--primary);
        }

        .action-btn.primary {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .action-btn.primary:hover {
            background: var(--primary-dark);
        }

        /* Grid View */
        .grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .doc-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            transition: all 0.2s;
        }

        .doc-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-color: var(--primary);
        }

        .doc-card-header {
            background: linear-gradient(135deg, var(--primary) 0%, #3b82f6 100%);
            color: white;
            padding: 0.75rem;
            font-weight: 700;
            text-align: center;
            font-size: 1rem;
        }

        .doc-card-body {
            padding: 1rem;
        }

        .doc-card h3 {
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .doc-card h3 a {
            color: var(--text);
            text-decoration: none;
        }

        .doc-card h3 a:hover {
            color: var(--primary);
        }

        .doc-card-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 0.75rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .doc-card-footer {
            padding: 0.625rem 1rem;
            background: var(--bg);
            border-top: 1px solid var(--border);
            display: flex;
            gap: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--border);
            margin-bottom: 1rem;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--surface);
            padding: 1.5rem;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-secondary);
            cursor: pointer;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: var(--bg);
        }

        .modal h2 {
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.875rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        /* Tablet */
        @media (max-width: 1024px) {
            .container {
                padding: 0.875rem;
            }
            .logo-image {
                width: 38px;
                height: 38px;
            }
            .logo-main {
                font-size: 1rem;
            }
            .logo-subtitle {
                font-size: 0.65rem;
            }
        }

        /* Mobile */
        @media (max-width: 768px) {
            .header-container {
                flex-wrap: wrap;
                min-height: auto;
                padding: 0.625rem 0.875rem;
                gap: 0.625rem;
            }
            .logo {
                gap: 0.625rem;
            }
            .logo-image {
                width: 36px;
                height: 36px;
                padding: 4px;
            }
            .logo-main {
                font-size: 0.95rem;
            }
            .logo-main i {
                font-size: 1.1rem;
            }
            .logo-subtitle {
                font-size: 0.625rem;
            }
            .nav {
                flex: 1;
            }
            .nav a {
                font-size: 0.8rem;
            }
            .admin-btn {
                padding: 0.4rem 0.875rem;
                font-size: 0.75rem;
            }
            .container {
                padding: 0.625rem;
            }
            .toolbar {
                padding: 0.75rem;
            }
            .toolbar-row {
                flex-direction: column;
                gap: 0.625rem;
            }
            .search-group {
                flex-direction: column;
            }
            .table-view {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .grid-view {
                grid-template-columns: 1fr;
            }
            .doc-card {
                padding: 0.875rem;
            }
        }

        /* Small Mobile */
        @media (max-width: 480px) {
            .logo {
                gap: 0.5rem;
            }
            .logo-image {
                width: 32px;
                height: 32px;
                padding: 3px;
            }
            .logo-main {
                font-size: 0.85rem;
            }
            .logo-subtitle {
                font-size: 0.6rem;
            }
            .header-container {
                gap: 0.5rem;
                padding: 0.5rem 0.75rem;
            }
            .nav a {
                font-size: 0.75rem;
                gap: 0.25rem;
            }
            .admin-btn {
                padding: 0.35rem 0.75rem;
                font-size: 0.7rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <div class="logo">
                <div class="logo-image">
                    <img src="assets/images/daihatsu-logo.png" alt="<?php echo COMPANY_NAME; ?>" onerror="this.parentElement.innerHTML='<i class=\'fas fa-industry\' style=\'font-size:32px;color:#0284c7;\'></i>'">
                </div>
                <div class="logo-text">
                    <div class="logo-main">
                        <?php echo COMPANY_NAME; ?>
                    </div>
                    <div class="logo-subtitle">
                        <?php echo DEPARTMENT; ?> | Document Management System
                    </div>
                </div>
            </div>
            <div class="header-spacer"></div>
            <a href="#" onclick="openLoginModal(); return false;" class="admin-btn">
                <i class="fas fa-user-shield"></i>
                Admin Login
            </a>
        </div>
    </header>

    <div class="container">
        <div class="toolbar">
            <form action="" method="GET">
                <div class="toolbar-row">
                    <input type="text" name="search" placeholder="Search documents..." value="<?php echo clean($searchQuery); ?>" class="search-input">
                    <select name="folder">
                        <option value="">All Folders</option>
                        <?php foreach ($folders as $folder): ?>
                            <option value="<?php echo $folder['id']; ?>" <?php echo $folderId == $folder['id'] ? 'selected' : ''; ?>>
                                <?php echo clean($folder['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                    <?php if ($searchQuery || $folderId): ?>
                        <a href="index.php" class="btn">Clear</a>
                    <?php endif; ?>
                    <input type="hidden" name="view" value="<?php echo $view; ?>">
                    <div class="view-toggle">
                        <a href="?view=table<?php echo $searchQuery ? '&search='.urlencode($searchQuery) : ''; ?><?php echo $folderId ? '&folder='.$folderId : ''; ?>" class="<?php echo $view === 'table' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> Table
                        </a>
                        <a href="?view=grid<?php echo $searchQuery ? '&search='.urlencode($searchQuery) : ''; ?><?php echo $folderId ? '&folder='.$folderId : ''; ?>" class="<?php echo $view === 'grid' ? 'active' : ''; ?>">
                            <i class="fas fa-th"></i> Grid
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <?php if (!$searchQuery && !$folderId): ?>
            <!-- Folder Navigation -->
            <div class="folders-section">
                <div class="section-header">
                    <h2>
                        Browse by Folder
                    </h2>
                </div>
                <div class="folders-grid">
                    <?php foreach ($folders as $folder): ?>
                        <a href="folder_view.php?id=<?php echo $folder['id']; ?>" class="folder-card">
                            <div class="folder-card-icon">
                                <i class="fas fa-folder"></i>
                            </div>
                            <div class="folder-card-name"><?php echo clean($folder['name']); ?></div>
                            <div class="folder-card-count">
                                <i class="fas fa-file-alt"></i>
                                <?php echo $folder['document_count']; ?> document<?php echo $folder['document_count'] != 1 ? 's' : ''; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="section-header">
                <h2>
                    All Documents
                </h2>
            </div>
        <?php endif; ?>

        <?php if ($searchQuery || $folderId): ?>
            <div class="results-info">
                <i class="fas fa-info-circle"></i>
                Found <?php echo count($documents); ?> document(s)
                <?php if ($searchQuery): ?>matching "<?php echo clean($searchQuery); ?>"<?php endif; ?>
                <?php if ($folderId):
                    $folderName = array_filter($folders, fn($f) => $f['id'] == $folderId);
                    if ($folderName): ?> in "<?php echo clean(array_values($folderName)[0]['name']); ?>"<?php endif;
                endif; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($documents)): ?>
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <p><?php echo $searchQuery || $folderId ? 'No documents found' : 'No documents available'; ?></p>
            </div>
        <?php elseif ($view === 'table'): ?>
            <div class="table-view">
                <table>
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Folder</th>
                            <th>Tags</th>
                            <th>Date</th>
                            <th>Size</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td>
                                    <div class="doc-title">
                                        <div class="doc-icon">PDF</div>
                                        <div>
                                            <a href="view_document.php?id=<?php echo $doc['id']; ?>">
                                                <?php echo clean($doc['title']); ?>
                                            </a>
                                            <?php if ($doc['description']): ?>
                                                <div class="doc-description"><?php echo clean($doc['description']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $doc['folder_name'] ? clean($doc['folder_name']) : '-'; ?></td>
                                <td>
                                    <?php if ($doc['tags']): ?>
                                        <div class="tag-list">
                                            <?php foreach (array_slice(explode(',', $doc['tags']), 0, 2) as $tag): ?>
                                                <span class="tag"><?php echo clean(trim($tag)); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>-<?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></td>
                                <td><?php echo formatFileSize($doc['file_size']); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="action-btn">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="download.php?id=<?php echo $doc['id']; ?>" class="action-btn primary">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="grid-view">
                <?php foreach ($documents as $doc): ?>
                    <div class="doc-card">
                        <div class="doc-card-header">PDF</div>
                        <div class="doc-card-body">
                            <h3><a href="view_document.php?id=<?php echo $doc['id']; ?>"><?php echo clean($doc['title']); ?></a></h3>
                            <?php if ($doc['description']): ?>
                                <p class="doc-description"><?php echo clean(substr($doc['description'], 0, 80)).(strlen($doc['description']) > 80 ? '...' : ''); ?></p>
                            <?php endif; ?>
                            <?php if ($doc['tags']): ?>
                                <div class="tag-list" style="margin-top: 0.75rem;">
                                    <?php foreach (array_slice(explode(',', $doc['tags']), 0, 3) as $tag): ?>
                                        <span class="tag"><?php echo clean(trim($tag)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="doc-card-meta">
                                <?php if ($doc['folder_name']): ?>
                                    <span class="meta-item"><i class="fas fa-folder"></i> <?php echo clean($doc['folder_name']); ?></span>
                                <?php endif; ?>
                                <span class="meta-item"><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></span>
                                <span class="meta-item"><i class="fas fa-file"></i> <?php echo formatFileSize($doc['file_size']); ?></span>
                            </div>
                        </div>
                        <div class="doc-card-footer">
                            <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="action-btn" style="flex: 1;">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="download.php?id=<?php echo $doc['id']; ?>" class="action-btn primary" style="flex: 1;">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="loginModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeLoginModal()">&times;</button>
            <h2>Admin Login</h2>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php
                    $errorMsg = 'An error occurred';
                    switch($_GET['error']) {
                        case 'invalid':
                            $errorMsg = 'Invalid username or password';
                            break;
                        case 'inactive':
                            $errorMsg = 'Your account has been deactivated';
                            break;
                        case 'empty':
                            $errorMsg = 'Please enter both username and password';
                            break;
                        case 'system':
                            $errorMsg = 'System error. Please contact administrator';
                            break;
                    }
                    echo $errorMsg;
                    ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['logout'])): ?>
                <div class="alert alert-success">Logged out successfully</div>
            <?php endif; ?>
            <form action="auth/login.php" method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">Login</button>
            </form>
        </div>
    </div>

    <script>
        function openLoginModal() {
            document.getElementById('loginModal').classList.add('active');
        }
        function closeLoginModal() {
            document.getElementById('loginModal').classList.remove('active');
        }
        window.onclick = function(event) {
            const modal = document.getElementById('loginModal');
            if (event.target === modal) closeLoginModal();
        }

        // Get search input element
        const searchInput = document.querySelector('input[name="search"]');

        // Auto-focus search input if there's a search query
        if (searchInput.value) {
            searchInput.focus();
            // Move cursor to end of text
            searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
        }

        // Auto-submit form when folder dropdown changes
        document.querySelector('select[name="folder"]').addEventListener('change', function() {
            this.closest('form').submit();
        });

        // Auto-search functionality with debounce and URL update (no page reload)
        let searchTimeout;
        const folderSelect = document.querySelector('select[name="folder"]');

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchValue = this.value.trim();

            searchTimeout = setTimeout(function() {
                // Update URL without page reload
                const url = new URL(window.location);
                if (searchValue) {
                    url.searchParams.set('search', searchValue);
                } else {
                    url.searchParams.delete('search');
                }
                window.history.pushState({}, '', url);

                // Reload page to show results
                window.location.reload();
            }, 800); // Increased to 800ms for smoother typing
        });

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                const searchValue = this.value.trim();

                const url = new URL(window.location);
                if (searchValue) {
                    url.searchParams.set('search', searchValue);
                } else {
                    url.searchParams.delete('search');
                }
                window.location.href = url.toString();
            }
        });

        <?php if (isset($_GET['error'])): ?>openLoginModal();<?php endif; ?>
        <?php if (isset($_GET['logout'])): ?>
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert-success');
                alerts.forEach(alert => {
                    alert.style.transition = 'opacity 0.3s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                });
            }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>
