<?php
/**
 * System Activity Logs (Super Admin Audit)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();

$pageTitle = 'System Audit Activity Logs';
$pageHeading = 'System Activity Logs';
$activePage = 'activity';

// Action: Clear all logs (only if requested by post)
if (isset($_POST['clear_logs'])) {
    try {
        $db->query("DELETE FROM activity_logs");
        logActivity('Clear Logs', 'All system activity logs were cleared by Super Admin.');
        $_SESSION['alert_success'] = 'All activity logs have been cleared successfully!';
    } catch (PDOException $e) {
        $_SESSION['alert_error'] = 'Failed to clear logs: ' . $e->getMessage();
    }
    redirect('activity.php');
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-list-check text-primary fs-5"></i>
            <h5 class="m-0 font-weight-700">Audit Trail & Security Logs</h5>
        </div>
        <form action="" method="POST" id="clearLogsForm">
            <button type="submit" name="clear_logs" class="btn btn-danger btn-sm" id="clearBtn">
                <i class="fa-regular fa-trash-can me-1"></i> Clear Audit Logs
            </button>
        </form>
    </div>
    <div class="card-body">
        
        <table class="table table-hover align-middle datatable w-100">
            <thead>
                <tr>
                    <th style="width: 80px;">Log ID</th>
                    <th style="width: 180px;">Date & Time</th>
                    <th>Action Executed</th>
                    <th>Audit Description / Details</th>
                    <th>User / Actor</th>
                    <th style="width: 140px;">IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->query("
                    SELECT a.*, u.username, u.role 
                    FROM activity_logs a 
                    LEFT JOIN users u ON a.user_id = u.id 
                    ORDER BY a.id DESC
                ");
                while ($log = $stmt->fetch()):
                    $roleLabel = $log['role'] ? '(' . strtoupper($log['role']) . ')' : '(SYSTEM)';
                    $roleClass = ($log['role'] === 'admin') ? 'text-primary' : (($log['role'] === 'agent') ? 'text-success' : 'text-muted');
                ?>
                    <tr>
                        <td><span class="text-muted font-weight-600">#<?php echo $log['id']; ?></span></td>
                        <td>
                            <span class="d-block small text-main font-weight-500">
                                <?php echo date('d-M-Y', strtotime($log['created_at'])); ?>
                            </span>
                            <span class="text-muted small" style="font-size: 0.75rem;">
                                <?php echo date('H:i:s A', strtotime($log['created_at'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="font-weight-600 text-main small text-uppercase bg-light border px-2 py-1 rounded" style="font-size: 0.75rem;">
                                <?php echo sanitize($log['action']); ?>
                            </span>
                        </td>
                        <td>
                            <p class="m-0 small text-muted text-wrap" style="max-width: 350px;">
                                <?php echo sanitize($log['details']); ?>
                            </p>
                        </td>
                        <td>
                            <strong class="text-main small d-block"><?php echo sanitize($log['username'] ?? 'SYSTEM'); ?></strong>
                            <small class="<?php echo $roleClass; ?> font-weight-500" style="font-size: 0.7rem;">
                                <?php echo $roleLabel; ?>
                            </small>
                        </td>
                        <td>
                            <span class="badge bg-light text-muted border font-monospace">
                                <?php echo sanitize($log['ip_address']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
    </div>
</div>

<script>
$(document).ready(function() {
    $('#clearBtn').on('click', function(e) {
        e.preventDefault();
        Swal.fire({
            title: 'Are you absolutely sure?',
            text: "This will permanently wipe all audit log records from the database! This action is irreversible.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, clear all logs'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#clearLogsForm').submit();
            }
        });
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
