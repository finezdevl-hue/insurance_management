<?php
/**
 * Automated WhatsApp Alert Instant Trigger API Endpoint
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

$agentId = getEffectiveAgentId();
$db = getDBConnection();

$action = $_REQUEST['action'] ?? '';

// 1. Send Single Automatic WhatsApp Alert
if ($action === 'send_single') {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $vehicleId = !empty($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null;
    $type = trim($_POST['type'] ?? 'Insurance');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    
    // Check Agent Wallet Balance
    $quota = canAgentSendMessages($agentId, 1);
    if (!$quota['allowed']) {
        echo json_encode(['success' => false, 'error' => 'Insufficient SMS message balance. Please recharge your wallet.']);
        exit;
    }
    
    // Fetch Customer & Vehicle Details
    $stmtC = $db->prepare("SELECT name, mobile_number, whatsapp_number FROM customers WHERE id = ?");
    $stmtC->execute([$customerId]);
    $customer = $stmtC->fetch();
    
    if (!$customer) {
        echo json_encode(['success' => false, 'error' => 'Customer record not found.']);
        exit;
    }
    
    $vehNumber = 'Vehicle';
    $expiryDate = date('Y-m-d');
    
    if ($vehicleId) {
        $stmtV = $db->prepare("SELECT vehicle_number FROM vehicles WHERE id = ?");
        $stmtV->execute([$vehicleId]);
        $vehNumber = $stmtV->fetchColumn() ?: 'Vehicle';
        
        if ($type === 'Insurance') {
            $stmtI = $db->prepare("SELECT expiry_date FROM insurances WHERE vehicle_id = ? ORDER BY expiry_date DESC LIMIT 1");
            $stmtI->execute([$vehicleId]);
            $expiryDate = $stmtI->fetchColumn() ?: date('Y-m-d');
        } elseif ($type === 'Pollution') {
            $stmtP = $db->prepare("SELECT expiry_date FROM pollution_certificates WHERE vehicle_id = ? ORDER BY expiry_date DESC LIMIT 1");
            $stmtP->execute([$vehicleId]);
            $expiryDate = $stmtP->fetchColumn() ?: date('Y-m-d');
        }
    } elseif ($type === 'Health') {
        $stmtH = $db->prepare("SELECT policy_number, expiry_date FROM health_insurances WHERE id = ?");
        $stmtH->execute([$customerId]);
        $hRow = $stmtH->fetch();
        if ($hRow) {
            $vehNumber = 'Policy #' . ($hRow['policy_number'] ?? '');
            $expiryDate = $hRow['expiry_date'];
        }
    }
    
    $shopInfo = getShopDetailsForReminder($vehicleId, $type, $agentId);
    $shopName = $shopInfo['shop_name'];
    $shopAddress = $shopInfo['shop_address'];
    $shopMobile = $shopInfo['mobile_number'];
    $centersUrl = $shopInfo['centers_link'];
    
    $centerLocationText = $shopName;
    if (!empty($shopAddress) && $shopAddress !== $shopName && $shopAddress !== 'our testing center') {
        $centerLocationText .= ' (' . $shopAddress . ')';
    }
    
    $targetNumber = !empty($whatsapp) ? $whatsapp : (!empty($customer['whatsapp_number']) ? $customer['whatsapp_number'] : $customer['mobile_number']);
    
    if ($type === 'Pollution') {
        $message = "Dear Customer,\n\nThis is an automated reminder that the Pollution Certificate (PUC) for vehicle {$vehNumber} is expiring on {$expiryDate}.\n\nPlease visit our testing center at {$centerLocationText} (Phone: {$shopMobile}) to renew it.\n\nView Testing Centers & Directions:\n{$centersUrl}\n\nThank You.";
    } else {
        $message = "Dear Customer,\n\nThis is an automated reminder that your {$type} for {$vehNumber} is expiring on {$expiryDate}.\n\nPlease renew it to stay protected. Contact {$shopName}: {$shopMobile}.\n\nView Testing Centers:\n{$centersUrl}";
    }
    
    // Execute WhatsApp Send API (passing $shopName for template param {{3}})
    $result = sendWhatsAppMessage($targetNumber, $message, $shopName, $vehNumber, $expiryDate, $shopMobile, $centersUrl);
    
    // Deduct Message Credit
    deductAgentMessages($agentId, 1);
    
    // Audit Log
    $status = !empty($result['success']) ? 'sent' : 'failed';
    $stmtLog = $db->prepare("
        INSERT INTO reminder_history (customer_id, vehicle_id, reminder_type, reminder_period, sent_by_user_id, status, message, api_response)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmtLog->execute([$customerId, $vehicleId, $type, 'Auto Instant Alert', $agentId, $status, $message, json_encode($result)]);
    
    logActivity('Send Auto Alert', "Sent instant automated WhatsApp alert to {$customer['name']} ({$targetNumber}) for {$type} on {$vehNumber}.");
    
    echo json_encode([
        'success' => true,
        'message' => "Automated WhatsApp Alert sent successfully to {$customer['name']} ({$vehNumber})!"
    ]);
    exit;
}

// 2. Trigger All Automated Expiry Reminders for Agent
if ($action === 'send_all_auto') {
    $phpCli = PHP_BINARY;
    $cronScript = __DIR__ . '/cron/send_automated_notifications.php';
    
    // Run cron process logic for current agent
    require_once __DIR__ . '/cron/send_automated_notifications.php';
    
    echo json_encode([
        'success' => true,
        'message' => "Automated Daily Renewal Reminders executed successfully for all expiring accounts!"
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action.']);
