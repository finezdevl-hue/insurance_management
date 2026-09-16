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
    SELECT r.*, u.shop_name as agency_name
    FROM reminder_history r
    LEFT JOIN users u ON r.agent_id = u.id
    ORDER BY r.id DESC
    LIMIT 50
");
$logs = $stmt->fetchAll();
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Reminder History Logs</h2>
    <p class="small text-muted m-0">Audit log of system notifications</p>
</div>

<?php if (!empty($logs)): ?>
    <?php foreach ($logs as $log): ?>
        <div class="mobile-card">
            <div class="mobile-card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-primary-subtle text-primary border"><?php echo sanitize($log['agency_name'] ?? 'System'); ?></span>
                    <span class="small text-muted"><?php echo date('d M Y, h:i A', strtotime($log['sent_at'])); ?></span>
                </div>
                <p class="small text-dark mb-0 font-weight-500"><?php echo sanitize($log['message_text'] ?? 'Notification alert sent'); ?></p>
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
