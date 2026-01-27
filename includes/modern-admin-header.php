<style>
    /* Header Container */
    .admin-header {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); /* Daihatsu Blue Gradient */
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        position: sticky;
        top: 0;
        z-index: 1000;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .admin-header-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 1.25rem;
        display: flex;
        align-items: center;
        height: 64px; /* Compact height */
        gap: 2rem;
    }

    /* Logo Section */
    .admin-logo {
        display: flex;
        align-items: center;
        gap: 1rem;
        text-decoration: none;
        flex-shrink: 0;
    }

    .admin-logo-image {
        height: 42px;
        width: auto;
        display: flex;
        align-items: center;
        background: transparent; /* No box background */
        /* Removed the white filter so original colors show */
    }

    .admin-logo-image img {
        height: 100%;
        width: auto;
        object-fit: contain;
        /* Optional: Add a subtle drop shadow if the logo has dark text, to make it pop on blue */
        filter: drop-shadow(0 1px 2px rgba(255,255,255,0.2)); 
    }

    .admin-logo-text {
        display: flex;
        flex-direction: column;
        justify-content: center;
        border-left: 1px solid rgba(255,255,255,0.3);
        padding-left: 1rem;
        height: 38px;
    }

    .admin-logo-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: white;
        line-height: 1.2;
        letter-spacing: -0.01em;
    }

    .admin-logo-subtitle {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 400;
        letter-spacing: 0.02em;
    }

    /* Navigation */
    .admin-nav {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex: 1;
        margin-left: 1rem;
    }

    .admin-nav-item {
        color: rgba(255,255,255,0.85);
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s ease;
    }

    .admin-nav-item:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .admin-nav-item.active {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        font-weight: 600;
    }

    /* User Section */
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
    }

    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.8rem;
        border: 1px solid rgba(255,255,255,0.3);
    }

    .user-name {
        font-size: 0.85rem;
        font-weight: 500;
        color: white;
        display: none; 
    }
    @media (min-width: 1024px) { .user-name { display: block; } }

    .logout-btn {
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255,255,255,0.2);
        color: white;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    .logout-btn:hover {
        background: #ef4444; /* Red */
        border-color: #ef4444;
        transform: translateY(-1px);
    }

    /* --- MODAL STYLES --- */
    .custom-modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(15, 23, 42, 0.65); /* Dark blur backdrop */
        backdrop-filter: blur(4px);
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.2s ease-out;
    }

    .custom-modal.active { display: flex; }

    .custom-modal-content {
        background: white;
        border-radius: 16px;
        width: 90%;
        max-width: 380px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        overflow: hidden;
        animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        text-align: center;
        padding: 2rem 1.5rem;
    }

    .modal-icon-wrapper {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: #fee2e2;
        color: #dc2626;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        margin: 0 auto 1.25rem auto;
    }

    .modal-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 0.5rem;
    }

    .modal-text {
        color: #6b7280;
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
        line-height: 1.5;
    }

    .modal-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }

    .modal-btn {
        padding: 0.75rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
    }

    .btn-cancel {
        background: white;
        border: 1px solid #d1d5db;
        color: #374151;
    }
    .btn-cancel:hover { background: #f3f4f6; }

    .btn-confirm {
        background: #dc2626;
        color: white;
        box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.2);
    }
    .btn-confirm:hover { background: #b91c1c; box-shadow: 0 6px 10px -2px rgba(220, 38, 38, 0.3); }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

<header class="admin-header">
    <div class="admin-header-container">
        <a href="dashboard.php" class="admin-logo">
            <div class="admin-logo-image">
                <img src="../assets/images/daihatsu-logo.png" alt="Daihatsu" onerror="this.style.display='none'">
            </div>
            <div class="admin-logo-text">
                <div class="admin-logo-title">Document Management System</div>
                <div class="admin-logo-subtitle">Production Engineering Casting</div>
            </div>
        </a>
        
        <nav class="admin-nav">
            <a href="dashboard.php" class="admin-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>
            <a href="projects.php" class="admin-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i> Projects
            </a>
            <a href="folders.php" class="admin-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'folders.php' ? 'active' : ''; ?>">
                <i class="fas fa-folder-open"></i> Folders
            </a>
            <a href="documents.php" class="admin-nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'documents.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i> Documents
            </a>
        </nav>
        
        <div class="header-user">
            <div class="user-profile" title="<?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></div>
            </div>
            <button onclick="openLogoutModal()" class="logout-btn" title="Sign Out">
                <i class="fas fa-power-off"></i>
            </button>
        </div>
    </div>
</header>

<div id="logoutModal" class="custom-modal">
    <div class="custom-modal-content">
        <div class="modal-icon-wrapper">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        <h3 class="modal-title">Sign Out?</h3>
        <p class="modal-text">Are you sure you want to end your session?</p>
        <div class="modal-actions">
            <button onclick="closeLogoutModal()" class="modal-btn btn-cancel">Cancel</button>
            <a href="../auth/logout.php" class="modal-btn btn-confirm">Sign Out</a>
        </div>
    </div>
</div>

<script>
    const logoutModal = document.getElementById('logoutModal');

    function openLogoutModal() {
        logoutModal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Stop scrolling
    }

    function closeLogoutModal() {
        logoutModal.classList.remove('active');
        document.body.style.overflow = 'auto'; // Resume scrolling
    }

    // Close on click outside
    window.onclick = function(e) {
        if (e.target == logoutModal) closeLogoutModal();
    }
    
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === "Escape" && logoutModal.classList.contains('active')) {
            closeLogoutModal();
        }
    });
</script>