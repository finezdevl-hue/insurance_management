<?php
/**
 * Global Helper Functions
 * Vehicle Details & Insurance Renewal Management System
 */

// Import database configuration
require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize string output
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a specific URL
 * @param string $url
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if logged in user has a specific role
 * @param string $role ('admin' or 'agent')
 * @return bool
 */
function hasRole($role) {
    return (isLoggedIn() && $_SESSION['role'] === $role);
}

/**
 * Enforce role-based access or redirect to login
 * @param string $role
 */
function checkAccess($role) {
    if (!isLoggedIn()) {
        $loginUrl = '../index.php';
        redirect($loginUrl);
    }
    
    // Safety check: if session role is set but is invalid (e.g. from another app on localhost),
    // destroy the active session and redirect to the current portal login page.
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'agent'])) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $loginUrl = '../index.php';
        redirect($loginUrl);
    }
    
    // Real-time status and expiry check for logged-in user
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT status, expiry_date FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $userStatus = $stmt->fetch();
    
    if (!$userStatus || $userStatus['status'] === 'suspended' || ($role === 'agent' && !empty($userStatus['expiry_date']) && date('Y-m-d') > $userStatus['expiry_date'])) {
        // Destroy session and force logout
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        // Start a temporary session just to show the error message
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['alert_error'] = 'Your session has expired or your account has been suspended/expired. Please contact Super Admin.';
        $loginUrl = '../index.php';
        redirect($loginUrl);
    }
    
    if ($_SESSION['role'] !== $role) {
        $loginUrl = ($_SESSION['role'] === 'admin') ? '../admin/index.php' : '../agent/index.php';
        redirect($loginUrl);
    }
}

/**
 * Log user activity in the database
 * @param string $action
 * @param string $details
 * @return bool
 */
