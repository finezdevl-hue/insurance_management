<?php
/**
 * WhatsApp Reminder History Log (Agent View)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce agent login
checkAccess('agent');

$db = getDBConnection();
$agentId = $_SESSION['user_id'];

$pageTitle = 'WhatsApp Sent History';
$pageHeading = 'My Sent Messages';
$activePage = 'sent_history';

// Calculate summary stats for the agent
$stmt = $db->prepare("SELECT COUNT(*) FROM reminder_history WHERE status = 'sent' AND sent_by_user_id = ?");
$stmt->execute([$agentId]);
$totalSent = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM reminder_history WHERE status = 'failed' AND sent_by_user_id = ?");
$stmt->execute([$agentId]);
$totalFailed = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM reminder_history WHERE reminder_type = 'Insurance' AND sent_by_user_id = ?");
$stmt->execute([$agentId]);
$insReminders = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM reminder_history WHERE reminder_type = 'Pollution' AND sent_by_user_id = ?");
$stmt->execute([$agentId]);
$pucReminders = $stmt->fetchColumn();

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
                    <th>Vehicle Details</th>
                    <th>Notification Info</th>
                    <th>Message Details</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch full reminder history joining customer, vehicle, and sender
                $stmt = $db->prepare("
                    SELECT r.*, c.name as customer_name, c.mobile_number, v.vehicle_number
                    FROM reminder_history r
                    JOIN customers c ON r.customer_id = c.id
                    JOIN vehicles v ON r.vehicle_id = v.id
                    WHERE r.sent_by_user_id = ?
                    ORDER BY r.id DESC
                ");
                $stmt->execute([$agentId]);
                
                while ($log = $stmt->fetch()):
                    $statusClass = ($log['status'] === 'sent') ? 'badge-active' : 'badge-suspended';
                    $typeIcon = ($log['reminder_type'] === 'Insurance') ? 'fa-building-shield text-primary' : (($log['reminder_type'] === 'Pollution') ? 'fa-wind text-info' : 'fa-car text-warning');
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
                            <strong class="text-primary d-block"><?php echo sanitize($log['vehicle_number']); ?></strong>
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
                            <p class="m-0 small text-muted text-truncate" style="max-width: 250px;" title="<?php echo sanitize($log['message']); ?>">
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
