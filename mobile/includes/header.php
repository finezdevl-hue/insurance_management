<?php
/**
 * Dedicated Mobile Header Template
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';

$settings = getSystemSettings();
$current_user = null;

if (isLoggedIn()) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();
}

$pageTitle = $pageTitle ?? $settings['system_name'];
$role = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta name="theme-color" content="#10b981">
    <link rel="manifest" href="../../manifest.json">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('../../sw.js').catch(err => {});
            });
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Global CSS -->
    <link href="../../assets/css/style.css" rel="stylesheet">
    
    <style>
        :root {
            --mobile-bg: #f8fafc;
            --font-main: 'Outfit', sans-serif;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--mobile-bg);
            color: #0f172a;
            padding-bottom: 78px;
            -webkit-tap-highlight-color: transparent;
        }

        /* Mobile Header Navbar */
        #mobile-header-nav {
            height: 60px;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 1030;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }

        .mobile-brand-title {
            font-family: var(--font-main);
            font-weight: 700;
            font-size: 1.05rem;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .mobile-user-badge {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #eff6ff;
            color: #2563eb;
            font-weight: 700;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #bfdbfe;
        }

        /* Mobile Cards & Touch Elements */
        .mobile-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .mobile-card-header {
            padding: 1rem 1.15rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .mobile-card-body {
            padding: 1.15rem;
        }

        .form-control, .form-select, .btn {
            min-height: 48px;
            font-size: 16px; /* Prevents auto-zoom on iOS */
            border-radius: 12px;
        }

        .touch-action-btn {
            min-height: 44px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            padding: 0.5rem 1rem;
        }

        /* Drawer Slide-Out Menu */
        #mobile-drawer {
            position: fixed;
            top: 0;
            right: -280px;
            width: 280px;
            height: 100vh;
            background: #ffffff;
            z-index: 1060;
            box-shadow: -5px 0 25px rgba(0,0,0,0.15);
            transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }

        #mobile-drawer.open {
            right: 0;
        }

        #mobile-drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(2px);
            z-index: 1055;
            display: none;
        }

        #mobile-drawer-overlay.show {
            display: block;
        }
    </style>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

    <!-- Loading Animation Screen -->
    <div id="loader-wrapper">
        <div class="loader"></div>
    </div>

    <!-- Mobile Top Header Nav -->
    <nav id="mobile-header-nav">
        <a href="<?php echo $role === 'admin' ? '../admin/index.php' : '../agent/index.php'; ?>" class="text-decoration-none">
            <h1 class="mobile-brand-title">
                <i class="fa-solid fa-shield-halved text-primary fs-5"></i>
                <span>Vehicle Care</span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1" style="font-size: 0.65rem;">MOBILE</span>
            </h1>
        </a>

        <?php if (isLoggedIn()): ?>
        <div class="d-flex align-items-center gap-2">
            <span class="small font-weight-600 text-muted d-none d-xs-inline">
                <?php echo date('d M'); ?>
            </span>
            <div class="dropdown">
                <button class="mobile-user-badge border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <?php 
                        if ($current_user && !empty($current_user['shop_name']) && ($role === 'agent' || $role === 'shop')) {
                            echo substr(sanitize($current_user['shop_name']), 0, 2);
                        } else {
                            echo substr(sanitize($current_user['username']), 0, 2);
                        }
                    ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end border shadow mt-2" style="border-radius: 14px; min-width: 200px;">
                    <li class="px-3 py-2 border-bottom">
                        <p class="m-0 small text-muted">Logged in as</p>
                        <p class="m-0 font-weight-600 text-truncate text-dark">
                            <?php echo sanitize($current_user['username']); ?>
                        </p>
                        <span class="badge bg-light text-muted border text-uppercase" style="font-size: 0.68rem;"><?php echo sanitize($role); ?></span>
                    </li>
                    <?php if ($role === 'agent' || $role === 'shop'): ?>
                        <li>
                            <a class="dropdown-item py-2" href="../agent/profile.php">
                                <i class="fa-solid fa-store me-2 text-muted"></i> Outlet Profile
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if ($role === 'admin'): ?>
                        <li>
                            <a class="dropdown-item py-2" href="../admin/settings.php">
                                <i class="fa-solid fa-gears me-2 text-muted"></i> System Settings
                            </a>
                        </li>
                    <?php endif; ?>
                    <li>
                        <a class="dropdown-item py-2 text-primary" href="?view=desktop">
                            <i class="fa-solid fa-desktop me-2"></i> Desktop Version
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item py-2 text-danger" href="<?php echo $role === 'admin' ? '../../admin/logout.php' : '../../agent/logout.php'; ?>">
                            <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Log Out
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </nav>

    <!-- Mobile Main Container -->
    <div class="container-fluid p-3">
