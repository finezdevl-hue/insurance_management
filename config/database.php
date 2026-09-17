<?php
/**
 * Database Configuration and Connection
 * Vehicle Details & Insurance Renewal Management System
 */

// Include Logger Engine if present so all subsequent errors & queries are tracked
if (file_exists(__DIR__ . '/../includes/logger.php')) {
    require_once __DIR__ . '/../includes/logger.php';
} else {
    if (!function_exists('logDatabaseQuery')) { function logDatabaseQuery($sql, $params = [], $durationMs = 0, $error = null) {} }
    if (!function_exists('logSystemError')) { function logSystemError($level, $message, $file = '', $line = 0, $trace = null) {} }
    if (!function_exists('renderFriendlyErrorPage')) {
        function renderFriendlyErrorPage($message, $file = '', $line = 0) {
            echo "<div style='font-family:sans-serif;padding:30px;max-width:700px;margin:50px auto;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;'><h3>System Error</h3><p>" . nl2br(htmlspecialchars($message)) . "</p></div>";
        }
    }
}

// Database configuration constants (Change these for production hosting)
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'vehicle_manage');

// Site URL constant (Auto-detected or default to production alert.finez.in)
if (!defined('SITE_URL')) {
    if (getenv('SITE_URL')) {
        define('SITE_URL', rtrim(getenv('SITE_URL'), '/'));
    } elseif (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
        define('SITE_URL', 'http://localhost/vehicle_manage');
    } else {
        define('SITE_URL', 'https://alert.finez.in');
    }
}

// For Live Production Server (e.g. alert.finez.in):
// define('DB_HOST', 'localhost');
// define('DB_USER', 'xmynywjyjd');
// define('DB_PASS', 'UfRbJus5Va');
// define('DB_NAME', 'xmynywjyjd');

/**
 * Get PDO Database Connection
 * @return PDO
 */
function getDBConnection() {
    static $pdo = null;
    static $schemaReady = false;
    
    if ($pdo === null) {
        $startTime = microtime(true);
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $durationMs = (microtime(true) - $startTime) * 1000;
            logDatabaseQuery("CONNECT TO DATABASE " . DB_NAME, [], $durationMs);
        } catch (PDOException $e) {
            $durationMs = (microtime(true) - $startTime) * 1000;
            logSystemError('DATABASE_CONNECTION_ERROR', "Failed to connect to MySQL database (" . DB_NAME . " at " . DB_HOST . "): " . $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
            logDatabaseQuery("CONNECT TO DATABASE " . DB_NAME, [], $durationMs, $e->getMessage());

            if (!headers_sent()) {
                http_response_code(500);
            }

            renderFriendlyErrorPage(
                "Database Connection Error: " . $e->getMessage() . ".\n\nPlease verify your MySQL database credentials (DB_HOST, DB_USER, DB_PASS, DB_NAME) in 'config/database.php'.",
                $e->getFile(),
                $e->getLine()
            );
            exit;
        }
    }

    if (!$schemaReady) {
        ensureRuntimeSchema($pdo);
        $schemaReady = true;
    }
    
    return $pdo;
}

/**
 * Execute a query with automatic query logging and execution time tracking
 * @param PDO $pdo
 * @param string $sql
 * @param array $params
 * @return PDOStatement
 */
function executeLoggedQuery(PDO $pdo, $sql, $params = []) {
    $startTime = microtime(true);
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $durationMs = (microtime(true) - $startTime) * 1000;
        logDatabaseQuery($sql, $params, $durationMs);
        return $stmt;
    } catch (PDOException $e) {
        $durationMs = (microtime(true) - $startTime) * 1000;
        logDatabaseQuery($sql, $params, $durationMs, $e->getMessage());
        logSystemError('SQL_QUERY_ERROR', "Query Failed: {$sql} | Error: " . $e->getMessage(), $e->getFile(), $e->getLine());
        throw $e;
    }
}

/**
 * Apply lightweight runtime schema updates for backward compatibility.
 * @param PDO $pdo
 * @return void
 */
