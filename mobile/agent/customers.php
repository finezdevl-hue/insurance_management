<?php
/**
 * Dedicated Mobile Customers Directory
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('agent');

$pageTitle = 'Customer Directory';
$activePage = 'customers';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$agentId = getEffectiveAgentId();

$search = trim($_GET['search'] ?? '');
$params = [$agentId];
$where = "WHERE agent_id = ?";

if (!empty($search)) {
    $where .= " AND (name LIKE ? OR mobile_number LIKE ? OR city LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql = "SELECT * FROM customers $where ORDER BY id DESC LIMIT 50";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Customer Directory</h2>
    <p class="small text-muted m-0">Contact list & quick call/message actions</p>
</div>

<!-- Mobile Search Bar -->
<div class="mobile-card p-2 mb-3">
    <form action="" method="GET">
        <div class="input-group">
            <span class="input-group-text bg-transparent border-0"><i class="fa-solid fa-search text-muted"></i></span>
            <input type="text" name="search" class="form-control border-0 ps-0" placeholder="Search customer name or mobile..." value="<?php echo sanitize($search); ?>" inputmode="search">
            <?php if (!empty($search)): ?>
                <a href="customers.php" class="btn btn-link text-muted"><i class="fa-solid fa-xmark"></i></a>
            <?php endif; ?>
            <button type="submit" class="btn btn-success px-3 rounded-3">Search</button>
        </div>
    </form>
</div>

<!-- Customers Card List -->
<?php if (!empty($customers)): ?>
    <?php foreach ($customers as $cust): ?>
        <div class="mobile-card">
            <div class="mobile-card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <h6 class="m-0 font-weight-700 text-dark"><?php echo sanitize($cust['name']); ?></h6>
                        <span class="small text-muted"><i class="fa-solid fa-location-dot me-1"></i><?php echo sanitize($cust['city'] ?? 'Location N/A'); ?></span>
                    </div>
                    <div class="user-avatar bg-success-subtle text-success font-weight-700" style="width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                        <?php echo substr(sanitize($cust['name']), 0, 2); ?>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between gap-2 mt-3">
                    <?php if (!empty($cust['mobile_number'])): ?>
                        <a href="tel:<?php echo sanitize($cust['mobile_number']); ?>" class="btn btn-outline-secondary btn-sm touch-action-btn flex-grow-1">
                            <i class="fa-solid fa-phone me-1"></i> Call
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($cust['whatsapp_number'])): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $cust['whatsapp_number']); ?>" target="_blank" class="btn btn-success btn-sm touch-action-btn flex-grow-1">
                            <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="mobile-card p-4 text-center text-muted">
        <i class="fa-solid fa-users fs-2 mb-2 d-block text-secondary opacity-50"></i>
        <p class="m-0 font-weight-500">No customers found.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
