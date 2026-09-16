<?php
/**
 * Dedicated Mobile Admin Insurance Companies Master
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('admin');

$pageTitle = 'Insurance Companies';
$activePage = 'companies';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();
$companies = $db->query("SELECT * FROM insurance_companies ORDER BY name ASC")->fetchAll();
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Insurance Companies</h2>
    <p class="small text-muted m-0">Master insurance providers list</p>
</div>

<?php if (!empty($companies)): ?>
    <?php foreach ($companies as $comp): ?>
        <div class="mobile-card">
            <div class="mobile-card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="m-0 font-weight-700 text-dark"><?php echo sanitize($comp['name']); ?></h6>
                    <span class="small text-muted">Status: Active</span>
                </div>
                <i class="fa-solid fa-building-shield text-primary fs-4"></i>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="mobile-card p-4 text-center text-muted">
        <i class="fa-solid fa-building-shield fs-2 mb-2 d-block text-secondary opacity-50"></i>
        <p class="m-0 font-weight-500">No insurance companies found.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
