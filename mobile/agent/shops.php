<?php
/**
 * Dedicated Mobile Sub-Shops & Outlets
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('agent');

$pageTitle = 'Sub-Shops & Outlets';
$activePage = 'shops';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$agentId = getEffectiveAgentId();

$stmt = $db->prepare("SELECT * FROM users WHERE role = 'shop' AND parent_agent_id = ? ORDER BY id DESC");
$stmt->execute([$agentId]);
$shops = $stmt->fetchAll();
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Sub-Shops & Testing Centers</h2>
    <p class="small text-muted m-0">Managed outlets under your agency</p>
</div>

<?php if (!empty($shops)): ?>
    <?php foreach ($shops as $shop): ?>
        <div class="mobile-card">
            <div class="mobile-card-header">
                <div>
                    <h6 class="m-0 font-weight-700 text-dark"><?php echo sanitize($shop['shop_name'] ?? $shop['username']); ?></h6>
                    <span class="small text-muted">Owner: <?php echo sanitize($shop['shop_owner_name'] ?? 'N/A'); ?></span>
                </div>
                <span class="badge <?php echo $shop['status'] === 'active' ? 'bg-success-subtle text-success border' : 'bg-danger-subtle text-danger border'; ?>">
                    <?php echo ucfirst(sanitize($shop['status'])); ?>
                </span>
            </div>
            <div class="mobile-card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted">Username / ID:</span>
                    <span class="small font-weight-600 text-dark"><?php echo sanitize($shop['username']); ?></span>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted">License Expiry:</span>
                    <span class="small font-weight-600 text-dark"><?php echo !empty($shop['expiry_date']) ? date('d M Y', strtotime($shop['expiry_date'])) : 'Unlimited'; ?></span>
                </div>
                <?php if (!empty($shop['mobile_number'])): ?>
                    <a href="tel:<?php echo sanitize($shop['mobile_number']); ?>" class="btn btn-outline-secondary btn-sm touch-action-btn w-100 mt-2">
                        <i class="fa-solid fa-phone me-1"></i> Call Outlet
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="mobile-card p-4 text-center text-muted">
        <i class="fa-solid fa-store fs-2 mb-2 d-block text-secondary opacity-50"></i>
        <p class="m-0 font-weight-500">No sub-shops or testing centers created yet.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
