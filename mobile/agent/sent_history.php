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
$agentId = getEffectiveAgentId();

$stmt = $db->prepare("SELECT * FROM reminder_history WHERE agent_id = ? ORDER BY id DESC LIMIT 50");
$stmt->execute([$agentId]);
$sentLogs = $stmt->fetchAll();
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Sent Messages Log</h2>
    <p class="small text-muted m-0">History of notification alerts sent</p>
</div>

<?php if (!empty($sentLogs)): ?>
    <?php foreach ($sentLogs as $log): ?>
        <div class="mobile-card">
            <div class="mobile-card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-primary-subtle text-primary border"><?php echo sanitize($log['reminder_type'] ?? 'Notification'); ?></span>
                    <span class="small text-muted"><?php echo date('d M Y, h:i A', strtotime($log['sent_at'])); ?></span>
                </div>
                <p class="small text-dark mb-0 font-weight-500"><?php echo sanitize($log['message_text'] ?? 'Notification sent successfully'); ?></p>
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
