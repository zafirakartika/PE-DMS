<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../index.php');
}

// Get statistics
$statsStmt = $pdo->query("SELECT
    (SELECT COUNT(*) FROM documents) as total_docs,
    (SELECT COUNT(*) FROM folders) as total_folders,
    (SELECT COUNT(*) FROM projects) as total_projects,
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT SUM(download_count) FROM documents) as total_downloads,
    (SELECT SUM(file_size) FROM documents) as total_size
");
$stats = $statsStmt->fetch();

// Get recent projects with document count
$recentProjects = $pdo->query("
    SELECT p.*, f.name as folder_name, u.full_name as creator_name,
           COUNT(d.id) as document_count
    FROM projects p
    LEFT JOIN folders f ON p.folder_id = f.id
    LEFT JOIN users u ON p.created_by = u.id
    LEFT JOIN documents d ON p.id = d.project_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 5
")->fetchAll();

// Get recent documents (5 only)
$recentDocs = $pdo->query("
    SELECT d.*, f.name as folder_name, p.name as project_name, dt.name as type_name, u.full_name as uploader_name
    FROM documents d
    LEFT JOIN folders f ON d.folder_id = f.id
    LEFT JOIN projects p ON d.project_id = p.id
    LEFT JOIN document_types dt ON d.document_type_id = dt.id
    LEFT JOIN users u ON d.uploaded_by = u.id
    ORDER BY d.uploaded_at DESC
    LIMIT 5
")->fetchAll();

// Get recent activity (8 only)
$recentActivity = $pdo->query("
    SELECT al.*, u.full_name as user_name, d.title as document_title
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    LEFT JOIN documents d ON al.document_id = d.id
    ORDER BY al.created_at DESC
    LIMIT 8
")->fetchAll();

// Get top downloaded documents
$topDocs = $pdo->query("
    SELECT d.title, d.download_count, d.id
    FROM documents d
    WHERE d.download_count > 0
    ORDER BY d.download_count DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/modern-style.css">
    <style>
        :root {
            --sky-50: #f0f9ff;
            --sky-100: #e0f2fe;
            --sky-500: #0ea5e9;
            --sky-600: #0284c7;
            --sky-700: #0369a1;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --green-500: #10b981;
            --green-600: #059669;
            --red-500: #ef4444;
            --orange-500: #f59e0b;
            --purple-500: #a855f7;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--gray-50);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--gray-900);
            font-size: 14px;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem;
        }

        .dashboard-header {
            margin-bottom: 1rem;
        }

        .dashboard-header h1 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            margin: 0 0 0.15rem 0;
        }

        .dashboard-header p {
            color: var(--gray-600);
            margin: 0;
            font-size: 0.8rem;
        }

        /* Stats Grid - Compact */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .stat-card {
            background: white;
            border-radius: 6px;
            padding: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
        }

        .stat-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .stat-icon.blue { background: var(--sky-100); color: var(--sky-600); }
        .stat-icon.green { background: #d1fae5; color: var(--green-600); }
        .stat-icon.orange { background: #fed7aa; color: var(--orange-500); }
        .stat-icon.purple { background: #e9d5ff; color: var(--purple-500); }

        .stat-content {
            flex: 1;
        }

        .stat-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1;
            margin-bottom: 0.15rem;
        }

        .stat-label {
            font-size: 0.7rem;
            color: var(--gray-600);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            padding: 0.75rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header h2 {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .card-header h2 i {
            font-size: 0.8rem;
            color: var(--sky-600);
        }

        .card-body {
            padding: 0;
        }

        /* Recent Documents - Compact List */
        .doc-list {
            max-height: 240px;
            overflow-y: auto;
        }

        .doc-item {
            padding: 0.6rem 0.8rem;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            transition: background 0.15s;
        }

        .doc-item:last-child {
            border-bottom: none;
        }

        .doc-item:hover {
            background: var(--gray-50);
        }

        .doc-icon {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .doc-icon.pdf { background: #fee2e2; color: #dc2626; }
        .doc-icon.word { background: #dbeafe; color: #2563eb; }
        .doc-icon.excel { background: #d1fae5; color: #16a34a; }
        .doc-icon.ppt { background: #fed7aa; color: #ea580c; }

        .doc-info {
            flex: 1;
            min-width: 0;
        }

        .doc-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-900);
            margin: 0 0 0.125rem 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .doc-title a {
            color: var(--gray-900);
            text-decoration: none;
        }

        .doc-title a:hover {
            color: var(--sky-600);
        }

        .doc-meta {
            font-size: 0.7rem;
            color: var(--gray-500);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .doc-meta span {
            display: flex;
            align-items: center;
            gap: 0.2rem;
        }

        /* Activity List - Compact */
        .activity-list {
            max-height: 240px;
            overflow-y: auto;
        }

        .activity-item {
            padding: 0.6rem 0.8rem;
            border-bottom: 1px solid var(--gray-100);
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .activity-icon.upload { background: var(--sky-100); color: var(--sky-600); }
        .activity-icon.download { background: #d1fae5; color: var(--green-600); }
        .activity-icon.delete { background: #fee2e2; color: #dc2626; }
        .activity-icon.login { background: #e9d5ff; color: var(--purple-500); }

        .activity-content {
            flex: 1;
            min-width: 0;
        }

        .activity-text {
            font-size: 0.875rem;
            color: var(--gray-900);
            margin: 0 0 0.25rem 0;
        }

        .activity-text strong {
            font-weight: 600;
        }

        .activity-time {
            font-size: 0.75rem;
            color: var(--gray-500);
        }

        /* Top Downloads */
        .top-list {
            padding: 0.5rem 1rem 1rem 1rem;
        }

        .top-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
        }

        .top-rank {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--sky-100);
            color: var(--sky-600);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            flex-shrink: 0;
        }

        .top-rank.gold { background: #fef3c7; color: #f59e0b; }
        .top-rank.silver { background: var(--gray-200); color: var(--gray-600); }
        .top-rank.bronze { background: #fed7aa; color: #ea580c; }

        .top-info {
            flex: 1;
            min-width: 0;
        }

        .top-title {
            font-size: 0.875rem;
            color: var(--gray-900);
            margin: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .top-count {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--gray-600);
            flex-shrink: 0;
        }

        /* Empty State */
        .empty-state {
            padding: 2rem;
            text-align: center;
            color: var(--gray-500);
            font-size: 0.875rem;
        }

        .empty-state i {
            font-size: 2rem;
            color: var(--gray-300);
            margin-bottom: 0.5rem;
        }

        /* Custom Scrollbar */
        .doc-list::-webkit-scrollbar,
        .activity-list::-webkit-scrollbar {
            width: 6px;
        }

        .doc-list::-webkit-scrollbar-track,
        .activity-list::-webkit-scrollbar-track {
            background: var(--gray-100);
        }

        .doc-list::-webkit-scrollbar-thumb,
        .activity-list::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 3px;
        }

        .doc-list::-webkit-scrollbar-thumb:hover,
        .activity-list::-webkit-scrollbar-thumb:hover {
            background: var(--gray-400);
        }
    </style>
</head>
<body>
    <?php include '../includes/modern-admin-header.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Dashboard Overview</h1>
            <p>Welcome back, <?php echo clean($_SESSION['full_name'] ?? 'Admin'); ?>!</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['total_docs']); ?></div>
                    <div class="stat-label">Documents</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['total_folders']); ?></div>
                    <div class="stat-label">Folders</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['total_projects']); ?></div>
                    <div class="stat-label">Projects</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fas fa-download"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['total_downloads'] ?? 0); ?></div>
                    <div class="stat-label">Downloads</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon purple">
                    <i class="fas fa-hdd"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo formatFileSize($stats['total_size'] ?? 0); ?></div>
                    <div class="stat-label">Storage</div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="dashboard-grid">
            <!-- Recent Documents -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-clock"></i> Recent Documents</h2>
                    <a href="documents.php" class="view-all">View All →</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentDocs)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <p>No documents uploaded yet</p>
                        </div>
                    <?php else: ?>
                        <div class="doc-list">
                            <?php foreach ($recentDocs as $doc):
                                $ext = strtolower(pathinfo($doc['filename'], PATHINFO_EXTENSION));
                                $iconClass = ($ext === 'pdf') ? 'pdf' :
                                            (($ext === 'doc' || $ext === 'docx') ? 'word' :
                                            (($ext === 'xls' || $ext === 'xlsx') ? 'excel' : 'ppt'));
                                $iconName = ($ext === 'pdf') ? 'file-pdf' :
                                           (($ext === 'doc' || $ext === 'docx') ? 'file-word' :
                                           (($ext === 'xls' || $ext === 'xlsx') ? 'file-excel' : 'file-powerpoint'));
                            ?>
                                <div class="doc-item">
                                    <div class="doc-icon <?php echo $iconClass; ?>">
                                        <i class="fas fa-<?php echo $iconName; ?>"></i>
                                    </div>
                                    <div class="doc-info">
                                        <p class="doc-title">
                                            <a href="view_document.php?id=<?php echo $doc['id']; ?>">
                                                <?php echo clean($doc['title']); ?>
                                            </a>
                                        </p>
                                        <div class="doc-meta">
                                            <span><i class="fas fa-folder"></i> <?php echo clean($doc['folder_name'] ?? 'N/A'); ?></span>
                                            <span><i class="fas fa-clock"></i> <?php echo date('M d, H:i', strtotime($doc['uploaded_at'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Projects -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-briefcase"></i> Recent Projects</h2>
                    <a href="projects.php" class="view-all">View All →</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentProjects)): ?>
                        <div class="empty-state">
                            <i class="fas fa-project-diagram"></i>
                            <p>No projects created yet</p>
                        </div>
                    <?php else: ?>
                        <div class="doc-list">
                            <?php foreach ($recentProjects as $project): ?>
                                <div class="doc-item">
                                    <div class="doc-icon" style="background: #e9d5ff; color: #7c3aed;">
                                        <i class="fas fa-briefcase"></i>
                                    </div>
                                    <div class="doc-info">
                                        <p class="doc-title">
                                            <a href="view_project.php?id=<?php echo $project['id']; ?>">
                                                <?php echo clean($project['name']); ?>
                                            </a>
                                        </p>
                                        <div class="doc-meta">
                                            <span><i class="fas fa-folder"></i> <?php echo clean($project['folder_name'] ?? 'No Folder'); ?></span>
                                            <span><i class="fas fa-file"></i> <?php echo $project['document_count']; ?> docs</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-history"></i> Recent Activity</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($recentActivity)): ?>
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <p>No activity yet</p>
                        </div>
                    <?php else: ?>
                        <div class="activity-list">
                            <?php foreach ($recentActivity as $activity):
                                $iconClass = ($activity['action'] === 'upload') ? 'upload' :
                                            (($activity['action'] === 'download') ? 'download' :
                                            (($activity['action'] === 'delete') ? 'delete' : 'login'));
                                $iconName = ($activity['action'] === 'upload') ? 'upload' :
                                           (($activity['action'] === 'download') ? 'download' :
                                           (($activity['action'] === 'delete') ? 'trash' : 'sign-in-alt'));
                            ?>
                                <div class="activity-item">
                                    <div class="activity-icon <?php echo $iconClass; ?>">
                                        <i class="fas fa-<?php echo $iconName; ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p class="activity-text">
                                            <strong><?php echo clean($activity['user_name'] ?? 'System'); ?></strong>
                                            <?php echo strtolower($activity['action']); ?>ed
                                            <?php if ($activity['document_title']): ?>
                                                "<?php echo clean($activity['document_title']); ?>"
                                            <?php endif; ?>
                                        </p>
                                        <div class="activity-time">
                                            <i class="fas fa-clock"></i>
                                            <?php
                                            // Display actual timestamp in a readable format
                                            $timestamp = strtotime($activity['created_at']);
                                            echo date('M d, Y H:i', $timestamp);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Downloads -->
        <?php if (!empty($topDocs)): ?>
            <div class="card" style="margin-top: 1rem;">
                <div class="card-header">
                    <h2><i class="fas fa-fire"></i> Top Downloads</h2>
                </div>
                <div class="card-body">
                    <div class="top-list">
                        <?php foreach ($topDocs as $index => $doc):
                            $rankClass = ($index === 0) ? 'gold' : (($index === 1) ? 'silver' : (($index === 2) ? 'bronze' : ''));
                        ?>
                            <div class="top-item">
                                <div class="top-rank <?php echo $rankClass; ?>">
                                    <?php echo $index + 1; ?>
                                </div>
                                <div class="top-info">
                                    <p class="top-title"><?php echo clean($doc['title']); ?></p>
                                </div>
                                <div class="top-count">
                                    <?php echo number_format($doc['download_count']); ?> downloads
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
