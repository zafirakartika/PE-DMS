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
    <link rel="stylesheet" href="assets/css/modern-style.css">
    <style>
        /* --- HEADER STYLES (MATCHING ADMIN) --- */
        .public-header {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); /* Daihatsu Blue */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .public-header-container {
            max-width: 1400px; /* Matches Admin max-width */
            margin: 0 auto;
            padding: 0 1.25rem;
            display: flex;
            align-items: center;
            height: 64px; /* Compact height */
            gap: 2rem;
        }

        /* Logo */
        .public-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            flex-shrink: 0;
        }

        .public-logo-image {
            height: 42px;
            width: auto;
            display: flex;
            align-items: center;
            background: transparent; /* No box */
        }

        .public-logo-image img {
            height: 100%;
            width: auto;
            object-fit: contain;
            /* Subtle shadow to ensure logo pops on blue background */
            filter: drop-shadow(0 1px 2px rgba(255,255,255,0.2));
        }

        .public-logo-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-left: 1px solid rgba(255,255,255,0.3);
            padding-left: 1rem;
            height: 38px;
        }

        .public-logo-title {
            font-size: 0.95rem;
            font-weight: 700;
            color: white;
            line-height: 1.2;
            letter-spacing: -0.01em;
        }

        .public-logo-subtitle {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 400;
            letter-spacing: 0.02em;
        }

        /* Admin Button */
        .header-actions {
            margin-left: auto;
        }

        .admin-btn {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .admin-btn:hover {
            background: white;
            color: #0284c7;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        /* --- PAGE CONTENT STYLES --- */
        .container {
            max-width: 1400px; /* Matches Header */
            margin: 0 auto;
            padding: 1.5rem;
        }

        /* Toolbar */
        .toolbar {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .toolbar-row {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 0.5rem 0.75rem 0.5rem 2.25rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.9rem;
            background: #ffffff;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: 0.75rem center;
        }

        .search-input:focus {
            outline: none;
            border-color: #0284c7;
            box-shadow: 0 0 0 2px rgba(2, 132, 199, 0.1);
        }

        select, .btn {
            padding: 0.5rem 1rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 0.9rem;
            background: #ffffff;
            cursor: pointer;
            font-weight: 500;
            color: #475569;
        }

        .btn-primary {
            background: #0284c7;
            color: white;
            border-color: #0284c7;
        }

        .btn-primary:hover {
            background: #0369a1;
        }

        .view-toggle {
            display: flex;
            background: #f1f5f9;
            border-radius: 6px;
            padding: 0.25rem;
            gap: 0.25rem;
        }

        .view-toggle a {
            padding: 0.4rem 0.8rem;
            border-radius: 4px;
            text-decoration: none;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .view-toggle a.active {
            background: #ffffff;
            color: #0284c7;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        /* Sections */
        .section-header h2 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 1rem;
        }

        /* Folder Grid */
        .folders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .folder-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 1rem;
            transition: all 0.2s;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .folder-card:hover {
            border-color: #0284c7;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .folder-card-icon {
            width: 48px;
            height: 48px;
            background: #e0f2fe;
            color: #0284c7;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .folder-card-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .folder-card-count {
            font-size: 0.75rem;
            color: #64748b;
        }

        /* Table View */
        .table-view {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .table-view table { width: 100%; border-collapse: collapse; }
        .table-view th {
            background: #f8fafc;
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
        }
        .table-view td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #334155;
        }
        .table-view tr:last-child td { border-bottom: none; }
        .table-view tr:hover { background: #f8fafc; }

        .doc-title a { color: #1e293b; text-decoration: none; font-weight: 500; }
        .doc-title a:hover { color: #0284c7; }
        .doc-description { font-size: 0.8rem; color: #64748b; margin-top: 0.15rem; }

        .tag {
            background: #f1f5f9;
            color: #64748b;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            border: 1px solid #e2e8f0;
        }

        /* Action Buttons */
        .action-btn {
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-weight: 500;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
            background: white;
            color: #475569;
        }
        .action-btn:hover { border-color: #cbd5e1; background: #f8fafc; color: #1e293b; }
        
        .action-btn.primary {
            background: #0284c7;
            color: white;
            border-color: #0284c7;
        }
        .action-btn.primary:hover { background: #0369a1; }

        /* Grid View */
        .grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1rem;
        }

        .doc-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.2s;
        }
        .doc-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); border-color: #0284c7; }

        .doc-card-header {
            background: #f1f5f9;
            color: #64748b;
            padding: 0.5rem;
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }

        .doc-card-body { padding: 1rem; }
        .doc-card h3 { font-size: 0.95rem; margin-bottom: 0.5rem; line-height: 1.4; }
        .doc-card h3 a { color: #1e293b; text-decoration: none; }
        .doc-card h3 a:hover { color: #0284c7; }
        
        .doc-card-meta { display: flex; gap: 0.75rem; font-size: 0.75rem; color: #94a3b8; margin-top: 0.75rem; }
        .meta-item i { color: #cbd5e1; }

        .doc-card-footer {
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 0.5rem;
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
            background: rgba(15, 23, 42, 0.65); /* Matches Admin Modal backdrop */
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            position: relative;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #94a3b8;
            cursor: pointer;
        }
        .modal-close:hover { color: #475569; }

        .modal h2 { font-size: 1.25rem; margin-bottom: 1.5rem; color: #1e293b; font-weight: 700; }
        
        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem; color: #475569; }
        .form-group input { width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; }
        .form-group input:focus { border-color: #0284c7; outline: none; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1); }
        
        .alert { padding: 0.75rem; border-radius: 6px; margin-bottom: 1.25rem; font-size: 0.9rem; }
        .alert-error { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
    </style>
</head>
<body>
    <header class="public-header">
        <div class="public-header-container">
            <a href="index.php" class="public-logo">
                <div class="public-logo-image">
                    <img src="assets/images/daihatsu-logo.png" alt="Daihatsu" onerror="this.style.display='none'">
                </div>
                <div class="public-logo-text">
                    <div class="public-logo-title">Document Management System</div>
                    <div class="public-logo-subtitle">Production Engineering Casting</div>
                </div>
            </a>
            
            <div class="header-actions">
                <a href="#" onclick="openLoginModal(); return false;" class="admin-btn">
                    <i class="fas fa-user-shield"></i> <span>Admin Login</span>
                </a>
            </div>
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
            <div class="folders-section">
                <div class="section-header">
                    <h2>Browse by Folder</h2>
                </div>
                <div class="folders-grid">
                    <?php foreach ($folders as $folder): ?>
                        <a href="folder_view.php?id=<?php echo $folder['id']; ?>" class="folder-card">
                            <div class="folder-card-icon">
                                <i class="fas fa-folder"></i>
                            </div>
                            <div class="folder-card-name"><?php echo clean($folder['name']); ?></div>
                            <div class="folder-card-count">
                                <?php echo $folder['document_count']; ?> document<?php echo $folder['document_count'] != 1 ? 's' : ''; ?>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="section-header">
                <h2>All Documents</h2>
            </div>
        <?php endif; ?>

        <?php if ($searchQuery || $folderId): ?>
            <div style="margin-bottom: 1rem; color: #64748b; font-size: 0.9rem;">
                <i class="fas fa-info-circle"></i>
                Found <strong><?php echo count($documents); ?></strong> document(s)
                <?php if ($searchQuery): ?>matching "<?php echo clean($searchQuery); ?>"<?php endif; ?>
                <?php if ($folderId):
                    $folderName = array_filter($folders, fn($f) => $f['id'] == $folderId);
                    if ($folderName): ?> in "<?php echo clean(array_values($folderName)[0]['name']); ?>"<?php endif;
                endif; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($documents)): ?>
            <div style="text-align: center; padding: 4rem; color: #94a3b8; background: white; border-radius: 10px; border: 1px solid #e2e8f0;">
                <i class="fas fa-folder-open" style="font-size: 3rem; margin-bottom: 1rem; color: #cbd5e1;"></i>
                <p><?php echo $searchQuery || $folderId ? 'No documents found matching your criteria.' : 'No documents available yet.'; ?></p>
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
                                        <div style="display:flex; align-items:center; gap:0.5rem;">
                                            <i class="far fa-file-pdf" style="color: #ef4444; font-size: 1.1rem;"></i>
                                            <div>
                                                <a href="view_document.php?id=<?php echo $doc['id']; ?>">
                                                    <?php echo clean($doc['title']); ?>
                                                </a>
                                                <?php if ($doc['description']): ?>
                                                    <div class="doc-description"><?php echo clean($doc['description']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $doc['folder_name'] ? clean($doc['folder_name']) : '-'; ?></td>
                                <td>
                                    <?php if ($doc['tags']): ?>
                                        <div style="display:flex; gap:0.25rem; flex-wrap:wrap;">
                                            <?php foreach (array_slice(explode(',', $doc['tags']), 0, 2) as $tag): ?>
                                                <span class="tag"><?php echo clean(trim($tag)); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>-<?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></td>
                                <td><?php echo formatFileSize($doc['file_size']); ?></td>
                                <td>
                                    <div style="display:flex; gap:0.5rem;">
                                        <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="action-btn">
                                            View
                                        </a>
                                        <a href="download.php?id=<?php echo $doc['id']; ?>" class="action-btn primary">
                                            <i class="fas fa-download"></i>
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
                        <div class="doc-card-header">
                            <i class="far fa-file-pdf"></i> PDF Document
                        </div>
                        <div class="doc-card-body">
                            <h3><a href="view_document.php?id=<?php echo $doc['id']; ?>"><?php echo clean($doc['title']); ?></a></h3>
                            
                            <?php if ($doc['description']): ?>
                                <p style="font-size:0.85rem; color:#64748b; margin-bottom:0.75rem; line-height:1.5;">
                                    <?php echo clean(substr($doc['description'], 0, 80)).(strlen($doc['description']) > 80 ? '...' : ''); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($doc['tags']): ?>
                                <div style="display:flex; gap:0.25rem; flex-wrap:wrap;">
                                    <?php foreach (array_slice(explode(',', $doc['tags']), 0, 3) as $tag): ?>
                                        <span class="tag"><?php echo clean(trim($tag)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="doc-card-meta">
                                <?php if ($doc['folder_name']): ?>
                                    <span class="meta-item"><i class="fas fa-folder"></i> <?php echo clean($doc['folder_name']); ?></span>
                                <?php endif; ?>
                                <span class="meta-item"><i class="fas fa-file"></i> <?php echo formatFileSize($doc['file_size']); ?></span>
                            </div>
                        </div>
                        <div class="doc-card-footer">
                            <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="action-btn" style="flex: 1; justify-content:center;">
                                View
                            </a>
                            <a href="download.php?id=<?php echo $doc['id']; ?>" class="action-btn primary" style="flex: 1; justify-content:center;">
                                Download
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
            <h2><i class="fas fa-user-shield" style="color:#0284c7; margin-right:0.5rem;"></i> Admin Login</h2>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php
                    $errorMsg = 'An error occurred';
                    switch($_GET['error']) {
                        case 'invalid': $errorMsg = 'Invalid username or password'; break;
                        case 'inactive': $errorMsg = 'Your account has been deactivated'; break;
                        case 'empty': $errorMsg = 'Please enter both username and password'; break;
                        case 'system': $errorMsg = 'System error. Please contact administrator'; break;
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
                    <input type="text" name="username" required autofocus placeholder="Enter your username">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter your password">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; padding:0.75rem; font-size:1rem; margin-top: 0.5rem;">Login to Dashboard</button>
            </form>
        </div>
    </div>

    <script>
        function openLoginModal() {
            document.getElementById('loginModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        function closeLoginModal() {
            document.getElementById('loginModal').classList.remove('active');
            document.body.style.overflow = 'auto';
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
            searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
        }

        // Auto-submit form when folder dropdown changes
        document.querySelector('select[name="folder"]').addEventListener('change', function() {
            this.closest('form').submit();
        });

        // Auto-search functionality with debounce
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchValue = this.value.trim();
            searchTimeout = setTimeout(function() {
                const url = new URL(window.location);
                if (searchValue) url.searchParams.set('search', searchValue);
                else url.searchParams.delete('search');
                window.history.pushState({}, '', url);
                window.location.reload();
            }, 800);
        });

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                const searchValue = this.value.trim();
                const url = new URL(window.location);
                if (searchValue) url.searchParams.set('search', searchValue);
                else url.searchParams.delete('search');
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