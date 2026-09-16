<?php
/**
 * Dedicated Mobile Admin Settings
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('admin');

$pageTitle = 'System Settings';
$activePage = 'settings';

$db = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $systemName = trim($_POST['system_name'] ?? '');
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $notificationSendTime = trim($_POST['notification_send_time'] ?? '09:00');
    $autoNotificationsEnabled = isset($_POST['auto_notifications_enabled']) ? 1 : 0;
    
    if (!empty($systemName)) {
        try {
            $stmt = $db->prepare("
                UPDATE settings 
                SET system_name = ?, contact_email = ?, contact_phone = ?,
                    notification_send_time = ?, auto_notifications_enabled = ?
                WHERE id = (SELECT id FROM (SELECT id FROM settings ORDER BY id DESC LIMIT 1) as t)
            ");
            $stmt->execute([$systemName, $contactEmail, $contactPhone, $notificationSendTime, $autoNotificationsEnabled]);
            $_SESSION['alert_success'] = 'Settings & message dispatch schedule updated!';
            redirect('settings.php');
        } catch (PDOException $e) {
            $_SESSION['alert_error'] = 'Error: ' . $e->getMessage();
        }
    }
}

$settings = getSystemSettings();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">System Settings</h2>
    <p class="small text-muted m-0">Preferences & message dispatch timing</p>
</div>

<div class="mobile-card p-3 mb-3">
    <form action="" method="POST">
        <h6 class="font-weight-700 text-primary mb-3">
            <i class="fa-solid fa-clock me-1"></i> Automated Reminders Schedule
        </h6>
        
        <div class="mb-3">
            <label class="form-label font-weight-600">Daily Message Dispatch Time</label>
            <input type="time" name="notification_send_time" class="form-control form-control-lg font-monospace font-weight-600" value="<?php echo sanitize($settings['notification_send_time'] ?? '09:00'); ?>" required>
            <div class="form-text small">Scheduled at: <strong><?php echo date('h:i A', strtotime($settings['notification_send_time'] ?? '09:00')); ?></strong> daily.</div>
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" id="auto_notifications_enabled" name="auto_notifications_enabled" value="1" <?php echo !empty($settings['auto_notifications_enabled']) ? 'checked' : ''; ?>>
            <label class="form-check-label font-weight-600" for="auto_notifications_enabled">Enable Automated Messaging</label>
        </div>

        <hr class="my-3 text-muted">

        <h6 class="font-weight-700 text-primary mb-3">
            <i class="fa-solid fa-circle-info me-1"></i> General Details
        </h6>

        <div class="mb-3">
            <label class="form-label font-weight-600">System Name</label>
            <input type="text" name="system_name" class="form-control" value="<?php echo sanitize($settings['system_name'] ?? ''); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label font-weight-600">Support Email</label>
            <input type="email" name="contact_email" class="form-control" value="<?php echo sanitize($settings['contact_email'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label class="form-label font-weight-600">Support Mobile</label>
            <input type="text" name="contact_phone" class="form-control" value="<?php echo sanitize($settings['contact_phone'] ?? ''); ?>">
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2.5 font-weight-600">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Settings
        </button>
    </form>
</div>

<div class="mobile-card p-3 mb-3 text-center">
    <p class="small text-muted mb-2">Want to trigger automated WhatsApp messages right now?</p>
    <a href="../../cron/send_automated_notifications.php?run_cron=1&force=1&redirect=mobile/admin/settings.php" class="btn btn-outline-success btn-sm w-100 py-2 font-weight-600" onclick="return confirm('Trigger all automated messages now?');">
        <i class="fa-solid fa-bolt me-1"></i> Trigger Messages Now
    </a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
