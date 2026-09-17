<?php
/**
 * Automated Notification Script (Cron Job)
 * Vehicle Details & Insurance Renewal Management System
 * Run this script via server crontab (e.g., 5 times a day) or CLI / Webhook.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Allow running from CLI, or with ?run_cron=1, or by an active Admin
$isAdminUser = isLoggedIn() && hasRole('admin');
if (php_sapi_name() !== 'cli' && !isset($_GET['run_cron']) && !$isAdminUser) {
    die('This script can only be run from the command line, via server cron, or by an authenticated administrator.');
}

$db = getDBConnection();

echo "Starting Automated WhatsApp Renewal Notifications Dispatch...\n";

$stats = [
    'total_processed' => 0,
    'sent' => 0,
    'failed' => 0,
    'skipped' => 0
];

// Fetch all active agents
$stmtAgents = $db->query("SELECT * FROM users WHERE role = 'agent' AND status = 'active'");
$agents = $stmtAgents->fetchAll(PDO::FETCH_ASSOC);

foreach ($agents as $agent) {
    echo "Processing Agent: {$agent['username']}...\n";
    
    // Check if agent has message balance
    if ($agent['message_balance'] <= 0) {
        echo " - Skipped: No message balance.\n";
        $stats['skipped']++;
        continue;
    }
    
    $agentId = $agent['id'];
    $daysBefore = (int)($agent['notify_days_before'] ?? 7);
    $isEarlyEnabled = $agent['notify_before_enabled'] ?? 1;
    $is1DayEnabled = $agent['notify_1day_before_enabled'] ?? 1;
    $isAfterEnabled = $agent['notify_after_expiry_enabled'] ?? 1;
    
    // Collect all reminders to send for this agent
    $remindersToSend = [];
    
    $checkTargets = [];
    if ($isEarlyEnabled) $checkTargets[] = ['days' => $daysBefore, 'period' => "{$daysBefore} Days"];
    if ($is1DayEnabled) $checkTargets[] = ['days' => 1, 'period' => "1 Day"];
    if ($isAfterEnabled) $checkTargets[] = ['days' => -1, 'period' => "-1 Day"];
    
    foreach ($checkTargets as $target) {
        $daysDiff = $target['days'];
        $periodLabel = $target['period'];
        
        // 1. Vehicle Insurances
        if (!empty($agent['access_vehicle_insurance'])) {
            $stmt = $db->prepare("
                SELECT i.id as policy_id, i.expiry_date, v.id as vehicle_id, v.vehicle_number, 
                       c.id as customer_id, c.name as customer_name, c.whatsapp_number, c.mobile_number
                FROM insurances i
                JOIN vehicles v ON i.vehicle_id = v.id
                JOIN customers c ON v.customer_id = c.id
                WHERE i.agent_id = ? AND DATEDIFF(i.expiry_date, CURDATE()) = ?
            ");
            $stmt->execute([$agentId, $daysDiff]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $remindersToSend[] = [
                    'type' => 'Insurance',
                    'period' => $periodLabel,
                    'customer_id' => $row['customer_id'],
                    'vehicle_id' => $row['vehicle_id'],
                    'whatsapp' => $row['whatsapp_number'] ?: $row['mobile_number'],
                    'name' => $row['customer_name'],
                    'vehicle_number' => $row['vehicle_number'],
                    'expiry' => $row['expiry_date'],
                    'details' => 'Vehicle Insurance'
                ];
            }
        }
        
        // 2. Pollution Certificates (PUC)
        if (!empty($agent['access_pollution'])) {
            $stmt = $db->prepare("
                SELECT p.id as cert_id, p.expiry_date, v.id as vehicle_id, v.vehicle_number,
                       c.id as customer_id, c.name as customer_name, c.whatsapp_number, c.mobile_number
                FROM pollution_certificates p
                JOIN vehicles v ON p.vehicle_id = v.id
                JOIN customers c ON v.customer_id = c.id
                WHERE p.agent_id = ? AND DATEDIFF(p.expiry_date, CURDATE()) = ?
            ");
            $stmt->execute([$agentId, $daysDiff]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $remindersToSend[] = [
                    'type' => 'Pollution',
                    'period' => $periodLabel,
                    'customer_id' => $row['customer_id'],
                    'vehicle_id' => $row['vehicle_id'],
                    'whatsapp' => $row['whatsapp_number'] ?: $row['mobile_number'],
                    'name' => $row['customer_name'],
                    'vehicle_number' => $row['vehicle_number'],
                    'expiry' => $row['expiry_date'],
                    'details' => 'PUC'
                ];
            }
        }
        
        // 3. Health Insurances
        if (!empty($agent['access_health_insurance'])) {
            $stmt = $db->prepare("
                SELECT h.id as policy_id, h.expiry_date,
                       c.id as customer_id, c.name as customer_name, c.whatsapp_number, c.mobile_number
                FROM health_insurances h
                JOIN customers c ON h.customer_id = c.id
                WHERE h.agent_id = ? AND DATEDIFF(h.expiry_date, CURDATE()) = ?
            ");
            $stmt->execute([$agentId, $daysDiff]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $remindersToSend[] = [
                    'type' => 'Health',
                    'period' => $periodLabel,
                    'customer_id' => $row['customer_id'],
                    'vehicle_id' => null,
                    'whatsapp' => $row['whatsapp_number'] ?: $row['mobile_number'],
                    'name' => $row['customer_name'],
                    'vehicle_number' => 'Health Policy',
                    'expiry' => $row['expiry_date'],
                    'details' => 'Health Insurance'
                ];
            }
        }
    }
    
    // Process and Send Reminders
    foreach ($remindersToSend as $rem) {
        $stats['total_processed']++;
        
        // Check balance
        $agentStmt = $db->prepare("SELECT message_balance FROM users WHERE id = ?");
        $agentStmt->execute([$agentId]);
        $currentBalance = (int)$agentStmt->fetchColumn();
        if ($currentBalance <= 0) {
            echo " - Agent ran out of balance.\n";
            $stats['skipped']++;
            break;
        }
        
        // Prevent duplicate dispatch for same policy in the same cycle/period
        $isForce = (isset($_GET['force']) && $_GET['force'] == '1') || (in_array('--force', $argv ?? []));
        if (!$isForce) {
            $stmtCheck = $db->prepare("
                SELECT id FROM reminder_history 
                WHERE customer_id = ? AND vehicle_id <=> ? AND reminder_type = ? AND reminder_period = ? 
                AND DATEDIFF(CURDATE(), sent_date) < 60
            ");
            $stmtCheck->execute([$rem['customer_id'], $rem['vehicle_id'], $rem['type'], $rem['period']]);
            if ($stmtCheck->fetchColumn()) {
                echo " - Already sent {$rem['type']} ({$rem['period']}) to {$rem['name']}. Skipping.\n";
                $stats['skipped']++;
                continue;
            }
        }
        
        $targetShopId = !empty($rem['created_by_shop_id']) ? (int)$rem['created_by_shop_id'] : $agentId;
        $quota = canAgentSendMessages($targetShopId, 1);
        if (!$quota['allowed']) {
            echo " - Skipped: Shop center #{$targetShopId} has insufficient message balance.\n";
            $stats['skipped']++;
            continue;
        }
        
        // Compose message with shop details
        $shopInfo = getShopDetailsForReminder($rem['vehicle_id'], $rem['type'], $agentId);
        $shopName = $shopInfo['shop_name'];
        $shopAddress = $shopInfo['shop_address'];
        $shopMobile = $shopInfo['mobile_number'];
        $centersUrl = (defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'http://localhost/vehicle_manage') . '/centers.php?agent_id=' . $agentId;
        
        if ($rem['type'] === 'Pollution') {
            $message = "Dear Customer,\n\nThis is to remind you that the Pollution Certificate (PUC) for your vehicle {$rem['vehicle_number']} is expiring on {$rem['expiry']}.\n\nPlease visit our testing center at {$shopAddress} (Phone: {$shopMobile}) to renew it and avoid penalties.\n\nView Testing Centers & Directions:\n{$centersUrl}\n\nThank You.";
        } elseif ($rem['period'] == '-1 Day') {
            $message = "Dear Customer,\n\nURGENT: Your {$rem['type']} for vehicle {$rem['vehicle_number']} expired yesterday ({$rem['expiry']}). Please renew it immediately to avoid penalties. Contact: {$shopMobile}.\n\nView Testing Centers:\n{$centersUrl}";
        } else {
            $message = "Dear Customer,\n\nYour {$rem['type']} for vehicle {$rem['vehicle_number']} will expire on {$rem['expiry']}. Please renew it to stay protected. Contact: {$shopMobile}.\n\nView Testing Centers:\n{$centersUrl}";
        }
        
        // Send WhatsApp
        $result = sendWhatsAppMessage($rem['whatsapp'], $message, $rem['name'], $rem['vehicle_number'], $rem['expiry'], $shopMobile, $centersUrl);
        
        // Deduct message balance
        deductAgentMessages($targetShopId, 1);
        
        // Log to reminder history
        $stmtLog = $db->prepare("
            INSERT INTO reminder_history (customer_id, vehicle_id, reminder_type, reminder_period, sent_by_user_id, status, message, api_response)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $status = $result['success'] ? 'sent' : 'failed';
        $stmtLog->execute([
            $rem['customer_id'], 
            $rem['vehicle_id'], 
            $rem['type'], 
            $rem['period'], 
            $agentId, 
            $status, 
            $message, 
            $result['response']
        ]);
        
        if ($result['success']) {
            $stats['sent']++;
        } else {
            $stats['failed']++;
        }
        
        echo " - Sent {$rem['type']} ({$rem['period']}) to {$rem['name']}.\n";
    }
}

echo "Automated Notifications Finished. Sent: {$stats['sent']}, Failed: {$stats['failed']}, Skipped: {$stats['skipped']}.\n";

if (isset($_GET['redirect'])) {
    $_SESSION['alert_success'] = "Automated WhatsApp Reminders processed! [Sent: {$stats['sent']}, Failed: {$stats['failed']}, Skipped: {$stats['skipped']}]";
    redirect('../' . ltrim($_GET['redirect'], '/'));
}
?>
