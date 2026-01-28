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

// Get recent projects
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

// Get recent documents
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

// Get recent activity
$recentActivity = $pdo->query("
    SELECT al.*, u.full_name as user_name, d.title as document_title
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    LEFT JOIN documents d ON al.document_id = d.id
    ORDER BY al.created_at DESC
    LIMIT 8
")->fetchAll();

// Get top downloads
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
</head>
<body>
    <?php include '../includes/modern-admin-header.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Dashboard Overview</h1>
            <p>Welcome back, <?php echo clean($_SESSION['full_name'] ?? 'Admin'); ?>!</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['total_docs']); ?></div>
                    <div class="stat-label">Documents</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fas fa-folder"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['total_folders']); ?></div>
                    <div class="stat-label">Folders</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-briefcase"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['total_projects']); ?></div>
                    <div class="stat-label">Projects</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-download"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($stats['total_downloads'] ?? 0); ?></div>
                    <div class="stat-label">Downloads</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-hdd"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo formatFileSize($stats['total_size'] ?? 0); ?></div>
                    <div class="stat-label">Storage</div>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
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
                                $iconClass = ($ext === 'pdf') ? 'pdf' : (($ext === 'doc' || $ext === 'docx') ? 'word' : (($ext === 'xls' || $ext === 'xlsx') ? 'excel' : 'ppt'));
                                $iconName = ($ext === 'pdf') ? 'file-pdf' : (($ext === 'doc' || $ext === 'docx') ? 'file-word' : (($ext === 'xls' || $ext === 'xlsx') ? 'file-excel' : 'file-powerpoint'));
                            ?>
                                <div class="doc-item">
                                    <div class="doc-icon <?php echo $iconClass; ?>">
                                        <i class="fas fa-<?php echo $iconName; ?>"></i>
                                    </div>
                                    <div class="doc-info">
                                        <p class="doc-title">
                                            <a href="view_document.php?id=<?php echo $doc['id']; ?>"><?php echo clean($doc['title']); ?></a>
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
                                            <a href="view_project.php?id=<?php echo $project['id']; ?>"><?php echo clean($project['name']); ?></a>
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
                                $iconClass = ($activity['action'] === 'upload') ? 'upload' : (($activity['action'] === 'download') ? 'download' : (($activity['action'] === 'delete') ? 'delete' : 'login'));
                                $iconName = ($activity['action'] === 'upload') ? 'upload' : (($activity['action'] === 'download') ? 'download' : (($activity['action'] === 'delete') ? 'trash' : 'sign-in-alt'));
                            ?>
                                <div class="activity-item">
                                    <div class="activity-icon <?php echo $iconClass; ?>">
                                        <i class="fas fa-<?php echo $iconName; ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <p class="activity-text">
                                            <strong><?php echo clean($activity['user_name'] ?? 'System'); ?></strong>
                                            <?php echo strtolower($activity['action']); ?>ed
                                            <?php if ($activity['document_title']): ?> "<?php echo clean($activity['document_title']); ?>" <?php endif; ?>
                                        </p>
                                        <div class="activity-time">
                                            <i class="fas fa-clock"></i> <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($topDocs)): ?>
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-fire"></i> Top Downloads</h2>
                </div>
                <div class="card-body">
                    <div class="top-list">
                        <?php foreach ($topDocs as $index => $doc):
                            $rankClass = ($index === 0) ? 'gold' : (($index === 1) ? 'silver' : (($index === 2) ? 'bronze' : ''));
                        ?>
                            <div class="top-item">
                                <div class="top-rank <?php echo $rankClass; ?>"><?php echo $index + 1; ?></div>
                                <div class="top-info"><?php echo clean($doc['title']); ?></div>
                                <div class="top-count"><?php echo number_format($doc['download_count']); ?> downloads</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>