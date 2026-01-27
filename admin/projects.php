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
        body { background: linear-gradient(135deg, #f0f4ff 0%, #f9fafb 100%); font-size: 14px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif; }
        .container { max-width: 1400px; margin: 0 auto; padding: 1.25rem; }
        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; gap: 1rem; }
        .page-header h1 { font-size: 1.5rem; font-weight: 800; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin: 0; letter-spacing: -0.5px; }

        .filter-container { background: white; padding: 1rem; border-radius: 12px; margin-bottom: 1.25rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); border: 1px solid rgba(2, 132, 199, 0.1); }
        .filter-form { display: flex; gap: 0.6rem; flex-wrap: wrap; }
        .filter-input { flex: 1; min-width: 250px; padding: 0.5rem 0.8rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.8rem; transition: all 0.3s; font-family: inherit; }
        .filter-input:focus { outline: none; border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1); }
        .filter-select { padding: 0.5rem 0.8rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.8rem; transition: all 0.3s; background: white; cursor: pointer; font-family: inherit; }
        .filter-select:focus { outline: none; border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1); }
        .btn { padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.8rem; font-weight: 600; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.3s; letter-spacing: 0.3px; }
        .btn-primary { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: white; box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(2, 132, 199, 0.4); }
        .btn-secondary { background: white; color: #374151; border: 2px solid #e5e7eb; }
        .btn-secondary:hover { border-color: #0284c7; color: #0284c7; }

        .alert { padding: 0.8rem 1rem; border-radius: 10px; margin-bottom: 1rem; font-size: 0.8rem; display: flex; align-items: center; gap: 0.6rem; font-weight: 500; }
        .alert-success { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #065f46; border-left: 4px solid #10b981; }
        .alert-error { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #991b1b; border-left: 4px solid #ef4444; }

        .projects-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
        .project-card { background: white; border-radius: 12px; padding: 1.1rem; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); transition: all 0.3s; border: 1px solid #f0f0f0; position: relative; overflow: hidden; }
        .project-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #0284c7, #06b6d4); }
        .project-card:hover { transform: translateY(-4px); box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1); }

        .project-header { display: flex; align-items: flex-start; gap: 0.8rem; margin-bottom: 0.8rem; }
        .project-icon { width: 44px; height: 44px; background: linear-gradient(135deg, #0284c7 0%, #06b6d4 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem; flex-shrink: 0; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3); }
        .project-title { flex: 1; }
        .project-title h3 { font-size: 1.05rem; font-weight: 700; color: #111827; margin: 0 0 0.25rem 0; letter-spacing: -0.3px; }
        .project-meta { font-size: 0.75rem; color: #9ca3af; font-weight: 500; }

        .project-description { font-size: 0.8rem; color: #6b7280; margin-bottom: 0.75rem; line-height: 1.5; }

        .project-stats { display: flex; gap: 1.2rem; padding: 0.75rem 0; border-top: 1px solid #f3f4f6; border-bottom: 1px solid #f3f4f6; margin-bottom: 0.75rem; }
        .stat { font-size: 0.75rem; color: #6b7280; font-weight: 500; }

        .project-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
        .btn-action { padding: 0.5rem 0.7rem; border-radius: 8px; font-size: 0.75rem; font-weight: 600; text-decoration: none; text-align: center; border: 1.5px solid #e5e7eb; background: white; color: #374151; transition: all 0.3s; cursor: pointer; letter-spacing: 0.2px; }
        .btn-action:hover { background: #f0f9ff; border-color: #0284c7; color: #0284c7; transform: translateY(-1px); }
        .btn-action.btn-danger { color: #dc2626; border-color: #fecaca; }
        .btn-action.btn-danger:hover { background: #fee2e2; border-color: #dc2626; }

        .empty-state { background: white; border-radius: 12px; padding: 2.5rem 1.5rem; text-align: center; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); border: 1px solid #f0f0f0; }
        .empty-state p { color: #6b7280; margin-bottom: 1rem; font-size: 0.9rem; font-weight: 500; }
    </style>
</head>
<body>
    <?php include '../includes/modern-admin-header.php'; ?>

    <div class="container">
        <div class="page-header">
            <div>
                <h1>Projects</h1>
            </div>
            <a href="add_project.php" class="btn btn-primary">
                New Project
            </a>
        </div>

        <div class="filter-container">
            <form action="" method="GET" class="filter-form">
                <input type="text" name="search" placeholder="Search projects by name..." 
                       value="<?php echo clean($searchQuery); ?>" class="filter-input">
                <select name="folder" class="filter-select">
                    <option value="">All Folders</option>
                    <?php foreach ($folders as $folder): ?>
                        <option value="<?php echo $folder['id']; ?>" <?php echo ($folderId == $folder['id']) ? 'selected' : ''; ?>>
                            <?php echo clean($folder['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">
                    Search
                </button>
                <?php if ($searchQuery || $folderId): ?>
                    <a href="projects.php" class="btn btn-secondary">
                        Clear
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                if ($_GET['success'] === 'created') echo 'Project created successfully';
                elseif ($_GET['success'] === 'updated') echo 'Project updated successfully';
                elseif ($_GET['success'] === 'deleted') echo 'Project deleted successfully';
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                if ($_GET['error'] === 'has_documents') echo 'Cannot delete project that contains documents';
                elseif ($_GET['error'] === 'delete_failed') echo 'Failed to delete project';
                ?>
            </div>
        <?php endif; ?>

        <?php if (empty($projects)): ?>
            <div class="empty-state">
                <p>No projects found</p>
                <a href="add_project.php" class="btn btn-primary">
                    Create Your First Project
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
                                <div class="project-meta">
                                    <?php echo clean($project['folder_name'] ?? 'No Folder'); ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($project['description']): ?>
                            <div class="project-description">
                                <?php echo clean($project['description']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="project-stats">
                            <div class="stat">
                                <?php echo $project['document_count']; ?> document<?php echo $project['document_count'] != 1 ? 's' : ''; ?>
                            </div>
                            <div class="stat">
                                By <?php echo clean($project['creator_name'] ?? 'N/A'); ?>
                            </div>
                        </div>

                        <div class="project-actions">
                            <a href="upload_document.php?project_id=<?php echo $project['id']; ?>" class="btn-action" style="background: #f0f9ff; border-color: #0284c7; color: #0284c7; font-weight: 600;" title="Add document to this project">
                                Add
                            </a>
                            <a href="view_project.php?id=<?php echo $project['id']; ?>" class="btn-action">
                                View
                            </a>
                            <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn-action">
                                Edit
                            </a>
                            <?php if ($project['document_count'] == 0): ?>
                                <a href="delete_project.php?id=<?php echo $project['id']; ?>"
                                   class="btn-action btn-danger"
                                   onclick="return confirm('Delete this project?')">
                                    Delete
                                </a>
                            <?php else: ?>
                                <span class="btn-action" title="Has documents" style="opacity: 0.5; cursor: not-allowed;">
                                    Locked
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
