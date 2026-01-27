<?php
require_once 'config/config.php';

// Get folder ID and search query - PUBLIC ACCESS (no login required)
$folderId = $_GET['id'] ?? 0;
$searchQuery = $_GET['search'] ?? '';

// Fetch folder details
$stmt = $pdo->prepare("
    SELECT f.*, u.full_name as creator_name, COUNT(d.id) as document_count
    FROM folders f
    LEFT JOIN users u ON f.created_by = u.id
    LEFT JOIN documents d ON f.id = d.folder_id
    WHERE f.id = ?
    GROUP BY f.id
");
$stmt->execute([$folderId]);
$folder = $stmt->fetch();

if (!$folder) {
    header('Location: index.php');
    exit;
}

// Get documents in this folder with search
$sql = "
    SELECT d.*, u.full_name as uploader_name
    FROM documents d
    LEFT JOIN users u ON d.uploaded_by = u.id
    WHERE d.folder_id = ?
";

$params = [$folderId];

if ($searchQuery) {
    $searchTerm = '%' . $searchQuery . '%';
    $sql .= " AND (d.title LIKE ? OR d.description LIKE ? OR d.tags LIKE ? OR d.pdf_text_content LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY d.uploaded_at DESC";

$docsStmt = $pdo->prepare($sql);
$docsStmt->execute($params);
$documents = $docsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo clean($folder['name']); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/modern-style.css">
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
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            position: static;
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
            text-decoration: none;
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

        .header-actions {
            margin-left: auto;
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            color: white;
            padding: 0.4rem 0.875rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.25s;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .back-btn:hover {
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

        .folder-header {
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .folder-header-content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .folder-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, #6366f1 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
        }

        .folder-info {
            flex: 1;
        }

        .folder-info h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.25rem;
        }

        .folder-info p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .folder-stats {
            display: flex;
            gap: 1.5rem;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .folder-stats span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .search-bar {
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .search-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .search-input {
            flex: 1;
            padding: 0.5rem 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.875rem;
            transition: border-color 0.2s;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .search-btn {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .search-btn:hover {
            background: var(--primary-dark);
        }

        .clear-btn {
            background: var(--surface);
            color: var(--text);
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .clear-btn:hover {
            background: var(--hover);
        }

        .search-info {
            background: var(--sky-50);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 0.625rem 0.875rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-info i {
            color: var(--primary);
        }

        .documents-list {
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }

        .documents-list-header {
            background: var(--sky-50);
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text);
        }

        .document-item {
            padding: 0.875rem 1rem;
            border-bottom: 1px solid var(--border);
            transition: background 0.2s;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .document-item:last-child {
            border-bottom: none;
        }

        .document-item:hover {
            background: var(--hover);
        }

        .doc-icon {
            width: 45px;
            height: 45px;
            background: var(--primary);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .doc-info {
            flex: 1;
            min-width: 0;
        }

        .doc-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text);
            margin-bottom: 0.25rem;
        }

        .doc-title a {
            color: var(--text);
            text-decoration: none;
            transition: color 0.2s;
        }

        .doc-title a:hover {
            color: var(--primary);
        }

        .doc-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.75rem;
            color: var(--text-secondary);
            flex-wrap: wrap;
        }

        .doc-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .doc-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .btn-icon {
            padding: 0.4rem 0.75rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text);
            text-decoration: none;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.2s;
            font-weight: 500;
        }

        .btn-icon:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--border);
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .folder-header-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .folder-info h1 {
                font-size: 1.25rem;
            }

            .search-form {
                flex-direction: column;
                align-items: stretch;
            }

            .search-btn,
            .clear-btn {
                justify-content: center;
            }

            .document-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .doc-actions {
                width: 100%;
            }

            .btn-icon {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <a href="index.php" class="logo">
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
            </a>
            <div class="header-actions">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to All Documents</span>
                </a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="folder-header">
            <div class="folder-header-content">
                <div class="folder-icon">
                    <i class="fas fa-folder-open" style="font-size: 2rem;"></i>
                </div>
                <div class="folder-info">
                    <h1><?php echo clean($folder['name']); ?></h1>
                    <?php if ($folder['description']): ?>
                        <p><?php echo clean($folder['description']); ?></p>
                    <?php endif; ?>
                    <div class="folder-stats">
                        <span>
                            <i class="fas fa-file-alt"></i>
                            <?php echo $folder['document_count']; ?> document(s)
                        </span>
                        <?php if ($folder['creator_name']): ?>
                            <span>
                                <i class="fas fa-user"></i>
                                Created by <?php echo clean($folder['creator_name']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <form action="" method="GET" class="search-form">
                <input type="hidden" name="id" value="<?php echo $folderId; ?>">
                <input type="text" name="search" placeholder="Search documents by title, description, or tags..."
                       value="<?php echo clean($searchQuery); ?>" class="search-input">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i>
                    Search
                </button>
                <?php if ($searchQuery): ?>
                    <a href="folder_view.php?id=<?php echo $folderId; ?>" class="clear-btn">
                        <i class="fas fa-times"></i>
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($searchQuery): ?>
            <div class="search-info">
                <i class="fas fa-info-circle"></i>
                Found <?php echo count($documents); ?> document(s) matching "<?php echo clean($searchQuery); ?>"
            </div>
        <?php endif; ?>

        <div class="documents-list">
            <div class="documents-list-header">
                <i class="fas fa-list"></i> Documents in this folder
            </div>

            <?php if (empty($documents)): ?>
                <div class="empty-state">
                    <i class="fas fa-<?php echo $searchQuery ? 'search' : 'folder-open'; ?>"></i>
                    <p><?php echo $searchQuery ? 'No documents found matching your search' : 'No documents in this folder yet'; ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($documents as $doc): ?>
                    <div class="document-item">
                        <div class="doc-icon">
                            <i class="fas fa-file-pdf"></i>
                        </div>
                        <div class="doc-info">
                            <div class="doc-title">
                                <a href="view_document.php?id=<?php echo $doc['id']; ?>">
                                    <?php echo clean($doc['title']); ?>
                                </a>
                            </div>
                            <div class="doc-meta">
                                <span>
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?>
                                </span>
                                <span>
                                    <i class="fas fa-hdd"></i>
                                    <?php echo formatFileSize($doc['file_size']); ?>
                                </span>
                                <span>
                                    <i class="fas fa-download"></i>
                                    <?php echo number_format($doc['download_count']); ?> downloads
                                </span>
                                <?php if ($doc['uploader_name']): ?>
                                    <span>
                                        <i class="fas fa-user"></i>
                                        <?php echo clean($doc['uploader_name']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="doc-actions">
                            <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="btn-icon">
                                <i class="fas fa-eye"></i>
                                View
                            </a>
                            <a href="download.php?id=<?php echo $doc['id']; ?>" class="btn-icon">
                                <i class="fas fa-download"></i>
                                Download
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Get search input element
        const searchInput = document.querySelector('input[name="search"]');
        const folderId = document.querySelector('input[name="id"]').value;

        // Auto-focus search input if there's a search query
        if (searchInput.value) {
            searchInput.focus();
            // Move cursor to end of text
            searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
        }

        // Auto-search functionality with debounce and URL update (no page reload)
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchValue = this.value.trim();

            searchTimeout = setTimeout(function() {
                // Update URL without page reload
                const url = new URL(window.location);
                url.searchParams.set('id', folderId);
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
                url.searchParams.set('id', folderId);
                if (searchValue) {
                    url.searchParams.set('search', searchValue);
                } else {
                    url.searchParams.delete('search');
                }
                window.location.href = url.toString();
            }
        });
    </script>
</body>
</html>
