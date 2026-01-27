<?php
/**
 * MODERN ADMIN DASHBOARD TEMPLATE
 * Replace the existing dashboard.php with this modern design
 */
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

// Get recent documents
$recentDocs = $pdo->query("
    SELECT d.*, f.name as folder_name, u.full_name as uploader_name
    FROM documents d
    LEFT JOIN folders f ON d.folder_id = f.id
    LEFT JOIN users u ON d.uploaded_by = u.id
    ORDER BY d.uploaded_at DESC
    LIMIT 8
")->fetchAll();

// Get recent activity
$recentActivity = $pdo->query("
    SELECT al.*, u.full_name as user_name, d.title as document_title
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    LEFT JOIN documents d ON al.document_id = d.id
    ORDER BY al.created_at DESC
    LIMIT 6
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/daihatsu-logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/modern-style.css">
    <style>
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: #3b82f6;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .stat-icon.primary {
            background: #eff6ff;
            color: #3b82f6;
        }

        .stat-icon.success {
            background: #ecfdf5;
            color: #10b981;
        }

        .stat-icon.warning {
            background: #fffbeb;
            color: #f59e0b;
        }

        .stat-icon.danger {
            background: #fef2f2;
            color: #ef4444;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #475569;
            font-weight: 500;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: #3b82f6;
        }

        .list-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.3s;
        }

        .list-item:hover {
            background: #f8fafc;
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .list-item-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #3b82f6;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .list-item-content {
            flex: 1;
        }

        .list-item-title {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .list-item-meta {
            font-size: 0.875rem;
            color: #94a3b8;
        }

        .list-item-action {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            color: #475569;
            cursor: pointer;
            transition: all 0.3s;
            flex-shrink: 0;
        }

        .list-item-action:hover {
            background: #3b82f6;
            color: white;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/modern-admin-header.php'; ?>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header" style="margin-bottom: 2.5rem;">
            <div class="page-header-content">
                <h1>Dashboard</h1>
                <p>Welcome back! Here's an overview of your system.</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-file"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_docs'] ?? 0); ?></div>
                <div class="stat-label">Documents</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_folders'] ?? 0); ?></div>
                <div class="stat-label">Folders</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_projects'] ?? 0); ?></div>
                <div class="stat-label">Projects</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_users'] ?? 0); ?></div>
                <div class="stat-label">Users</div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-grid">
            <!-- Recent Documents -->
            <div class="card">
                <div class="card-header">
                    <h3 class="section-title">
                        <i class="fas fa-file-check"></i> Recent Documents
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (count($recentDocs) > 0): ?>
                        <?php foreach ($recentDocs as $doc): ?>
                            <div class="list-item">
                                <div class="list-item-icon">
                                    <i class="fas fa-file-pdf"></i>
                                </div>
                                <div class="list-item-content">
                                    <div class="list-item-title"><?php echo htmlspecialchars($doc['title']); ?></div>
                                    <div class="list-item-meta">
                                        By <?php echo htmlspecialchars($doc['uploader_name'] ?? 'Unknown'); ?> • 
                                        <?php echo time_ago($doc['uploaded_at']); ?>
                                    </div>
                                </div>
                                <a href="view_document.php?id=<?php echo $doc['id']; ?>" class="list-item-action" title="View">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; padding: 2rem; color: #94a3b8;">
                            No documents yet. <a href="upload_document.php">Upload one</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3 class="section-title">
                        <i class="fas fa-history"></i> Recent Activity
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (count($recentActivity) > 0): ?>
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="list-item">
                                <div class="list-item-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="list-item-content">
                                    <div class="list-item-title">
                                        <?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?>
                                    </div>
                                    <div class="list-item-meta">
                                        <?php echo $activity['action']; ?> • 
                                        <?php echo time_ago($activity['created_at']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; padding: 2rem; color: #94a3b8;">
                            No activity recorded yet.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Helper function for time ago
    function time_ago($datetime) {
        $time = strtotime($datetime);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) return 'just now';
        elseif ($diff < 3600) return floor($diff / 60) . 'm ago';
        elseif ($diff < 86400) return floor($diff / 3600) . 'h ago';
        elseif ($diff < 604800) return floor($diff / 86400) . 'd ago';
        else return date('M d, Y', $time);
    }
    ?>
</body>
</html>
