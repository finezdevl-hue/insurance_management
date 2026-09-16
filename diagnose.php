<?php
/**
 * Standalone Server Diagnostics & DB Connectivity Test
 * Vehicle Details & Insurance Renewal Management System
 * Upload this file and visit: https://alert.finez.in/diagnose.php
 */

// Enable full error display for this diagnostic script
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Server & Database Diagnostics | Vehicle Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background-color: #0f172a; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; padding: 2rem 1rem; }
        .diag-card { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 2rem; max-width: 800px; margin: 0 auto; }
        .code-box { background: #0b1329; border: 1px solid #334155; border-radius: 8px; padding: 1rem; font-family: monospace; font-size: 0.85rem; color: #38bdf8; }
    </style>
</head>
<body>

<div class="diag-card shadow-lg">
    <div class="d-flex align-items-center gap-3 mb-4 border-bottom border-secondary pb-3">
        <div style="width: 48px; height: 48px; border-radius: 50%; background: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
            <i class="fa-solid fa-stethoscope text-white"></i>
        </div>
        <div>
            <h4 class="m-0 text-white font-weight-700">Live Server Diagnostics</h4>
            <p class="m-0 text-muted small">Diagnostic probe for <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost'); ?></p>
        </div>
    </div>

    <!-- 1. PHP Environment -->
    <h6 class="text-info text-uppercase small font-weight-700 mb-2">1. PHP Environment</h6>
    <div class="code-box mb-4">
        <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
        <strong>Server Software:</strong> <?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?><br>
        <strong>Loaded Extensions:</strong><br>
        <?php 
        $reqExts = ['pdo', 'pdo_mysql', 'curl', 'mbstring', 'openssl', 'json', 'gd'];
        foreach ($reqExts as $ext) {
            $status = extension_loaded($ext) ? '<span class="text-success">[OK - Loaded]</span>' : '<span class="text-danger">[MISSING!]</span>';
            echo " - {$ext}: {$status}<br>";
        }
        ?>
    </div>

    <!-- 2. Database Connection Test -->
    <h6 class="text-info text-uppercase small font-weight-700 mb-2">2. Database Connection Test</h6>
    <div class="code-box mb-4">
        <?php
        $configFile = __DIR__ . '/config/database.php';
        if (!file_exists($configFile)) {
            echo '<span class="text-danger">ERROR: config/database.php was not found on server!</span><br>';
        } else {
            echo "<strong>Checking config/database.php:</strong> Found.<br>";
            
            try {
                require_once $configFile;
                echo "<strong>Constants Defined:</strong><br>";
                echo " - DB_HOST: " . (defined('DB_HOST') ? htmlspecialchars(DB_HOST) : 'NOT DEFINED') . "<br>";
                echo " - DB_USER: " . (defined('DB_USER') ? htmlspecialchars(DB_USER) : 'NOT DEFINED') . "<br>";
                echo " - DB_NAME: " . (defined('DB_NAME') ? htmlspecialchars(DB_NAME) : 'NOT DEFINED') . "<br>";
                echo " - DB_PASS: " . (defined('DB_PASS') ? (empty(DB_PASS) ? '(empty password)' : '(password set, length: ' . strlen(DB_PASS) . ')') : 'NOT DEFINED') . "<br><br>";

                echo "<strong>Testing Connection to MySQL...</strong><br>";
                $testPdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                echo '<span class="text-success"><strong>SUCCESS: Connected to MySQL Database successfully!</strong></span><br><br>';

                // Check existing tables
                $stmtTables = $testPdo->query("SHOW TABLES");
                $tables = $stmtTables->fetchAll(PDO::FETCH_COLUMN);
                echo "<strong>Tables found in database (" . count($tables) . "):</strong><br>";
                if (empty($tables)) {
                    echo '<span class="text-warning">WARNING: The database is connected but contains 0 tables! Please import vehicle_manage.sql.</span><br>';
                } else {
                    echo '<span class="text-light">' . implode(', ', $tables) . '</span><br><br>';
                    
                    // Check users table
                    if (in_array('users', $tables)) {
                        $usersCount = $testPdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                        echo " - Total users in 'users' table: <strong class='text-success'>{$usersCount}</strong><br>";
                    }
                    // Check settings table
                    if (in_array('settings', $tables)) {
                        $settingsCount = $testPdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
                        echo " - Records in 'settings' table: <strong class='text-success'>{$settingsCount}</strong><br>";
                    }
                }

            } catch (PDOException $e) {
                echo '<span class="text-danger"><strong>DATABASE CONNECTION FAILED:</strong></span><br>';
                echo '<span class="text-danger">' . htmlspecialchars($e->getMessage()) . '</span><br><br>';
                echo '<strong>Troubleshooting Checklist:</strong><br>';
                echo '1. Ensure DB_USER and DB_PASS match your cPanel MySQL user credentials.<br>';
                echo '2. In cPanel MySQL Databases, make sure you clicked <em>"Add User To Database"</em> and checked <em>"ALL PRIVILEGES"</em>.<br>';
                echo '3. If DB_HOST is not "localhost", check with your hosting provider for the correct host IP/hostname.<br>';
            } catch (Throwable $e) {
                echo '<span class="text-danger"><strong>PHP ERROR:</strong> ' . htmlspecialchars($e->getMessage()) . ' in ' . htmlspecialchars($e->getFile()) . ' on line ' . $e->getLine() . '</span><br>';
            }
        }
        ?>
    </div>

    <!-- 3. Logs & Directory Permissions -->
    <h6 class="text-info text-uppercase small font-weight-700 mb-2">3. Logs & Directory Permissions</h6>
    <div class="code-box mb-4">
        <?php
        $logsDir = __DIR__ . '/logs';
        echo "<strong>Logs Directory:</strong> " . (is_dir($logsDir) ? '<span class="text-success">Exists</span>' : '<span class="text-warning">Missing (will be created automatically)</span>') . "<br>";
        echo "<strong>Logs Directory Writable:</strong> " . (is_writable(__DIR__) || is_writable($logsDir) ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No (Permission issue)</span>') . "<br>";
        
        $errorLog = $logsDir . '/error.log';
        if (file_exists($errorLog)) {
            echo "<strong>logs/error.log size:</strong> " . round(filesize($errorLog) / 1024, 2) . " KB<br>";
            $lastLines = array_slice(file($errorLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES), -5);
            if (!empty($lastLines)) {
                echo "<strong>Last logged errors:</strong><br>";
                foreach ($lastLines as $l) {
                    echo "<span class='text-warning'>" . htmlspecialchars($l) . "</span><br>";
                }
            }
        }
        ?>
    </div>

    <div class="d-flex justify-content-between">
        <a href="index.php" class="btn btn-primary font-weight-600">
            <i class="fa-solid fa-arrow-left me-1"></i> Go to Login Page
        </a>
        <a href="diagnose.php" class="btn btn-outline-secondary text-light">
            <i class="fa-solid fa-rotate-right me-1"></i> Re-test
        </a>
    </div>

</div>

</body>
</html>
