<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../index.php');
}

$folderId = $_GET['id'] ?? null;
if (!$folderId) {
    redirect('folders.php');
}

// Get folder details
$stmt = $pdo->prepare("SELECT * FROM folders WHERE id = ?");
$stmt->execute([$folderId]);
$folder = $stmt->fetch();

if (!$folder) {
    redirect('folders.php?error=not_found');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $description = clean($_POST['description']);

    $updateStmt = $pdo->prepare("UPDATE folders SET name = ?, description = ? WHERE id = ?");
    $updateStmt->execute([$name, $description, $folderId]);

    redirect('folders.php?success=updated');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Folder - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/modern-style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f9fafb; font-size: 14px; }
        .container { max-width: 600px; margin: 0 auto; padding: 1rem; }
        .page-header { display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1rem; }
        .page-header h1 { font-size: 1.25rem; font-weight: 700; color: #111827; margin: 0; }
        .page-header i { font-size: 1.5rem; color: #0284c7; }

        .form-container { background: white; border-radius: 8px; padding: 1.1rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); }
        .form-group { margin-bottom: 0.9rem; }
        .form-group:last-of-type { margin-bottom: 1rem; }

        label { display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem; }
        input[type="text"], textarea { width: 100%; padding: 0.4rem 0.6rem; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 0.8rem; color: #111827; transition: all 0.2s; }
        input[type="text"]:focus, textarea:focus { outline: none; border-color: #0284c7; box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.1); }
        textarea { resize: vertical; min-height: 70px; font-family: inherit; }

        .form-actions { display: flex; gap: 0.6rem; padding-top: 0.5rem; border-top: 1px solid #f3f4f6; }
        .btn { padding: 0.4rem 1rem; border-radius: 6px; font-size: 0.8rem; font-weight: 500; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; transition: all 0.2s; }
        .btn-primary { background: #0284c7; color: white; }
        .btn-primary:hover { background: #0369a1; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(2, 132, 199, 0.3); }
        .btn-secondary { background: #f3f4f6; color: #374151; }
        .btn-secondary:hover { background: #e5e7eb; }
    </style>
</head>
<body>
    <?php include '../includes/modern-admin-header.php'; ?>

    <div class="container">
        <div class="page-header">
            <i class="fas fa-edit"></i>
            <h1>Edit Folder</h1>
        </div>

        <div class="form-container">
            <form action="" method="POST" class="folder-form">
                <div class="form-group">
                    <label for="name">Folder Name *</label>
                    <input type="text" id="name" name="name" value="<?php echo clean($folder['name']); ?>" placeholder="Enter folder name" required autofocus>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" placeholder="Enter folder description (optional)"><?php echo clean($folder['description']); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Folder
                    </button>
                    <a href="folders.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
