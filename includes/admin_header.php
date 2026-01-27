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
        min-height: 56px;
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
        width: 40px;
        height: 40px;
        background: white;
        border-radius: 6px;
        padding: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        flex-shrink: 0;
    }

    .admin-logo-image img {
        width: 100%;
        height: 100%;
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
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
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
        border: 1px solid rgba(255, 255, 255, 0.2);
        position: relative;
    }

    .admin-nav-item:hover {
        background: white;
        color: #0284c7;
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
        background: rgba(255, 255, 255, 0.15);
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .user-profile:hover {
        background: white;
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
        background: rgba(255, 255, 255, 0.15);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.2);
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
        background: white;
        color: #0284c7;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
    }

    @media (max-width: 768px) {
        .admin-header-container {
            flex-wrap: wrap;
            min-height: auto;
            padding: 0.75rem;
        }

        .admin-nav {
            flex-basis: 100%;
            gap: 1rem;
            order: 3;
        }

        .user-info {
            display: none;
        }
    }
</style>

<header class="admin-header">
    <div class="admin-header-container">
        <a href="dashboard.php" class="admin-logo">
            <div class="admin-logo-image">
                <img src="assets/images/daihatsu-logo.png" alt="Document Management System" style="object-fit: contain;" onerror="this.parentElement.innerHTML='<i class=\"fas fa-file-archive\" style=\"color:#0071e3;font-size:24px;\"></i>'">
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
            <a href="../auth/logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?');">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</header>
