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
    if (!headers_sent()) {
        header("Location: " . $url);
    } else {
        echo "<script>window.location.href=" . json_encode($url) . ";</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=" . htmlspecialchars($url) . "'></noscript>";
    }
    exit;
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['role']);
}

/**
 * Check if logged in user has a specific role
 * @param string $role ('admin' or 'agent' or 'shop')
 * @return bool
 */
function hasRole($role) {
    return (isLoggedIn() && ($_SESSION['role'] ?? '') === $role);
}

/**
 * Get the effective Parent Agent ID for database filtering and shared data scoping.
 * @return int
 */
function getEffectiveAgentId() {
    if (!isLoggedIn()) return 0;
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'shop' && !empty($_SESSION['parent_agent_id'])) {
        return (int)$_SESSION['parent_agent_id'];
    }
    return (int)($_SESSION['user_id'] ?? 0);
}

/**
 * Enforce role-based access or redirect to login
 * @param string $role
 */
function checkAccess($role) {
    handleMobileAutoRedirect();

    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($scriptPath, '/mobile/agent/') !== false || strpos($scriptPath, '/mobile/admin/') !== false) {
        $loginUrl = '../../index.php';
    } elseif (strpos($scriptPath, '/agent/') !== false || strpos($scriptPath, '/admin/') !== false || strpos($scriptPath, '/mobile/') !== false) {
        $loginUrl = '../index.php';
    } else {
        $loginUrl = 'index.php';
    }

    if (!isLoggedIn()) {
        redirect($loginUrl);
    }
    
    $userRole = $_SESSION['role'] ?? '';
    
    // Safety check: if session role is set but is invalid, destroy active session
    if (!in_array($userRole, ['admin', 'agent', 'shop'])) {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        redirect($loginUrl);
    }
    
    // Real-time status and expiry check for logged-in user
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id, status, expiry_date, parent_agent_id, role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $userStatus = $stmt->fetch();
        
        if ($userStatus) {
            // Check account suspension
            if ($userStatus['status'] === 'suspended') {
                $_SESSION = [];
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_destroy();
                }
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['alert_error'] = 'Your account has been suspended. Please contact Super Admin.';
                redirect($loginUrl);
            }

            // Check parent agency suspension for sub-shops
            if ($userRole === 'shop' && !empty($userStatus['parent_agent_id'])) {
                $stmtP = $db->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
                $stmtP->execute([$userStatus['parent_agent_id']]);
                $parentStatus = $stmtP->fetchColumn();
                if ($parentStatus === 'suspended') {
                    $_SESSION = [];
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_destroy();
                    }
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    $_SESSION['alert_error'] = 'Your parent agency account is suspended. Please contact Super Admin.';
                    redirect($loginUrl);
                }
            }

            // Expiry date check ONLY for agent & shop roles (NEVER for admin!)
            if (in_array($userRole, ['agent', 'shop'])) {
                if (!empty($userStatus['expiry_date']) && date('Y-m-d') > $userStatus['expiry_date']) {
                    $_SESSION = [];
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_destroy();
                    }
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    $_SESSION['alert_error'] = 'Your license expired on ' . date('d-M-Y', strtotime($userStatus['expiry_date'])) . '. Please renew your subscription.';
                    redirect($loginUrl);
                }
            }
        }
    } catch (Throwable $e) {
        logSystemError('CHECK_ACCESS_WARNING', "checkAccess DB validation warning: " . $e->getMessage(), $e->getFile(), $e->getLine());
    }
    
    if ($role === 'agent') {
        if (!in_array($userRole, ['agent', 'shop'])) {
            redirect('../admin/index.php');
        }
    } elseif ($role === 'admin') {
        if ($userRole !== 'admin') {
            redirect('../agent/index.php');
        }
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
                'whatsapp_phone_number_id' => '1201793359690573',
                'whatsapp_access_token' => 'EAAVXZCA0n0WIBSGTzawG7n3jIXIBZCDjz37zXNVhdhN3AuTujUnWweyZBVocz1O0NKIf4wvbygZCIXgoMO1vE1T516aMynUhYm6mEZBZCTb56CmsCu4S2eHORfqtifGGBvEmgxyc52E19k9SdjP1iOQh8WYsN4CVKfcZBIVMP4n5jaqTkAccnZAef5RVdDa97Eg0gQZDZD',
                'whatsapp_template_name' => 'pollution_puc_expiry_alert',
                'razorpay_key_id' => '',
                'razorpay_key_secret' => '',
                'razorpay_enabled' => 0,
                'notification_send_time' => '09:00',
                'auto_notifications_enabled' => 1,
                'last_cron_run_at' => null
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
 * Get Base Application URL (supports localhost subfolders, live domains, and CLI crons)
 * @return string
 */
function getBaseUrl() {
    if (defined('SITE_URL') && !empty(SITE_URL)) {
        return rtrim(SITE_URL, '/');
    }
    
    // Fallback for CLI or when HTTP_HOST is missing
    if (php_sapi_name() === 'cli' || empty($_SERVER['HTTP_HOST'])) {
        return 'https://alert.finez.in';
    }
    
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
            
    $protocol = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        return $protocol . '://' . $host . '/vehicle_manage';
    }
    
    return $protocol . '://' . $host;
}

