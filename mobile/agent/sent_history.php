<?php
/**
 * Dedicated Mobile Sent Messages History
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('agent');

$pageTitle = 'Sent Messages History';
$activePage = 'sent_history';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$userId = (int)$_SESSION['user_id'];
$effectiveAgentId = getEffectiveAgentId();

$stmt = $db->prepare("
    SELECT r.*, 
           COALESCE(c.name, 'Customer') as customer_name, 
           COALESCE(c.mobile_number, c.whatsapp_number, '') as customer_phone,
           COALESCE(v.vehicle_number, '') as vehicle_number
    FROM reminder_history r
    LEFT JOIN customers c ON r.customer_id = c.id
    LEFT JOIN vehicles v ON r.vehicle_id = v.id
    WHERE (
        r.sent_by_user_id = ? 
        OR r.sent_by_user_id IN (SELECT id FROM users WHERE parent_agent_id = ?)
        OR c.agent_id = ?
        OR v.agent_id = ?
    )
    ORDER BY r.id DESC 
    LIMIT 50
");
$stmt->execute([$userId, $userId, $effectiveAgentId, $effectiveAgentId]);
$sentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Sent Messages Log</h2>
    <p class="small text-muted m-0">History of notification alerts sent</p>
</div>

<?php if (!empty($sentLogs)): ?>
    <?php foreach ($sentLogs as $log): 
        $statusBadge = ($log['status'] === 'sent') ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
        $vehDisplay = !empty($log['vehicle_number']) ? formatVehicleNumber($log['vehicle_number']) : (($log['reminder_type'] === 'Health') ? 'Health Policy' : 'Vehicle');
    ?>
        <div class="mobile-card mb-2">
            <div class="mobile-card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border"><?php echo sanitize($log['reminder_type'] ?? 'Notification'); ?></span>
                        <span class="badge <?php echo $statusBadge; ?> border text-uppercase" style="font-size: 0.65rem;"><?php echo sanitize($log['status']); ?></span>
                    </div>
                    <span class="small text-muted"><?php echo date('d M Y, h:i A', strtotime($log['sent_date'])); ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong class="text-dark small"><?php echo sanitize($log['customer_name']); ?></strong>
                    <span class="text-primary font-weight-600 small"><?php echo sanitize($vehDisplay); ?></span>
                </div>
                <p class="small text-muted mb-0 font-weight-500" style="white-space: pre-line;"><?php echo sanitize($log['message'] ?? 'Notification sent successfully'); ?></p>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="mobile-card p-4 text-center text-muted">
        <i class="fa-solid fa-paper-plane fs-2 mb-2 d-block text-secondary opacity-50"></i>
        <p class="m-0 font-weight-500">No sent message logs recorded.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