function logActivity($action, $details = '') {
    $db = getDBConnection();
    $userId = $_SESSION['user_id'] ?? null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    try {
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$userId, $action, $details, $ipAddress]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Fetch all system settings from database
 * @return array
 */
function getSystemSettings() {
    $db = getDBConnection();
    try {
        $stmt = $db->query("SELECT * FROM settings ORDER BY id DESC LIMIT 1");
        $settings = $stmt->fetch();
        if (!$settings) {
            // Return fallback default values
            return [
                'system_name' => 'Vehicle Details & Insurance Renewal Management System',
                'contact_email' => 'support@vehicledetails.com',
                'contact_phone' => '+1234567890',
                'currency' => 'INR',
                'single_message_price' => 1.00,
                'whatsapp_api_url' => 'https://graph.facebook.com/v17.0',
                'whatsapp_phone_number_id' => '',
                'whatsapp_access_token' => '',
                'whatsapp_template_name' => 'insurance_renewal_alert'
            ];
        }
        return $settings;
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Calculate the number of days until a specific date
 * @param string $dateStr (YYYY-MM-DD)
 * @return int (Positive for upcoming, negative for expired)
 */
function getDaysUntil($dateStr) {
    $targetDate = new DateTime($dateStr);
    $currentDate = new DateTime(date('Y-m-d'));
    $interval = $currentDate->diff($targetDate);
    $days = (int)$interval->format('%r%a');
    return $days;
}

/**
 * Format expiry status and return an array with label class, text, and days count
 * @param string $dateStr
 * @return array
 */
function getExpiryStatus($dateStr) {
    if (empty($dateStr)) {
        return ['class' => 'secondary', 'text' => 'No Date', 'days' => 999, 'badge' => 'bg-secondary'];
    }
    
    $days = getDaysUntil($dateStr);
    
    if ($days < 0) {
        return [
            'class' => 'danger',
            'badge' => 'bg-danger',
            'text' => 'Expired (' . abs($days) . ' days ago)',
            'days' => $days
        ];
    } elseif ($days == 0) {
        return [
            'class' => 'warning',
            'badge' => 'bg-warning text-dark',
            'text' => 'Expires Today',
            'days' => $days
        ];
    } elseif ($days <= 7) {
        return [
            'class' => 'warning',
            'badge' => 'bg-warning text-dark',
            'text' => 'Expires in ' . $days . ' days',
            'days' => $days
        ];
    } elseif ($days <= 15) {
        return [
            'class' => 'info',
            'badge' => 'bg-info text-dark',
            'text' => 'Expires in ' . $days . ' days',
            'days' => $days
        ];
    } elseif ($days <= 30) {
        return [
            'class' => 'primary',
            'badge' => 'bg-primary',
            'text' => 'Expires in ' . $days . ' days',
            'days' => $days
        ];
    } else {
        return [
            'class' => 'success',
            'badge' => 'bg-success',
            'text' => 'Active (' . $days . ' days left)',
            'days' => $days
        ];
    }
}

/**
 * Handle secure file upload
 * @param array $file (From $_FILES['key'])
 * @param string $subFolder (Inside '../uploads/')
 * @param array $allowedExtensions
 * @param int $maxSize
 * @return string|false (Uploaded filename or false on failure)
 */
function handleFileUpload($file, $subFolder, $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'], $maxSize = 10485760) { // 10MB
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $fileName = $file['name'];
    $fileTmpPath = $file['tmp_name'];
    $fileSize = $file['size'];
    
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        return false; // Extension not allowed
    }
    
    if ($fileSize > $maxSize) {
        return false; // File too large
    }
    
    // Create direct paths to root uploads folder
    $baseUploadDir = __DIR__ . '/../uploads/';
    $targetDir = $baseUploadDir . $subFolder . '/';
    
    // Check if upload directory exists, if not create it
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Sanitize and generate unique file name
    $newFileName = md5(time() . '_' . uniqid()) . '.' . $fileExtension;
    $destPath = $targetDir . $newFileName;
    
    if (move_uploaded_file($fileTmpPath, $destPath)) {
        return $newFileName;
    }
    
    return false;
}

/**
 * Send WhatsApp message using Facebook Cloud API
 * If API access is not configured, it will simulate a successful send for testing.
 * @param string $whatsappNumber
 * @param string $message
 * @param string $customerName
 * @param string $vehicleNumber
 * @param string $expiryDate
 * @return array ('success' => bool, 'response' => string)
 */
function sendWhatsAppMessage($whatsappNumber, $message, $customerName = '', $vehicleNumber = '', $expiryDate = '') {
    $settings = getSystemSettings();
    $db = getDBConnection();
    
    // Format WhatsApp Number (Must have country code without +, e.g., 919876543210)
    $cleanNumber = preg_replace('/[^0-9]/', '', $whatsappNumber);
    // If country code is missing, prefix with 91 (India) as a standard fallback or let it be
    if (strlen($cleanNumber) === 10) {
        $cleanNumber = '91' . $cleanNumber;
    }
    
    $apiUrl = $settings['whatsapp_api_url'] ?? 'https://graph.facebook.com/v17.0';
    $phoneId = $settings['whatsapp_phone_number_id'] ?? '';
    $accessToken = $settings['whatsapp_access_token'] ?? '';
    $templateName = $settings['whatsapp_template_name'] ?? 'insurance_renewal_alert';
    
    // If not configured, mock sending
    if (empty($phoneId) || empty($accessToken) || $accessToken === 'EAAZB...MOCK_ACCESS_TOKEN') {
        // Return simulated success
        $mockResponse = json_encode([
            'messaging_product' => 'whatsapp',
            'contacts' => [['input' => $cleanNumber, 'wa_id' => $cleanNumber]],
            'messages' => [['id' => 'wamid.HBgL' . $cleanNumber . 'YVAg8EHwAd', 'message_status' => 'accepted']]
        ]);
        
        return [
            'success' => true,
            'response' => $mockResponse,
            'simulated' => true
        ];
    }
    
    $fullUrl = $apiUrl . '/' . $phoneId . '/messages';
    
    // Constructing standard template payload for Cloud API (Recommended)
    // Cloud API requires template sending for business-initiated messages.
    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $cleanNumber,
        'type' => 'template',
        'template' => [
            'name' => $templateName,
            'language' => ['code' => 'en_US'],
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $customerName],
                        ['type' => 'text', 'text' => $vehicleNumber],
                        ['type' => 'text', 'text' => $expiryDate]
                    ]
                ]
            ]
        ]
    ];
    
    // Note: If you want to send plain text instead of template, we can support that,
    // but WhatsApp Cloud API usually restricts text messages to 24hr customer service windows.
    // Let's provide standard curl call.
    $ch = curl_init($fullUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For localhost environments
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $isSuccess = ($httpCode === 200 || $httpCode === 201);
    
    return [
        'success' => $isSuccess,
        'response' => $response ? $response : 'Curl failed to contact WhatsApp API',
        'simulated' => false
    ];
}

/**
 * Get current configured single message price.
 * @return float
 */
function getSingleMessagePrice() {
    $settings = getSystemSettings();
    $price = isset($settings['single_message_price']) ? (float)$settings['single_message_price'] : 0.0;
    return $price > 0 ? $price : 1.0;
}

/**
 * Fetch message wallet summary for an agent.
 * @param int $agentId
 * @return array
 */
