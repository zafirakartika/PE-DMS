<?php
/**
 * MODERN LIST PAGE TEMPLATE
 * Use this as a template for folders.php, documents.php, projects.php, etc.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Folders - <?php echo SITE_NAME; ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/images/daihatsu-logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/modern-style.css">
    <style>
        .page-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .toolbar-search {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .toolbar-search input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #0f172a;
            transition: all 0.3s;
        }

        .toolbar-search input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .toolbar-search i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .toolbar-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }

        .btn:hover {
            border-color: #3b82f6;
            color: #3b82f6;
            background: #eff6ff;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover {
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
            transform: translateY(-2px);
        }

        .table-container {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }

        .table thead {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .table th {
            padding: 1.25rem;
            text-align: left;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: 0.3px;
            font-size: 0.875rem;
            text-transform: uppercase;
        }

        .table td {
            padding: 1.25rem;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
        }

        .table tbody tr {
            transition: all 0.3s;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .item-name {
            font-weight: 600;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .item-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #3b82f6;
            font-size: 1rem;
        }

        .table-meta {
            font-size: 0.875rem;
            color: #94a3b8;
        }

        .table-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 6px;
            color: #475569;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.875rem;
        }

        .action-btn:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .action-btn.danger:hover {
            background: #ef4444;
            border-color: #ef4444;
        }

        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
        }

        .empty-icon {
            font-size: 3rem;
            color: #cbd5e1;
            margin-bottom: 1rem;
        }

        .empty-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .empty-text {
            color: #94a3b8;
            margin-bottom: 1.5rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            border-left: 4px solid transparent;
            animation: slideIn 0.3s ease-out;
        }

        .alert-success {
            background: #ecfdf5;
            color: #065f46;
            border-left-color: #10b981;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .page-toolbar {
                flex-direction: column;
            }

            .toolbar-search {
                min-width: 100%;
            }

            .table {
                font-size: 0.85rem;
            }

            .table th,
            .table td {
                padding: 0.75rem;
            }

            .action-btn {
                width: 32px;
                height: 32px;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/modern-admin-header.php'; ?>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-content">
                <h1>Folders</h1>
                <p>Manage your document folders</p>
            </div>
        </div>

        <!-- Page Toolbar -->
        <div class="page-toolbar">
            <div class="toolbar-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search folders..." id="searchInput">
            </div>
            <div class="toolbar-actions">
                <button class="btn" id="refreshBtn">
                    <i class="fas fa-sync"></i> Refresh
                </button>
                <a href="add_folder.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Folder
                </a>
            </div>
        </div>

        <!-- Table -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th width="40%">Name</th>
                        <th width="30%">Created</th>
                        <th width="20%">Items</th>
                        <th width="10%" style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <!-- Table rows will be populated here -->
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-folder-open"></i>
                                </div>
                                <div class="empty-title">No folders yet</div>
                                <div class="empty-text">Create your first folder to get started</div>
                                <a href="add_folder.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create Folder
                                </a>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Example table data structure
        const exampleFolders = [
            {
                id: 1,
                name: 'Annual Reports',
                created: '2024-01-15',
                items: 12
            },
            {
                id: 2,
                name: 'Meeting Minutes',
                created: '2024-01-10',
                items: 8
            },
            {
                id: 3,
                name: 'Policies',
                created: '2024-01-05',
                items: 5
            }
        ];

        // Render table
        function renderTable(folders) {
            const tbody = document.getElementById('tableBody');
            
            if (folders.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="fas fa-folder-open"></i>
                                </div>
                                <div class="empty-title">No folders found</div>
                                <div class="empty-text">Try adjusting your search criteria</div>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = folders.map(folder => `
                <tr>
                    <td>
                        <div class="item-name">
                            <div class="item-icon">
                                <i class="fas fa-folder"></i>
                            </div>
                            <span>${folder.name}</span>
                        </div>
                    </td>
                    <td>
                        <div class="table-meta">${new Date(folder.created).toLocaleDateString()}</div>
                    </td>
                    <td>
                        <div class="table-meta">${folder.items} items</div>
                    </td>
                    <td style="text-align: center;">
                        <div class="table-actions">
                            <a href="edit_folder.php?id=${folder.id}" class="action-btn" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button class="action-btn danger" title="Delete" onclick="if(confirm('Delete this folder?')) window.location='delete_folder.php?id=${folder.id}'">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            const filtered = exampleFolders.filter(f => f.name.toLowerCase().includes(query));
            renderTable(filtered);
        });

        // Refresh button
        document.getElementById('refreshBtn').addEventListener('click', function() {
            renderTable(exampleFolders);
        });

        // Initial render
        renderTable(exampleFolders);
    </script>
</body>
</html>
