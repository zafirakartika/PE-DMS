<?php
/**
 * DMS Database Setup & Verification Script
 * This script will:
 * 1. Create the database if it doesn't exist
 * 2. Create all required tables
 * 3. Insert default admin user and folders
 * 4. Verify everything is set up correctly
 */

$host = 'localhost';
$dbname = 'dms_db';
$username = 'root';
$password = '';

$setupLog = [];

// Connect to MySQL without selecting database
try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $setupLog[] = ['success' => true, 'message' => 'Connected to MySQL server'];
} catch (PDOException $e) {
    die("<h1 style='color:red;'>Error: Cannot connect to MySQL server</h1><p>" . $e->getMessage() . "</p>");
}

// Create database
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $setupLog[] = ['success' => true, 'message' => 'Database created/verified: dms_db'];
} catch (PDOException $e) {
    $setupLog[] = ['success' => false, 'message' => 'Error creating database: ' . $e->getMessage()];
}

// Select database
try {
    $pdo->exec("USE $dbname");
    $setupLog[] = ['success' => true, 'message' => 'Selected database: dms_db'];
} catch (PDOException $e) {
    $setupLog[] = ['success' => false, 'message' => 'Error selecting database: ' . $e->getMessage()];
}

// Create users table (Admin accounts only)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role VARCHAR(20) DEFAULT 'admin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            is_active BOOLEAN DEFAULT TRUE
        ) ENGINE=InnoDB
    ");
    $setupLog[] = ['success' => true, 'message' => 'Table created/verified: users (admin-only)'];
} catch (PDOException $e) {
    $setupLog[] = ['success' => false, 'message' => 'Error creating users table: ' . $e->getMessage()];
}

// Create folders table
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS folders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    ");
    $setupLog[] = ['success' => true, 'message' => 'Table created/verified: folders'];
} catch (PDOException $e) {
    $setupLog[] = ['success' => false, 'message' => 'Error creating folders table: ' . $e->getMessage()];
}

// Create documents table
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size BIGINT,
            folder_id INT,
            description TEXT,
            tags VARCHAR(500),
            pdf_text_content MEDIUMTEXT,
            uploaded_by INT,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            download_count INT DEFAULT 0,
            FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE SET NULL,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    ");
    $setupLog[] = ['success' => true, 'message' => 'Table created/verified: documents'];

    // Add fulltext index if not exists
    try {
        $pdo->exec("ALTER TABLE documents ADD FULLTEXT INDEX idx_search (title, description, tags, pdf_text_content)");
        $setupLog[] = ['success' => true, 'message' => 'Created fulltext index on documents'];
    } catch (PDOException $e) {
        // Index might already exist, that's okay
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            $setupLog[] = ['success' => false, 'message' => 'Note: ' . $e->getMessage()];
        }
    }
} catch (PDOException $e) {
    $setupLog[] = ['success' => false, 'message' => 'Error creating documents table: ' . $e->getMessage()];
}

// Create activity_logs table
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            action VARCHAR(50),
            document_id INT,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
    $setupLog[] = ['success' => true, 'message' => 'Table created/verified: activity_logs'];
} catch (PDOException $e) {
    $setupLog[] = ['success' => false, 'message' => 'Error creating activity_logs table: ' . $e->getMessage()];
}

// Check if admin user exists
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
$adminExists = $stmt->fetchColumn() > 0;

if (!$adminExists) {
    // Create admin user with password 'admin123'
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', $adminPassword, 'System Administrator', 'admin']);
        $setupLog[] = ['success' => true, 'message' => 'Created admin user (username: admin, password: admin123)'];
    } catch (PDOException $e) {
        $setupLog[] = ['success' => false, 'message' => 'Error creating admin user: ' . $e->getMessage()];
    }
} else {
    $setupLog[] = ['success' => true, 'message' => 'Admin user already exists'];
}

// Check if default folders exist
$stmt = $pdo->query("SELECT COUNT(*) FROM folders");
$folderCount = $stmt->fetchColumn();

