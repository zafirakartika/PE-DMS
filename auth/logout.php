<?php
require_once '../config/config.php';

if (isLoggedIn()) {
    // Log activity
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, 'logout', 'User logged out', ?)");
    $stmt->execute([$_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
}

// Destroy session
session_destroy();
redirect('../index.php?logout=1');
?>
