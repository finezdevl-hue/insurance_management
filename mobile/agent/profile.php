<?php
/**
 * Dedicated Mobile Profile Page
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('agent');

$pageTitle = 'Outlet Profile';
$activePage = 'profile';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$userId = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Outlet Profile</h2>
    <p class="small text-muted m-0">Account & agency details</p>
</div>

<div class="mobile-card p-3">
    <div class="text-center mb-3">
        <div class="user-avatar bg-primary text-white font-weight-700 mx-auto mb-2" style="width: 56px; height: 56px; border-radius: 50%; font-size: 1.4rem; display: flex; align-items: center; justify-content: center;">
            <?php echo substr(sanitize($user['shop_name'] ?? $user['username']), 0, 2); ?>
        </div>
        <h5 class="font-weight-700 m-0 text-dark"><?php echo sanitize($user['shop_name'] ?? $user['username']); ?></h5>
        <span class="badge bg-success-subtle text-success border border-success-subtle mt-1"><?php echo ucfirst(sanitize($user['role'])); ?> Portal</span>
    </div>

    <div class="border-top pt-3">
        <div class="mb-2">
            <span class="small text-muted d-block">Username / Partner ID:</span>
            <span class="fw-bold text-dark"><?php echo sanitize($user['username']); ?></span>
        </div>
        <div class="mb-2">
            <span class="small text-muted d-block">Owner Name:</span>
            <span class="fw-bold text-dark"><?php echo sanitize($user['shop_owner_name'] ?? 'N/A'); ?></span>
        </div>
        <div class="mb-2">
            <span class="small text-muted d-block">Email Address:</span>
            <span class="fw-bold text-dark"><?php echo sanitize($user['email']); ?></span>
        </div>
        <div class="mb-2">
            <span class="small text-muted d-block">Mobile Number:</span>
            <span class="fw-bold text-dark"><?php echo sanitize($user['mobile_number'] ?? 'N/A'); ?></span>
        </div>
        <div class="mb-2">
            <span class="small text-muted d-block">License Expiration:</span>
            <span class="fw-bold text-dark"><?php echo !empty($user['expiry_date']) ? date('d M Y', strtotime($user['expiry_date'])) : 'Unlimited'; ?></span>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
