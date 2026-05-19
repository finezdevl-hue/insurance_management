<?php
/**
 * Agent Dashboard
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce Agent Access
checkAccess('agent');

$db = getDBConnection();
$agentId = $_SESSION['user_id'];

$pageTitle = 'Agent Dashboard Portal';
$pageHeading = 'Agency Dashboard';
$activePage = 'dashboard';
$messageSummary = getAgentMessageSummary($agentId);

// --- STATISTICAL DATA (AGENT SPECIFIC) ---

// 1. Total Customers
$stmt = $db->prepare("SELECT COUNT(*) FROM customers WHERE agent_id = ?");
$stmt->execute([$agentId]);
$totalCustomers = $stmt->fetchColumn();

// 2. Total Vehicles
$stmt = $db->prepare("SELECT COUNT(*) FROM vehicles WHERE agent_id = ?");
$stmt->execute([$agentId]);
$totalVehicles = $stmt->fetchColumn();

// 3. Expiring Insurance (next 30 days)
$upcomingInsurances = 0;
if (hasAgentAccess('vehicle')) {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM insurances 
        WHERE agent_id = ? AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$agentId]);
    $upcomingInsurances = $stmt->fetchColumn();
}

// 4. Expiring Pollution (next 30 days)
$upcomingPollutions = 0;
if (hasAgentAccess('pollution')) {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM pollution_certificates 
        WHERE agent_id = ? AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$agentId]);
    $upcomingPollutions = $stmt->fetchColumn();
}

// 5. Expired Renewals (Insurance or Pollution)
$expiredInsurances = 0;
if (hasAgentAccess('vehicle')) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM insurances WHERE agent_id = ? AND expiry_date < CURDATE()");
    $stmt->execute([$agentId]);
    $expiredInsurances = $stmt->fetchColumn();
}

$expiredPollutions = 0;
if (hasAgentAccess('pollution')) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM pollution_certificates WHERE agent_id = ? AND expiry_date < CURDATE()");
    $stmt->execute([$agentId]);
    $expiredPollutions = $stmt->fetchColumn();
}
$totalExpired = $expiredInsurances + $expiredPollutions;

// Health Insurance statistics if enabled
$totalHealthPolicies = 0;
$upcomingHealthPolicies = 0;
$expiredHealthPolicies = 0;
if (hasAgentAccess('health')) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM health_insurances WHERE agent_id = ?");
    $stmt->execute([$agentId]);
    $totalHealthPolicies = $stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM health_insurances 
        WHERE agent_id = ? AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$agentId]);
    $upcomingHealthPolicies = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM health_insurances WHERE agent_id = ? AND expiry_date < CURDATE()");
    $stmt->execute([$agentId]);
    $expiredHealthPolicies = $stmt->fetchColumn();
}

// 6. Upcoming Expirations Alert List (Limit 8, sorted by closest expiry)
$parts = [];
$params = [];

if (hasAgentAccess('vehicle')) {
    $parts[] = "
        SELECT 'Insurance' as type, i.expiry_date, v.vehicle_number, v.brand, v.model, c.name as customer_name, c.mobile_number, c.whatsapp_number, c.id as customer_id, v.id as vehicle_id
        FROM insurances i
        JOIN vehicles v ON i.vehicle_id = v.id
        JOIN customers c ON v.customer_id = c.id
        WHERE i.agent_id = ? AND i.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ";
    $params[] = $agentId;
}

if (hasAgentAccess('pollution')) {
    $parts[] = "
        SELECT 'Pollution' as type, p.expiry_date, v.vehicle_number, v.brand, v.model, c.name as customer_name, c.mobile_number, c.whatsapp_number, c.id as customer_id, v.id as vehicle_id
        FROM pollution_certificates p
        JOIN vehicles v ON p.vehicle_id = v.id
        JOIN customers c ON v.customer_id = c.id
        WHERE p.agent_id = ? AND p.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ";
    $params[] = $agentId;
}

if (hasAgentAccess('health')) {
    $parts[] = "
        SELECT 'Health' as type, h.expiry_date, 'N/A' as vehicle_number, 'Health Policy' as brand, '' as model, c.name as customer_name, c.mobile_number, c.whatsapp_number, c.id as customer_id, 0 as vehicle_id
        FROM health_insurances h
        JOIN customers c ON h.customer_id = c.id
        WHERE h.agent_id = ? AND h.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ";
    $params[] = $agentId;
}

if (!empty($parts)) {
    $query = implode(" UNION ALL ", $parts) . " ORDER BY expiry_date ASC LIMIT 8";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $expiringAlerts = $stmt->fetchAll();
} else {
    $expiringAlerts = [];
}

// --- ONE-CLICK WHATSAPP DISPATCH HANDLER (AJAX or POST) ---
if (isset($_POST['send_whatsapp_reminder'])) {
    $custId = (int)$_POST['customer_id'];
    $vehicleId = (int)$_POST['vehicle_id'];
    $remType = $_POST['reminder_type'];
    $expiryVal = $_POST['expiry_date'];
    $quotaCheck = canAgentSendMessages($agentId, 1);

    if (!$quotaCheck['allowed']) {
        $_SESSION['alert_error'] = getMessageLimitError($quotaCheck['balance'], $quotaCheck['requested']);
        redirect('index.php');
    }
    
    // Fetch details
    $stmtC = $db->prepare("SELECT name, whatsapp_number FROM customers WHERE id = ? AND agent_id = ?");
    $stmtC->execute([$custId, $agentId]);
    $customer = $stmtC->fetch();
    
    $vehNumber = 'Health Policy';
    if ($remType !== 'Health') {
        $stmtV = $db->prepare("SELECT vehicle_number FROM vehicles WHERE id = ? AND agent_id = ?");
        $stmtV->execute([$vehicleId, $agentId]);
        $vehNumber = $stmtV->fetchColumn();
    }
    
    if ($customer && ($remType === 'Health' || $vehNumber)) {
        $name = $customer['name'];
        $whatsapp = $customer['whatsapp_number'];
        
        // Dynamic message compilation
        $message = "Hello {$name}, your " . ($remType === 'Health' ? 'Health Insurance Policy' : "vehicle {$vehNumber} {$remType}") . " will expire on {$expiryVal}. Please renew it soon.";
        
        // Execute transmission
        $apiResult = sendWhatsAppMessage($whatsapp, $message, $name, $vehNumber, $expiryVal);
        
        // Log results in reminder history
        $status = $apiResult['success'] ? 'sent' : 'failed';
        $apiResp = $apiResult['response'];
        
        // Determine reminder threshold period based on days until
        $daysUntil = getDaysUntil($expiryVal);
        $period = ($daysUntil <= 1) ? '1 Day' : (($daysUntil <= 7) ? '7 Days' : (($daysUntil <= 15) ? '15 Days' : '30 Days'));
        
        $stmtHistory = $db->prepare("
            INSERT INTO reminder_history (customer_id, vehicle_id, reminder_type, reminder_period, sent_by_user_id, status, message, api_response)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtHistory->execute([$custId, $vehicleId ?: null, $remType, $period, $agentId, $status, $message, $apiResp]);
        
        if ($apiResult['success']) {
            deductAgentMessages($agentId, 1);
            $_SESSION['alert_success'] = "WhatsApp alert sent successfully to {$name}!";
        } else {
            $_SESSION['alert_error'] = 'API dispatch failed: ' . $apiResult['response'];
        }
        
        // Log action
        logActivity('WhatsApp Reminder Sent', "Sent $remType renewal notification to $name (Status: $status)");
    } else {
        $_SESSION['alert_error'] = 'Customer or vehicle records not found.';
    }
    
    redirect('index.php');
}

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Statistics row -->
<div class="row">
    <!-- Customers Count -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-primary">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-users-viewfinder"></i>
            </div>
            <p class="stat-title">My Customers</p>
            <h3 class="stat-value text-primary"><?php echo $totalCustomers; ?></h3>
        </div>
    </div>
    
    <!-- Vehicles Count -->
    <?php if (hasAgentAccess('vehicle') || hasAgentAccess('pollution')): ?>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-success">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-car"></i>
            </div>
            <p class="stat-title">My Registered Vehicles</p>
            <h3 class="stat-value text-success"><?php echo $totalVehicles; ?></h3>
        </div>
    </div>
    <?php endif; ?>

    <!-- Health Policies Count -->
    <?php if (hasAgentAccess('health')): ?>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-info">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                <i class="fa-solid fa-heart-pulse"></i>
            </div>
            <p class="stat-title">My Health Policies</p>
            <h3 class="stat-value text-info"><?php echo $totalHealthPolicies; ?></h3>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Expirations next 30 days -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-warning">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <p class="stat-title">30-Day Renewals</p>
            <h3 class="stat-value text-warning"><?php echo ($upcomingInsurances + $upcomingPollutions + $upcomingHealthPolicies); ?></h3>
        </div>
    </div>
    
    <!-- Expired already -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-danger">
            <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <p class="stat-title">Expired Records</p>
            <h3 class="stat-value text-danger"><?php echo ($totalExpired + $expiredHealthPolicies); ?></h3>
        </div>
    </div>
</div>

<div class="row mt-2">
    <div class="col-12">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <div>
                    <h5 class="mb-1"><i class="fa-solid fa-wallet text-primary me-2"></i>Message Wallet</h5>
                    <p class="mb-1 text-muted">Remaining messages: <strong><?php echo (int)$messageSummary['balance']; ?></strong></p>
                    <small class="text-muted">Current rate: Rs <?php echo number_format((float)$messageSummary['unit_price'], 2); ?> per message</small>
                </div>
                <div class="text-lg-end">
                    <?php if ((int)$messageSummary['balance'] <= 0): ?>
                        <span class="badge bg-danger">No message count left. Contact admin.</span>
                    <?php else: ?>
                        <span class="badge bg-success"><?php echo (int)$messageSummary['balance']; ?> messages available</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Urgent Expiry Notifications with One-Click WhatsApp Action -->
    <div class="col-12 col-xl-8">
        <div class="card h-100">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h5 class="card-title"><i class="fa-solid fa-bell text-warning me-2"></i>Urgent Renewals Alert Center</h5>
                <a href="reminders.php" class="btn btn-sm btn-light border text-primary">All Renewals</a>
            </div>
            <div class="card-body">
                
                <?php if (count($expiringAlerts) > 0): ?>
                    <div class="table-responsive border-0">
                        <table class="table align-middle">
                            <thead>
                                <tr class="text-muted small uppercase">
                                    <th>Vehicle & Customer</th>
                                    <th>Renewal Expiry</th>
                                    <th>Status Threshold</th>
                                    <th class="text-end">Notify Owner</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expiringAlerts as $alert): 
                                    $status = getExpiryStatus($alert['expiry_date']);
                                    $daysLeft = $status['days'];
                                    $borderClass = ($daysLeft <= 7) ? 'text-danger bg-danger-light' : (($daysLeft <= 15) ? 'text-warning bg-warning-light' : 'text-info bg-info-light');
                                ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($alert['type'] === 'Health'): ?>
                                                    <strong class="text-main"><span class="badge bg-danger bg-opacity-10 text-danger border-0 font-weight-600 px-2 py-1"><i class="fa-solid fa-heart-pulse me-1"></i> Health Insurance Policy</span></strong>
                                                <?php else: ?>
                                                    <strong class="text-main"><?php echo sanitize($alert['vehicle_number']); ?></strong>
                                                    <span class="small text-muted">(<?php echo sanitize($alert['brand'] . ' ' . $alert['model']); ?>)</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted d-block">
                                                Owner: <strong><?php echo sanitize($alert['customer_name']); ?></strong> | Tel: <?php echo sanitize($alert['mobile_number']); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="d-block small font-weight-600 text-main">
                                                <i class="fa-solid <?php echo ($alert['type'] === 'Insurance') ? 'fa-building-shield text-primary' : (($alert['type'] === 'Health') ? 'fa-heart-pulse text-danger' : 'fa-wind text-info'); ?> me-1"></i>
                                                <?php echo sanitize($alert['type']); ?> Expiry
                                            </span>
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">
                                                Date: <?php echo date('d-M-Y', strtotime($alert['expiry_date'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $status['badge']; ?>">
                                                <?php echo sanitize($status['text']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="customer_id" value="<?php echo $alert['customer_id']; ?>">
                                                <input type="hidden" name="vehicle_id" value="<?php echo $alert['vehicle_id']; ?>">
                                                <input type="hidden" name="reminder_type" value="<?php echo $alert['type']; ?>">
                                                <input type="hidden" name="expiry_date" value="<?php echo $alert['expiry_date']; ?>">
                                                
                                                <button type="submit" name="send_whatsapp_reminder" class="btn btn-sm btn-success px-3" title="Send WhatsApp alert">
                                                    <i class="fa-brands fa-whatsapp"></i> Alert
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fa-regular fa-bell-slash text-muted fs-1 mb-3"></i>
                        <p class="text-muted">Congratulations! No upcoming policies or pollution certificates expiring within the next 30 days.</p>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
    
    <!-- Quick Links & Templates widget -->
    <div class="col-12 col-xl-4 mt-4 mt-xl-0">
        <div class="card h-100">
            <div class="card-header bg-white py-3">
                <h5 class="card-title"><i class="fa-regular fa-message text-success me-2"></i>Alert Message Templates</h5>
            </div>
            <div class="card-body">
                
                <div class="alert bg-light border p-3 mb-3" style="border-radius: var(--radius-sm);">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-success"><i class="fa-brands fa-whatsapp me-1"></i> WhatsApp Message Template</span>
                    </div>
                    <p class="m-0 small text-muted font-monospace bg-white p-2.5 border rounded">
                        "Hello <strong>{customer_name}</strong>, your vehicle <strong>{vehicle_number}</strong> <strong>{reminder_type}</strong> will expire on <strong>{expiry_date}</strong>. Please renew it soon."
                    </p>
                    <div class="mt-2 text-muted small" style="font-size: 0.75rem;">
                        <i class="fa-solid fa-circle-info me-1"></i> Automatically sent using WhatsApp Cloud API templates on clicking the <strong>Alert</strong> button.
                    </div>
                </div>
                
                <!-- Quick Operations Navigator -->
                <h6 class="font-weight-600 mb-3 mt-4 text-main border-bottom pb-2">Quick Navigation Shortcuts</h6>
                <div class="d-grid gap-2">
                    <a href="customers.php?action=add" class="btn btn-outline-primary text-start">
                        <i class="fa-solid fa-user-plus me-2 text-muted"></i> Register New Customer (ID Upload)
                    </a>
                    <a href="vehicles.php?action=add" class="btn btn-outline-primary text-start">
                        <i class="fa-solid fa-car-rear me-2 text-muted"></i> Add Vehicle Record & Upload Photo
                    </a>
                    <a href="vehicles.php" class="btn btn-outline-primary text-start">
                        <i class="fa-solid fa-folder-open me-2 text-muted"></i> Manage Insurance & Pollution Certificates
                    </a>
                </div>
                
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
