<header class="public-header">
    <div class="public-header-container">
        <a href="index.php" class="public-logo">
            <div class="public-logo-image">
                <img src="assets/images/daihatsu-logo.png" alt="Document Management System" onerror="this.parentElement.innerHTML='<i class=\'fas fa-file-archive\' style=\'font-size:32px;color:#0071e3;\'></i>'">
            </div>
            <div class="public-logo-text">
                <div class="public-logo-title">Document Management System</div>
                <div class="public-logo-subtitle">Production Engineering Casting</div>
            </div>
        </a>
        
        <nav class="public-nav">
            <a href="index.php" class="public-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="folder_view.php" class="public-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'folder_view.php' ? 'active' : ''; ?>">
                <i class="fas fa-folder"></i> Browse
            </a>
        </nav>
        
        <div class="header-user">
            <?php if (isLoggedIn()) : ?>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'User', 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></div>
                        <div class="user-role">Member</div>
                    </div>
                </div>
                <a href="auth/logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            <?php else : ?>
                <a href="auth/login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>
