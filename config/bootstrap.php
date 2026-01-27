<?php
/**
 * Bootstrap file - loads all configuration and dependencies
 * Include this file at the top of every page
 */

// Load application configuration
require_once __DIR__ . '/app.php';

// Load database connection
$pdo = require_once __DIR__ . '/database.php';

// Load helper functions
require_once __DIR__ . '/../core/helpers/functions.php';

// Check session timeout
if (isLoggedIn()) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        // Session expired
        session_unset();
        session_destroy();
        redirect('../index.php');
    }
    $_SESSION['last_activity'] = time();
}
