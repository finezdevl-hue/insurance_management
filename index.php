<?php
/**
 * Single Unified Mobile-Friendly Login Portal
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/includes/functions.php';

// Auto-redirect mobile devices
handleMobileAutoRedirect();

// If already logged in, redirect to respective dashboard
if (isLoggedIn()) {
    $isMobile = isMobileDevice();
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin') {
        redirect($isMobile ? 'mobile/admin/index.php' : 'admin/index.php');
    } elseif ($role === 'agent' || $role === 'shop') {
        redirect($isMobile ? 'mobile/agent/index.php' : 'agent/index.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';            
    } else {
        $db = getDBConnection();
        try {
            // Find user in users database
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Check account status
                if ($user['status'] === 'suspended') {
                    $error = 'Your account has been suspended. Please contact Super Admin.';
                } elseif ($user['role'] === 'shop') {
                    // Check shop's own validity / expiry date
                    if (!empty($user['expiry_date']) && date('Y-m-d') > $user['expiry_date']) {
                        $error = 'Your testing center license expired on ' . date('d-M-Y', strtotime($user['expiry_date'])) . '. Please contact Super Admin to renew your license.';
                    } else {
                        // Verify parent agent status
                        $stmtParent = $db->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
                        $stmtParent->execute([$user['parent_agent_id']]);
                        $parent = $stmtParent->fetch();
                        
                        if (!$parent || $parent['status'] === 'suspended') {
                            $error = 'Your parent agency account is suspended. Please contact Super Admin.';
                        } else {
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['role'] = $user['role'];
                            $_SESSION['parent_agent_id'] = $user['parent_agent_id'];
                            $_SESSION['email'] = $user['email'];
                            
                            $isMobile = isMobileDevice();
                            logActivity('Center Login', "Testing Center logged in: {$user['shop_name']}");
                            $_SESSION['alert_success'] = 'Welcome back! Testing Center: ' . ($user['shop_name'] ?? $user['username']);
                            redirect($isMobile ? 'mobile/agent/index.php' : 'agent/index.php');
                        }
                    }
                } elseif ($user['role'] === 'agent' && !empty($user['expiry_date']) && date('Y-m-d') > $user['expiry_date']) {
                    $error = 'Your agency license expired on ' . date('d-M-Y', strtotime($user['expiry_date'])) . '. Please contact Super Admin to renew license.';
                } else {
                    // Start session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    
                    $isMobile = isMobileDevice();
                    if ($user['role'] === 'admin') {
                        logActivity('Admin Login', 'Super Admin logged in successfully.');
                        $_SESSION['alert_success'] = 'Welcome back, Super Admin!';
                        redirect($isMobile ? 'mobile/admin/index.php' : 'admin/index.php');
                    } else {
                        logActivity('Agent Login', "Agent logged in: {$user['shop_name']}");
                        $_SESSION['alert_success'] = 'Welcome back! Agency: ' . ($user['shop_name'] ?? $user['username']);
                        redirect($isMobile ? 'mobile/agent/index.php' : 'agent/index.php');
                    }
                }
            } else {
                $error = 'Invalid username or password. Please try again.';
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $stmtLog = $db->prepare("INSERT INTO activity_logs (action, details, ip_address) VALUES (?, ?, ?)");
                $stmtLog->execute(['Unified Login Failed', "Failed login attempt for username: $username", $ipAddress]);
            }
        } catch (PDOException $e) {
            $error = 'System error: ' . $e->getMessage();
        }
    }
}

// Fetch system name and contact from settings
$systemName = 'Vehicle Details & Insurance Renewal Management System';
$contactPhone = '';
$contactEmail = '';
try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT system_name, contact_phone, contact_email FROM settings LIMIT 1");
    if ($row = $stmt->fetch()) {
        $systemName = $row['system_name'] ?: $systemName;
        $contactPhone = $row['contact_phone'] ?? '';
        $contactEmail = $row['contact_email'] ?? '';
    }
} catch (PDOException $e) {
    // Fallback
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Sign In | <?php echo sanitize($systemName); ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --primary: #059669;
            --primary-dark: #047857;
            --primary-light: #10b981;
            --primary-glow: rgba(16, 185, 129, 0.35);
            --primary-gradient: linear-gradient(135deg, #059669 0%, #10b981 100%);
            --font-display: 'Outfit', sans-serif;
            --font-body: 'Plus Jakarta Sans', sans-serif;
            --glass-bg: rgba(15, 23, 42, 0.55);
            --glass-border: rgba(255, 255, 255, 0.12);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            font-family: var(--font-body);
            background-color: #f8fafc;
            color: #0f172a;
            overflow: hidden; /* Zero scrollbar on desktop */
        }

        /* Full Screen Split Container */
        .login-split-container {
            display: flex;
            height: 100vh;
            max-height: 100vh;
            width: 100vw;
            overflow: hidden;
            background-color: #f8fafc;
        }

        /* ---------------- LEFT HERO COLUMN ---------------- */
        .login-hero-side {
            flex: 1.15;
            position: relative;
            background: #090e1a url('assets/images/login_hero.jpg') center center / cover no-repeat;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: clamp(1.5rem, 3.5vh, 2.75rem) clamp(2rem, 3.5vw, 3.5rem);
            color: #ffffff;
            height: 100vh;
            max-height: 100vh;
            overflow: hidden;
        }

        .login-hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(145deg, 
                rgba(9, 14, 26, 0.94) 0%, 
                rgba(4, 120, 87, 0.72) 48%, 
                rgba(9, 14, 26, 0.96) 100%
            );
            backdrop-filter: blur(3px);
            z-index: 1;
        }

        .hero-content-wrapper {
            position: relative;
            z-index: 2;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Brand Badge */
        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(16px);
            padding: 0.5rem 1.15rem;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
        }

        .brand-icon {
            width: 34px;
            height: 34px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #fff;
            box-shadow: 0 0 14px var(--primary-glow);
        }

        .brand-name {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.95rem;
            color: #ffffff;
            letter-spacing: -0.01em;
            white-space: nowrap;
        }

        /* Hero Text */
        .hero-center-section {
            margin: auto 0;
            padding: 0.5rem 0;
        }

        .hero-main-title {
            font-family: var(--font-display);
            font-size: clamp(1.85rem, 2.7vw, 2.65rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.03em;
            margin-bottom: 0.85rem;
            color: #ffffff;
            text-shadow: 0 2px 10px rgba(0,0,0,0.4);
        }

        .hero-main-title span {
            background: linear-gradient(135deg, #34d399 0%, #a7f3d0 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-subtitle {
            font-size: clamp(0.88rem, 1.1vw, 0.98rem);
            color: rgba(255, 255, 255, 0.82);
            max-width: 540px;
            line-height: 1.55;
            margin-bottom: 1.4rem;
        }

        /* Modern Feature Pill Badges */
        .hero-features-list {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            max-width: 520px;
            margin-bottom: 1.25rem;
        }

        .feature-pill-item {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(12px);
            padding: 0.65rem 1rem;
            border-radius: 12px;
            transition: all 0.25s ease;
        }

        .feature-pill-item:hover {
            transform: translateX(4px);
            background: rgba(15, 23, 42, 0.75);
            border-color: rgba(52, 211, 153, 0.4);
        }

        .feature-pill-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            flex-shrink: 0;
        }

        .feature-pill-content h6 {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.88rem;
            color: #ffffff;
            margin: 0;
            line-height: 1.2;
        }

        .feature-pill-content p {
            font-size: 0.74rem;
            color: rgba(255, 255, 255, 0.68);
            margin: 0;
            line-height: 1.25;
        }

        /* Hero Footer Stats */
        .hero-footer-stats {
            display: flex;
            align-items: center;
            gap: 2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .hero-stat-item h5 {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 1.25rem;
            color: #34d399;
            margin: 0;
            line-height: 1.1;
        }

        .hero-stat-item p {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.65);
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* ---------------- RIGHT FORM COLUMN ---------------- */
        .login-form-side {
            flex: 1;
            background-color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1rem, 3vh, 2rem) clamp(1rem, 2vw, 2.5rem);
            height: 100vh;
            max-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        .login-form-box {
            width: 100%;
            max-width: 410px;
            background: #ffffff;
            border-radius: 20px;
            padding: clamp(1.4rem, 2.7vh, 2rem) clamp(1.4rem, 2.2vw, 2.1rem);
            box-shadow: 0 20px 45px -15px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(226, 232, 240, 0.9);
            transition: transform 0.3s ease;
        }

        .login-box-header {
            margin-bottom: 1.15rem;
            text-align: left;
        }

        .login-box-title {
            font-family: var(--font-display);
            font-weight: 800;
            font-size: 1.55rem;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin-bottom: 0.2rem;
        }

        .login-box-subtitle {
            font-size: 0.82rem;
            color: #64748b;
            margin: 0;
        }

        .role-chips {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            margin-top: 0.65rem;
            flex-wrap: wrap;
        }

        .role-chip {
            font-size: 0.68rem;
            font-weight: 600;
            padding: 0.2rem 0.55rem;
            border-radius: 50px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .role-chip i {
            color: #10b981;
            font-size: 0.7rem;
        }

        /* Inputs */
        .form-group-wrap {
            margin-bottom: 0.9rem;
        }

        .form-label {
            font-family: var(--font-display);
            font-weight: 600;
            font-size: 0.8rem;
            color: #1e293b;
            margin-bottom: 0.3rem;
            display: block;
        }

        .input-group-modern {
            position: relative;
            display: flex;
            align-items: center;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 11px;
            transition: all 0.2s ease;
            height: 44px;
        }

        .input-group-modern:focus-within {
            border-color: #10b981;
            background-color: #ffffff;
            box-shadow: 0 0 0 3.5px rgba(16, 185, 129, 0.12);
        }

        .input-icon {
            padding: 0 0.85rem;
            color: #94a3b8;
            font-size: 0.9rem;
            transition: color 0.2s ease;
            display: flex;
            align-items: center;
        }

        .input-group-modern:focus-within .input-icon {
            color: #10b981;
        }

        .input-modern {
            flex: 1;
            border: none;
            background: transparent;
            padding: 0.6rem 0.35rem;
            font-size: 0.88rem;
            color: #0f172a;
            outline: none;
            font-family: var(--font-body);
        }

        .input-modern::placeholder {
            color: #94a3b8;
            font-size: 0.82rem;
        }

        .btn-toggle-password {
            background: transparent;
            border: none;
            padding: 0 0.85rem;
            color: #94a3b8;
            cursor: pointer;
            font-size: 0.9rem;
            transition: color 0.2s ease;
            display: flex;
            align-items: center;
        }

        .btn-toggle-password:hover {
            color: #10b981;
        }

        /* Sign In Button */
        .btn-signin {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 0.95rem;
            width: 100%;
            height: 46px;
            border-radius: 11px;
            background: var(--primary-gradient);
            border: none;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.45);
            transition: all 0.2s ease;
            cursor: pointer;
            margin-top: 1rem;
        }

        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px -4px rgba(16, 185, 129, 0.55);
            color: #ffffff;
        }

        .btn-signin:active {
            transform: scale(0.98);
        }

        /* Footer Help */
        .login-footer-help {
            text-align: center;
            margin-top: 1rem;
            padding-top: 0.75rem;
            border-top: 1px solid #f1f5f9;
            font-size: 0.74rem;
            color: #64748b;
        }

        .login-footer-help a {
            color: #059669;
            text-decoration: none;
            font-weight: 600;
        }

        .login-footer-help a:hover {
            text-decoration: underline;
        }

        .login-footer-copy {
            color: #94a3b8;
            font-size: 0.68rem;
            margin-top: 0.3rem;
        }

        /* Mobile & Tablet Styles */
        @media (max-width: 991.98px) {
            html, body {
                height: auto;
                min-height: 100vh;
                overflow-y: auto;
            }

            .login-split-container {
                flex-direction: column;
                height: auto;
                min-height: 100vh;
                max-height: none;
                overflow-y: auto;
            }

            .login-hero-side {
                flex: none;
                height: auto;
                max-height: none;
                padding: 2rem 1.25rem;
            }

            .hero-main-title {
                font-size: 1.7rem;
            }

            .hero-subtitle, .hero-features-list, .hero-footer-stats {
                display: none;
            }

            .login-form-side {
                flex: 1;
                height: auto;
                max-height: none;
                padding: 1.75rem 1rem 2.5rem;
            }

            .login-form-box {
                padding: 1.75rem 1.25rem;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            }
        }
    </style>
</head>
<body>

<div class="login-split-container">
    
    <!-- LEFT HERO COLUMN (STRICTLY NON-SCROLLABLE) -->
    <div class="login-hero-side">
        <div class="login-hero-overlay"></div>
        
        <div class="hero-content-wrapper">
            <!-- Brand Badge -->
            <div>
                <div class="brand-badge">
                    <div class="brand-icon">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <span class="brand-name"><?php echo sanitize($systemName); ?></span>
                </div>
            </div>

            <!-- Hero Main Info & Feature Pills -->
            <div class="hero-center-section">
                <h1 class="hero-main-title">
                    Smart Vehicle & <span>Insurance Renewal</span> Management
                </h1>
                <p class="hero-subtitle">
                    Automated customer WhatsApp reminders, multi-shop agency sync, PUC logs, and instant online subscription renewals.
                </p>

                <!-- Clean Compact Feature Pills -->
                <div class="hero-features-list">
                    <div class="feature-pill-item">
                        <div class="feature-pill-icon">
                            <i class="fa-solid fa-bell"></i>
                        </div>
                        <div class="feature-pill-content">
                            <h6>Automated WhatsApp Alerts</h6>
                            <p>Direct customer notifications before policy & PUC expiry</p>
                        </div>
                    </div>

                    <div class="feature-pill-item">
                        <div class="feature-pill-icon">
                            <i class="fa-solid fa-car-side"></i>
                        </div>
                        <div class="feature-pill-content">
                            <h6>Vehicle & Policy Database</h6>
                            <p>Centralized tracking for vehicles, customers & certificates</p>
                        </div>
                    </div>

                    <div class="feature-pill-item">
                        <div class="feature-pill-icon">
                            <i class="fa-solid fa-crown"></i>
                        </div>
                        <div class="feature-pill-content">
                            <h6>Instant Subscription Renewals</h6>
                            <p>Flexible duration plans with instant online Razorpay checkout</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Stats Row -->
            <div class="hero-footer-stats">
                <div class="hero-stat-item">
                    <h5>100%</h5>
                    <p>Automated Reminders</p>
                </div>
                <div class="hero-stat-item">
                    <h5>256-Bit</h5>
                    <p>SSL Encryption</p>
                </div>
                <div class="hero-stat-item">
                    <h5>24/7</h5>
                    <p>Portal Availability</p>
                </div>
            </div>
        </div>
    </div>

    <!-- RIGHT LOGIN FORM COLUMN (PERFECTLY CENTERED & NON-SCROLLABLE) -->
    <div class="login-form-side">
        <div class="login-form-box">
            
            <div class="login-box-header">
                <h2 class="login-box-title">Welcome Back</h2>
                <p class="login-box-subtitle">Sign in to access your administrative agency portal.</p>
                
                <div class="role-chips">
                    <span class="role-chip"><i class="fa-solid fa-user-gear"></i> Super Admin</span>
                    <span class="role-chip"><i class="fa-solid fa-user-tie"></i> Agency Partner</span>
                    <span class="role-chip"><i class="fa-solid fa-store"></i> Testing Center</span>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 p-2 px-3 rounded-3 border-0 small" role="alert">
                    <i class="fa-solid fa-circle-exclamation text-danger flex-shrink-0"></i>
                    <div><?php echo sanitize($error); ?></div>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['alert_success'])): ?>
                <div class="alert alert-success d-flex align-items-center gap-2 mb-3 p-2 px-3 rounded-3 border-0 small" role="alert">
                    <i class="fa-solid fa-circle-check text-success flex-shrink-0"></i>
                    <div><?php echo sanitize($_SESSION['alert_success']); unset($_SESSION['alert_success']); ?></div>
                </div>
            <?php endif; ?>

            <form action="" method="POST" autocomplete="on">
                
                <!-- Username Input -->
                <div class="form-group-wrap">
                    <label for="username" class="form-label">Username / Account ID</label>
                    <div class="input-group-modern">
                        <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                        <input type="text" class="input-modern" id="username" name="username" placeholder="Enter your username" required autofocus value="<?php echo isset($_POST['username']) ? sanitize($_POST['username']) : ''; ?>">
                    </div>
                </div>

                <!-- Password Input -->
                <div class="form-group-wrap">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group-modern">
                        <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" class="input-modern" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="btn-toggle-password" id="togglePasswordBtn" title="Toggle password visibility" tabindex="-1">
                            <i class="fa-regular fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-signin">
                    <span>Sign In to Portal</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>

            <div class="login-footer-help">
                <span>Need support or renewal? </span>
                <?php if (!empty($contactPhone)): ?>
                    <a href="tel:<?php echo sanitize($contactPhone); ?>">Contact Admin</a>
                <?php else: ?>
                    <a href="mailto:<?php echo sanitize($contactEmail ?: 'support@domain.com'); ?>">Contact Admin</a>
                <?php endif; ?>
                <div class="login-footer-copy">
                    &copy; <?php echo date('Y'); ?> <?php echo sanitize($systemName); ?>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
    // Password visibility toggle
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('togglePasswordIcon');

    if (toggleBtn && passwordInput && toggleIcon) {
        toggleBtn.addEventListener('click', function() {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            toggleIcon.className = isPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
        });
    }
</script>

</body>
</html>
