<?php
/**
 * Dedicated Mobile Admin Activity Logs
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('admin');

$pageTitle = 'Activity Logs';
$activePage = 'activity';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();

$stmt = $db->query("
    SELECT a.*, u.username 
    FROM activity_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.id DESC
    LIMIT 50
");
$logs = $stmt->fetchAll();
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Activity Audit Logs</h2>
    <p class="small text-muted m-0">Recent user actions & system events</p>
</div>

<?php if (!empty($logs)): ?>
    <?php foreach ($logs as $log): ?>
        <div class="mobile-card">
            <div class="mobile-card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="fw-bold small text-dark"><?php echo sanitize($log['action']); ?></span>
                    <span class="small text-muted" style="font-size: 0.7rem;"><?php echo date('d M Y, h:i A', strtotime($log['created_at'])); ?></span>
                </div>
                <p class="small text-muted mb-1"><?php echo sanitize($log['details']); ?></p>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="small text-muted" style="font-size: 0.72rem;">User: <?php echo sanitize($log['username'] ?? 'System/Guest'); ?></span>
                    <span class="small text-muted" style="font-size: 0.72rem;">IP: <?php echo sanitize($log['ip_address']); ?></span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="mobile-card p-4 text-center text-muted">
        <i class="fa-solid fa-list-check fs-2 mb-2 d-block text-secondary opacity-50"></i>
        <p class="m-0 font-weight-500">No activity logs recorded.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
