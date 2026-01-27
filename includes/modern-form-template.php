<?php
/**
 * MODERN FORM PAGE TEMPLATE
 * Use this as a template for add_folder.php, add_project.php, edit_document.php, etc.
 */
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
    <style>
        .form-page {
            max-width: 700px;
            margin: 0 auto;
        }

        .form-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .form-header {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .form-header-breadcrumb {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: #94a3b8;
        }

        .form-header-breadcrumb a {
            color: #3b82f6;
            text-decoration: none;
            transition: color 0.3s;
        }

        .form-header-breadcrumb a:hover {
            color: #1e40af;
        }

        .form-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
        }

        .required {
            color: #ef4444;
            margin-left: 0.25rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #0f172a;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            background: #eff6ff;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #94a3b8;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-hint {
            display: block;
            font-size: 0.85rem;
            color: #94a3b8;
            margin-top: 0.5rem;
            font-weight: 400;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            padding-top: 1.5rem;
            border-top: 1px solid #f1f5f9;
            margin-top: 2rem;
        }

        .btn {
            flex: 1;
            padding: 0.875rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        .btn-secondary {
            background: white;
            color: #475569;
            border: 2px solid #e2e8f0;
        }

        .btn-secondary:hover {
            border-color: #3b82f6;
            color: #3b82f6;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            border-left: 4px solid transparent;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border-left-color: #10b981;
        }

        .alert-error {
            background: #fef2f2;
            color: #7f1d1d;
            border-left-color: #ef4444;
        }

        .alert-warning {
            background: #fffbeb;
            color: #78350f;
            border-left-color: #f59e0b;
        }
    </style>
</head>
<body>
    <?php include '../includes/modern-admin-header.php'; ?>

    <div class="container">
        <div class="form-page">
            <!-- Breadcrumb & Title -->
            <div class="form-header">
                <div class="form-header-breadcrumb">
                    <a href="folders.php">Folders</a>
                    <span>/</span>
                    <span>Create Folder</span>
                </div>
                <h1>Create Folder</h1>
            </div>

            <!-- Form Card -->
            <div class="form-card">
                <form action="" method="POST" class="form">
                    <div class="form-group">
                        <label for="name">Folder Name <span class="required">*</span></label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            placeholder="Enter folder name" 
                            required 
                            autofocus
                        >
                        <span class="form-hint">Give your folder a clear, descriptive name</span>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea 
                            id="description" 
                            name="description" 
                            placeholder="Enter folder description (optional)"
                        ></textarea>
                        <span class="form-hint">Add context about what this folder contains</span>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Create Folder
                        </button>
                        <a href="folders.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
