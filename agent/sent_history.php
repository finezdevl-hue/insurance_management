<?php
/**
 * WhatsApp Reminder History Log (Agent & Shop View)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce agent or shop login
checkAccess('agent');

$db = getDBConnection();
$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'agent';
$effectiveAgentId = getEffectiveAgentId();

$pageTitle = 'WhatsApp Sent History';
$pageHeading = 'My Sent Messages';
$activePage = 'sent_history';

// Calculate summary stats across the agency network
$statsWhere = "(r.sent_by_user_id = ? OR r.sent_by_user_id IN (SELECT id FROM users WHERE parent_agent_id = ?) OR c.agent_id = ? OR v.agent_id = ?)";
$statsParams = [$userId, $userId, $effectiveAgentId, $effectiveAgentId];

$stmt = $db->prepare("
    SELECT 
        COUNT(CASE WHEN r.status = 'sent' THEN 1 END) as total_sent,
        COUNT(CASE WHEN r.status = 'failed' THEN 1 END) as total_failed,
        COUNT(CASE WHEN r.reminder_type = 'Insurance' THEN 1 END) as ins_reminders,
        COUNT(CASE WHEN r.reminder_type = 'Pollution' THEN 1 END) as puc_reminders
    FROM reminder_history r
    LEFT JOIN customers c ON r.customer_id = c.id
    LEFT JOIN vehicles v ON r.vehicle_id = v.id
    WHERE $statsWhere
");
$stmt->execute($statsParams);
$stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

$totalSent = (int)($stats['total_sent'] ?? 0);
$totalFailed = (int)($stats['total_failed'] ?? 0);
$insReminders = (int)($stats['ins_reminders'] ?? 0);
$pucReminders = (int)($stats['puc_reminders'] ?? 0);

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Statistics Overview widgets -->
<div class="row">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card stat-card border-start border-4 border-success">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-paper-plane"></i>
            </div>
            <p class="stat-title">Delivered Messages</p>
            <h3 class="stat-value text-success"><?php echo $totalSent; ?></h3>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card stat-card border-start border-4 border-danger">
            <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <p class="stat-title">Failed Dispatches</p>
            <h3 class="stat-value text-danger"><?php echo $totalFailed; ?></h3>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card stat-card border-start border-4 border-primary">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-building-shield"></i>
            </div>
            <p class="stat-title">Insurance Reminders</p>
            <h3 class="stat-value text-primary"><?php echo $insReminders; ?></h3>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card stat-card border-start border-4 border-info">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                <i class="fa-solid fa-wind"></i>
            </div>
            <p class="stat-title">Pollution (PUC) Reminders</p>
            <h3 class="stat-value text-info"><?php echo $pucReminders; ?></h3>
        </div>
    </div>
</div>

<!-- History Log Card -->
<div class="card shadow-sm border-0 mt-3">
    <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-primary fs-5"></i>
            <h5 class="m-0 font-weight-700">Detailed Message Transmissions</h5>
        </div>
    </div>
    <div class="card-body">
        
        <table class="table table-hover align-middle datatable w-100">
            <thead>
                <tr>
                    <th>Sent Date</th>
                    <th>Customer Name</th>
                    <th>Vehicle / Policy</th>
                    <th>Notification Info</th>
                    <th>Message Details</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch full reminder history joining customer, vehicle, and sender with LEFT JOINs
                $stmt = $db->prepare("
                    SELECT r.*, 
                           COALESCE(c.name, 'Customer') as customer_name, 
                           COALESCE(c.mobile_number, c.whatsapp_number, 'N/A') as mobile_number, 
                           COALESCE(v.vehicle_number, '') as vehicle_number,
                           COALESCE(u.shop_name, u.username, 'Our Center') as sender_name
                    FROM reminder_history r
                    LEFT JOIN customers c ON r.customer_id = c.id
                    LEFT JOIN vehicles v ON r.vehicle_id = v.id
                    LEFT JOIN users u ON r.sent_by_user_id = u.id
                    WHERE $statsWhere
                    ORDER BY r.id DESC
                ");
                $stmt->execute($statsParams);
                
                while ($log = $stmt->fetch()):
                    $statusClass = ($log['status'] === 'sent') ? 'badge-active' : 'badge-suspended';
                    $typeIcon = ($log['reminder_type'] === 'Insurance') ? 'fa-building-shield text-primary' : (($log['reminder_type'] === 'Pollution') ? 'fa-wind text-info' : 'fa-heart-pulse text-danger');
                    
                    $vehDisplay = !empty($log['vehicle_number']) ? formatVehicleNumber($log['vehicle_number']) : (($log['reminder_type'] === 'Health') ? 'Health Policy' : 'N/A');
                ?>
                    <tr>
                        <!-- Date -->
                        <td>
                            <strong class="d-block"><?php echo date('d-M-Y', strtotime($log['sent_date'])); ?></strong>
                            <small class="text-muted"><?php echo date('h:i A', strtotime($log['sent_date'])); ?></small>
                        </td>
                        <!-- Customer -->
                        <td>
                            <strong class="text-main d-block"><?php echo sanitize($log['customer_name']); ?></strong>
                            <small class="text-muted"><i class="fa-solid fa-phone"></i> <?php echo sanitize($log['mobile_number']); ?></small>
                        </td>
                        <!-- Vehicle -->
                        <td>
                            <strong class="text-primary d-block"><?php echo sanitize($vehDisplay); ?></strong>
                            <?php if (!empty($log['sender_name']) && $log['sender_name'] !== 'Our Center'): ?>
                                <small class="text-muted" style="font-size: 0.72rem;"><i class="fa-solid fa-store me-1"></i><?php echo sanitize($log['sender_name']); ?></small>
                            <?php endif; ?>
                        </td>
                        <!-- Notification type -->
                        <td>
                            <span class="d-block small font-weight-600">
                                <i class="fa-solid <?php echo $typeIcon; ?> me-1"></i> <?php echo sanitize($log['reminder_type']); ?>
                            </span>
                            <span class="badge bg-light text-muted border text-uppercase" style="font-size: 0.65rem;">
                                Threshold: <?php echo sanitize($log['reminder_period']); ?>
                            </span>
                        </td>
                        <!-- Message Snippet -->
                        <td>
                            <p class="m-0 small text-muted text-truncate" style="max-width: 250px; cursor: pointer;" title="<?php echo sanitize($log['message']); ?>">
                                <?php echo sanitize($log['message']); ?>
                            </p>
                        </td>
                        <!-- Status -->
                        <td>
                            <span class="badge <?php echo $statusClass; ?>">
                                <?php echo strtoupper(sanitize($log['status'])); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