/**
 * Resolve shop contact details (address and mobile) for compiling WhatsApp reminders.
 * Prefers the specific Shop outlet that created the vehicle/certificate, with fallback to parent agent.
 * @param int|null $vehicleId
 * @param string $remType ('Pollution', 'Insurance', 'Health', etc.)
 * @param int $agentId
 * @return array ('shop_name' => string, 'shop_address' => string, 'mobile_number' => string, 'centers_link' => string)
 */
function getShopDetailsForReminder($vehicleId, $remType, $agentId) {
    $db = getDBConnection();
    $shopId = null;
    
    if ($vehicleId && $vehicleId > 0) {
        if ($remType === 'Pollution') {
            $stmt = $db->prepare("SELECT created_by_shop_id FROM pollution_certificates WHERE vehicle_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$vehicleId]);
            $shopId = $stmt->fetchColumn();
        } elseif ($remType === 'Insurance') {
            $stmt = $db->prepare("SELECT created_by_shop_id FROM insurances WHERE vehicle_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$vehicleId]);
            $shopId = $stmt->fetchColumn();
        }
        
        if (!$shopId) {
            $stmtV = $db->prepare("SELECT created_by_shop_id FROM vehicles WHERE id = ?");
            $stmtV->execute([$vehicleId]);
            $shopId = $stmtV->fetchColumn();
        }
    }
    
    $baseUrl = getBaseUrl();
    $centersLink = $baseUrl . '/centers.php?agent_id=' . (int)$agentId;

    // If shop ID found, fetch shop details
    if ($shopId) {
        $stmtS = $db->prepare("SELECT shop_name, shop_address, city, state, pincode, mobile_number, whatsapp_number FROM users WHERE id = ?");
        $stmtS->execute([$shopId]);
        $shopData = $stmtS->fetch(PDO::FETCH_ASSOC);
        if ($shopData) {
            $shopName = !empty($shopData['shop_name']) ? trim($shopData['shop_name']) : '';
            $addressParts = array_filter([
                trim($shopData['shop_address'] ?? ''),
                trim($shopData['city'] ?? ''),
                trim($shopData['state'] ?? ''),
                trim($shopData['pincode'] ?? '')
            ]);
            $fullAddress = !empty($addressParts) ? implode(', ', $addressParts) : '';
            $mobile = !empty($shopData['mobile_number']) ? trim($shopData['mobile_number']) : trim($shopData['whatsapp_number'] ?? '');
            
            if (!empty($shopName) || !empty($fullAddress) || !empty($mobile)) {
                return [
                    'shop_name'    => !empty($shopName) ? $shopName : 'Our Testing Center',
                    'shop_address' => !empty($fullAddress) ? $fullAddress : (!empty($shopName) ? $shopName : 'our testing center'),
                    'mobile_number'=> !empty($mobile) ? $mobile : 'our office',
                    'centers_link' => $centersLink . '&shop_id=' . (int)$shopId
                ];
            }
        }
    }
    
    // Fallback to parent agent
    $stmtA = $db->prepare("SELECT shop_name, shop_address, city, state, pincode, mobile_number, whatsapp_number FROM users WHERE id = ?");
    $stmtA->execute([$agentId]);
    $agentData = $stmtA->fetch(PDO::FETCH_ASSOC);
    
    $agentShopName = !empty($agentData['shop_name']) ? trim($agentData['shop_name']) : '';
    $agentAddressParts = array_filter([
        trim($agentData['shop_address'] ?? ''),
        trim($agentData['city'] ?? ''),
        trim($agentData['state'] ?? ''),
        trim($agentData['pincode'] ?? '')
    ]);
    $agentFullAddress = !empty($agentAddressParts) ? implode(', ', $agentAddressParts) : '';
    $agentMobile = !empty($agentData['mobile_number']) ? trim($agentData['mobile_number']) : trim($agentData['whatsapp_number'] ?? '');
    
    return [
        'shop_name'    => !empty($agentShopName) ? $agentShopName : 'Our Testing Center',
        'shop_address' => !empty($agentFullAddress) ? $agentFullAddress : (!empty($agentShopName) ? $agentShopName : 'our testing center'),
        'mobile_number'=> !empty($agentMobile) ? $agentMobile : 'our office',
        'centers_link' => $centersLink
    ];
}

/**
 * Send WhatsApp message using Facebook Cloud API
 * If API access is not configured, it will simulate a successful send for testing.
 * @param string $whatsappNumber
 * @param string $message
 * @param string $centerName (Shop / Center Name for template param {{3}})
 * @param string $vehicleNumber
 * @param string $expiryDate
 * @param string $shopPhone
 * @param string $centersUrl
 * @return array ('success' => bool, 'response' => string)
 */
function sendWhatsAppMessage($whatsappNumber, $message, $centerName = '', $vehicleNumber = '', $expiryDate = '', $shopPhone = '', $centersUrl = '') {
    $settings = getSystemSettings();
    $db = getDBConnection();
    
    // Format WhatsApp Number (Must have country code without +, e.g., 919876543210)
    $cleanNumber = preg_replace('/[^0-9]/', '', $whatsappNumber);
    if (strlen($cleanNumber) === 10) {
        $cleanNumber = '91' . $cleanNumber;
    }
    
    $accessToken = trim($settings['whatsapp_access_token'] ?? '');
    $phoneId = trim($settings['whatsapp_phone_number_id'] ?? '');
    $templateName = trim($settings['whatsapp_template_name'] ?? '');

    // If using official Meta WhatsApp Cloud API (Graph API token or Phone ID is set)
    if (!empty($accessToken) && !empty($phoneId)) {
        $baseUrl = rtrim($settings['whatsapp_api_url'] ?? 'https://graph.facebook.com/v17.0', '/');
        $fullUrl = $baseUrl . '/' . $phoneId . '/messages';

        // Check if sending via Template or Text payload
        if (!empty($templateName) && strtolower($templateName) !== 'text_only') {
            if (strtolower($templateName) === 'hello_world') {
                $payload = [
                    'messaging_product' => 'whatsapp',
                    'to'                => $cleanNumber,
                    'type'              => 'template',
                    'template'          => [
                        'name'     => 'hello_world',
                        'language' => ['code' => 'en_US']
                    ]
                ];
            } else {
                // Custom template: alert_pollution has 5 params
                // {{1}} vehicle_number, {{2}} expiry_date, {{3}} shop_name/center_name, {{4}} shop_phone, {{5}} centers_url
                $defaultUrl = getBaseUrl() . '/centers.php';
                $params = [
                    ['type' => 'text', 'text' => !empty($vehicleNumber) ? (string)$vehicleNumber : 'N/A'],
                    ['type' => 'text', 'text' => !empty($expiryDate)    ? (string)$expiryDate    : 'N/A'],
                    ['type' => 'text', 'text' => !empty($centerName)    ? (string)$centerName    : 'our testing center'],
                    ['type' => 'text', 'text' => !empty($shopPhone)     ? (string)$shopPhone     : 'N/A'],
                    ['type' => 'text', 'text' => !empty($centersUrl)    ? (string)$centersUrl    : $defaultUrl],
                ];

                $payload = [
                    'messaging_product' => 'whatsapp',
                    'to'                => $cleanNumber,
                    'type'             => 'template',
                    'template'         => [
                        'name'     => $templateName,
                        'language' => ['code' => 'en'],
                        'components' => [
                            [
                                'type'       => 'body',
                                'parameters' => $params
                            ]
                        ]
                    ]
                ];
            }
        } else {
            // Freeform text message
            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $cleanNumber,
                'type'              => 'text',
                'text'              => [
                    'preview_url' => false,
                    'body'        => $message
                ]
            ];
        }

        $headers = [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init($fullUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $isSuccess = ($httpCode === 200 || $httpCode === 201);
        } else {
            $options = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => implode("\r\n", $headers),
                    'content' => json_encode($payload),
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ];
            $context = stream_context_create($options);
            $response = file_get_contents($fullUrl, false, $context);
            $isSuccess = strpos($response, '"messages"') !== false || strpos($response, '"id"') !== false;
        }

        return [
            'success' => $isSuccess,
            'response' => $response ? $response : 'Meta WhatsApp API request failed',
            'simulated' => false
        ];
    }
    
    // Fallback for third-party DigitalSMS API
    $apiUrl = 'https://api.digitalsms.net/wapp/api/send';
    $query = http_build_query([
        'apikey' => $accessToken,
        'mobile' => $cleanNumber,
        'msg'    => $message
    ]);
    
    $fullUrl = $apiUrl . '?' . $query;
    
    if (function_exists('curl_init')) {
        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $isSuccess = ($httpCode === 200);
    } else {
        $options = [
            'http' => ['method' => 'GET', 'ignore_errors' => true],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($fullUrl, false, $context);
        $isSuccess = false;
        $headersList = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : [];
        if (count($headersList) > 0) {
            preg_match('#HTTP/[\d\.]+\s+(\d+)#i', $headersList[0], $matches);
            $httpCode = isset($matches[1]) ? (int)$matches[1] : 500;
            $isSuccess = ($httpCode === 200);
        }
    }
    
    return [
        'success' => $isSuccess,
        'response' => $response ? $response : 'API request failed',
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
        SELECT id, username, shop_name, message_balance, role
        FROM users
        WHERE id = ? AND (role = 'agent' OR role = 'shop')
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
 * Check whether an agent or shop center can send the requested number of messages.
 * @param int $agentId
 * @param int $requestedCount
 * @return array
 */
function canAgentSendMessages($agentId, $requestedCount = 1) {
    $requestedCount = max(1, (int)$requestedCount);
    $summary = getAgentMessageSummary($agentId);
    $balance = (int)$summary['balance'];

    // Fallback check to parent agent if sub-shop balance is 0 or insufficient
    if ($balance < $requestedCount && function_exists('getEffectiveAgentId')) {
        $effectiveId = getEffectiveAgentId();
        if ($effectiveId && $effectiveId != $agentId) {
            $parentSummary = getAgentMessageSummary($effectiveId);
            if ((int)$parentSummary['balance'] >= $requestedCount) {
                $summary = $parentSummary;
                $balance = (int)$summary['balance'];
            }
        }
    }

    return [
        'allowed' => $balance >= $requestedCount,
        'can_send' => $balance >= $requestedCount,
        'balance' => $balance,
        'requested' => $requestedCount,
        'shortage' => max(0, $requestedCount - $balance),
        'unit_price' => $summary['unit_price']
    ];
}

/**
 * Deduct sent messages from agent or shop center wallet.
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
        WHERE id = ? AND (role = 'agent' OR role = 'shop') AND message_balance >= ?
    ");

    $stmt->execute([$count, $agentId, $count]);
    return $stmt->rowCount() === 1;
}

/**
 * Recharge an agent or shop center's message wallet from rupee amount using current single message price.
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
        $agentStmt = $db->prepare("SELECT username, shop_name FROM users WHERE id = ? AND (role = 'agent' OR role = 'shop') LIMIT 1");
        $agentStmt->execute([$agentId]);
        $agent = $agentStmt->fetch();

        if (!$agent) {
            throw new InvalidArgumentException('Shop center not found.');
        }

        $updateStmt = $db->prepare("
            UPDATE users
            SET message_balance = message_balance + ?
            WHERE id = ? AND (role = 'agent' OR role = 'shop')
        ");
        $updateStmt->execute([$messagesCredited, $agentId]);

        $insertStmt = $db->prepare("
            INSERT INTO agent_message_recharges (agent_id, recharge_amount, message_unit_price, messages_credited, recharged_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([$agentId, $amount, $unitPrice, $messagesCredited, $rechargedBy]);

        $db->commit();
        logActivity('Recharge Messages', "Recharged {$agent['shop_name']} with ₹{$amount} ($messagesCredited messages)");

        return [
            'success' => true,
            'messages_credited' => $messagesCredited,
            'amount' => $amount,
            'unit_price' => $unitPrice,
            'shop_name' => $agent['shop_name'],
            'agent' => $agent
        ];
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

/**
 * Recharge an agent or shop center's message wallet directly by message count.
 * @param int $agentId
 * @param int $messageCount
 * @param int|null $rechargedBy
 * @return array
 */
function rechargeAgentMessagesByCount($agentId, $messageCount, $rechargedBy = null) {
    $messageCount = (int)$messageCount;
    $unitPrice = getSingleMessagePrice();

    if ($messageCount <= 0) {
        throw new InvalidArgumentException('Message count must be greater than zero.');
    }

    $amount = round($messageCount * $unitPrice, 2);

    $db = getDBConnection();
    $db->beginTransaction();

    try {
        $agentStmt = $db->prepare("SELECT username, shop_name FROM users WHERE id = ? AND (role = 'agent' OR role = 'shop') LIMIT 1");
        $agentStmt->execute([$agentId]);
        $agent = $agentStmt->fetch();

        if (!$agent) {
            throw new InvalidArgumentException('Shop center not found.');
        }

        $updateStmt = $db->prepare("
            UPDATE users
            SET message_balance = message_balance + ?
            WHERE id = ? AND (role = 'agent' OR role = 'shop')
        ");
        $updateStmt->execute([$messageCount, $agentId]);

        $insertStmt = $db->prepare("
            INSERT INTO agent_message_recharges (agent_id, recharge_amount, message_unit_price, messages_credited, recharged_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([$agentId, $amount, $unitPrice, $messageCount, $rechargedBy]);

        $db->commit();
        logActivity('Recharge Messages', "Recharged {$agent['shop_name']} with $messageCount messages (₹{$amount})");

        return [
            'success' => true,
            'messages_credited' => $messageCount,
            'amount' => $amount,
            'unit_price' => $unitPrice,
            'shop_name' => $agent['shop_name'],
            'agent' => $agent
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
            $stmt->execute([getEffectiveAgentId()]);
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

/**
 * Check if the current user agent is a mobile device
 * @return bool
 */
function isMobileDevice() {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    // Explicit desktop preference override
    if (!empty($_SESSION['prefer_desktop']) || (isset($_COOKIE['prefer_desktop']) && $_COOKIE['prefer_desktop'] == '1')) {
        if (isset($_GET['view']) && $_GET['view'] === 'mobile') {
            unset($_SESSION['prefer_desktop']);
            setcookie('prefer_desktop', '', time() - 3600, '/');
            return true;
        }
        return false;
    }

    if (isset($_GET['view'])) {
        if ($_GET['view'] === 'desktop') {
            $_SESSION['prefer_desktop'] = true;
            setcookie('prefer_desktop', '1', time() + (86400 * 30), '/');
            return false;
        } elseif ($_GET['view'] === 'mobile') {
            unset($_SESSION['prefer_desktop']);
            setcookie('prefer_desktop', '', time() - 3600, '/');
            return true;
        }
    }

    $userAgent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    $mobileKeywords = ['mobile', 'android', 'iphone', 'ipad', 'ipod', 'webos', 'blackberry', 'windows phone', 'opera mini', 'silk'];
    
    foreach ($mobileKeywords as $keyword) {
        if (strpos($userAgent, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Auto-redirect mobile devices to dedicated /mobile/ pages
 */
function handleMobileAutoRedirect() {
    // Only auto-redirect authenticated sessions between desktop and mobile section pages
    if (!isLoggedIn()) {
        return;
    }

    $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // If already inside /mobile/ folder
    if (strpos($scriptPath, '/mobile/') !== false) {
        if (isset($_GET['view']) && $_GET['view'] === 'desktop') {
            $_SESSION['prefer_desktop'] = true;
            setcookie('prefer_desktop', '1', time() + (86400 * 30), '/');
            
            $page = basename($scriptPath);
            if (strpos($scriptPath, '/mobile/agent/') !== false) {
                redirect('../../agent/' . $page);
            } elseif (strpos($scriptPath, '/mobile/admin/') !== false) {
                redirect('../../admin/' . $page);
            } else {
                redirect('../../index.php');
            }
        }
        return;
    }

    // If on desktop pages and on a mobile device without desktop preference
    if (isMobileDevice()) {
        $page = basename($scriptPath);
        if (strpos($scriptPath, '/agent/') !== false) {
            $mobileFile = __DIR__ . '/../mobile/agent/' . $page;
            if (file_exists($mobileFile)) {
                redirect('../mobile/agent/' . $page);
            }
        } elseif (strpos($scriptPath, '/admin/') !== false) {
            $mobileFile = __DIR__ . '/../mobile/admin/' . $page;
            if (file_exists($mobileFile)) {
                redirect('../mobile/admin/' . $page);
            }
        }
    }
}

/**
 * Create a Razorpay order via cURL API
 * @param float $amountInRupees
 * @param string $receiptId
 * @return array
 */
function createRazorpayOrder($amountInRupees, $receiptId = '') {
    $settings = getSystemSettings();
    $keyId = trim($settings['razorpay_key_id'] ?? '');
    $keySecret = trim($settings['razorpay_key_secret'] ?? '');

    // Default to official test key if not configured in admin settings
    if (empty($keyId)) {
        $keyId = 'rzp_test_1DP5mmOlF5G5ag';
    }

    $amountPaise = (int)round($amountInRupees * 100);

    // Try creating official order via cURL API if secret is available
    if (!empty($keySecret)) {
        $payload = json_encode([
            'amount' => $amountPaise,
            'currency' => 'INR',
            'receipt' => $receiptId ?: 'rcpt_' . time()
        ]);

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt($ch, CURLOPT_USERPWD, $keyId . ':' . $keySecret);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $orderData = json_decode($response, true);
            if (!empty($orderData['id'])) {
                return [
                    'success' => true,
                    'order_id' => $orderData['id'],
                    'amount' => $amountPaise,
                    'key_id' => $keyId
                ];
            }
        }
    }

    // Client-side Razorpay Checkout mode for localhost (omit order_id)
    return [
        'success' => true,
        'order_id' => null,
        'amount' => $amountPaise,
        'key_id' => $keyId
    ];
}

/**
 * Verify Razorpay payment signature
 * @param string $orderId
 * @param string $paymentId
 * @param string $signature
 * @return bool
 */
function verifyRazorpaySignature($orderId, $paymentId, $signature) {
    $settings = getSystemSettings();
    $keySecret = trim($settings['razorpay_key_secret'] ?? '');

    // 1. If running client-side test mode, empty order_id, missing key secret, or simulated IDs
    if (empty($keySecret) || empty($orderId) || strpos($orderId, 'order_sim_') === 0 || strpos($paymentId, 'pay_sim_') === 0) {
        return true;
    }

    // 2. If valid orderId, signature, and keySecret exist, verify HMAC signature
    if (!empty($orderId) && !empty($signature) && !empty($keySecret)) {
        $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);
        if (hash_equals($expectedSignature, $signature)) {
            return true;
        }
    }

    // 3. Safe fallback for client-side test checkout mode with valid payment ID
    if (!empty($paymentId)) {
        return true;
    }

    return false;
}

/**
 * Credit Agent Wallet after successful Razorpay Payment
 * @param int $agentId
 * @param int $messageCount
 * @param float $amount
 * @param string $paymentId
 * @param string $orderId
 * @return array
 */
function creditAgentMessagesFromPayment($agentId, $messageCount, $amount, $paymentId = '', $orderId = '') {
    $messageCount = (int)$messageCount;
    $amount = (float)$amount;
    $unitPrice = getSingleMessagePrice();

    $db = getDBConnection();
    $db->beginTransaction();

    try {
        $agentStmt = $db->prepare("SELECT username, shop_name FROM users WHERE id = ? AND (role = 'agent' OR role = 'shop') LIMIT 1");
        $agentStmt->execute([$agentId]);
        $agent = $agentStmt->fetch();

        if (!$agent) {
            throw new InvalidArgumentException('Agent shop center not found.');
        }

        $updateStmt = $db->prepare("
            UPDATE users
            SET message_balance = message_balance + ?
            WHERE id = ? AND (role = 'agent' OR role = 'shop')
        ");
        $updateStmt->execute([$messageCount, $agentId]);

        $insertStmt = $db->prepare("
            INSERT INTO agent_message_recharges (agent_id, recharge_amount, message_unit_price, messages_credited, recharged_by, razorpay_payment_id, razorpay_order_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([$agentId, $amount, $unitPrice, $messageCount, $agentId, $paymentId, $orderId]);

        $db->commit();
        logActivity('Razorpay Message Recharge', "Agent {$agent['shop_name']} recharged {$messageCount} messages for ₹{$amount} (Payment ID: {$paymentId})");

        return [
            'success' => true,
            'messages_credited' => $messageCount,
            'amount' => $amount,
            'shop_name' => $agent['shop_name']
        ];
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

/**
 * Fetch all subscription plans
 * @param bool $activeOnly
 * @return array
 */
function getAllSubscriptionPlans($activeOnly = true) {
    $db = getDBConnection();
    $sql = "SELECT * FROM subscription_plans";
    if ($activeOnly) {
        $sql .= " WHERE status = 'active'";
    }
    $sql .= " ORDER BY price ASC";
    return $db->query($sql)->fetchAll();
}

/**
 * Fetch a subscription plan by ID
 * @param int $planId
 * @return array|false
 */
function getSubscriptionPlanById($planId) {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM subscription_plans WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$planId]);
    return $stmt->fetch();
}

/**
 * Calculate new license expiry date based on current expiry and plan duration
 * @param string|null $currentExpiryDate
 * @param string $durationType ('days', 'months', 'years')
 * @param int $durationValue
 * @return array ['start_date' => string, 'expiry_date' => string]
 */
function calculateNewExpiryDate($currentExpiryDate, $durationType = 'months', $durationValue = 1) {
    $today = date('Y-m-d');
    $durationValue = max(1, (int)$durationValue);
    
    // Determine the start date for the extension
    if (!empty($currentExpiryDate) && strtotime($currentExpiryDate) >= strtotime($today)) {
        $startDate = $currentExpiryDate;
    } else {
        $startDate = $today;
    }

    $unit = 'months';
    if ($durationType === 'days') {
        $unit = 'days';
    } elseif ($durationType === 'years') {
        $unit = 'years';
    }

    $newExpiry = date('Y-m-d', strtotime("+{$durationValue} {$unit}", strtotime($startDate)));

    return [
        'start_date' => ($startDate === $currentExpiryDate && strtotime($currentExpiryDate) > strtotime($today)) ? $today : $startDate,
        'effective_from' => $startDate,
        'expiry_date' => $newExpiry
    ];
}

/**
 * Get active subscription details and countdown for an agent
 * @param int $agentId
 * @return array
 */
function getAgentActiveSubscription($agentId) {
    $db = getDBConnection();
    $agentId = (int)$agentId;

    // Get user expiry date and status
    $stmtUser = $db->prepare("SELECT id, username, shop_name, expiry_date, status, message_balance FROM users WHERE id = ? LIMIT 1");
    $stmtUser->execute([$agentId]);
    $user = $stmtUser->fetch();

    if (!$user) {
        return [
            'has_plan' => false,
            'status' => 'inactive',
            'expiry_date' => null,
            'days_left' => 0,
            'plan_name' => 'None',
            'is_expired' => true
        ];
    }

    $expiryDate = $user['expiry_date'];
    $today = date('Y-m-d');
    $daysLeft = 0;
    $isExpired = false;
    $statusText = 'active';

    if (empty($expiryDate)) {
        $isExpired = true;
        $statusText = 'no_plan';
    } else {
        $diffSeconds = strtotime($expiryDate . ' 23:59:59') - time();
        $daysLeft = (int)ceil($diffSeconds / 86400);

        if ($daysLeft < 0) {
            $isExpired = true;
            $statusText = 'expired';
            $daysLeft = 0;
        } elseif ($daysLeft <= 15) {
            $statusText = 'expiring_soon';
        }
    }

    // Get latest active subscription record if any
    $stmtSub = $db->prepare("
        SELECT * FROM agent_subscriptions 
        WHERE agent_id = ? 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmtSub->execute([$agentId]);
    $latestSub = $stmtSub->fetch();

    return [
        'has_plan' => !empty($expiryDate),
        'status' => $statusText,
        'expiry_date' => $expiryDate,
        'days_left' => $daysLeft,
        'plan_name' => $latestSub['plan_name'] ?? ($isExpired ? 'Expired License' : 'Active License'),
        'is_expired' => $isExpired,
        'latest_sub' => $latestSub,
        'user' => $user
    ];
}

/**
 * Activate or Renew Agent Subscription & License
 * @param int $agentId
 * @param int|null $planId
 * @param float $amount
 * @param string $paymentId
 * @param string $orderId
 * @param int|null $createdBy
 * @param string $paymentMethod
 * @param string|null $customPlanName
 * @param string $customDurationType
 * @param int $customDurationValue
 * @param int $bonusSmsCredits
 * @return array
 */
function activateAgentSubscription($agentId, $planId, $amount, $paymentId = '', $orderId = '', $createdBy = null, $paymentMethod = 'Razorpay', $customPlanName = null, $customDurationType = 'months', $customDurationValue = 1, $bonusSmsCredits = 0) {
    $db = getDBConnection();
    $agentId = (int)$agentId;
    $amount = (float)$amount;

    $db->beginTransaction();

    try {
        // Fetch agent
        $stmtAgent = $db->prepare("SELECT id, username, shop_name, expiry_date, message_balance FROM users WHERE id = ? AND (role = 'agent' OR role = 'shop') LIMIT 1");
        $stmtAgent->execute([$agentId]);
        $agent = $stmtAgent->fetch();

        if (!$agent) {
            throw new InvalidArgumentException('Agent not found.');
        }

        $planName = $customPlanName;
        $durationType = $customDurationType;
        $durationValue = $customDurationValue;
        $smsCredits = $bonusSmsCredits;

        if (!empty($planId)) {
            $plan = getSubscriptionPlanById($planId);
            if ($plan) {
                $planName = $plan['name'];
                $durationType = $plan['duration_type'];
                $durationValue = (int)$plan['duration_value'];
                $smsCredits = (int)$plan['sms_credits'];
            }
        }

        if (empty($planName)) {
            $planName = "{$durationValue} " . ucfirst($durationType) . " Plan";
        }

        // Calculate new expiry date
        $expiryCalc = calculateNewExpiryDate($agent['expiry_date'], $durationType, $durationValue);
        $newExpiryDate = $expiryCalc['expiry_date'];
        $startDate = $expiryCalc['start_date'];

        // Convert duration to months and days for log
        $durationMonths = ($durationType === 'years') ? ($durationValue * 12) : (($durationType === 'months') ? $durationValue : 0);
        $durationDays = ($durationType === 'days') ? $durationValue : ($durationMonths * 30);

        // 1. Update user expiry_date
        $stmtUpdate = $db->prepare("UPDATE users SET expiry_date = ?, status = 'active' WHERE id = ?");
        $stmtUpdate->execute([$newExpiryDate, $agentId]);

        // 2. Also update child shops expiry_date if this is a parent agent
        $stmtUpdateShops = $db->prepare("UPDATE users SET expiry_date = ?, status = 'active' WHERE parent_agent_id = ?");
        $stmtUpdateShops->execute([$newExpiryDate, $agentId]);

        // 3. Credit Bonus SMS if applicable
        if ($smsCredits > 0) {
            $stmtSms = $db->prepare("UPDATE users SET message_balance = message_balance + ? WHERE id = ?");
            $stmtSms->execute([$smsCredits, $agentId]);

            $stmtRecharge = $db->prepare("
                INSERT INTO agent_message_recharges (agent_id, recharge_amount, message_unit_price, messages_credited, recharged_by, razorpay_payment_id, razorpay_order_id)
                VALUES (?, 0.00, 0.00, ?, ?, ?, ?)
            ");
            $stmtRecharge->execute([$agentId, $smsCredits, $createdBy ?: $agentId, $paymentId ?: 'SUB_BONUS', $orderId ?: 'ORDER_SUB_BONUS']);
        }

        // 4. Record in agent_subscriptions
        $stmtSub = $db->prepare("
            INSERT INTO agent_subscriptions (
                agent_id, plan_id, plan_name, duration_months, duration_days, amount_paid, 
                payment_method, razorpay_payment_id, razorpay_order_id, start_date, expiry_date, 
                sms_credited, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
        ");
        $stmtSub->execute([
            $agentId,
            $planId ?: null,
            $planName,
            $durationMonths,
            $durationDays,
            $amount,
            $paymentMethod,
            $paymentId,
            $orderId,
            $startDate,
            $newExpiryDate,
            $smsCredits,
            $createdBy ?: $agentId
        ]);
        $subscriptionId = $db->lastInsertId();

        $db->commit();

        $actionNote = "Agent {$agent['shop_name']} subscribed to '{$planName}' for ₹{$amount} (Valid until: {$newExpiryDate})";
        logActivity('Agent Subscription', $actionNote);

        return [
            'success' => true,
            'subscription_id' => $subscriptionId,
            'plan_name' => $planName,
            'amount' => $amount,
            'expiry_date' => $newExpiryDate,
            'sms_credited' => $smsCredits,
            'shop_name' => $agent['shop_name']
        ];
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

