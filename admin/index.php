<?php
/**
 * Super Admin Dashboard
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();

$pageTitle = 'Super Admin Dashboard';
$pageHeading = 'Dashboard Overview';
$activePage = 'dashboard';

// --- STATISTICAL DATA ---
// 1. Total Agents
$stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'agent'");
$totalAgents = $stmt->fetchColumn();

// 2. Total Customers
$stmt = $db->query("SELECT COUNT(*) FROM customers");
$totalCustomers = $stmt->fetchColumn();

// 3. Total Vehicles
$stmt = $db->query("SELECT COUNT(*) FROM vehicles");
$totalVehicles = $stmt->fetchColumn();

// 4. Upcoming Insurance Expiry (within 30 days)
$stmt = $db->query("SELECT COUNT(*) FROM insurances WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
$upcomingInsurances = $stmt->fetchColumn();

// 5. Upcoming Pollution Expiry (within 30 days)
$stmt = $db->query("SELECT COUNT(*) FROM pollution_certificates WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
$upcomingPollutions = $stmt->fetchColumn();

// 6. Total Expired (Insurance or Pollution already expired)
$stmt = $db->query("SELECT COUNT(*) FROM insurances WHERE expiry_date < CURDATE()");
$expiredInsurances = $stmt->fetchColumn();
$stmt = $db->query("SELECT COUNT(*) FROM pollution_certificates WHERE expiry_date < CURDATE()");
$expiredPollutions = $stmt->fetchColumn();
$totalExpired = $expiredInsurances + $expiredPollutions;

// 7. Recent Activity Logs (Limit 5)
$stmt = $db->query("SELECT a.*, u.username, u.role FROM activity_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 5");
$recentLogs = $stmt->fetchAll();

// --- DATA FOR CHARTS ---
// Chart A: Monthly registrations (Vehicles registered per month in current year)
$currentYear = date('Y');
$stmt = $db->prepare("SELECT MONTH(created_at) as month, COUNT(*) as count FROM vehicles WHERE YEAR(created_at) = ? GROUP BY MONTH(created_at) ORDER BY MONTH(created_at)");
$stmt->execute([$currentYear]);
$monthlyRegData = array_fill(1, 12, 0); // Fill array with 0 for months 1-12
while ($row = $stmt->fetch()) {
    $monthlyRegData[(int)$row['month']] = (int)$row['count'];
}

// Chart B: Agent-wise vehicles distribution (Agent Performance)
$stmt = $db->query("SELECT u.shop_name, u.username, COUNT(v.id) as vehicle_count FROM users u LEFT JOIN vehicles v ON u.id = v.agent_id WHERE u.role = 'agent' GROUP BY u.id ORDER BY vehicle_count DESC LIMIT 5");
$agentPerformance = $stmt->fetchAll();

// Upcoming Expiries Alert List (Combined insurance and pollution, limit 5 for dashboard)
// Query active insurances expiring in 30 days
$stmt = $db->query("
    SELECT 'Insurance' as type, i.expiry_date, v.vehicle_number, c.name as customer_name, c.mobile_number, u.shop_name
    FROM insurances i
    JOIN vehicles v ON i.vehicle_id = v.id
    JOIN customers c ON v.customer_id = c.id
    JOIN users u ON v.agent_id = u.id
    WHERE i.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    UNION ALL
    SELECT 'Pollution' as type, p.expiry_date, v.vehicle_number, c.name as customer_name, c.mobile_number, u.shop_name
    FROM pollution_certificates p
    JOIN vehicles v ON p.vehicle_id = v.id
    JOIN customers c ON v.customer_id = c.id
    JOIN users u ON v.agent_id = u.id
    WHERE p.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY expiry_date ASC
    LIMIT 5
");
$expiringAlerts = $stmt->fetchAll();

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Statistics row -->
<div class="row">
    <!-- Total Agents -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-user-tie"></i>
            </div>
            <p class="stat-title">Active Partners</p>
            <h3 class="stat-value"><?php echo $totalAgents; ?></h3>
        </div>
    </div>
    
    <!-- Total Customers -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-users"></i>
            </div>
            <p class="stat-title">Total Customers</p>
            <h3 class="stat-value"><?php echo $totalCustomers; ?></h3>
        </div>
    </div>
    
    <!-- Total Vehicles -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                <i class="fa-solid fa-car-side"></i>
            </div>
            <p class="stat-title">Registered Vehicles</p>
            <h3 class="stat-value"><?php echo $totalVehicles; ?></h3>
        </div>
    </div>
    
    <!-- Expirations pending -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card">
            <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <p class="stat-title">Expired Renewals</p>
            <h3 class="stat-value"><?php echo $totalExpired; ?></h3>
        </div>
    </div>
</div>

<div class="row mt-2">
    <!-- Chart A: Monthly registrations -->
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="fa-solid fa-chart-line text-primary me-2"></i>Monthly Vehicle Enrollments</h5>
                <span class="text-muted small">Year: <?php echo $currentYear; ?></span>
            </div>
            <div class="card-body">
                <div style="height: 300px; position: relative;">
                    <canvas id="monthlyRegistrationsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Chart B: Agent performance -->
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><i class="fa-solid fa-circle-nodes text-success me-2"></i>Top Agents Performance</h5>
            </div>
            <div class="card-body">
                <div style="height: 300px; position: relative; display: flex; align-items: center; justify-content: center;">
                    <?php if (count($agentPerformance) > 0): ?>
                        <canvas id="agentPerformanceChart"></canvas>
                    <?php else: ?>
                        <p class="text-muted text-center">No agent data registered yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-2">
    <!-- Urgent Expiry Notifications -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title"><i class="fa-solid fa-bell text-warning me-2"></i>Urgent Expiry Alerts (Next 30 Days)</h5>
                <a href="reports.php" class="btn btn-sm btn-light border text-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (count($expiringAlerts) > 0): ?>
                    <div class="expiry-alerts-list">
                        <?php foreach ($expiringAlerts as $alert): 
                            $status = getExpiryStatus($alert['expiry_date']);
                            $daysLeft = $status['days'];
                            $borderClass = ($daysLeft <= 7) ? 'border-danger' : (($daysLeft <= 15) ? 'border-warning' : 'border-info');
                        ?>
                            <div class="expiry-widget-item <?php echo $borderClass; ?>">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-secondary"><?php echo sanitize($alert['type']); ?> Expiry</span>
                                        <strong class="text-main"><?php echo sanitize($alert['vehicle_number']); ?></strong>
                                    </div>
                                    <p class="m-0 small text-muted">
                                        Owner: <?php echo sanitize($alert['customer_name']); ?> | Agent: <?php echo sanitize($alert['shop_name']); ?>
                                    </p>
                                </div>
                                <div class="text-end">
                                    <span class="badge <?php echo $status['badge']; ?> mb-1 d-inline-block">
                                        <?php echo sanitize($status['text']); ?>
                                    </span>
                                    <p class="m-0 small text-muted">Date: <?php echo date('d-M-Y', strtotime($alert['expiry_date'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fa-regular fa-bell-slash text-muted fs-1 mb-3"></i>
                        <p class="text-muted">No upcoming renewals expiring in the next 30 days.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent System Activity Logs -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title"><i class="fa-solid fa-list-check text-muted me-2"></i>Recent System Activity</h5>
                <a href="activity.php" class="btn btn-sm btn-light border text-secondary">All Logs</a>
            </div>
            <div class="card-body p-0">
                <?php if (count($recentLogs) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentLogs as $log): ?>
                            <div class="list-group-item px-4 py-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="m-0 text-main font-weight-600"><?php echo sanitize($log['action']); ?></h6>
                                    <small class="text-muted"><?php echo date('d M H:i', strtotime($log['created_at'])); ?></small>
                                </div>
                                <p class="m-0 small text-muted mb-1"><?php echo sanitize($log['details']); ?></p>
                                <div class="d-flex align-items-center justify-content-between">
                                    <small class="text-primary font-weight-500 text-uppercase" style="font-size: 0.7rem;">
                                        By: <?php echo sanitize($log['username'] ?? 'System'); ?> (<?php echo sanitize($log['role'] ?? 'System'); ?>)
                                    </small>
                                    <small class="text-muted" style="font-size: 0.7rem;">IP: <?php echo sanitize($log['ip_address']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fa-solid fa-list-ul text-muted fs-1 mb-3"></i>
                        <p class="text-muted">No logs recorded yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart Configurations JavaScript -->
<script>
$(document).ready(function() {
    // --- Chart A: Monthly registrations chart configuration ---
    const ctxMonthly = document.getElementById('monthlyRegistrationsChart').getContext('2d');
    const monthlyData = <?php echo json_encode(array_values($monthlyRegData)); ?>;
    
    new Chart(ctxMonthly, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Vehicles Registered',
                data: monthlyData,
                backgroundColor: 'rgba(59, 130, 246, 0.08)',
                borderColor: '#3b82f6',
                borderWidth: 3,
                tension: 0.35,
                fill: true,
                pointBackgroundColor: '#3b82f6',
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    },
                    grid: {
                        color: '#f1f5f9'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
    
    // --- Chart B: Agent Performance Donut Chart ---
    <?php if (count($agentPerformance) > 0): ?>
        const ctxAgent = document.getElementById('agentPerformanceChart').getContext('2d');
        const agentNames = <?php echo json_encode(array_map(function($a) { return $a['shop_name'] ?: $a['username']; }, $agentPerformance)); ?>;
        const vehicleCounts = <?php echo json_encode(array_map(function($a) { return (int)$a['vehicle_count']; }, $agentPerformance)); ?>;
        
        new Chart(ctxAgent, {
            type: 'doughnut',
            data: {
                labels: agentNames,
                datasets: [{
                    data: vehicleCounts,
                    backgroundColor: [
                        '#3b82f6', // Blue
                        '#10b981', // Emerald
                        '#f59e0b', // Amber
                        '#06b6d4', // Cyan
                        '#ef4444'  // Red
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: {
                                family: 'Outfit'
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    <?php endif; ?>
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
