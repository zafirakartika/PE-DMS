<?php
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validate input
    if (empty($username) || empty($password)) {
        redirect('../index.php?error=empty');
        exit;
    }

    try {
        // Fetch user from database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Check if user exists
        if (!$user) {
            redirect('../index.php?error=invalid');
            exit;
        }

        // Check if user is active
        if (!$user['is_active']) {
            redirect('../index.php?error=inactive');
            exit;
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            redirect('../index.php?error=invalid');
            exit;
        }

        // Login successful - Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['last_activity'] = time();

        // Update last login
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);

        // Log activity
        $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'login', 'Admin logged in', ?)");
        $logStmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);

        // Redirect to admin dashboard
        redirect('../admin/dashboard.php');
    } catch (PDOException $e) {
        // Database error
        error_log("Login error: " . $e->getMessage());
        redirect('../index.php?error=system');
    }
} else {
    redirect('../index.php');
}
?>