function ensureRuntimeSchema(PDO $pdo) {
    static $running = false;

    if ($running) {
        return;
    }

    $running = true;

    try {
        // 1. Column additions
        $columnChecks = [
            'settings' => [
                "single_message_price" => "ALTER TABLE settings ADD COLUMN single_message_price DECIMAL(10,2) NOT NULL DEFAULT 1.00 AFTER currency"
            ],
            'users' => [
                "access_health_insurance" => "ALTER TABLE users ADD COLUMN access_health_insurance TINYINT(1) NOT NULL DEFAULT 1 AFTER expiry_date",
                "access_vehicle_insurance" => "ALTER TABLE users ADD COLUMN access_vehicle_insurance TINYINT(1) NOT NULL DEFAULT 1 AFTER access_health_insurance",
                "access_pollution" => "ALTER TABLE users ADD COLUMN access_pollution TINYINT(1) NOT NULL DEFAULT 1 AFTER access_vehicle_insurance",
                "message_balance" => "ALTER TABLE users ADD COLUMN message_balance INT NOT NULL DEFAULT 0 AFTER access_pollution",
                "gst_number" => "ALTER TABLE users ADD COLUMN gst_number VARCHAR(50) DEFAULT NULL AFTER whatsapp_number",
                "license_number" => "ALTER TABLE users ADD COLUMN license_number VARCHAR(50) DEFAULT NULL AFTER gst_number",
                "pan_number" => "ALTER TABLE users ADD COLUMN pan_number VARCHAR(50) DEFAULT NULL AFTER license_number",
                "business_type" => "ALTER TABLE users ADD COLUMN business_type VARCHAR(100) DEFAULT NULL AFTER pan_number",
                "shop_banner" => "ALTER TABLE users ADD COLUMN shop_banner VARCHAR(255) DEFAULT NULL AFTER shop_logo"
            ],
            "settings" => [
                "notification_send_time" => "ALTER TABLE settings ADD COLUMN notification_send_time VARCHAR(10) NOT NULL DEFAULT '09:00' AFTER razorpay_enabled",
                "auto_notifications_enabled" => "ALTER TABLE settings ADD COLUMN auto_notifications_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER notification_send_time",
                "last_cron_run_at" => "ALTER TABLE settings ADD COLUMN last_cron_run_at DATETIME DEFAULT NULL AFTER auto_notifications_enabled"
            ]
        ];

        foreach ($columnChecks as $table => $columns) {
            foreach ($columns as $column => $sql) {
                try {
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) 
                        FROM information_schema.COLUMNS 
                        WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
                    ");
                    $stmt->execute([DB_NAME, $table, $column]);

                    if ((int)$stmt->fetchColumn() === 0) {
                        $pdo->exec($sql);
                    }
                } catch (Throwable $e) {
                    // Ignore column check error
                }
            }
        }

        // 2. Check & create agent_message_recharges table
        try {
            $tableStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'agent_message_recharges'
            ");
            $tableStmt->execute([DB_NAME]);

            if ((int)$tableStmt->fetchColumn() === 0) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS agent_message_recharges (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        agent_id INT NOT NULL,
                        recharge_amount DECIMAL(10,2) NOT NULL,
                        message_unit_price DECIMAL(10,2) NOT NULL DEFAULT 1.00,
                        messages_credited INT NOT NULL DEFAULT 0,
                        recharged_by INT DEFAULT NULL,
                        razorpay_payment_id VARCHAR(100) DEFAULT NULL,
                        razorpay_order_id VARCHAR(100) DEFAULT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (recharged_by) REFERENCES users(id) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
        } catch (Throwable $e) {
            // Ignore
        }

        // 3. Check & create subscription_plans table
        try {
            $plansTableStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'subscription_plans'
            ");
            $plansTableStmt->execute([DB_NAME]);

            if ((int)$plansTableStmt->fetchColumn() === 0) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS subscription_plans (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        name VARCHAR(100) NOT NULL,
                        duration_type ENUM('days', 'months', 'years') NOT NULL DEFAULT 'months',
                        duration_value INT NOT NULL,
                        price DECIMAL(10,2) NOT NULL,
                        sms_credits INT NOT NULL DEFAULT 0,
                        badge VARCHAR(50) DEFAULT NULL,
                        description TEXT DEFAULT NULL,
                        features TEXT DEFAULT NULL,
                        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");

                // Seed standard subscription plans
                $pdo->exec("
                    INSERT INTO subscription_plans (name, duration_type, duration_value, price, sms_credits, badge, description, features, status) VALUES
                    ('1 Month Plan', 'months', 1, 499.00, 0, 'Starter', '1 Month full access to all system features.', 'Full Agency Portal Access\nVehicle & Health Insurance Records\nPollution (PUC) Certificate Management\nAutomated WhatsApp Expiry Reminders\nSub-Shops & Testing Centers Included\nPriority Support & Updates', 'active'),
                    ('3 Months Plan', 'months', 3, 1299.00, 0, 'Quarterly', '3 Months full access to all system features.', 'Full Agency Portal Access\nVehicle & Health Insurance Records\nPollution (PUC) Certificate Management\nAutomated WhatsApp Expiry Reminders\nSub-Shops & Testing Centers Included\nPriority Support & Updates', 'active'),
                    ('6 Months Plan', 'months', 6, 2499.00, 0, 'Popular', '6 Months full access to all system features.', 'Full Agency Portal Access\nVehicle & Health Insurance Records\nPollution (PUC) Certificate Management\nAutomated WhatsApp Expiry Reminders\nSub-Shops & Testing Centers Included\nPriority Support & Updates', 'active'),
                    ('1 Year Plan', 'years', 1, 4499.00, 0, 'Best Value', '1 Year full access to all system features.', 'Full Agency Portal Access\nVehicle & Health Insurance Records\nPollution (PUC) Certificate Management\nAutomated WhatsApp Expiry Reminders\nSub-Shops & Testing Centers Included\nPriority Support & Updates', 'active'),
                    ('2 Years Plan', 'years', 2, 7999.00, 0, 'Max Savings', '2 Years full access to all system features.', 'Full Agency Portal Access\nVehicle & Health Insurance Records\nPollution (PUC) Certificate Management\nAutomated WhatsApp Expiry Reminders\nSub-Shops & Testing Centers Included\nPriority Support & Updates', 'active')
                ");
            }
        } catch (Throwable $e) {
            // Ignore
        }

        // 4. Check & create agent_subscriptions table
        try {
            $subTableStmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM information_schema.TABLES
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'agent_subscriptions'
            ");
            $subTableStmt->execute([DB_NAME]);

            if ((int)$subTableStmt->fetchColumn() === 0) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS agent_subscriptions (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        agent_id INT NOT NULL,
                        plan_id INT DEFAULT NULL,
                        plan_name VARCHAR(100) NOT NULL,
                        duration_months INT NOT NULL DEFAULT 0,
                        duration_days INT NOT NULL DEFAULT 0,
                        amount_paid DECIMAL(10,2) NOT NULL,
                        payment_method VARCHAR(50) NOT NULL DEFAULT 'Razorpay',
                        razorpay_payment_id VARCHAR(100) DEFAULT NULL,
                        razorpay_order_id VARCHAR(100) DEFAULT NULL,
                        start_date DATE NOT NULL,
                        expiry_date DATE NOT NULL,
                        sms_credited INT NOT NULL DEFAULT 0,
                        status ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
                        created_by INT DEFAULT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE SET NULL,
                        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                ");
            }
        } catch (Throwable $e) {
            // Ignore
        }

        // 5. Check & create system_error_logs and system_query_logs tables
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS system_error_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    level VARCHAR(20) NOT NULL DEFAULT 'ERROR',
                    message TEXT NOT NULL,
                    file VARCHAR(255) DEFAULT NULL,
                    line INT DEFAULT 0,
                    request_uri VARCHAR(255) DEFAULT NULL,
                    ip_address VARCHAR(45) DEFAULT NULL,
                    user_id INT DEFAULT NULL,
                    stack_trace MEDIUMTEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $pdo->exec("
                CREATE TABLE IF NOT EXISTS system_query_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    query_sql TEXT NOT NULL,
                    params_json TEXT DEFAULT NULL,
                    execution_time_ms DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    status VARCHAR(20) NOT NULL DEFAULT 'success',
                    error_message TEXT DEFAULT NULL,
                    user_id INT DEFAULT NULL,
                    ip_address VARCHAR(45) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } catch (Throwable $e) {
            // Ignore
        }

        // 5. Normalize existing unformatted vehicle registration numbers (e.g. KL17S1515 -> KL-17-S-1515)
        try {
            $stmtUnf = $pdo->query("SELECT id, vehicle_number FROM vehicles WHERE vehicle_number NOT LIKE '%-%' LIMIT 100");
            $unfVehs = $stmtUnf->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($unfVehs)) {
                $stmtUpdVeh = $pdo->prepare("UPDATE vehicles SET vehicle_number = ? WHERE id = ?");
                foreach ($unfVehs as $uv) {
                    $raw = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($uv['vehicle_number'])));
                    $formatted = null;
                    if (preg_match('/^([A-Z]{2})(\d{1,2})([A-Z]{1,3})(\d{1,4})$/', $raw, $m)) {
                        $formatted = "{$m[1]}-" . str_pad($m[2], 2, '0', STR_PAD_LEFT) . "-{$m[3]}-{$m[4]}";
                    } elseif (preg_match('/^([A-Z]{2})(\d{1,2})(\d{1,4})$/', $raw, $m)) {
                        $formatted = "{$m[1]}-" . str_pad($m[2], 2, '0', STR_PAD_LEFT) . "-{$m[3]}";
                    } elseif (preg_match('/^(\d{2})(BH)(\d{1,4})([A-Z]{1,2})$/', $raw, $m)) {
                        $formatted = "{$m[1]}-BH-{$m[3]}-{$m[4]}";
                    }
                    if ($formatted && $formatted !== $uv['vehicle_number']) {
                        try {
                            $stmtUpdVeh->execute([$formatted, $uv['id']]);
                        } catch (Throwable $e) {}
                    }
                }
            }
        } catch (Throwable $e) {}
    } catch (Throwable $e) {
        // Suppress runtime schema errors
    }

    $running = false;
}

// Configure session settings before starting session
if (session_status() === PHP_SESSION_NONE) {
    // Standard session and cookie parameters
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', '2592000'); // 30 days

    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
               (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 86400 * 30, // 30 days
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } else {
        session_set_cookie_params(86400 * 30, '/', null, $isHttps, true);
    }

    @session_start();

    // Prevent Cloudways Varnish / Nginx from caching dynamic PHP pages
    if (!headers_sent()) {
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private");
        header("Pragma: no-cache");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    }
}
