<?php
/**
 * Dedicated Mobile Admin Master Health List
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('admin');

$pageTitle = 'Master Health Insurance List';
$activePage = 'health';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();

$search = trim($_GET['search'] ?? '');
$params = [];
$where = "";

if (!empty($search)) {
    $where = "WHERE (h.policy_number LIKE ? OR h.customer_name LIKE ? OR h.mobile_number LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql = "
    SELECT h.*, u.shop_name as agency_name
    FROM health_insurances h
    LEFT JOIN users u ON h.agent_id = u.id
    $where
    ORDER BY h.id DESC
    LIMIT 50
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$policies = $stmt->fetchAll();
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Master Health Insurance List</h2>
    <p class="small text-muted m-0">System-wide health policy records</p>
</div>

<!-- Mobile Search Bar -->
<div class="mobile-card p-2 mb-3">
    <form action="" method="GET">
        <div class="input-group">
            <span class="input-group-text bg-transparent border-0"><i class="fa-solid fa-search text-muted"></i></span>
            <input type="text" name="search" class="form-control border-0 ps-0" placeholder="Search policy no or customer..." value="<?php echo sanitize($search); ?>" inputmode="search">
            <?php if (!empty($search)): ?>
                <a href="health.php" class="btn btn-link text-muted"><i class="fa-solid fa-xmark"></i></a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary px-3 rounded-3">Search</button>
        </div>
    </form>
</div>

<!-- Policy Card List -->
<?php if (!empty($policies)): ?>
    <?php foreach ($policies as $pol): ?>
        <div class="mobile-card">
            <div class="mobile-card-header">
                <div>
                    <h6 class="m-0 font-weight-700 text-dark"><?php echo sanitize($pol['customer_name']); ?></h6>
                    <span class="small text-muted">Policy: <?php echo sanitize($pol['policy_number']); ?></span>
                </div>
                <span class="badge bg-light text-dark border"><?php echo sanitize($pol['agency_name'] ?? 'Agency'); ?></span>
            </div>
            
            <div class="mobile-card-body p-3">
                <div class="d-flex align-items-center justify-content-between p-2 bg-light rounded-3">
                    <div>
                        <span class="small text-muted d-block" style="font-size: 0.72rem;">EXPIRY DATE</span>
                        <span class="fw-bold small <?php echo (!empty($pol['expiry_date']) && $pol['expiry_date'] < date('Y-m-d')) ? 'text-danger' : 'text-success'; ?>">
                            <?php echo !empty($pol['expiry_date']) ? date('d M Y', strtotime($pol['expiry_date'])) : 'N/A'; ?>
                        </span>
                    </div>
                    <div>
                        <span class="small text-muted d-block" style="font-size: 0.72rem;">PREMIUM</span>
                        <span class="fw-bold small text-dark">₹<?php echo number_format($pol['premium_amount'] ?? 0); ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="mobile-card p-4 text-center text-muted">
        <i class="fa-solid fa-heart-pulse fs-2 mb-2 d-block text-secondary opacity-50"></i>
        <p class="m-0 font-weight-500">No health policies found.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
