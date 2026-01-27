<?php
/**
 * System Verification Script
 * Use this to diagnose issues with the DMS installation
 */

$checks = [];
$allPassed = true;

// 1. Check PHP version
$phpVersion = phpversion();
$phpOk = version_compare($phpVersion, '7.4.0', '>=');
$checks[] = [
    'name' => 'PHP Version',
    'status' => $phpOk,
    'message' => "PHP $phpVersion" . ($phpOk ? ' (OK)' : ' (Requires 7.4+)'),
    'critical' => true
];
if (!$phpOk) $allPassed = false;

// 2. Check database connection
try {
    require_once 'config/config.php';
    $checks[] = [
        'name' => 'Database Connection',
        'status' => true,
        'message' => 'Connected to MySQL successfully',
        'critical' => true
    ];
} catch (Exception $e) {
    $checks[] = [
        'name' => 'Database Connection',
        'status' => false,
        'message' => 'Failed: ' . $e->getMessage(),
        'critical' => true
    ];
    $allPassed = false;
}

if (isset($pdo)) {
    // 3. Check if database exists
    try {
        $stmt = $pdo->query("SELECT DATABASE()");
        $dbName = $stmt->fetchColumn();
        $checks[] = [
            'name' => 'Database Selected',
            'status' => true,
            'message' => "Using database: $dbName",
            'critical' => true
        ];
    } catch (PDOException $e) {
        $checks[] = [
            'name' => 'Database Selected',
            'status' => false,
            'message' => 'No database selected',
            'critical' => true
        ];
        $allPassed = false;
    }

    // 4. Check required tables
    $requiredTables = ['users', 'documents', 'folders', 'activity_logs'];
    $existingTables = [];

    try {
        $stmt = $pdo->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $existingTables[] = $row[0];
        }

        foreach ($requiredTables as $table) {
            $exists = in_array($table, $existingTables);
            $checks[] = [
                'name' => "Table: $table",
                'status' => $exists,
                'message' => $exists ? 'Exists' : 'Missing',
                'critical' => true
            ];
            if (!$exists) $allPassed = false;
        }
    } catch (PDOException $e) {
        $checks[] = [
            'name' => 'Table Check',
            'status' => false,
            'message' => 'Error checking tables: ' . $e->getMessage(),
            'critical' => true
        ];
        $allPassed = false;
    }

    // 5. Check admin user
    try {
        $stmt = $pdo->query("SELECT * FROM users WHERE username = 'admin'");
        $admin = $stmt->fetch();

        if ($admin) {
            $checks[] = [
                'name' => 'Admin User',
                'status' => true,
                'message' => "Found (ID: {$admin['id']}, Active: " . ($admin['is_active'] ? 'Yes' : 'No') . ")",
                'critical' => true
            ];

            // Test password
            $passwordOk = password_verify('admin123', $admin['password']);
            $checks[] = [
                'name' => 'Admin Password',
                'status' => $passwordOk,
                'message' => $passwordOk ? 'Default password verified' : 'Password mismatch',
                'critical' => true
            ];
            if (!$passwordOk) $allPassed = false;

            // Check if active
            if (!$admin['is_active']) {
                $checks[] = [
                    'name' => 'Admin Account Status',
                    'status' => false,
                    'message' => 'Account is deactivated',
                    'critical' => true
                ];
                $allPassed = false;
            }
        } else {
            $checks[] = [
                'name' => 'Admin User',
                'status' => false,
                'message' => 'Admin user not found',
                'critical' => true
            ];
            $allPassed = false;
        }
    } catch (PDOException $e) {
        $checks[] = [
            'name' => 'Admin User',
            'status' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'critical' => true
        ];
        $allPassed = false;
    }

    // 6. Check folder count
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM folders");
        $folderCount = $stmt->fetchColumn();
        $checks[] = [
            'name' => 'Folders',
            'status' => $folderCount > 0,
            'message' => "$folderCount folder(s) found",
            'critical' => false
        ];
    } catch (PDOException $e) {
        $checks[] = [
            'name' => 'Folders',
            'status' => false,
            'message' => 'Error checking folders',
            'critical' => false
        ];
    }

    // 7. Check document count
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM documents");
        $docCount = $stmt->fetchColumn();
        $checks[] = [
            'name' => 'Documents',
            'status' => true,
            'message' => "$docCount document(s) in database",
            'critical' => false
        ];
    } catch (PDOException $e) {
        $checks[] = [
            'name' => 'Documents',
            'status' => false,
            'message' => 'Error checking documents',
            'critical' => false
        ];
    }
}

