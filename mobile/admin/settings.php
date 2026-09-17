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
    $currency = trim($_POST['currency'] ?? 'INR');
    $singleMessagePrice = isset($_POST['single_message_price']) ? (float)$_POST['single_message_price'] : 0.20;
    
    if (!empty($systemName)) {
        try {
            $stmt = $db->prepare("
                UPDATE settings 
                SET system_name = ?, contact_email = ?, contact_phone = ?, currency = ?, single_message_price = ?
                WHERE id = (SELECT id FROM (SELECT id FROM settings ORDER BY id DESC LIMIT 1) as t)
            ");
            $stmt->execute([$systemName, $contactEmail, $contactPhone, $currency, $singleMessagePrice]);
            $_SESSION['alert_success'] = 'Settings updated successfully!';
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
    <p class="small text-muted m-0">Global preferences & configurations</p>
</div>

<div class="mobile-card p-3 mb-3">
    <form action="" method="POST">
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

        <div class="mb-3">
            <label class="form-label font-weight-600">Local Currency</label>
            <select class="form-select" name="currency">
                <option value="INR" <?php echo ($settings['currency'] ?? '') === 'INR' ? 'selected' : ''; ?>>INR (₹)</option>
                <option value="USD" <?php echo ($settings['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                <option value="EUR" <?php echo ($settings['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                <option value="AED" <?php echo ($settings['currency'] ?? '') === 'AED' ? 'selected' : ''; ?>>AED</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label font-weight-600">Single Message Price (₹)</label>
            <input type="number" step="0.01" min="0.01" name="single_message_price" class="form-control" value="<?php echo sanitize((string)($settings['single_message_price'] ?? '0.20')); ?>">
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2.5 font-weight-600">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save Settings
        </button>
    </form>
</div>

<p class="small text-muted text-center m-0">Advanced WhatsApp Cloud API and Razorpay settings available on desktop.</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
