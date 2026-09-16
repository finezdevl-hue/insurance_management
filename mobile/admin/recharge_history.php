<?php
/**
 * Dedicated Mobile Admin Message Recharges
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('admin');

$pageTitle = 'Message Recharges';
$activePage = 'recharge_history';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();

$stmt = $db->query("
    SELECT r.*, u.shop_name as agency_name, u.username
    FROM agent_message_recharges r
    JOIN users u ON r.agent_id = u.id
    ORDER BY r.id DESC
    LIMIT 50
");
$recharges = $stmt->fetchAll();
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Message Recharges</h2>
    <p class="small text-muted m-0">SMS credit allocations history</p>
</div>

<?php if (!empty($recharges)): ?>
    <?php foreach ($recharges as $rec): ?>
        <div class="mobile-card">
            <div class="mobile-card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="m-0 font-weight-700 text-dark"><?php echo sanitize($rec['agency_name'] ?? $rec['username']); ?></h6>
                    <span class="small text-muted"><?php echo date('d M Y, h:i A', strtotime($rec['created_at'])); ?></span>
                </div>
                <span class="badge bg-success-subtle text-success border">+<?php echo number_format($rec['messages_added']); ?> SMS</span>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="mobile-card p-4 text-center text-muted">
        <i class="fa-solid fa-money-bill-transfer fs-2 mb-2 d-block text-secondary opacity-50"></i>
        <p class="m-0 font-weight-500">No message recharges recorded.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
