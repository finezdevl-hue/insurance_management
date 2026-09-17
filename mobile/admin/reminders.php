<?php
/**
 * Dedicated Mobile Admin Reminder History
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('admin');

$pageTitle = 'Reminder History';
$activePage = 'reminders';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();

$stmt = $db->query("
    SELECT r.*, 
           COALESCE(c.name, 'Customer') as customer_name,
           COALESCE(v.vehicle_number, '') as vehicle_number,
           COALESCE(u.shop_name, u.username, 'System') as agency_name
    FROM reminder_history r
    LEFT JOIN customers c ON r.customer_id = c.id
    LEFT JOIN vehicles v ON r.vehicle_id = v.id
    LEFT JOIN users u ON r.sent_by_user_id = u.id
    ORDER BY r.id DESC
    LIMIT 50
");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Reminder History Logs</h2>
    <p class="small text-muted m-0">Audit log of system notifications</p>
</div>

<?php if (!empty($logs)): ?>
    <?php foreach ($logs as $log): 
        $statusBadge = ($log['status'] === 'sent') ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
        $vehDisplay = !empty($log['vehicle_number']) ? formatVehicleNumber($log['vehicle_number']) : (($log['reminder_type'] === 'Health') ? 'Health Policy' : 'Vehicle');
    ?>
        <div class="mobile-card mb-2">
            <div class="mobile-card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border"><?php echo sanitize($log['agency_name'] ?? 'System'); ?></span>
                        <span class="badge <?php echo $statusBadge; ?> border text-uppercase" style="font-size: 0.65rem;"><?php echo sanitize($log['status']); ?></span>
                    </div>
                    <span class="small text-muted"><?php echo date('d M Y, h:i A', strtotime($log['sent_date'])); ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <strong class="text-dark small"><?php echo sanitize($log['customer_name']); ?></strong>
                    <span class="text-primary font-weight-600 small"><?php echo sanitize($vehDisplay); ?></span>
                </div>
                <p class="small text-muted mb-0 font-weight-500" style="white-space: pre-line;"><?php echo sanitize($log['message'] ?? 'Notification alert sent'); ?></p>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="mobile-card p-4 text-center text-muted">
        <i class="fa-solid fa-clock-rotate-left fs-2 mb-2 d-block text-secondary opacity-50"></i>
        <p class="m-0 font-weight-500">No reminder logs found.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
