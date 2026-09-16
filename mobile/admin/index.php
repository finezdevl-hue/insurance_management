<?php
/**
 * Dedicated Mobile Admin Dashboard
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('admin');

$pageTitle = 'Admin Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/../includes/header.php';

$db = getDBConnection();

// Fetch System Overview Stats
$totalAgents = $db->query("SELECT COUNT(*) FROM users WHERE role = 'agent'")->fetchColumn();
$totalShops = $db->query("SELECT COUNT(*) FROM users WHERE role = 'shop'")->fetchColumn();
$totalVehicles = $db->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$totalHealth = $db->query("SELECT COUNT(*) FROM health_insurances")->fetchColumn();
$recentLogs = $db->query("SELECT * FROM activity_logs ORDER BY id DESC LIMIT 5")->fetchAll();
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Super Admin Dashboard</h2>
    <p class="small text-muted m-0">System overview & metrics</p>
</div>

<!-- Touch Stat Grid -->
<div class="row g-2 mb-3">
    <div class="col-6">
        <a href="agents.php" class="text-decoration-none">
            <div class="mobile-card p-3 text-center h-100 mb-0">
                <i class="fa-solid fa-user-tie text-primary fs-3 mb-2"></i>
                <h4 class="font-weight-700 m-0 text-dark"><?php echo number_format($totalAgents); ?></h4>
                <span class="small text-muted">Total Agents</span>
            </div>
        </a>
    </div>
    <div class="col-6">
        <a href="shops.php" class="text-decoration-none">
            <div class="mobile-card p-3 text-center h-100 mb-0">
                <i class="fa-solid fa-store text-info fs-3 mb-2"></i>
                <h4 class="font-weight-700 m-0 text-dark"><?php echo number_format($totalShops); ?></h4>
                <span class="small text-muted">Testing Centers</span>
            </div>
        </a>
    </div>
    <div class="col-6">
        <a href="vehicles.php" class="text-decoration-none">
            <div class="mobile-card p-3 text-center h-100 mb-0">
                <i class="fa-solid fa-car-side text-success fs-3 mb-2"></i>
                <h4 class="font-weight-700 m-0 text-dark"><?php echo number_format($totalVehicles); ?></h4>
                <span class="small text-muted">Vehicles List</span>
            </div>
        </a>
    </div>
    <div class="col-6">
        <a href="health.php" class="text-decoration-none">
            <div class="mobile-card p-3 text-center h-100 mb-0">
                <i class="fa-solid fa-heart-pulse text-danger fs-3 mb-2"></i>
                <h4 class="font-weight-700 m-0 text-dark"><?php echo number_format($totalHealth); ?></h4>
                <span class="small text-muted">Health Policies</span>
            </div>
        </a>
    </div>
</div>

<!-- Recent Activity Log Card -->
<div class="mobile-card">
    <div class="mobile-card-header">
        <h6 class="m-0 font-weight-700 text-dark"><i class="fa-solid fa-list-check me-1 text-primary"></i> Recent System Activity</h6>
        <a href="activity.php" class="small text-decoration-none font-weight-600">View All</a>
    </div>
    <div class="mobile-card-body p-2">
        <?php if (!empty($recentLogs)): ?>
            <?php foreach ($recentLogs as $log): ?>
                <div class="p-2 border-bottom">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="fw-bold small text-dark"><?php echo sanitize($log['action']); ?></span>
                        <span class="small text-muted" style="font-size: 0.7rem;"><?php echo date('d M, h:i A', strtotime($log['created_at'])); ?></span>
                    </div>
                    <p class="small text-muted m-0"><?php echo sanitize($log['details']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-3 text-muted small">No recent activity logs.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
