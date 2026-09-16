<?php
/**
 * Global Header Template
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/functions.php';

// If user is not logged in, they shouldn't access files that include header.php directly without checks.
if (!isLoggedIn()) {
    // Note: Individual pages should call checkAccess() before header.php is loaded.
}

$settings = getSystemSettings();
$current_user = null;

if (isLoggedIn()) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch();
}

$pageTitle = $pageTitle ?? $settings['system_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize($pageTitle); ?></title>
    
    <!-- Meta tags for SEO -->
    <meta name="description" content="Manage vehicle details, pollution certificates, and insurance renewals with automatic alerts and bulk WhatsApp notifications.">
    <meta name="author" content="Antigravity System">
    <meta name="theme-color" content="#10b981">
    <link rel="manifest" href="../manifest.json">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('../sw.js').catch(err => {});
            });
        }
    </script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap5.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    
    <!-- jQuery (Must be in head or footer, DataTables requires it early or in footer) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

    <!-- Loading Animation Screen -->
    <div id="loader-wrapper">
        <div class="loader"></div>
    </div>

    <div id="wrapper">
        <!-- Sidebar Navigation -->
        <?php include_once __DIR__ . '/sidebar.php'; ?>

        <!-- Main Wrapper -->
        <div id="main-content">
            <!-- Header Navbar -->
            <nav id="header-nav">
                <div class="d-flex align-items-center gap-3">
                    <button class="sidebar-toggle-btn" id="sidebar-toggle" aria-label="Toggle Sidebar">
                        <i class="fa-solid fa-bars-staggered"></i>
                    </button>
                    <h5 class="m-0 font-weight-600 text-main page-heading-title text-truncate" style="max-width: 180px;">
                        <?php echo sanitize($pageHeading ?? 'Dashboard'); ?>
                    </h5>
                </div>
                
                <!-- Right Side widgets -->
                <div class="d-flex align-items-center gap-3">
                    <!-- Date counter / System Status badge -->
                    <span class="badge bg-light text-muted border d-none d-md-inline-block">
                        <i class="fa-regular fa-calendar-days me-1 text-primary"></i> 
                        <?php echo date('d M Y'); ?>
                    </span>
                    
                    <!-- Quick Expiry Alerts Counter (Dropdown / Tooltip) -->
                    <?php if (isLoggedIn()): ?>
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar text-uppercase">
                                <?php 
                                    if ($current_user && !empty($current_user['shop_name']) && $current_user['role'] === 'agent') {
                                        echo substr(sanitize($current_user['shop_name']), 0, 2);
                                    } else {
                                        echo substr(sanitize($current_user['username']), 0, 2);
                                    }
                                ?>
                            </div>
                            <span class="d-none d-lg-inline text-main font-weight-500">
                                <?php echo sanitize($current_user['shop_owner_name'] ?? $current_user['username']); ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border shadow-sm mt-2" style="border-radius: var(--radius-md);">
                            <li class="px-3 py-2 border-bottom">
                                <p class="m-0 small text-muted">Signed in as</p>
                                <p class="m-0 font-weight-600 text-truncate text-main" style="max-width: 180px;">
                                    <?php echo sanitize($current_user['email']); ?>
                                </p>
                            </li>
                            <?php if ($current_user['role'] === 'agent'): ?>
                                <li>
                                    <a class="dropdown-item py-2" href="../agent/profile.php">
                                        <i class="fa-solid fa-store me-2 text-muted"></i> Shop Details
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php if ($current_user['role'] === 'admin'): ?>
                                <li>
                                    <a class="dropdown-item py-2" href="../admin/settings.php">
                                        <i class="fa-solid fa-sliders me-2 text-muted"></i> System Settings
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item py-2 text-success" href="?view=mobile">
                                    <i class="fa-solid fa-mobile-screen-button me-2"></i> Mobile Version
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger" href="<?php echo $current_user['role'] === 'admin' ? '../admin/logout.php' : '../agent/logout.php'; ?>">
                                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Log Out
                                </a>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </nav>

            <!-- Page Inner Content -->
            <div class="container-fluid p-4">