if ($folderCount == 0) {
    // Create default folders
    try {
        $folders = [
            ['General', 'General documents'],
            ['Contracts', 'Contract documents'],
            ['Reports', 'Report documents'],
            ['Invoices', 'Invoice documents']
        ];

        $stmt = $pdo->prepare("INSERT INTO folders (name, description, created_by) VALUES (?, ?, 1)");
        foreach ($folders as $folder) {
            $stmt->execute($folder);
        }
        $setupLog[] = ['success' => true, 'message' => 'Created default folders'];
    } catch (PDOException $e) {
        $setupLog[] = ['success' => false, 'message' => 'Error creating folders: ' . $e->getMessage()];
    }
} else {
    $setupLog[] = ['success' => true, 'message' => "Found $folderCount existing folders"];
}

// Verify admin user can login
$stmt = $pdo->query("SELECT username, password, role, is_active FROM users WHERE username = 'admin'");
$adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

if ($adminUser) {
    $passwordVerify = password_verify('admin123', $adminUser['password']);
    $setupLog[] = [
        'success' => $passwordVerify,
        'message' => 'Admin password verification: ' . ($passwordVerify ? 'SUCCESS ✓' : 'FAILED ✗')
    ];
    $setupLog[] = [
        'success' => $adminUser['is_active'],
        'message' => 'Admin account status: ' . ($adminUser['is_active'] ? 'ACTIVE ✓' : 'INACTIVE ✗')
    ];
}

// Create uploads directory if it doesn't exist
$uploadsDir = __DIR__ . '/uploads';
if (!is_dir($uploadsDir)) {
    if (mkdir($uploadsDir, 0755, true)) {
        $setupLog[] = ['success' => true, 'message' => 'Created uploads directory'];
    } else {
        $setupLog[] = ['success' => false, 'message' => 'Failed to create uploads directory'];
    }
} else {
    $setupLog[] = ['success' => true, 'message' => 'Uploads directory exists'];
}

// Make uploads directory writable
if (is_writable($uploadsDir)) {
    $setupLog[] = ['success' => true, 'message' => 'Uploads directory is writable'];
} else {
    $setupLog[] = ['success' => false, 'message' => 'Uploads directory is NOT writable - please set permissions'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMS Setup</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #2563eb;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        .subtitle {
            color: #64748b;
            margin-bottom: 30px;
            font-size: 1rem;
        }
        .log-item {
            padding: 12px 16px;
            margin-bottom: 8px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }
        .log-item.success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .log-item.error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .icon {
            font-size: 18px;
            font-weight: bold;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #f1f5f9;
            border-radius: 8px;
        }
        .summary h2 {
            color: #1e293b;
            margin-bottom: 15px;
            font-size: 1.25rem;
        }
        .credential {
            background: white;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 10px;
            font-family: 'Courier New', monospace;
        }
        .credential strong {
            color: #2563eb;
        }
        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #1d4ed8;
        }
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            color: #92400e;
            padding: 12px 16px;
            border-radius: 6px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 DMS Setup Complete</h1>
        <p class="subtitle">Database and system initialization results</p>

        <div class="logs">
            <?php foreach ($setupLog as $log): ?>
                <div class="log-item <?php echo $log['success'] ? 'success' : 'error'; ?>">
                    <span class="icon"><?php echo $log['success'] ? '✓' : '✗'; ?></span>
                    <span><?php echo htmlspecialchars($log['message']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="summary">
            <h2>Login Credentials</h2>
            <div class="credential">
                <strong>Username:</strong> admin
            </div>
            <div class="credential">
                <strong>Password:</strong> admin123
            </div>
            <div class="credential">
                <strong>Role:</strong> Administrator
            </div>

            <a href="index.php" class="btn">Go to DMS Homepage →</a>
        </div>

        <div class="warning">
            <strong>⚠️ Security Notice:</strong> Please change the default admin password after your first login for security purposes.
        </div>
    </div>
</body>
</html>
