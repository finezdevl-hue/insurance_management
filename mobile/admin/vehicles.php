<?php
/**
 * Dedicated Mobile Admin Master Vehicle List
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('admin');

$pageTitle = 'Master Vehicle List';
$activePage = 'vehicles';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();

$search = trim($_GET['search'] ?? '');
$params = [];
$where = "";

if (!empty($search)) {
    $where = "WHERE (v.vehicle_number LIKE ? OR c.name LIKE ? OR c.mobile_number LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql = "
    SELECT v.*, c.name as customer_name, c.mobile_number, u.shop_name as agency_name,
           i.expiry_date as insurance_expiry, p.expiry_date as pollution_expiry
    FROM vehicles v
    LEFT JOIN customers c ON v.customer_id = c.id
    LEFT JOIN users u ON v.agent_id = u.id
    LEFT JOIN insurances i ON v.id = i.vehicle_id
    LEFT JOIN pollution_certificates p ON v.id = p.vehicle_id
    $where
    ORDER BY v.id DESC
    LIMIT 50
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Master Vehicles List</h2>
    <p class="small text-muted m-0">All vehicles registered across agencies</p>
</div>

<!-- Mobile Search Bar -->
<div class="mobile-card p-2 mb-3">
    <form action="" method="GET">
        <div class="input-group">
            <span class="input-group-text bg-transparent border-0"><i class="fa-solid fa-search text-muted"></i></span>
            <input type="text" name="search" class="form-control border-0 ps-0" placeholder="Search vehicle number or customer..." value="<?php echo sanitize($search); ?>" inputmode="search">
            <?php if (!empty($search)): ?>
                <a href="vehicles.php" class="btn btn-link text-muted"><i class="fa-solid fa-xmark"></i></a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary px-3 rounded-3">Search</button>
        </div>
    </form>
</div>

<!-- Vehicle Card List -->
<?php if (!empty($vehicles)): ?>
    <?php foreach ($vehicles as $veh): ?>
        <div class="mobile-card">
            <div class="mobile-card-header">
                <div>
                    <h6 class="m-0 font-weight-700 text-dark"><?php echo sanitize($veh['vehicle_number']); ?></h6>
                    <span class="small text-muted">Customer: <?php echo sanitize($veh['customer_name']); ?></span>
                </div>
                <span class="badge bg-light text-dark border"><?php echo sanitize($veh['agency_name'] ?? 'Agency'); ?></span>
            </div>
            
            <div class="mobile-card-body p-3">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="p-2 border rounded-3 bg-light">
                            <span class="small text-muted d-block" style="font-size: 0.72rem;">INSURANCE</span>
                            <span class="fw-bold small <?php echo (!empty($veh['insurance_expiry']) && $veh['insurance_expiry'] < date('Y-m-d')) ? 'text-danger' : 'text-success'; ?>">
                                <?php echo !empty($veh['insurance_expiry']) ? date('d M Y', strtotime($veh['insurance_expiry'])) : 'N/A'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 border rounded-3 bg-light">
                            <span class="small text-muted d-block" style="font-size: 0.72rem;">POLLUTION</span>
                            <span class="fw-bold small <?php echo (!empty($veh['pollution_expiry']) && $veh['pollution_expiry'] < date('Y-m-d')) ? 'text-danger' : 'text-success'; ?>">
                                <?php echo !empty($veh['pollution_expiry']) ? date('d M Y', strtotime($veh['pollution_expiry'])) : 'N/A'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="mobile-card p-4 text-center text-muted">
        <i class="fa-solid fa-car-side fs-2 mb-2 d-block text-secondary opacity-50"></i>
        <p class="m-0 font-weight-500">No vehicles found.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
