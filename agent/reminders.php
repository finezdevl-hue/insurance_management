<?php
/**
 * Expiry & Renewal Alerts Control Center (Agent View)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce Agent Access
checkAccess('agent');
if (!hasAgentAccess('vehicle') && !hasAgentAccess('pollution')) {
    $_SESSION['alert_error'] = 'Access denied. You do not have permissions for Vehicle or Pollution services.';
    redirect('index.php');
}

$db = getDBConnection();
$agentId = $_SESSION['user_id'];

$pageTitle = 'Renewal Reminders Control Room';
$pageHeading = 'Expiry & WhatsApp Renewals';
$activePage = 'reminders';
$messageSummary = getAgentMessageSummary($agentId);

// --- PROCESS CONTROLLER ACTIONS ---

// A. Individual One-Click WhatsApp dispatch (similar to dashboard handler)
if (isset($_POST['send_single_alert'])) {
    $custId = (int)$_POST['customer_id'];
    $vehicleId = (int)$_POST['vehicle_id'];
    $remType = $_POST['reminder_type'];
    $expiryVal = $_POST['expiry_date'];
    $senderUserId = $_SESSION['user_id'];
    $quotaCheck = canAgentSendMessages($senderUserId, 1);

    if (!$quotaCheck['allowed']) {
        $_SESSION['alert_error'] = getMessageLimitError($quotaCheck['balance'], $quotaCheck['requested']);
        redirect('reminders.php');
    }
    
    $stmtC = $db->prepare("SELECT name, whatsapp_number FROM customers WHERE id = ? AND agent_id = ?");
    $stmtC->execute([$custId, $agentId]);
    $customer = $stmtC->fetch();
    
    $stmtV = $db->prepare("SELECT vehicle_number FROM vehicles WHERE id = ? AND agent_id = ?");
    $stmtV->execute([$vehicleId, $agentId]);
    $vehNumber = $stmtV->fetchColumn();
    
    if ($customer && $vehNumber) {
        $name = $customer['name'];
        $whatsapp = $customer['whatsapp_number'];
        // Fetch specific creator shop or parent agent details for this reminder
        $shopInfo = getShopDetailsForReminder($vehicleId, $remType, $agentId);
        $shopName = $shopInfo['shop_name'];
        $shopAddress = $shopInfo['shop_address'];
        $agentMobile = $shopInfo['mobile_number'];
        $centersUrl = $shopInfo['centers_link'];
        
        $centerLocationText = $shopName;
        if (!empty($shopAddress) && $shopAddress !== $shopName && $shopAddress !== 'our testing center') {
            $centerLocationText .= ' (' . $shopAddress . ')';
        }
        
        if ($remType === 'Pollution') {
            $message = "Dear Customer,\n\nThis is to remind you that the Pollution Certificate (PUC) for your vehicle {$vehNumber} is expiring on {$expiryVal}.\n\nPlease visit our testing center at {$centerLocationText} (Phone: {$agentMobile}) to renew it and avoid penalties.\n\nView Testing Centers & Directions:\n{$centersUrl}\n\nThank You.";
        } else {
            $message = "Dear Customer,\n\nThis is an important reminder that the {$remType} for your vehicle {$vehNumber} is expiring on {$expiryVal}.\n\nPlease contact us at {$agentMobile} to process your renewal and ensure continuous validity.\n\nView Testing Centers & Details:\n{$centersUrl}";
        }
        
        $apiResult = sendWhatsAppMessage($whatsapp, $message, $shopName, $vehNumber, $expiryVal, $agentMobile, $centersUrl);
        $status = $apiResult['success'] ? 'sent' : 'failed';
        
        $daysUntil = getDaysUntil($expiryVal);
        $period = ($daysUntil <= 1) ? '1 Day' : (($daysUntil <= 7) ? '7 Days' : (($daysUntil <= 15) ? '15 Days' : '30 Days'));
        
        $stmtHistory = $db->prepare("
            INSERT INTO reminder_history (customer_id, vehicle_id, reminder_type, reminder_period, sent_by_user_id, status, message, api_response)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtHistory->execute([$custId, $vehicleId, $remType, $period, $agentId, $status, $message, $apiResult['response']]);
        
        if ($apiResult['success']) {
            deductAgentMessages($senderUserId, 1);
            $_SESSION['alert_success'] = "WhatsApp alert sent successfully to {$name}!";
        } else {
            $_SESSION['alert_error'] = 'API dispatch failed: ' . $apiResult['response'];
        }
        
        logActivity('WhatsApp Reminder Sent', "Sent individual $remType alert to $name (Vehicle: $vehNumber)");
    }
    redirect('reminders.php');
}

// B. Bulk WhatsApp dispatch (Sends to ALL clients expiring in next 30 days)
if (isset($_POST['send_bulk_alerts'])) {
    $parts = [];
    $params = [];
    
    if (hasAgentAccess('vehicle')) {
        $parts[] = "
            SELECT 'Insurance' as type, i.expiry_date, v.vehicle_number, c.name as customer_name, c.whatsapp_number, c.id as customer_id, v.id as vehicle_id
            FROM insurances i
            JOIN vehicles v ON i.vehicle_id = v.id
            JOIN customers c ON v.customer_id = c.id
            WHERE i.agent_id = ? AND i.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ";
        $params[] = $agentId;
    }
    
    if (hasAgentAccess('pollution')) {
        $parts[] = "
            SELECT 'Pollution' as type, p.expiry_date, v.vehicle_number, c.name as customer_name, c.whatsapp_number, c.id as customer_id, v.id as vehicle_id
            FROM pollution_certificates p
            JOIN vehicles v ON p.vehicle_id = v.id
            JOIN customers c ON v.customer_id = c.id
            WHERE p.agent_id = ? AND p.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ";
        $params[] = $agentId;
    }
    
    if (count($parts) > 0) {
        $stmt = $db->prepare(implode(" UNION ALL ", $parts));
        $stmt->execute($params);
        $allRecs = $stmt->fetchAll();
    } else {
        $allRecs = [];
    }
    $requiredMessages = count($allRecs);

    if ($requiredMessages > 0) {
        $senderUserId = $_SESSION['user_id'];
        $quotaCheck = canAgentSendMessages($senderUserId, $requiredMessages);
        if (!$quotaCheck['allowed']) {
            $_SESSION['alert_error'] = getMessageLimitError($quotaCheck['balance'], $quotaCheck['requested']);
            redirect('reminders.php');
        }
    }
    
    $successCount = 0;
    $failedCount = 0;
    
    foreach ($allRecs as $rec) {
        $name = $rec['customer_name'];
        $whatsapp = $rec['whatsapp_number'];
        $vehNumber = $rec['vehicle_number'];
        $remType = $rec['type'];
        $expiryVal = $rec['expiry_date'];
        
        // Fetch specific creator shop or parent agent details for this reminder
        $shopInfo = getShopDetailsForReminder($rec['vehicle_id'], $remType, $agentId);
        $shopName = $shopInfo['shop_name'];
        $shopAddress = $shopInfo['shop_address'];
        $agentMobile = $shopInfo['mobile_number'];
        $centersUrl = $shopInfo['centers_link'];
        
        $centerLocationText = $shopName;
        if (!empty($shopAddress) && $shopAddress !== $shopName && $shopAddress !== 'our testing center') {
            $centerLocationText .= ' (' . $shopAddress . ')';
        }
        
        if ($remType === 'Pollution') {
            $message = "Dear Customer,\n\nThis is to remind you that the Pollution Certificate (PUC) for your vehicle {$vehNumber} is expiring on {$expiryVal}.\n\nPlease visit our testing center at {$centerLocationText} (Phone: {$agentMobile}) to renew it and avoid penalties.\n\nView Testing Centers & Directions:\n{$centersUrl}\n\nThank You.";
        } else {
            $message = "Dear Customer,\n\nThis is an important reminder that the {$remType} for your vehicle {$vehNumber} is expiring on {$expiryVal}.\n\nPlease contact us at {$agentMobile} to process your renewal and ensure continuous validity.\n\nView Testing Centers & Details:\n{$centersUrl}";
        }
        
        $apiResult = sendWhatsAppMessage($whatsapp, $message, $shopName, $vehNumber, $expiryVal, $agentMobile, $centersUrl);
        $status = $apiResult['success'] ? 'sent' : 'failed';
        
        if ($apiResult['success']) {
            $successCount++;
        } else {
            $failedCount++;
        }
        
        $daysUntil = getDaysUntil($expiryVal);
        $period = ($daysUntil <= 1) ? '1 Day' : (($daysUntil <= 7) ? '7 Days' : (($daysUntil <= 15) ? '15 Days' : '30 Days'));
        
        $stmtHistory = $db->prepare("
            INSERT INTO reminder_history (customer_id, vehicle_id, reminder_type, reminder_period, sent_by_user_id, status, message, api_response)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtHistory->execute([$rec['customer_id'], $rec['vehicle_id'], $remType, $period, $agentId, $status, $message, $apiResult['response']]);
    }

    if ($successCount > 0) {
        deductAgentMessages($senderUserId, $successCount);
    }
    
    logActivity('Bulk WhatsApp Reminders', "Fired bulk alerts. Success: $successCount, Failed: $failedCount");
    
    if ($successCount > 0 || $failedCount > 0) {
        $_SESSION['alert_success'] = "Bulk dispatch completed. Delivered: $successCount, Failed: $failedCount.";
    } else {
        $_SESSION['alert_info'] = 'No upcoming renewals found to trigger bulk alerts.';
    }
    redirect('reminders.php');
}

// Fetch all expiring and pre-expired records belonging to this agent
// 1. Pre-Expired List
$partsExp = [];
$paramsExp = [];

if (hasAgentAccess('vehicle')) {
    $partsExp[] = "
        SELECT 'Insurance' as type, i.expiry_date, v.vehicle_number, c.name as customer_name, c.mobile_number, c.id as customer_id, v.id as vehicle_id
        FROM insurances i
        JOIN vehicles v ON i.vehicle_id = v.id
        JOIN customers c ON v.customer_id = c.id
        WHERE i.agent_id = ? AND i.expiry_date < CURDATE()
    ";
    $paramsExp[] = $agentId;
}

if (hasAgentAccess('pollution')) {
    $partsExp[] = "
        SELECT 'Pollution' as type, p.expiry_date, v.vehicle_number, c.name as customer_name, c.mobile_number, c.id as customer_id, v.id as vehicle_id
        FROM pollution_certificates p
        JOIN vehicles v ON p.vehicle_id = v.id
        JOIN customers c ON v.customer_id = c.id
        WHERE p.agent_id = ? AND p.expiry_date < CURDATE()
    ";
    $paramsExp[] = $agentId;
}

if (count($partsExp) > 0) {
    $stmtExp = $db->prepare(implode(" UNION ALL ", $partsExp) . " ORDER BY expiry_date DESC");
    $stmtExp->execute($paramsExp);
    $expiredRecords = $stmtExp->fetchAll();
} else {
    $expiredRecords = [];
}

// 2. Upcoming Renewals (next 30 days)
$partsUp = [];
$paramsUp = [];

if (hasAgentAccess('vehicle')) {
    $partsUp[] = "
        SELECT 'Insurance' as type, i.expiry_date, v.vehicle_number, c.name as customer_name, c.mobile_number, c.id as customer_id, v.id as vehicle_id
        FROM insurances i
        JOIN vehicles v ON i.vehicle_id = v.id
        JOIN customers c ON v.customer_id = c.id
        WHERE i.agent_id = ? AND i.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ";
    $paramsUp[] = $agentId;
}

if (hasAgentAccess('pollution')) {
    $partsUp[] = "
        SELECT 'Pollution' as type, p.expiry_date, v.vehicle_number, c.name as customer_name, c.mobile_number, c.id as customer_id, v.id as vehicle_id
        FROM pollution_certificates p
        JOIN vehicles v ON p.vehicle_id = v.id
        JOIN customers c ON v.customer_id = c.id
        WHERE p.agent_id = ? AND p.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ";
    $paramsUp[] = $agentId;
}

if (count($partsUp) > 0) {
    $stmtUp = $db->prepare(implode(" UNION ALL ", $partsUp) . " ORDER BY expiry_date ASC");
    $stmtUp->execute($paramsUp);
    $upcomingRecords = $stmtUp->fetchAll();
} else {
    $upcomingRecords = [];
}

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Actions console -->
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <h6 class="font-weight-700 m-0 text-main"><i class="fa-solid fa-wallet text-primary me-2"></i>Message Wallet</h6>
            <p class="m-0 small text-muted">Remaining messages: <strong><?php echo (int)$messageSummary['balance']; ?></strong></p>
        </div>
        <div class="text-md-end">
            <?php if ((int)$messageSummary['balance'] <= 0): ?>
                <span class="badge bg-danger">No message count left. Contact admin.</span>
            <?php else: ?>
                <span class="badge bg-success"><?php echo (int)$messageSummary['balance']; ?> messages available</span>
                <small class="d-block text-muted mt-1">Rate: Rs <?php echo number_format((float)$messageSummary['unit_price'], 2); ?> per message</small>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Header Actions console -->
<div class="card shadow-sm border-0 mb-4 bg-light">
    <div class="card-body py-3 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div>
            <h6 class="font-weight-700 m-0 text-main"><i class="fa-solid fa-bullhorn text-success me-2"></i>Reminders Control Station</h6>
            <p class="m-0 small text-muted">Send automated renewal warnings via official WhatsApp API integrations or simulations.</p>
        </div>
        <form action="" method="POST" id="bulkForm">
            <button type="submit" name="send_bulk_alerts" class="btn btn-success btn-sm w-100" id="bulkBtn">
                <i class="fa-brands fa-whatsapp me-1"></i> Send Bulk 30-Day Alerts
            </button>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <!-- Navigation Tabs -->
                <ul class="nav nav-tabs border-bottom mb-0" id="expiryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-2.5 px-4 font-weight-600 border-0 border-bottom" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab" aria-controls="upcoming" aria-selected="true">
                            <i class="fa-solid fa-bell me-2 text-warning"></i> Upcoming Expiries (30 Days)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-2.5 px-4 font-weight-600 border-0 border-bottom" id="expired-tab" data-bs-toggle="tab" data-bs-target="#expired" type="button" role="tab" aria-controls="expired" aria-selected="false">
                            <i class="fa-solid fa-circle-exclamation me-2 text-danger"></i> Pre-Expired Records
                        </button>
                    </li>
                </ul>
            </div>
            
            <div class="card-body">
                <div class="tab-content" id="expiryTabsContent">
                    
                    <!-- UPCOMING TAB -->
                    <div class="tab-pane fade show active" id="upcoming" role="tabpanel" aria-labelledby="upcoming-tab">
                        <table class="table table-hover align-middle datatable w-100">
                            <thead>
                                <tr>
                                    <th>Vehicle Number</th>
                                    <th>Owner Profile</th>
                                    <th>Renewal Target</th>
                                    <th>Expiry Date</th>
                                    <th>Status Badges</th>
                                    <th class="text-end">Notify</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingRecords as $row): 
                                    $status = getExpiryStatus($row['expiry_date']);
                                ?>
                                    <tr>
                                        <td><strong class="text-primary"><?php echo sanitize($row['vehicle_number']); ?></strong></td>
                                        <td>
                                            <span class="d-block font-weight-600 text-main small"><?php echo sanitize($row['customer_name']); ?></span>
                                            <small class="text-muted"><i class="fa-solid fa-phone"></i> <?php echo sanitize($row['mobile_number']); ?></small>
                                        </td>
                                        <td>
                                            <span class="small font-weight-600">
                                                <i class="fa-solid <?php echo ($row['type'] === 'Insurance') ? 'fa-building-shield text-primary' : (($row['type'] === 'Pollution') ? 'fa-wind text-info' : 'fa-car text-warning'); ?> me-1"></i>
                                                <?php echo sanitize($row['type']); ?> Expiry
                                            </span>
                                        </td>
                                        <td><strong><?php echo date('d-M-Y', strtotime($row['expiry_date'])); ?></strong></td>
                                        <td>
                                            <span class="badge <?php echo $status['badge']; ?>">
                                                <?php echo sanitize($status['text']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="customer_id" value="<?php echo $row['customer_id']; ?>">
                                                <input type="hidden" name="vehicle_id" value="<?php echo $row['vehicle_id']; ?>">
                                                <input type="hidden" name="reminder_type" value="<?php echo $row['type']; ?>">
                                                <input type="hidden" name="expiry_date" value="<?php echo $row['expiry_date']; ?>">
                                                
                                                <button type="submit" name="send_single_alert" class="btn btn-sm btn-success px-2.5 py-1">
                                                    <i class="fa-brands fa-whatsapp me-1"></i> Remind
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- EXPIRED TAB -->
                    <div class="tab-pane fade" id="expired" role="tabpanel" aria-labelledby="expired-tab">
                        <table class="table table-hover align-middle datatable w-100">
                            <thead>
                                <tr>
                                    <th>Vehicle Number</th>
                                    <th>Owner Profile</th>
                                    <th>Renewal Target</th>
                                    <th>Expired Date</th>
                                    <th>Status Badges</th>
                                    <th class="text-end">Notify</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expiredRecords as $row): 
                                    $status = getExpiryStatus($row['expiry_date']);
                                ?>
                                    <tr>
                                        <td><strong class="text-primary"><?php echo sanitize($row['vehicle_number']); ?></strong></td>
                                        <td>
                                            <span class="d-block font-weight-600 text-main small"><?php echo sanitize($row['customer_name']); ?></span>
                                            <small class="text-muted"><i class="fa-solid fa-phone"></i> <?php echo sanitize($row['mobile_number']); ?></small>
                                        </td>
                                        <td>
                                            <span class="small font-weight-600">
                                                <i class="fa-solid <?php echo ($row['type'] === 'Insurance') ? 'fa-building-shield text-primary' : (($row['type'] === 'Pollution') ? 'fa-wind text-info' : 'fa-car text-warning'); ?> me-1"></i>
                                                <?php echo sanitize($row['type']); ?> Expiry
                                            </span>
                                        </td>
                                        <td><strong class="text-danger"><?php echo date('d-M-Y', strtotime($row['expiry_date'])); ?></strong></td>
                                        <td>
                                            <span class="badge <?php echo $status['badge']; ?>">
                                                <?php echo sanitize($status['text']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="customer_id" value="<?php echo $row['customer_id']; ?>">
                                                <input type="hidden" name="vehicle_id" value="<?php echo $row['vehicle_id']; ?>">
                                                <input type="hidden" name="reminder_type" value="<?php echo $row['type']; ?>">
                                                <input type="hidden" name="expiry_date" value="<?php echo $row['expiry_date']; ?>">
                                                
                                                <button type="submit" name="send_single_alert" class="btn btn-sm btn-danger px-2.5 py-1">
                                                    <i class="fa-brands fa-whatsapp me-1"></i> Warn Expiry
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#bulkBtn').on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Trigger Bulk Expiry Alerts?',
            text: "This will automatically iterate through ALL your upcoming renewals expiring in the next 30 days and send them WhatsApp reminder messages. Proceed?",
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, fire alerts!'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#bulkForm').submit();
            }
        });
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
