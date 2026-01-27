<style>
    .admin-header {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .admin-header-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 1rem;
        display: flex;
        align-items: center;
        min-height: 70px;
        gap: 1.5rem;
    }

    .admin-logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
        flex-shrink: 0;
    }

    .admin-logo-image {
        width: auto;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .admin-logo-image img {
        height: 100%;
        width: auto;
        max-width: 150px;
        object-fit: contain;
    }

    .admin-logo-text {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
    }

    .admin-logo-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: white;
        letter-spacing: -0.01em;
        line-height: 1.2;
    }

    .admin-logo-subtitle {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.85);
        font-weight: 400;
        letter-spacing: 0.02em;
        line-height: 1.2;
    }

    .admin-nav {
        display: flex;
        align-items: center;
        gap: 2rem;
        flex: 1;
    }

    .admin-nav-item {
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.25s;
        position: relative;
    }

    .admin-nav-item:hover,
    .admin-nav-item.active {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
    }

    .admin-nav-item.active::after {
        display: none;
    }

    .header-user {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-left: auto;
    }

    .user-profile {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 1rem;
        background: transparent;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .user-profile:hover {
        background: rgba(255, 255, 255, 0.15);
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .user-profile:hover .user-avatar {
        background: #0284c7;
    }

    .user-info {
        display: flex;
        flex-direction: column;
    }

    .user-name {
        font-size: 0.875rem;
        font-weight: 600;
        color: white;
    }

    .user-profile:hover .user-name {
        color: #0284c7;
    }

    .user-role {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.8);
    }

    .logout-btn {
        padding: 0.5rem 1rem;
        background: transparent;
        color: white;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.25s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
    }

    .logout-btn:hover {
        background: rgba(255, 255, 255, 0.15);
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
    }
</style>

<header class="admin-header">
    <div class="admin-header-container">
        <a href="dashboard.php" class="admin-logo">
            <div class="admin-logo-image">
                <img src="../assets/images/daihatsu-logo.png" alt="Document Management System" style="max-height: 100%; max-width: 100%; object-fit: contain;">
            </div>
            <div class="admin-logo-text">
                <div class="admin-logo-title">Document Management System</div>
                <div class="admin-logo-subtitle">Production Engineering Casting</div>
            </div>
        </a>
        
        <nav class="admin-nav">
            <a href="dashboard.php" class="admin-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="projects.php" class="admin-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'active' : ''; ?>">
                <i class="fas fa-project-diagram"></i> Projects
            </a>
            <a href="folders.php" class="admin-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'folders.php' ? 'active' : ''; ?>">
                <i class="fas fa-folder"></i> Folders
            </a>
            <a href="documents.php" class="admin-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'documents.php' ? 'active' : ''; ?>">
                <i class="fas fa-file"></i> Documents
            </a>
        </nav>
        
        <div class="header-user">
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'User', 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></div>
                    <div class="user-role">Administrator</div>
                </div>
            </div>
            <a href="../auth/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</header>