function getAgentMessageSummary($agentId) {
    $db = getDBConnection();

    $stmt = $db->prepare("
        SELECT id, username, shop_name, message_balance
        FROM users
        WHERE id = ? AND role = 'agent'
        LIMIT 1
    ");
    $stmt->execute([$agentId]);
    $agent = $stmt->fetch();

    if (!$agent) {
        return [
            'exists' => false,
            'balance' => 0,
            'unit_price' => getSingleMessagePrice(),
            'total_recharge_amount' => 0.0,
            'total_messages_credited' => 0
        ];
    }

    $rechargeStmt = $db->prepare("
        SELECT COALESCE(SUM(recharge_amount), 0) AS total_amount,
               COALESCE(SUM(messages_credited), 0) AS total_messages
        FROM agent_message_recharges
        WHERE agent_id = ?
    ");
    $rechargeStmt->execute([$agentId]);
    $totals = $rechargeStmt->fetch() ?: ['total_amount' => 0, 'total_messages' => 0];

    return [
        'exists' => true,
        'balance' => (int)$agent['message_balance'],
        'unit_price' => getSingleMessagePrice(),
        'total_recharge_amount' => (float)$totals['total_amount'],
        'total_messages_credited' => (int)$totals['total_messages'],
        'username' => $agent['username'],
        'shop_name' => $agent['shop_name']
    ];
}

/**
 * Check whether an agent can send the requested number of messages.
 * @param int $agentId
 * @param int $requestedCount
 * @return array
 */
function canAgentSendMessages($agentId, $requestedCount = 1) {
    $requestedCount = max(1, (int)$requestedCount);
    $summary = getAgentMessageSummary($agentId);
    $balance = (int)$summary['balance'];

    return [
        'allowed' => $balance >= $requestedCount,
        'balance' => $balance,
        'requested' => $requestedCount,
        'shortage' => max(0, $requestedCount - $balance),
        'unit_price' => $summary['unit_price']
    ];
}

/**
 * Deduct sent messages from agent wallet.
 * @param int $agentId
 * @param int $count
 * @return bool
 */
function deductAgentMessages($agentId, $count = 1) {
    $count = max(1, (int)$count);
    $db = getDBConnection();

    $stmt = $db->prepare("
        UPDATE users
        SET message_balance = message_balance - ?
        WHERE id = ? AND role = 'agent' AND message_balance >= ?
    ");

    $stmt->execute([$count, $agentId, $count]);
    return $stmt->rowCount() === 1;
}

/**
 * Recharge an agent's message wallet from rupee amount using current single message price.
 * @param int $agentId
 * @param float $amount
 * @param int|null $rechargedBy
 * @return array
 */
function rechargeAgentMessages($agentId, $amount, $rechargedBy = null) {
    $amount = round((float)$amount, 2);
    $unitPrice = getSingleMessagePrice();

    if ($amount <= 0) {
        throw new InvalidArgumentException('Recharge amount must be greater than zero.');
    }

    if ($unitPrice <= 0) {
        throw new InvalidArgumentException('Single message price must be greater than zero.');
    }

    $messagesCredited = (int)floor($amount / $unitPrice);

    if ($messagesCredited < 1) {
        throw new InvalidArgumentException('Recharge amount is too low for the current per-message price.');
    }

    $db = getDBConnection();
    $db->beginTransaction();

    try {
        $agentStmt = $db->prepare("SELECT username, shop_name FROM users WHERE id = ? AND role = 'agent' LIMIT 1");
        $agentStmt->execute([$agentId]);
        $agent = $agentStmt->fetch();

        if (!$agent) {
            throw new InvalidArgumentException('Agent not found.');
        }

        $updateStmt = $db->prepare("
            UPDATE users
            SET message_balance = message_balance + ?
            WHERE id = ? AND role = 'agent'
        ");
        $updateStmt->execute([$messagesCredited, $agentId]);

        $insertStmt = $db->prepare("
            INSERT INTO agent_message_recharges (agent_id, recharge_amount, message_unit_price, messages_credited, recharged_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([$agentId, $amount, $unitPrice, $messagesCredited, $rechargedBy]);

        $db->commit();

        return [
            'agent' => $agent,
            'amount' => $amount,
            'unit_price' => $unitPrice,
            'messages_credited' => $messagesCredited
        ];
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

/**
 * Human-friendly insufficient message alert for agents.
 * @param int $balance
 * @param int $requested
 * @return string
 */
function getMessageLimitError($balance, $requested) {
    $balance = (int)$balance;
    $requested = (int)$requested;

    if ($balance <= 0) {
        return 'No message count left. Please contact admin.';
    }

    return "Message count is limited. You have only {$balance} messages left, so {$requested} messages were not sent.";
}

/**
 * Check if the currently logged in agent has access to a specific service
 * @param string $service ('health', 'vehicle', 'pollution')
 * @return bool
 */
function hasAgentAccess($service) {
    if (!isLoggedIn()) {
        return false;
    }
    if ($_SESSION['role'] === 'admin') {
        return true; // Admins have full access
    }
    
    static $permissions = null;
    if ($permissions === null) {
        try {
            $db = getDBConnection();
            $stmt = $db->prepare("SELECT access_health_insurance, access_vehicle_insurance, access_pollution FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $permissions = $stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    if (!$permissions) {
        return false;
    }
    
    if ($service === 'health') {
        return (int)$permissions['access_health_insurance'] === 1;
    } elseif ($service === 'vehicle') {
        return (int)$permissions['access_vehicle_insurance'] === 1;
    } elseif ($service === 'pollution') {
        return (int)$permissions['access_pollution'] === 1;
    }
    
    return false;
}
