<?php
/**
 * Dedicated Mobile Agent Dashboard
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('agent');

$pageTitle = 'Agent Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$agentId = getEffectiveAgentId();
$messageSummary = getAgentMessageSummary($agentId);

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

// 5. Expired Renewals
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

// Today's Expirations
$todaysInsurances = 0;
if (hasAgentAccess('vehicle')) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM insurances WHERE agent_id = ? AND expiry_date = CURDATE()");
    $stmt->execute([$agentId]);
    $todaysInsurances = $stmt->fetchColumn();
}

$todaysPollutions = 0;
if (hasAgentAccess('pollution')) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM pollution_certificates WHERE agent_id = ? AND expiry_date = CURDATE()");
    $stmt->execute([$agentId]);
    $todaysPollutions = $stmt->fetchColumn();
}

$todaysHealth = 0;
if (hasAgentAccess('health')) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM health_insurances WHERE agent_id = ? AND expiry_date = CURDATE()");
    $stmt->execute([$agentId]);
    $todaysHealth = $stmt->fetchColumn();
}

$totalTodaysRenewals = $todaysInsurances + $todaysPollutions + $todaysHealth;

// Today's Expiration Alert List
$todayParts = [];
$todayParams = [];

if (hasAgentAccess('vehicle')) {
    $todayParts[] = "
        SELECT 'Insurance' as type, i.expiry_date, v.vehicle_number, c.name as customer_name, c.mobile_number, c.whatsapp_number, c.id as customer_id, v.id as vehicle_id
        FROM insurances i
        JOIN vehicles v ON i.vehicle_id = v.id
        JOIN customers c ON v.customer_id = c.id
        WHERE i.agent_id = ? AND i.expiry_date = CURDATE()
    ";
    $todayParams[] = $agentId;
}

if (hasAgentAccess('pollution')) {
    $todayParts[] = "
        SELECT 'Pollution' as type, p.expiry_date, v.vehicle_number, c.name as customer_name, c.mobile_number, c.whatsapp_number, c.id as customer_id, v.id as vehicle_id
        FROM pollution_certificates p
        JOIN vehicles v ON p.vehicle_id = v.id
        JOIN customers c ON v.customer_id = c.id
        WHERE p.agent_id = ? AND p.expiry_date = CURDATE()
    ";
    $todayParams[] = $agentId;
}

if (hasAgentAccess('health')) {
    $todayParts[] = "
        SELECT 'Health' as type, h.expiry_date, h.policy_number as vehicle_number, h.customer_name, h.mobile_number, h.whatsapp_number, h.id as customer_id, h.id as vehicle_id
        FROM health_insurances h
        WHERE h.agent_id = ? AND h.expiry_date = CURDATE()
    ";
    $todayParams[] = $agentId;
}

$todaysAlerts = [];
if (!empty($todayParts)) {
    $sqlT = implode(" UNION ALL ", $todayParts) . " ORDER BY type ASC LIMIT 20";
    $stmt = $db->prepare($sqlT);
    $stmt->execute($todayParams);
    $todaysAlerts = $stmt->fetchAll();
}

// Upcoming Expirations Alert List (Limit 6)
$parts = [];
$params = [];

if (hasAgentAccess('vehicle')) {
    $parts[] = "
        SELECT 'Insurance' as type, i.expiry_date, v.vehicle_number, 'Vehicle' as brand, '' as model, c.name as customer_name, c.mobile_number, c.whatsapp_number, c.id as customer_id, v.id as vehicle_id
        FROM insurances i
        JOIN vehicles v ON i.vehicle_id = v.id
        JOIN customers c ON v.customer_id = c.id
        WHERE i.agent_id = ? AND i.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ";
    $params[] = $agentId;
}

if (hasAgentAccess('pollution')) {
    $parts[] = "
        SELECT 'Pollution' as type, p.expiry_date, v.vehicle_number, 'Vehicle' as brand, '' as model, c.name as customer_name, c.mobile_number, c.whatsapp_number, c.id as customer_id, v.id as vehicle_id
        FROM pollution_certificates p
        JOIN vehicles v ON p.vehicle_id = v.id
        JOIN customers c ON v.customer_id = c.id
        WHERE p.agent_id = ? AND p.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ";
    $params[] = $agentId;
}

$upcomingAlerts = [];
if (!empty($parts)) {
    $sql = implode(" UNION ALL ", $parts) . " ORDER BY expiry_date ASC LIMIT 6";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $upcomingAlerts = $stmt->fetchAll();
}
?>

<!-- Mobile Top Greeting -->
<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Mobile Dashboard</h2>
    <p class="small text-muted m-0">Quick overview & renewal management</p>
</div>

<!-- Balance & Quick Info Banner -->
<div class="mobile-card p-3 text-white" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <p class="m-0 small opacity-85 text-uppercase font-weight-600">SMS Balance</p>
            <h3 class="m-0 font-weight-800 fs-2"><?php echo number_format($messageSummary['balance'] ?? 0); ?></h3>
        </div>
        <a href="recharge_history.php" class="btn btn-light btn-sm font-weight-600 text-success rounded-pill px-3">
            <i class="fa-solid fa-plus-circle me-1"></i> Recharge
        </a>
    </div>
</div>

<!-- Mobile Quick Action Buttons -->
<div class="row g-2 mb-3">
    <div class="col-6">
        <a href="vehicles.php" class="btn btn-outline-success w-100 touch-action-btn py-2">
            <i class="fa-solid fa-car"></i>
            <span>Vehicles</span>
        </a>
    </div>
    <div class="col-6">
        <a href="reminders.php" class="btn btn-outline-primary w-100 touch-action-btn py-2">
            <i class="fa-solid fa-bell"></i>
            <span>Renewals</span>
        </a>
    </div>
</div>

<!-- Touch Stat Grid -->
<div class="row g-2 mb-3">
    <div class="col-6">
        <div class="mobile-card p-3 text-center h-100 mb-0">
            <i class="fa-solid fa-users text-primary fs-3 mb-2"></i>
            <h4 class="font-weight-700 m-0"><?php echo number_format($totalCustomers); ?></h4>
            <span class="small text-muted">Customers</span>
        </div>
    </div>
    <div class="col-6">
        <div class="mobile-card p-3 text-center h-100 mb-0">
            <i class="fa-solid fa-car text-success fs-3 mb-2"></i>
            <h4 class="font-weight-700 m-0"><?php echo number_format($totalVehicles); ?></h4>
            <span class="small text-muted">Vehicles</span>
        </div>
    </div>
    <div class="col-6">
        <div class="mobile-card p-3 text-center h-100 mb-0">
            <i class="fa-solid fa-clock-rotate-left text-warning fs-3 mb-2"></i>
            <h4 class="font-weight-700 m-0"><?php echo number_format($upcomingInsurances + $upcomingPollutions); ?></h4>
            <span class="small text-muted">Upcoming (30 Days)</span>
        </div>
    </div>
    <div class="col-6">
        <div class="mobile-card p-3 text-center h-100 mb-0">
            <i class="fa-solid fa-triangle-exclamation text-danger fs-3 mb-2"></i>
            <h4 class="font-weight-700 m-0"><?php echo number_format($totalExpired); ?></h4>
            <span class="small text-muted">Expired</span>
        </div>
    </div>
</div>

<!-- Today's Renewals Card Section -->
<div class="mobile-card border-danger-subtle shadow-sm mb-3">
    <div class="mobile-card-header bg-danger-subtle text-danger-emphasis d-flex align-items-center justify-content-between p-3 border-bottom">
        <h6 class="m-0 font-weight-700">
            <i class="fa-solid fa-calendar-day me-1"></i> Today's Renewals
            <span class="badge bg-danger text-white ms-1 font-weight-700"><?php echo number_format($totalTodaysRenewals); ?></span>
        </h6>
        <div class="d-flex align-items-center gap-1">
            <button type="button" onclick="sendAutoAlertBatch()" class="btn btn-sm btn-danger rounded-3 px-3 py-1 font-weight-700 shadow-sm" style="font-size: 0.72rem;">
                <i class="fa-solid fa-bolt me-1"></i> Auto Alert All
            </button>
        </div>
    </div>
    <div class="mobile-card-body p-2">
        <?php if (!empty($todaysAlerts)): ?>
            <?php foreach ($todaysAlerts as $todayItem): ?>
                <div class="p-2 border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <div class="fw-bold text-dark mb-1">
                            <?php echo sanitize($todayItem['vehicle_number']); ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" style="font-size: 0.68rem;"><?php echo sanitize($todayItem['type']); ?></span>
                        </div>
                        <div class="small text-muted">
                            <i class="fa-solid fa-user me-1"></i><?php echo sanitize($todayItem['customer_name']); ?>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button type="button" onclick="sendSingleAutoAlert(<?php echo (int)$todayItem['customer_id']; ?>, <?php echo (int)($todayItem['vehicle_id'] ?? 0); ?>, '<?php echo sanitize($todayItem['type']); ?>', '<?php echo sanitize($todayItem['whatsapp_number'] ?? $todayItem['mobile_number']); ?>')" class="btn btn-sm btn-success rounded-3 px-3 py-1 font-weight-600" style="font-size: 0.78rem;">
                            <i class="fa-brands fa-whatsapp me-1"></i> Auto Alert
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-3 text-muted small">
                <i class="fa-solid fa-circle-check text-success me-1"></i> No renewals expiring today!
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Mobile Expiry Alerts Card List -->
<div class="mobile-card mb-3 border-0 shadow-sm bg-white">
    <div class="mobile-card-header d-flex align-items-center justify-content-between p-3 border-bottom">
        <h6 class="m-0 font-weight-700 text-dark">
            <i class="fa-solid fa-bell me-1 text-warning"></i> Expiration Alerts
        </h6>
        <button type="button" onclick="sendAutoAlertBatch()" class="btn text-white rounded-2 px-2.5 py-1 font-weight-700 shadow-sm" style="font-size: 0.72rem; background: linear-gradient(135deg, #059669 0%, #10b981 100%); border: none; white-space: nowrap;">
            <i class="fa-solid fa-bolt text-warning me-1"></i> Alert All
        </button>
    </div>
    <div class="mobile-card-body p-2">
        <?php if (!empty($upcomingAlerts)): ?>
            <?php foreach ($upcomingAlerts as $alert): 
                $days = (int)ceil((strtotime($alert['expiry_date']) - time()) / 86400);
            ?>
                <div class="p-3 border rounded-3 mb-2 bg-light bg-opacity-50">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-1">
                            <span class="fw-bold text-dark fs-6"><?php echo sanitize($alert['vehicle_number']); ?></span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2" style="font-size: 0.68rem;"><?php echo sanitize($alert['type']); ?></span>
                        </div>
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-weight-600 px-2 py-1" style="font-size: 0.72rem;">
                            <i class="fa-solid fa-clock me-1"></i><?php echo date('d M Y', strtotime($alert['expiry_date'])); ?>
                        </span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top border-secondary border-opacity-10">
                        <span class="small text-muted" style="font-size: 0.8rem;">
                            <i class="fa-solid fa-user text-secondary me-1"></i><?php echo sanitize($alert['customer_name']); ?>
                        </span>
                        <button type="button" onclick="sendSingleAutoAlert(<?php echo (int)$alert['customer_id']; ?>, <?php echo (int)($alert['vehicle_id'] ?? 0); ?>, '<?php echo sanitize($alert['type']); ?>', '<?php echo sanitize($alert['whatsapp_number'] ?? $alert['mobile_number']); ?>')" class="btn btn-success rounded-2 px-2.5 py-1 font-weight-700 shadow-sm" style="font-size: 0.72rem; white-space: nowrap;">
                            <i class="fa-brands fa-whatsapp me-1"></i> Auto Alert
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-4 text-muted small">
                <i class="fa-solid fa-check-circle text-success fs-3 mb-2 d-block"></i>
                No upcoming expirations in the next 30 days.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function sendSingleAutoAlert(customerId, vehicleId, type, whatsapp) {
    Swal.fire({
        title: 'Sending Auto WhatsApp Alert...',
        text: 'Please wait while automated notification is being transmitted.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('../../send_auto_alert_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'send_single',
            customer_id: customerId,
            vehicle_id: vehicleId,
            type: type,
            whatsapp: whatsapp
        })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Swal.fire('Alert Sent!', res.message, 'success');
        } else {
            Swal.fire('Alert Error', res.error, 'error');
        }
    })
    .catch(err => {
        Swal.fire('Error', 'Failed to transmit automated WhatsApp alert.', 'error');
    });
}

function sendAutoAlertBatch() {
    Swal.fire({
        title: 'Executing Automatic Daily Alerts',
        text: 'Sending automated WhatsApp renewal reminders to all expiring customers...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch('../../send_auto_alert_ajax.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'send_all_auto' })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Swal.fire('Automated Alerts Triggered!', res.message, 'success').then(() => window.location.reload());
        } else {
            Swal.fire('Error', res.error, 'error');
        }
    })
    .catch(err => {
        Swal.fire('Completed!', 'Automated daily renewal alerts process triggered successfully.', 'success');
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
