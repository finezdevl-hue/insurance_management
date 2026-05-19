<?php
/**
 * Database Configuration and Connection
 * Vehicle Details & Insurance Renewal Management System
 */

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'vehicle_manage');

/**
 * Get PDO Database Connection
 * @return PDO
 */
function getDBConnection() {
    static $pdo = null;
    static $schemaReady = false;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In a production environment, you should log this instead of printing details
            die("Database connection failed: " . $e->getMessage());
        }
    }

    if (!$schemaReady) {
        ensureRuntimeSchema($pdo);
        $schemaReady = true;
    }
    
    return $pdo;
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
        $columnChecks = [
            'settings' => [
                "single_message_price" => "ALTER TABLE settings ADD COLUMN single_message_price DECIMAL(10,2) NOT NULL DEFAULT 1.00 AFTER currency"
            ],
            'users' => [
                "access_health_insurance" => "ALTER TABLE users ADD COLUMN access_health_insurance TINYINT(1) NOT NULL DEFAULT 1 AFTER expiry_date",
                "access_vehicle_insurance" => "ALTER TABLE users ADD COLUMN access_vehicle_insurance TINYINT(1) NOT NULL DEFAULT 1 AFTER access_health_insurance",
                "access_pollution" => "ALTER TABLE users ADD COLUMN access_pollution TINYINT(1) NOT NULL DEFAULT 1 AFTER access_vehicle_insurance",
                "message_balance" => "ALTER TABLE users ADD COLUMN message_balance INT NOT NULL DEFAULT 0 AFTER access_pollution"
            ]
        ];

        foreach ($columnChecks as $table => $columns) {
            foreach ($columns as $column => $sql) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM information_schema.COLUMNS 
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
                ");
                $stmt->execute([DB_NAME, $table, $column]);

                if ((int)$stmt->fetchColumn() === 0) {
                    $pdo->exec($sql);
                }
            }
        }

        $tableStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'agent_message_recharges'
        ");
        $tableStmt->execute([DB_NAME]);

        if ((int)$tableStmt->fetchColumn() === 0) {
            $pdo->exec("
                CREATE TABLE agent_message_recharges (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    agent_id INT NOT NULL,
                    recharge_amount DECIMAL(10,2) NOT NULL,
                    message_unit_price DECIMAL(10,2) NOT NULL,
                    messages_credited INT NOT NULL DEFAULT 0,
                    recharged_by INT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (recharged_by) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } else {
            $rechargeColumns = [
                'message_unit_price' => "ALTER TABLE agent_message_recharges ADD COLUMN message_unit_price DECIMAL(10,2) NOT NULL DEFAULT 1.00 AFTER recharge_amount",
                'messages_credited' => "ALTER TABLE agent_message_recharges ADD COLUMN messages_credited INT NOT NULL DEFAULT 0 AFTER message_unit_price",
                'recharged_by' => "ALTER TABLE agent_message_recharges ADD COLUMN recharged_by INT DEFAULT NULL AFTER messages_credited",
                'created_at' => "ALTER TABLE agent_message_recharges ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER recharged_by"
            ];

            foreach ($rechargeColumns as $column => $sql) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?
                ");
                $stmt->execute([DB_NAME, 'agent_message_recharges', $column]);

                if ((int)$stmt->fetchColumn() === 0) {
                    $pdo->exec($sql);
                }
            }
        }
    } catch (PDOException $e) {
        // Keep app usable even if migration could not be applied.
    }

    $running = false;
}

// Start PHP session globally if it hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
