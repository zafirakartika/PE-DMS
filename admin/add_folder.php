<?php
require_once '../config/config.php';

if (!isLoggedIn() || !isSuperAdmin()) {
    redirect('../index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean($_POST['name']);
    $description = clean($_POST['description']);

    $stmt = $pdo->prepare("INSERT INTO folders (name, description, created_by) VALUES (?, ?, ?)");
    $stmt->execute([$name, $description, $_SESSION['user_id']]);

    redirect('folders.php?success=created');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Folder - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/daihatsu-logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/modern-style.css">
</head>
<body>
    <?php include '../includes/modern-admin-header.php'; ?>

    <div class="container">
        <div style="max-width: 600px;">
            <!-- Breadcrumb & Title -->
            <div style="margin-bottom: 1.5rem;">
                <div style="display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; font-size: 0.875rem; color: #86868b;">
                    <a href="folders.php" style="color: #0071e3; text-decoration: none; transition: color 0.3s;">Folders</a>
                    <span>/</span>
                    <span>Create Folder</span>
                </div>
                <h1 style="font-size: 1.5rem; font-weight: 700; color: #1d1d1f; margin: 0; letter-spacing: -0.5px;">Create Folder</h1>
                <p style="font-size: 0.875rem; color: #86868b; margin-top: 0.25rem; font-weight: 400;">Add a new folder to organize your documents</p>
            </div>

            <!-- Form Card -->
            <div style="background: #ffffff; border: 1px solid #e5e5e7; border-radius: 0.75rem; padding: 1.25rem; box-shadow: 0 1px 3px rgb(0 0 0 / 0.06);">
                <form action="" method="POST">
                    <div style="margin-bottom: 1rem;">
                        <label for="name" style="display: block; font-size: 0.9rem; font-weight: 600; color: #1d1d1f; margin-bottom: 0.35rem;">Folder Name <span style="color: #ef4444;">*</span></label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            placeholder="My Project Folder"
                            style="width: 100%; padding: 0.6rem 0.75rem; background: #ffffff; border: 1px solid #e5e5e7; border-radius: 0.5rem; font-size: 0.9rem; color: #1d1d1f; transition: all 0.3s; font-family: inherit;"
                            required 
                            autofocus
                        >
                        <span style="display: block; font-size: 0.8rem; color: #a1a1a6; margin-top: 0.3rem; font-weight: 400;">Choose a clear, descriptive name for your folder</span>
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label for="description" style="display: block; font-size: 0.9rem; font-weight: 600; color: #1d1d1f; margin-bottom: 0.35rem;">Description</label>
                        <textarea 
                            id="description" 
                            name="description" 
                            placeholder="Optional: Add a description to help team members understand what this folder contains"
                            style="width: 100%; padding: 0.6rem 0.75rem; background: #ffffff; border: 1px solid #e5e5e7; border-radius: 0.5rem; font-size: 0.9rem; color: #1d1d1f; transition: all 0.3s; font-family: inherit; min-height: 90px; resize: vertical;"
                        ></textarea>
                        <span style="display: block; font-size: 0.8rem; color: #a1a1a6; margin-top: 0.3rem; font-weight: 400;">This helps team members understand the folder's purpose</span>
                    </div>

                    <div style="display: flex; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid #e5e5e7; margin-top: 1.25rem;">
                        <button type="submit" style="flex: 1; padding: 0.6rem 1rem; background: #0071e3; color: white; border: none; border-radius: 0.6rem; font-size: 0.9rem; font-weight: 600; text-decoration: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.35rem; transition: all 0.3s;">
                            <i class="fas fa-plus"></i> Create Folder
                        </button>
                        <a href="folders.php" style="flex: 1; padding: 0.6rem 1rem; background: white; color: #86868b; border: 1px solid #e5e5e7; border-radius: 0.6rem; font-size: 0.9rem; font-weight: 600; text-decoration: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.35rem; transition: all 0.3s;">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
