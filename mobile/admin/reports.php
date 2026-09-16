<?php
/**
 * Dedicated Mobile Admin Expiry Reports
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('admin');

$pageTitle = 'Expiry Reports';
$activePage = 'reports';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();

$days = (int)($_GET['days'] ?? 30);

$stmt = $db->prepare("
    SELECT i.expiry_date, v.vehicle_number, c.name as customer_name, u.shop_name as agency_name
    FROM insurances i
    JOIN vehicles v ON i.vehicle_id = v.id
    JOIN customers c ON v.customer_id = c.id
    JOIN users u ON i.agent_id = u.id
    WHERE i.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
    ORDER BY i.expiry_date ASC
    LIMIT 50
");
$stmt->execute([$days]);
$reports = $stmt->fetchAll();
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">System Expiry Reports</h2>
    <p class="small text-muted m-0">System-wide upcoming renewal tracking</p>
</div>

<!-- Mobile Days Filter Pill Selector -->
<div class="d-flex align-items-center gap-2 mb-3 overflow-auto pb-1" style="white-space: nowrap;">
    <a href="reports.php?days=7" class="btn btn-sm rounded-pill <?php echo $days === 7 ? 'btn-primary' : 'btn-light border'; ?>">Next 7 Days</a>
    <a href="reports.php?days=15" class="btn btn-sm rounded-pill <?php echo $days === 15 ? 'btn-primary' : 'btn-light border'; ?>">Next 15 Days</a>
    <a href="reports.php?days=30" class="btn btn-sm rounded-pill <?php echo $days === 30 ? 'btn-primary' : 'btn-light border'; ?>">Next 30 Days</a>
</div>

<?php if (!empty($reports)): ?>
    <?php foreach ($reports as $rep): ?>
        <div class="mobile-card">
            <div class="mobile-card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="m-0 font-weight-700 text-dark"><?php echo sanitize($rep['vehicle_number']); ?></h6>
                    <span class="small text-muted"><i class="fa-solid fa-user me-1"></i><?php echo sanitize($rep['customer_name']); ?> (<?php echo sanitize($rep['agency_name']); ?>)</span>
                </div>
                <span class="badge bg-warning-subtle text-warning-emphasis border">
                    <?php echo date('d M Y', strtotime($rep['expiry_date'])); ?>
                </span>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="mobile-card p-4 text-center text-muted">
        <i class="fa-solid fa-file-invoice-dollar fs-2 mb-2 d-block text-secondary opacity-50"></i>
        <p class="m-0 font-weight-500">No expirations found for selected period.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
