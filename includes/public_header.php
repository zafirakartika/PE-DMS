<style>
    .public-header {
        background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        position: static;
        z-index: 100;
    }

    .public-header-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 1rem;
        display: flex;
        align-items: center;
        min-height: 56px;
        gap: 1.5rem;
    }

    .public-logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
    }

    .public-logo-image {
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

    .public-logo-image img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .public-logo-text {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
    }

    .public-logo-main {
        font-size: 1.1rem;
        font-weight: 700;
        color: white;
        letter-spacing: -0.01em;
        line-height: 1.2;
    }

    .public-logo-subtitle {
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.85);
        font-weight: 400;
        letter-spacing: 0.02em;
        line-height: 1.2;
    }

    .public-header-actions {
        margin-left: auto;
        display: flex;
        gap: 0.75rem;
        align-items: center;
    }

    .public-btn {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        color: white;
        padding: 0.4rem 0.875rem;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.25s;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .public-btn:hover {
        background: white;
        color: #0284c7;
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
    }

    .public-btn.btn-admin {
        background: rgba(255, 255, 255, 0.2);
    }

    @media (max-width: 768px) {
        .public-header-container {
            flex-wrap: wrap;
        }

        .public-logo-main {
            font-size: 1rem;
        }

        .public-logo-subtitle {
            font-size: 0.65rem;
        }

        .public-header-actions {
            width: 100%;
            order: 3;
            justify-content: flex-start;
        }
    }
</style>

<header class="public-header">
    <div class="public-header-container">
        <a href="<?php echo isset($headerBackUrl) ? $headerBackUrl : 'index.php'; ?>" class="public-logo">
            <div class="public-logo-image">
                <img src="<?php echo isset($assetsPath) ? $assetsPath : 'assets'; ?>/images/daihatsu-logo.png"
                     alt="<?php echo COMPANY_NAME; ?>"
                     onerror="this.parentElement.innerHTML='<i class=\'fas fa-industry\' style=\'font-size:32px;color:#0284c7;\'></i>'">
            </div>
            <div class="public-logo-text">
                <div class="public-logo-main">
                    Document Management System
                </div>
                <div class="public-logo-subtitle">
                    Production Engineering Casting
                </div>
            </div>
        </a>
        <div class="public-header-actions">
            <?php if (isset($showBackButton) && $showBackButton): ?>
                <a href="<?php echo $headerBackUrl ?? 'index.php'; ?>" class="public-btn">
                    <i class="fas fa-arrow-left"></i>
                    <span><?php echo $backButtonText ?? 'Back'; ?></span>
                </a>
            <?php endif; ?>
            <?php if (!isset($hideAdminButton) || !$hideAdminButton): ?>
                <a href="#" onclick="openLoginModal(); return false;" class="public-btn btn-admin">
                    <!--<i class="fas fa-user-shield"></i>-->
                    Login as Admin
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>