// 8. Check uploads directory
$uploadDir = __DIR__ . '/uploads';
$uploadExists = is_dir($uploadDir);
$uploadWritable = $uploadExists && is_writable($uploadDir);

$checks[] = [
    'name' => 'Uploads Directory',
    'status' => $uploadExists,
    'message' => $uploadExists ? 'Exists' : 'Missing',
    'critical' => true
];
if (!$uploadExists) $allPassed = false;

if ($uploadExists) {
    $checks[] = [
        'name' => 'Uploads Writable',
        'status' => $uploadWritable,
        'message' => $uploadWritable ? 'Yes' : 'No (Please set permissions)',
        'critical' => true
    ];
    if (!$uploadWritable) $allPassed = false;
}

// 9. Check required PHP extensions
$requiredExt = ['pdo', 'pdo_mysql', 'fileinfo', 'mbstring'];
foreach ($requiredExt as $ext) {
    $loaded = extension_loaded($ext);
    $checks[] = [
        'name' => "PHP Extension: $ext",
        'status' => $loaded,
        'message' => $loaded ? 'Loaded' : 'Not loaded',
        'critical' => true
    ];
    if (!$loaded) $allPassed = false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMS System Verification</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #2563eb;
            margin-bottom: 10px;
            font-size: 2rem;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .status-badge.success {
            background: #d1fae5;
            color: #065f46;
        }
        .status-badge.error {
            background: #fee2e2;
            color: #991b1b;
        }
        .checks {
            margin-top: 30px;
        }
        .check-item {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 4px solid;
        }
        .check-item.pass {
            background: #d1fae5;
            border-color: #10b981;
        }
        .check-item.fail {
            background: #fee2e2;
            border-color: #ef4444;
        }
        .check-item.warning {
            background: #fef3c7;
            border-color: #f59e0b;
        }
        .check-icon {
            width: 24px;
            height: 24px;
            margin-right: 12px;
            font-size: 18px;
            font-weight: bold;
        }
        .check-content {
            flex: 1;
        }
        .check-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .check-message {
            font-size: 13px;
            color: #64748b;
        }
        .actions {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
            display: flex;
            gap: 12px;
        }
        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #2563eb;
            color: white;
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 System Verification</h1>

        <?php if ($allPassed): ?>
            <span class="status-badge success">✓ All Checks Passed</span>
        <?php else: ?>
            <span class="status-badge error">✗ Issues Found</span>
        <?php endif; ?>

        <div class="checks">
            <?php foreach ($checks as $check): ?>
                <div class="check-item <?php echo $check['status'] ? 'pass' : ($check['critical'] ? 'fail' : 'warning'); ?>">
                    <div class="check-icon">
                        <?php echo $check['status'] ? '✓' : '✗'; ?>
                    </div>
                    <div class="check-content">
                        <div class="check-name"><?php echo htmlspecialchars($check['name']); ?></div>
                        <div class="check-message"><?php echo htmlspecialchars($check['message']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="actions">
            <?php if ($allPassed): ?>
                <a href="index.php" class="btn btn-primary">Go to Homepage →</a>
            <?php else: ?>
                <a href="setup.php" class="btn btn-primary">Run Setup Script</a>
                <a href="verify.php" class="btn btn-secondary">Refresh Verification</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
