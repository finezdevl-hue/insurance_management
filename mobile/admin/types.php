<?php
/**
 * Dedicated Mobile Admin Vehicle Types Master
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('admin');

$pageTitle = 'Vehicle Types';
$activePage = 'types';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$types = $db->query("SELECT * FROM vehicle_types ORDER BY name ASC")->fetchAll();
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Vehicle Types</h2>
    <p class="small text-muted m-0">Master vehicle categories list</p>
</div>

<?php if (!empty($types)): ?>
    <?php foreach ($types as $type): ?>
        <div class="mobile-card">
            <div class="mobile-card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="m-0 font-weight-700 text-dark"><?php echo sanitize($type['name']); ?></h6>
                    <span class="small text-muted">Category: <?php echo sanitize($type['category'] ?? 'General'); ?></span>
                </div>
                <i class="fa-solid fa-truck-pickup text-primary fs-4"></i>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="mobile-card p-4 text-center text-muted">
        <i class="fa-solid fa-truck-pickup fs-2 mb-2 d-block text-secondary opacity-50"></i>
        <p class="m-0 font-weight-500">No vehicle types found.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
