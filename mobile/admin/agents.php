<?php
/**
 * Dedicated Mobile Agents Management
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('admin');

$pageTitle = 'Agents Management';
$activePage = 'agents';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();

$search = trim($_GET['search'] ?? '');
$params = [];
$where = "WHERE role = 'agent'";

if (!empty($search)) {
    $where .= " AND (username LIKE ? OR shop_name LIKE ? OR shop_owner_name LIKE ? OR mobile_number LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$stmt = $db->prepare("SELECT * FROM users $where ORDER BY id DESC");
$stmt->execute($params);
$agents = $stmt->fetchAll();
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Agents Management</h2>
    <p class="small text-muted m-0">Mobile agency partners list</p>
</div>

<!-- Mobile Search Bar -->
<div class="mobile-card p-2 mb-3">
    <form action="" method="GET">
        <div class="input-group">
            <span class="input-group-text bg-transparent border-0"><i class="fa-solid fa-search text-muted"></i></span>
            <input type="text" name="search" class="form-control border-0 ps-0" placeholder="Search agency or username..." value="<?php echo sanitize($search); ?>" inputmode="search">
            <?php if (!empty($search)): ?>
                <a href="agents.php" class="btn btn-link text-muted"><i class="fa-solid fa-xmark"></i></a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary px-3 rounded-3">Search</button>
        </div>
    </form>
</div>

<!-- Agents Card List -->
<?php if (!empty($agents)): ?>
    <?php foreach ($agents as $ag): ?>
        <div class="mobile-card">
            <div class="mobile-card-header">
                <div>
                    <h6 class="m-0 font-weight-700 text-dark"><?php echo sanitize($ag['shop_name'] ?? $ag['username']); ?></h6>
                    <span class="small text-muted">Owner: <?php echo sanitize($ag['shop_owner_name'] ?? 'N/A'); ?></span>
                </div>
                <span class="badge <?php echo $ag['status'] === 'active' ? 'bg-success-subtle text-success border' : 'bg-danger-subtle text-danger border'; ?>">
                    <?php echo ucfirst(sanitize($ag['status'])); ?>
                </span>
            </div>
            <div class="mobile-card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted">Username / ID:</span>
                    <span class="small font-weight-600 text-dark"><?php echo sanitize($ag['username']); ?></span>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted">SMS Balance:</span>
                    <span class="small font-weight-600 text-primary"><?php echo number_format($ag['message_balance'] ?? 0); ?> Credits</span>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small text-muted">License Expiry:</span>
                    <span class="small font-weight-600 text-dark"><?php echo !empty($ag['expiry_date']) ? date('d M Y', strtotime($ag['expiry_date'])) : 'Unlimited'; ?></span>
                </div>
                <?php if (!empty($ag['mobile_number'])): ?>
                    <a href="tel:<?php echo sanitize($ag['mobile_number']); ?>" class="btn btn-outline-secondary btn-sm touch-action-btn w-100 mt-2">
                        <i class="fa-solid fa-phone me-1"></i> Call Agency
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="mobile-card p-4 text-center text-muted">
        <i class="fa-solid fa-user-tie fs-2 mb-2 d-block text-secondary opacity-50"></i>
        <p class="m-0 font-weight-500">No agency accounts found.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
