<?php
/**
 * Dedicated Mobile Reminders & Expiry Alerts
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('agent');

$pageTitle = 'Expiry & Renewals';
$activePage = 'reminders';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$agentId = getEffectiveAgentId();

// Filter Days (Default 30 days, 0 = Today)
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
$typeFilter = $_GET['type'] ?? 'all';

// Build Expiry Query
$parts = [];
$params = [];

if ($days === 0) {
    if (hasAgentAccess('vehicle') && ($typeFilter === 'all' || $typeFilter === 'insurance')) {
        $parts[] = "
            SELECT 'Insurance' as type, i.expiry_date, v.vehicle_number, c.name as customer_name, c.mobile_number, c.whatsapp_number, c.id as customer_id
            FROM insurances i
            JOIN vehicles v ON i.vehicle_id = v.id
            JOIN customers c ON v.customer_id = c.id
            WHERE i.agent_id = ? AND i.expiry_date = CURDATE()
        ";
        $params[] = $agentId;
    }

    if (hasAgentAccess('pollution') && ($typeFilter === 'all' || $typeFilter === 'pollution')) {
        $parts[] = "
            SELECT 'Pollution' as type, p.expiry_date, v.vehicle_number, c.name as customer_name, c.mobile_number, c.whatsapp_number, c.id as customer_id
            FROM pollution_certificates p
            JOIN vehicles v ON p.vehicle_id = v.id
            JOIN customers c ON v.customer_id = c.id
            WHERE p.agent_id = ? AND p.expiry_date = CURDATE()
        ";
        $params[] = $agentId;
    }
} else {
    if (hasAgentAccess('vehicle') && ($typeFilter === 'all' || $typeFilter === 'insurance')) {
        $parts[] = "
            SELECT 'Insurance' as type, i.expiry_date, v.vehicle_number, c.name as customer_name, c.mobile_number, c.whatsapp_number, c.id as customer_id
            FROM insurances i
            JOIN vehicles v ON i.vehicle_id = v.id
            JOIN customers c ON v.customer_id = c.id
            WHERE i.agent_id = ? AND i.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
        ";
        $params[] = $agentId;
        $params[] = $days;
    }

    if (hasAgentAccess('pollution') && ($typeFilter === 'all' || $typeFilter === 'pollution')) {
        $parts[] = "
            SELECT 'Pollution' as type, p.expiry_date, v.vehicle_number, c.name as customer_name, c.mobile_number, c.whatsapp_number, c.id as customer_id
            FROM pollution_certificates p
            JOIN vehicles v ON p.vehicle_id = v.id
            JOIN customers c ON v.customer_id = c.id
            WHERE p.agent_id = ? AND p.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
        ";
        $params[] = $agentId;
        $params[] = $days;
    }
}

$reminders = [];
if (!empty($parts)) {
    $sql = implode(" UNION ALL ", $parts) . " ORDER BY expiry_date ASC LIMIT 100";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $reminders = $stmt->fetchAll();
}
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Expiry & Renewal Alerts</h2>
    <p class="small text-muted m-0">Send instant renewal reminders</p>
</div>

<!-- Mobile Days Filter Pill Selector -->
<div class="d-flex align-items-center gap-2 mb-3 overflow-auto pb-1" style="white-space: nowrap;">
    <a href="reminders.php?days=0" class="btn btn-sm rounded-pill <?php echo $days === 0 ? 'btn-danger' : 'btn-light border'; ?>">Today Expirations</a>
    <a href="reminders.php?days=7" class="btn btn-sm rounded-pill <?php echo $days === 7 ? 'btn-success' : 'btn-light border'; ?>">Next 7 Days</a>
    <a href="reminders.php?days=15" class="btn btn-sm rounded-pill <?php echo $days === 15 ? 'btn-success' : 'btn-light border'; ?>">Next 15 Days</a>
    <a href="reminders.php?days=30" class="btn btn-sm rounded-pill <?php echo $days === 30 ? 'btn-success' : 'btn-light border'; ?>">Next 30 Days</a>
</div>

<!-- Reminders Card List -->
<?php if (!empty($reminders)): ?>
    <?php foreach ($reminders as $rem): 
        $daysLeft = (int)ceil((strtotime($rem['expiry_date']) - time()) / 86400);
    ?>
        <div class="mobile-card">
            <div class="mobile-card-header">
                <div>
                    <h6 class="m-0 font-weight-700 text-dark"><?php echo sanitize($rem['vehicle_number']); ?></h6>
                    <span class="small text-muted"><i class="fa-solid fa-user me-1"></i><?php echo sanitize($rem['customer_name']); ?></span>
                </div>
                <span class="badge <?php echo $rem['type'] === 'Insurance' ? 'bg-primary-subtle text-primary border' : 'bg-info-subtle text-info border'; ?>">
                    <?php echo sanitize($rem['type']); ?>
                </span>
            </div>
            
            <div class="mobile-card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-3 p-2 bg-light rounded-3">
                    <div>
                        <span class="small text-muted d-block" style="font-size: 0.72rem;">EXPIRY DATE</span>
                        <span class="fw-bold small text-dark"><?php echo date('d M Y', strtotime($rem['expiry_date'])); ?></span>
                    </div>
                    <div>
                        <span class="badge bg-warning-subtle text-warning-emphasis border">
                            <?php echo $daysLeft > 0 ? "$daysLeft Days Left" : "Expires Today"; ?>
                        </span>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between gap-2">
                    <?php if (!empty($rem['mobile_number'])): ?>
                        <a href="tel:<?php echo sanitize($rem['mobile_number']); ?>" class="btn btn-outline-secondary btn-sm touch-action-btn flex-grow-1">
                            <i class="fa-solid fa-phone me-1"></i> Call
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($rem['whatsapp_number'])): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $rem['whatsapp_number']); ?>" target="_blank" class="btn btn-success btn-sm touch-action-btn flex-grow-1">
                            <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="mobile-card p-4 text-center text-muted">
        <i class="fa-solid fa-circle-check fs-2 mb-2 d-block text-success opacity-75"></i>
        <p class="m-0 font-weight-500">No renewals expiring in the selected period.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
