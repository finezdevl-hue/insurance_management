<?php
/**
 * Settings Management (with Razorpay Payment Gateway & WhatsApp API Config)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();

$pageTitle = 'Settings Management';
$pageHeading = 'System Preferences & Payment Config';
$activePage = 'settings';

// Load current settings
$settings = getSystemSettings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input
    $systemName = trim($_POST['system_name'] ?? '');
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $currency = trim($_POST['currency'] ?? 'INR');
    $singleMessagePrice = isset($_POST['single_message_price']) ? (float)$_POST['single_message_price'] : 0.20;
    $whatsappApiUrl = trim($_POST['whatsapp_api_url'] ?? '');
    $whatsappPhoneId = trim($_POST['whatsapp_phone_number_id'] ?? '');
    $whatsappToken = trim($_POST['whatsapp_access_token'] ?? '');
    $whatsappTemplate = trim($_POST['whatsapp_template_name'] ?? '');
    $razorpayKeyId = trim($_POST['razorpay_key_id'] ?? '');
    $razorpayKeySecret = trim($_POST['razorpay_key_secret'] ?? '');
    $razorpayEnabled = isset($_POST['razorpay_enabled']) ? 1 : 0;
    
    if (empty($systemName)) {
        $_SESSION['alert_error'] = 'System Name cannot be empty.';
    } elseif ($singleMessagePrice <= 0) {
        $_SESSION['alert_error'] = 'Single message price must be greater than zero.';
    } else {
        try {
            // Check if settings record exists (or insert/update)
            $stmt = $db->query("SELECT COUNT(*) FROM settings");
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                // Update
                $stmt = $db->prepare("
                    UPDATE settings 
                    SET system_name = ?, contact_email = ?, contact_phone = ?, currency = ?, single_message_price = ?,
                        whatsapp_api_url = ?, whatsapp_phone_number_id = ?, whatsapp_access_token = ?, whatsapp_template_name = ?,
                        razorpay_key_id = ?, razorpay_key_secret = ?, razorpay_enabled = ?
                    WHERE id = (SELECT id FROM (SELECT id FROM settings ORDER BY id DESC LIMIT 1) as t)
                ");
                $stmt->execute([
                    $systemName, $contactEmail, $contactPhone, $currency, $singleMessagePrice,
                    $whatsappApiUrl, $whatsappPhoneId, $whatsappToken, $whatsappTemplate,
                    $razorpayKeyId, $razorpayKeySecret, $razorpayEnabled
                ]);
            } else {
                // Insert
                $stmt = $db->prepare("
                    INSERT INTO settings (system_name, contact_email, contact_phone, currency, single_message_price, whatsapp_api_url, whatsapp_phone_number_id, whatsapp_access_token, whatsapp_template_name, razorpay_key_id, razorpay_key_secret, razorpay_enabled)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $systemName, $contactEmail, $contactPhone, $currency, $singleMessagePrice,
                    $whatsappApiUrl, $whatsappPhoneId, $whatsappToken, $whatsappTemplate,
                    $razorpayKeyId, $razorpayKeySecret, $razorpayEnabled
                ]);
            }
            
            // Log activity
            logActivity('Update Settings', 'Global preferences, Razorpay gateway & WhatsApp configurations updated.');
            
            // Reload settings
            $settings = getSystemSettings();
            
            $_SESSION['alert_success'] = 'Settings & API configurations updated successfully!';
            redirect('settings.php');
            
        } catch (PDOException $e) {
            $_SESSION['alert_error'] = 'Database error: ' . $e->getMessage();
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-sliders text-primary fs-5"></i>
                    <h5 class="m-0 font-weight-700">System Preferences & Configurations</h5>
                </div>
            </div>
            <div class="card-body p-4">
                
                <form action="" method="POST">
                    
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs border-bottom mb-4" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active py-2.5 px-4 font-weight-600 border-0 border-bottom" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab">
                                <i class="fa-solid fa-circle-info me-2 text-muted"></i> General Info
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2.5 px-4 font-weight-600 border-0 border-bottom" id="razorpay-tab" data-bs-toggle="tab" data-bs-target="#razorpay" type="button" role="tab">
                                <i class="fa-solid fa-credit-card me-2 text-muted"></i> Razorpay Gateway
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2.5 px-4 font-weight-600 border-0 border-bottom" id="whatsapp-tab" data-bs-toggle="tab" data-bs-target="#whatsapp" type="button" role="tab">
                                <i class="fa-brands fa-whatsapp me-2 text-muted"></i> WhatsApp Cloud API
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tabs Content -->
                    <div class="tab-content" id="settingsTabsContent">
                        
                        <!-- TAB 1: General Info -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-12">
                                    <label for="system_name" class="form-label font-weight-600">System Name / Portal Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="system_name" name="system_name" value="<?php echo sanitize($settings['system_name'] ?? ''); ?>" required placeholder="Enter primary system name">
                                    <div class="form-text">This will be displayed in browser headers, titles, and templates.</div>
                                </div>
                                
                                <div class="col-12 col-md-6">
                                    <label for="contact_email" class="form-label font-weight-600">Support Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fa-solid fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo sanitize($settings['contact_email'] ?? ''); ?>" placeholder="support@domain.com">
                                    </div>
                                </div>
                                
                                <div class="col-12 col-md-6">
                                    <label for="contact_phone" class="form-label font-weight-600">Support Mobile Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fa-solid fa-phone"></i></span>
                                        <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo sanitize($settings['contact_phone'] ?? ''); ?>" placeholder="+919876543210">
                                    </div>
                                </div>
                                
                                <div class="col-12 col-md-6">
                                    <label for="currency" class="form-label font-weight-600">Local Currency Code</label>
                                    <select class="form-select" id="currency" name="currency">
                                        <option value="INR" <?php echo ($settings['currency'] ?? '') === 'INR' ? 'selected' : ''; ?>>INR (₹ - Indian Rupee)</option>
                                        <option value="USD" <?php echo ($settings['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD ($ - United States Dollar)</option>
                                        <option value="EUR" <?php echo ($settings['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR (€ - Euro)</option>
                                        <option value="AED" <?php echo ($settings['currency'] ?? '') === 'AED' ? 'selected' : ''; ?>>AED (Dirham)</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="single_message_price" class="form-label font-weight-600">Single Message Price (Rupees) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">₹</span>
                                        <input type="number" min="0.01" step="0.01" class="form-control" id="single_message_price" name="single_message_price" value="<?php echo sanitize((string)($settings['single_message_price'] ?? '0.20')); ?>" placeholder="0.20">
                                    </div>
                                    <div class="form-text">Recharge payment amounts will be converted into SMS message credits using this unit rate.</div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: Razorpay Payment Gateway Tab -->
                        <div class="tab-pane fade" id="razorpay" role="tabpanel">
                            <div class="alert alert-primary border-0 p-3 mb-4 d-flex gap-3 align-items-start" style="border-radius: 8px;">
                                <i class="fa-solid fa-credit-card fs-4 text-primary"></i>
                                <div>
                                    <h6 class="font-weight-600 text-main mb-1">Razorpay Payment Gateway Integration</h6>
                                    <p class="m-0 small text-muted">
                                        Enter your official Razorpay Key ID and Secret Key from your Razorpay Dashboard (https://dashboard.razorpay.com). 
                                        Agents can instantly purchase SMS credits and subscription plans via UPI, Google Pay, PhonePe, Cards, and Netbanking.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="row g-4">
                                <div class="col-12">
                                    <div class="form-check form-switch fs-5">
                                        <input class="form-check-input" type="checkbox" role="switch" id="razorpay_enabled" name="razorpay_enabled" value="1" <?php echo !empty($settings['razorpay_enabled']) ? 'checked' : ''; ?>>
                                        <label class="form-check-input-label font-weight-600 fs-6 ms-2" for="razorpay_enabled">Enable Razorpay Payment Gateway for Agents</label>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="razorpay_key_id" class="form-label font-weight-600">Razorpay Key ID</label>
                                    <input type="text" class="form-control font-monospace" id="razorpay_key_id" name="razorpay_key_id" value="<?php echo sanitize($settings['razorpay_key_id'] ?? ''); ?>" placeholder="rzp_live_xxxxxxxx or rzp_test_xxxxxxxx">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="razorpay_key_secret" class="form-label font-weight-600">Razorpay Key Secret</label>
                                    <input type="password" class="form-control font-monospace" id="razorpay_key_secret" name="razorpay_key_secret" value="<?php echo sanitize($settings['razorpay_key_secret'] ?? ''); ?>" placeholder="Enter Razorpay Key Secret">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: WhatsApp API Tab -->
                        <div class="tab-pane fade" id="whatsapp" role="tabpanel">
                            <div class="alert alert-info border-0 p-3 mb-4 d-flex gap-3 align-items-start" style="border-radius: 8px;">
                                <i class="fa-solid fa-circle-question fs-4 text-primary"></i>
                                <div>
                                    <h6 class="font-weight-600 text-main mb-1">WhatsApp Cloud API Configuration</h6>
                                    <p class="m-0 small text-muted">
                                        Integrates directly with the official Facebook WhatsApp Cloud API for automated renewal notifications.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="row g-4">
                                <div class="col-12 col-md-8">
                                    <label for="whatsapp_api_url" class="form-label font-weight-600">Facebook Graph Endpoint API URL</label>
                                    <input type="url" class="form-control" id="whatsapp_api_url" name="whatsapp_api_url" value="<?php echo sanitize($settings['whatsapp_api_url'] ?? 'https://graph.facebook.com/v17.0'); ?>" placeholder="https://graph.facebook.com/v17.0">
                                </div>
                                
                                <div class="col-12 col-md-4">
                                    <label for="whatsapp_phone_number_id" class="form-label font-weight-600">Phone Number ID</label>
                                    <input type="text" class="form-control" id="whatsapp_phone_number_id" name="whatsapp_phone_number_id" value="<?php echo sanitize($settings['whatsapp_phone_number_id'] ?? ''); ?>" placeholder="e.g. 1201793359690573">
                                </div>

                                <div class="col-12">
                                    <label for="whatsapp_access_token" class="form-label font-weight-600">Permanent Meta Access Token</label>
                                    <textarea class="form-control font-monospace" id="whatsapp_access_token" name="whatsapp_access_token" rows="3" placeholder="EAAVXZCA0n0..."><?php echo sanitize($settings['whatsapp_access_token'] ?? ''); ?></textarea>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="whatsapp_template_name" class="form-label font-weight-600">Default Template Name</label>
                                    <input type="text" class="form-control" id="whatsapp_template_name" name="whatsapp_template_name" value="<?php echo sanitize($settings['whatsapp_template_name'] ?? 'pollution_puc_expiry_alert'); ?>" placeholder="pollution_puc_expiry_alert">
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="small text-muted">
                            <i class="fa-solid fa-shield-halved text-success me-1"></i> Changes will take effect immediately.
                        </span>
                        <button type="submit" class="btn btn-primary px-4 py-2 font-weight-600">
                            <i class="fa-solid fa-floppy-disk me-2"></i> Save Configurations
                        </button>
                    </div>

                </form>

            </div>
        </div>

    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
