<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../index.php');
}

// Get search parameter
$searchQuery = $_GET['search'] ?? '';
$folderId = $_GET['folder'] ?? '';

// Build query with search
$sql = "
    SELECT p.*,
           COUNT(d.id) as document_count,
           u.full_name as creator_name,
           f.name as folder_name
    FROM projects p
    LEFT JOIN documents d ON p.id = d.project_id
    LEFT JOIN users u ON p.created_by = u.id
    LEFT JOIN folders f ON p.folder_id = f.id
    WHERE 1=1
";

$params = [];

if ($searchQuery) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $searchTerm = "%$searchQuery%";
    $params = [$searchTerm, $searchTerm];
}

if ($folderId) {
    $sql .= " AND p.folder_id = ?";
    $params[] = $folderId;
}

$sql .= " GROUP BY p.id ORDER BY p.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();

// Get all folders for filter dropdown
$folders = $pdo->query("SELECT * FROM folders ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Projects - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/modern-style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f9fafb; font-size: 14px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        .container { max-width: 1400px; margin: 0 auto; padding: 1rem; }
        
        /* Header Consistency */
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; }
        .page-header h1 { font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0; }

        /* Search Bar Consistency */
        .search-container { background: white; padding: 0.8rem; border-radius: 8px; margin-bottom: 0.75rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .search-form { display: flex; gap: 0.6rem; flex-wrap: wrap; }
        .search-input { flex: 1; min-width: 200px; padding: 0.4rem 0.6rem; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.8rem; }
        .search-select { padding: 0.4rem 2rem 0.4rem 0.6rem; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.8rem; background: white; cursor: pointer; }
        
        /* Buttons */
        .btn { padding: 0.4rem 0.8rem; border-radius: 6px; font-size: 0.8rem; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.2s; }
        .btn-primary { background: #0284c7; color: white; }
        .btn-primary:hover { background: #0369a1; }
        .btn-secondary { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }
        .btn-secondary:hover { background: #e5e7eb; }

        /* Alerts */
        .alert { padding: 0.6rem 0.8rem; border-radius: 6px; margin-bottom: 0.75rem; font-size: 0.8rem; display: flex; align-items: center; gap: 0.4rem; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }

        /* Grid & Cards */
        .projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0.75rem; }
        .project-card { background: white; border-radius: 8px; padding: 0.9rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); transition: all 0.2s; display: flex; flex-direction: column; border: 1px solid transparent; }
        .project-card:hover { box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); transform: translateY(-2px); border-color: #e5e7eb; }

        .project-header { display: flex; align-items: center; gap: 0.8rem; margin-bottom: 0.6rem; }
        .project-icon { width: 42px; height: 42px; background: #e0f2fe; color: #0284c7; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }
        
        .project-title { flex: 1; min-width: 0; }
        .project-title h3 { font-size: 0.95rem; font-weight: 600; color: #111827; margin: 0 0 0.1rem 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .project-subtitle { font-size: 0.75rem; color: #6b7280; display: flex; align-items: center; gap: 0.3rem; }

        .project-description { font-size: 0.8rem; color: #4b5563; margin-bottom: 0.75rem; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 2.25em; }

        .project-stats { display: flex; align-items: center; justify-content: space-between; padding-top: 0.6rem; border-top: 1px solid #f3f4f6; margin-bottom: 0.6rem; font-size: 0.75rem; color: #6b7280; }
        .stat-item { display: flex; align-items: center; gap: 0.3rem; }

        /* Actions */
        .project-actions { display: flex; gap: 0.4rem; }
        .btn-action { flex: 1; padding: 0.35rem 0.5rem; border-radius: 5px; font-size: 0.75rem; font-weight: 500; text-decoration: none; text-align: center; border: 1px solid #e5e7eb; background: white; color: #374151; transition: all 0.2s; display: inline-flex; justify-content: center; align-items: center; gap: 0.3rem; }
        .btn-action:hover { background: #f9fafb; border-color: #0284c7; color: #0284c7; }
        .btn-action.btn-primary-light { background: #f0f9ff; color: #0284c7; border-color: #bae6fd; }
        .btn-action.btn-primary-light:hover { background: #e0f2fe; }
        .btn-action.btn-danger { color: #dc2626; }
        .btn-action.btn-danger:hover { background: #fee2e2; border-color: #dc2626; }
        .btn-action.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; background: #f3f4f6; }

        .empty-state { background: white; border-radius: 8px; padding: 2rem 1.5rem; text-align: center; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .empty-state p { color: #6b7280; margin-bottom: 1rem; font-size: 0.9rem; }
    </style>
</head>
<body>
    <?php include '../includes/modern-admin-header.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>Manage Projects</h1>
            <a href="add_project.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Project
            </a>
        </div>

        <div class="search-container">
            <form action="" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search projects by name..." 
                       value="<?php echo clean($searchQuery); ?>" class="search-input">
                <select name="folder" class="search-select">
                    <option value="">All Folders</option>
                    <?php foreach ($folders as $folder): ?>
                        <option value="<?php echo $folder['id']; ?>" <?php echo ($folderId == $folder['id']) ? 'selected' : ''; ?>>
                            <?php echo clean($folder['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if ($searchQuery || $folderId): ?>
                    <a href="projects.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php
                if ($_GET['success'] === 'created') echo 'Project created successfully';
                elseif ($_GET['success'] === 'updated') echo 'Project updated successfully';
                elseif ($_GET['success'] === 'deleted') echo 'Project deleted successfully';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php
                if ($_GET['error'] === 'has_documents') echo 'Cannot delete project that contains documents';
                elseif ($_GET['error'] === 'delete_failed') echo 'Failed to delete project';
                ?>
            </div>
        <?php endif; ?>

        <?php if (empty($projects)): ?>
            <div class="empty-state">
                <i class="fas fa-briefcase" style="font-size: 2.5rem; color: #d1d5db; margin-bottom: 0.75rem;"></i>
                <p>No projects found</p>
                <a href="add_project.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create Your First Project
                </a>
            </div>
        <?php else: ?>
            <div class="projects-grid">
                <?php foreach ($projects as $project): ?>
                    <div class="project-card">
                        <div class="project-header">
                            <div class="project-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="project-title">
                                <h3><?php echo clean($project['name']); ?></h3>
                                <div class="project-subtitle">
                                    <i class="fas fa-folder"></i> <?php echo clean($project['folder_name'] ?? 'No Folder'); ?>
                                </div>
                            </div>
                        </div>

                        <div class="project-description">
                            <?php echo $project['description'] ? clean($project['description']) : '<span style="color:#9ca3af; font-style:italic;">No description provided</span>'; ?>
                        </div>

                        <div class="project-stats">
                            <div class="stat-item">
                                <i class="fas fa-file-alt"></i> <?php echo $project['document_count']; ?> Docs
                            </div>
                            <div class="stat-item">
                                <i class="fas fa-user"></i> <?php echo clean($project['creator_name'] ?? 'N/A'); ?>
                            </div>
                        </div>

                        <div class="project-actions">
                            <a href="upload_document.php?project_id=<?php echo $project['id']; ?>" class="btn-action btn-primary-light" title="Add Document">
                                <i class="fas fa-plus"></i> Add
                            </a>
                            <a href="view_project.php?id=<?php echo $project['id']; ?>" class="btn-action">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn-action">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <?php if ($project['document_count'] == 0): ?>
                                <a href="delete_project.php?id=<?php echo $project['id']; ?>"
                                   class="btn-action btn-danger"
                                   onclick="return confirm('Delete this project?')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php else: ?>
                                <span class="btn-action disabled" title="Locked">
                                    <i class="fas fa-lock"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>