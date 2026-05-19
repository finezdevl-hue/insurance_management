<?php
/**
 * WhatsApp Reminder History Log (Super Admin View)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();

$pageTitle = 'WhatsApp Reminder History';
$pageHeading = 'WhatsApp Reminders Log';
$activePage = 'reminders';

// Calculate summary stats
$totalSent = $db->query("SELECT COUNT(*) FROM reminder_history WHERE status = 'sent'")->fetchColumn();
$totalFailed = $db->query("SELECT COUNT(*) FROM reminder_history WHERE status = 'failed'")->fetchColumn();
$insReminders = $db->query("SELECT COUNT(*) FROM reminder_history WHERE reminder_type = 'Insurance'")->fetchColumn();
$pucReminders = $db->query("SELECT COUNT(*) FROM reminder_history WHERE reminder_type = 'Pollution'")->fetchColumn();

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Statistics Overview widgets -->
<div class="row">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card stat-card">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-paper-plane"></i>
            </div>
            <p class="stat-title">Delivered Messages</p>
            <h3 class="stat-value text-success"><?php echo $totalSent; ?></h3>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card stat-card">
            <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <p class="stat-title">Failed Dispatches</p>
            <h3 class="stat-value text-danger"><?php echo $totalFailed; ?></h3>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card stat-card">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-building-shield"></i>
            </div>
            <p class="stat-title">Insurance Reminders</p>
            <h3 class="stat-value text-primary"><?php echo $insReminders; ?></h3>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card stat-card">
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
                    <th>Customer Name</th>
                    <th>Vehicle Details</th>
                    <th>Notification Info</th>
                    <th>Message Details</th>
                    <th>Status</th>
                    <th>Sent By</th>
                    <th class="text-end">API Logs</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch full reminder history joining customer, vehicle, and sender
                $stmt = $db->query("
                    SELECT r.*, c.name as customer_name, c.mobile_number, v.vehicle_number, u.username as sender_name, u.shop_name
                    FROM reminder_history r
                    JOIN customers c ON r.customer_id = c.id
                    JOIN vehicles v ON r.vehicle_id = v.id
                    JOIN users u ON r.sent_by_user_id = u.id
                    ORDER BY r.id DESC
                ");
                
                while ($log = $stmt->fetch()):
                    $statusClass = ($log['status'] === 'sent') ? 'badge-active' : 'badge-suspended';
                    $typeIcon = ($log['reminder_type'] === 'Insurance') ? 'fa-building-shield text-primary' : (($log['reminder_type'] === 'Pollution') ? 'fa-wind text-info' : 'fa-car text-warning');
                ?>
                    <tr>
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
                        <!-- Sent By -->
                        <td>
                            <strong class="small d-block text-main"><?php echo sanitize($log['shop_name']); ?></strong>
                            <small class="text-muted">(@<?php echo sanitize($log['sender_name']); ?>)</small>
                        </td>
                        <!-- Collapsible raw JSON response modal toggle -->
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-light border show-api-btn" 
                                    data-message="<?php echo sanitize($log['message']); ?>" 
                                    data-response='<?php echo sanitize($log['api_response'] ?: "{}"); ?>'>
                                <i class="fa-solid fa-code"></i> Logs
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
    </div>
</div>

<!-- API Log Modal -->
<div class="modal fade" id="apiModal" tabindex="-1" aria-labelledby="apiModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-700" id="apiModalLabel"><i class="fa-solid fa-code text-primary me-2"></i>API Response Payload</h5>
                <button type="button" class="btn-close" data-bs-dismiss="none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label font-weight-600">Dispatched Message Content:</label>
                    <div class="p-3 bg-light border rounded small text-muted" id="modal-msg-text"></div>
                </div>
                <div>
                    <label class="form-label font-weight-600">Raw Endpoint Server Response:</label>
                    <pre class="p-3 bg-dark text-success border rounded small font-monospace" style="max-height: 250px; overflow-y: auto;" id="modal-api-response"></pre>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close Logs</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // API modal popup handler
    $('.show-api-btn').on('click', function() {
        const msg = $(this).data('message');
        const resp = $(this).data('response');
        
        $('#modal-msg-text').text(msg);
        
        // Format JSON cleanly
        try {
            const formatted = JSON.stringify(resp, null, 4);
            $('#modal-api-response').text(formatted);
        } catch(e) {
            $('#modal-api-response').text(JSON.stringify(resp));
        }
        
        // Trigger Bootstrap modal
        const myModal = new bootstrap.Modal(document.getElementById('apiModal'));
        myModal.show();
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
