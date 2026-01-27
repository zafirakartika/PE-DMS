<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../index.php');
}

$projectId = $_GET['id'] ?? null;

if (!$projectId) {
    redirect('projects.php');
}

// Get project details
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$projectId]);
$project = $stmt->fetch();

if (!$project) {
    redirect('projects.php?error=not_found');
}

// Get all folders for dropdown
$folders = $pdo->query("SELECT * FROM folders ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $description = clean($_POST['description']);
    $folder_id = $_POST['folder_id'] ?: null;

    $stmt = $pdo->prepare("UPDATE projects SET name = ?, description = ?, folder_id = ? WHERE id = ?");
    $stmt->execute([$name, $description, $folder_id, $projectId]);

    redirect('projects.php?success=updated');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/daihatsu-logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/modern-style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: linear-gradient(135deg, #f0f4ff 0%, #f9fafb 100%); font-size: 14px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 1.25rem; }

        .form-container { background: white; border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08); border: 1px solid #f0f0f0; }
        .form-group { margin-bottom: 1rem; }
        .form-group:last-of-type { margin-bottom: 1.25rem; }

        label { display: block; font-size: 0.9rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; letter-spacing: 0.2px; }
        input[type="text"], textarea, select { width: 100%; padding: 0.6rem 0.8rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem; color: #111827; transition: all 0.3s; font-family: inherit; }
        input[type="text"]:focus, textarea:focus, select:focus { outline: none; border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1); background: #f0f9ff; }
        textarea { resize: vertical; min-height: 80px; }

        .form-actions { display: flex; gap: 0.75rem; padding-top: 0.8rem; border-top: 1px solid #f3f4f6; }
        .btn { padding: 0.6rem 1.25rem; border-radius: 8px; font-size: 0.85rem; font-weight: 600; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.3s; letter-spacing: 0.3px; }
        .btn-primary { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: white; box-shadow: 0 2px 8px rgba(2, 132, 199, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(2, 132, 199, 0.4); }
        .btn-secondary { background: white; color: #6b7280; border: 2px solid #e5e7eb; font-weight: 600; }
        .btn-secondary:hover { border-color: #0284c7; color: #0284c7; }
    </style>
</head>
<body>
    <?php include '../includes/modern-admin-header.php'; ?>

    <div class="container">
        <div style="margin-bottom: 1.5rem;">
            <a href="projects.php" style="color: #6b7280; text-decoration: none; font-size: 0.85rem; font-weight: 600; display: inline-block; margin-bottom: 0.75rem; letter-spacing: 0.2px;">← Projects</a>
            <h1 style="margin: 0; font-size: 1.4rem; color: #111827; font-weight: 800; letter-spacing: -0.5px;">Edit Project</h1>
        </div>

        <div class="form-container">
            <form action="" method="POST" class="project-form">
                <div class="form-group">
                    <label for="name">Project Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($project['name']); ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="folder_id">Folder</label>
                    <select id="folder_id" name="folder_id">
                        <option value="">-- No Folder --</option>
                        <?php foreach ($folders as $folder): ?>
                            <option value="<?php echo $folder['id']; ?>" <?php echo ($project['folder_id'] == $folder['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($folder['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($project['description']); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Update Project
                    </button>
                    <a href="projects.php" class="btn btn-secondary">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
