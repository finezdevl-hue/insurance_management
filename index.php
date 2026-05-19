<?php
/**
 * Root Router & Unified Premium Login Gateway Portal
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect to respective dashboard
if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        redirect('admin/index.php');
    } elseif ($_SESSION['role'] === 'agent') {
        redirect('agent/index.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
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
                // Check if user is suspended
                if ($user['status'] === 'suspended') {
                    $error = 'Your account has been suspended. Please contact Super Admin.';
                } elseif ($user['role'] === 'agent' && !empty($user['expiry_date']) && date('Y-m-d') > $user['expiry_date']) {
                    $error = 'Your agency account expired on ' . date('d-M-Y', strtotime($user['expiry_date'])) . '. Please contact Super Admin to renew your license.';
                } else {
                    // Start session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Log activity & set alert based on role
                    if ($user['role'] === 'admin') {
                        logActivity('Admin Login', 'Super Admin logged in successfully.');
                        $_SESSION['alert_success'] = 'Welcome back, ' . $user['username'] . '!';
                        redirect('admin/index.php');
                    } else {
                        logActivity('Agent Login', "Agent logged in successfully. Shop: {$user['shop_name']}");
                        $_SESSION['alert_success'] = 'Welcome back! Shop: ' . $user['shop_name'];
                        redirect('agent/index.php');
                    }
                }
            } else {
                $error = 'Invalid username or password.';
                // Log failed attempt without userId
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $stmtLog = $db->prepare("INSERT INTO activity_logs (action, details, ip_address) VALUES (?, ?, ?)");
                $stmtLog->execute(['Unified Login Failed', "Failed login attempt for username: $username", $ipAddress]);
            }
        } catch (PDOException $e) {
            $error = 'An error occurred: ' . $e->getMessage();
        }
    }
}

// Fetch system name from database settings
$systemName = 'Vehicle Details & Insurance Renewal Management System';
try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT system_name FROM settings LIMIT 1");
    if ($row = $stmt->fetch()) {
        $systemName = $row['system_name'];
    }
} catch (PDOException $e) {
    // Graceful fallback
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unified Login Gateway | <?php echo sanitize($systemName); ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #059669 0%, #10b981 100%);
            --font-outfit: 'Outfit', sans-serif;
            --font-inter: 'Inter', sans-serif;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --shadow-premium: 0 20px 40px -15px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: var(--font-inter);
            color: var(--text-main);
            background-color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow-x: hidden;
        }

        /* Ambient background lights */
        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.06) 0%, rgba(255, 255, 255, 0) 70%);
            top: -150px;
            right: -100px;
            z-index: -1;
        }

        body::after {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.05) 0%, rgba(255, 255, 255, 0) 70%);
            bottom: -150px;
            left: -100px;
            z-index: -1;
        }

        .gateway-wrapper {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
        }

        .login-card {
            background-color: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: 24px;
            box-shadow: var(--shadow-premium);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card-header {
            background: var(--primary-gradient);
            padding: 3rem 2rem 2.5rem 2rem;
            text-align: center;
            color: #ffffff;
            position: relative;
        }

        .gateway-logo-badge {
            width: 56px;
            height: 56px;
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            color: #ffffff;
            margin: 0 auto 1.25rem auto;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.05);
            animation: bounceIn 0.8s ease-out;
        }

        .login-card-title {
            font-family: var(--font-outfit);
            font-weight: 800;
            font-size: 1.6rem;
            letter-spacing: -0.02em;
            margin: 0;
            line-height: 1.2;
        }

        .login-card-subtitle {
            font-size: 0.88rem;
            opacity: 0.85;
            margin-top: 0.5rem;
            line-height: 1.4;
        }

        .login-card-body {
            padding: 2.5rem 2.25rem;
        }

        .form-label {
            font-family: var(--font-outfit);
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .input-group {
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .input-group:focus-within {
            border-color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.12);
        }

        .input-group-text {
            background-color: #f8fafc;
            border: none;
            color: var(--text-muted);
            padding-left: 1rem;
            padding-right: 0.75rem;
        }

        .form-control {
            border: none;
            padding: 0.75rem 1rem 0.75rem 0.5rem;
            font-size: 0.95rem;
            color: var(--text-main);
        }

        .form-control:focus {
            box-shadow: none;
            outline: none;
        }

        .password-toggle {
            cursor: pointer;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            color: var(--text-muted);
            background-color: #ffffff;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--text-main);
        }

        .btn-gateway-submit {
            font-family: var(--font-outfit);
            font-weight: 700;
            font-size: 1rem;
            padding: 0.8rem;
            border-radius: 12px;
            background: var(--primary-gradient);
            border: none;
            color: #ffffff;
            width: 100%;
            transition: transform 0.2s ease, opacity 0.2s ease;
            box-shadow: 0 8px 20px -6px rgba(16, 185, 129, 0.3);
        }

        .btn-gateway-submit:hover {
            opacity: 0.95;
            transform: translateY(-1px);
        }

        .btn-gateway-submit:active {
            transform: translateY(1px);
        }

        .footer-section {
            padding: 2rem 0;
            text-align: center;
            border-top: 1px solid var(--border-color);
            background-color: #ffffff;
        }

        .footer-text {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin: 0;
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 0.9;
                transform: scale(1.1);
            }
            80% {
                transform: scale(0.89);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body>

    <!-- Unified Login Gateway Wrapper -->
    <div class="gateway-wrapper">
        <div class="login-card">
            <div class="login-card-header">
                <div class="gateway-logo-badge">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h2 class="login-card-title">Unified Access Gateway</h2>
                <p class="login-card-subtitle">Sign in to access your custom Admin or Agent dashboards</p>
            </div>
            
            <div class="login-card-body">
                
                <!-- Display Errors -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: 10px; border: none; background-color: #fef2f2; color: #991b1b;">
                        <i class="fa-solid fa-circle-exclamation fs-5"></i>
                        <div class="small font-weight-500"><?php echo sanitize($error); ?></div>
                    </div>
                <?php endif; ?>
                
                <!-- Session Alerts -->
                <?php if (isset($_SESSION['alert_error'])): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: 10px; border: none; background-color: #fef2f2; color: #991b1b;">
                        <i class="fa-solid fa-circle-exclamation fs-5"></i>
                        <div class="small font-weight-500">
                            <?php 
                            echo sanitize($_SESSION['alert_error']); 
                            unset($_SESSION['alert_error']);
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" autocomplete="off">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username / Partner ID</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-user-tag"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required value="<?php echo isset($_POST['username']) ? sanitize($_POST['username']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                            <span class="password-toggle" id="togglePasswordBtn"><i class="fa-solid fa-eye" id="toggleIcon"></i></span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-gateway-submit">
                        Secure Authentication <i class="fa-solid fa-arrow-right-to-bracket ms-1"></i>
                    </button>
                </form>
                
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    <div class="footer-section">
        <div class="container">
            <p class="footer-text">© <?php echo date('Y'); ?> <strong><?php echo sanitize($systemName); ?></strong>. All rights reserved.</p>
            <p class="footer-text mt-1 text-muted" style="font-size: 0.72rem;">Managed Secure Gateway Portals. Backed by Core PHP & Bootstrap 5.</p>
        </div>
    </div>

    <!-- Bootstrap JS & Toggle Password Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePasswordBtn = document.querySelector('#togglePasswordBtn');
            const passwordInput = document.querySelector('#password');
            const toggleIcon = document.querySelector('#toggleIcon');

            if (togglePasswordBtn && passwordInput && toggleIcon) {
                togglePasswordBtn.addEventListener('click', function() {
                    const isPassword = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                    toggleIcon.classList.toggle('fa-eye');
                    toggleIcon.classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html>
