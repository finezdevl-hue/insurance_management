<?php
/**
 * Settings Management
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();

$pageTitle = 'Settings Management';
$pageHeading = 'System Settings';
$activePage = 'settings';

// Load current settings
$settings = getSystemSettings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input
    $systemName = trim($_POST['system_name']);
    $contactEmail = trim($_POST['contact_email']);
    $contactPhone = trim($_POST['contact_phone']);
    $currency = trim($_POST['currency']);
    $singleMessagePrice = isset($_POST['single_message_price']) ? (float)$_POST['single_message_price'] : 0;
    $whatsappApiUrl = trim($_POST['whatsapp_api_url']);
    $whatsappPhoneId = trim($_POST['whatsapp_phone_number_id']);
    $whatsappToken = trim($_POST['whatsapp_access_token']);
    $whatsappTemplate = trim($_POST['whatsapp_template_name']);
    
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
                        whatsapp_api_url = ?, whatsapp_phone_number_id = ?, whatsapp_access_token = ?, whatsapp_template_name = ?
                    WHERE id = (SELECT id FROM (SELECT id FROM settings ORDER BY id DESC LIMIT 1) as t)
                ");
                $stmt->execute([
                    $systemName, $contactEmail, $contactPhone, $currency, $singleMessagePrice,
                    $whatsappApiUrl, $whatsappPhoneId, $whatsappToken, $whatsappTemplate
                ]);
            } else {
                // Insert
                $stmt = $db->prepare("
                    INSERT INTO settings (system_name, contact_email, contact_phone, currency, single_message_price, whatsapp_api_url, whatsapp_phone_number_id, whatsapp_access_token, whatsapp_template_name)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $systemName, $contactEmail, $contactPhone, $currency, $singleMessagePrice,
                    $whatsappApiUrl, $whatsappPhoneId, $whatsappToken, $whatsappTemplate
                ]);
            }
            
            // Log activity
            logActivity('Update Settings', 'Global system and WhatsApp API configurations updated.');
            
            // Reload settings
            $settings = getSystemSettings();
            
            $_SESSION['alert_success'] = 'Settings updated successfully!';
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
        
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-sliders text-primary fs-5"></i>
                    <h5 class="m-0 font-weight-700">System Preferences</h5>
                </div>
            </div>
            <div class="card-body">
                
                <form action="" method="POST">
                    
                    <!-- Tabs Navigation -->
                    <ul class="nav nav-tabs border-bottom mb-4" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active py-2.5 px-4 font-weight-600 border-0 border-bottom" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                                <i class="fa-solid fa-circle-info me-2 text-muted"></i> General Info
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2.5 px-4 font-weight-600 border-0 border-bottom" id="whatsapp-tab" data-bs-toggle="tab" data-bs-target="#whatsapp" type="button" role="tab" aria-controls="whatsapp" aria-selected="false">
                                <i class="fa-brands fa-whatsapp me-2 text-muted"></i> WhatsApp Cloud API
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tabs Content -->
                    <div class="tab-content" id="settingsTabsContent">
                        
                        <!-- General Info Tab -->
                        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="row g-4">
                                <div class="col-12">
                                    <label for="system_name" class="form-label">System Name / Portal Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="system_name" name="system_name" value="<?php echo sanitize($settings['system_name']); ?>" required placeholder="Enter primary system name">
                                    <div class="form-text">This will be displayed in browser headers, titles, and templates.</div>
                                </div>
                                
                                <div class="col-12 col-md-6">
                                    <label for="contact_email" class="form-label">Support Email Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fa-solid fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo sanitize($settings['contact_email'] ?? ''); ?>" placeholder="support@domain.com">
                                    </div>
                                </div>
                                
                                <div class="col-12 col-md-6">
                                    <label for="contact_phone" class="form-label">Support Mobile Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="fa-solid fa-phone"></i></span>
                                        <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo sanitize($settings['contact_phone'] ?? ''); ?>" placeholder="+919876543210">
                                    </div>
                                </div>
                                
                                <div class="col-12 col-md-6">
                                    <label for="currency" class="form-label">Local Currency Code</label>
                                    <select class="form-select" id="currency" name="currency">
                                        <option value="INR" <?php echo ($settings['currency'] ?? '') === 'INR' ? 'selected' : ''; ?>>INR (₹ - Indian Rupee)</option>
                                        <option value="USD" <?php echo ($settings['currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD ($ - United States Dollar)</option>
                                        <option value="EUR" <?php echo ($settings['currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR (€ - Euro)</option>
                                        <option value="AED" <?php echo ($settings['currency'] ?? '') === 'AED' ? 'selected' : ''; ?>>AED (Dirham)</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="single_message_price" class="form-label">Single Message Price (Rupees)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rs</span>
                                        <input type="number" min="0.01" step="0.01" class="form-control" id="single_message_price" name="single_message_price" value="<?php echo sanitize((string)($settings['single_message_price'] ?? '1.00')); ?>" placeholder="1.00">
                                    </div>
                                    <div class="form-text">Admin recharge amounts will be converted into message credits using this rate.</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- WhatsApp API Tab -->
                        <div class="tab-pane fade" id="whatsapp" role="tabpanel" aria-labelledby="whatsapp-tab">
                            <div class="alert alert-info border-0 p-3 mb-4 d-flex gap-3 align-items-start" style="border-radius: 8px;">
                                <i class="fa-solid fa-circle-question fs-4 text-primary"></i>
                                <div>
                                    <h6 class="font-weight-600 text-main mb-1">WhatsApp Cloud API Configuration</h6>
                                    <p class="m-0 small text-muted">
                                        Integrates directly with the official Facebook WhatsApp Cloud API. Leave fields empty or set Access Token to default mock value to use the built-in <strong>Simulated Sandbox Mode</strong>. 
                                        In Sandbox Mode, all alerts return a simulated "success" log for easy demonstration and local testing.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="row g-4">
                                <div class="col-12 col-md-8">
                                    <label for="whatsapp_api_url" class="form-label">Facebook Graph Endpoint API URL</label>
                                    <input type="url" class="form-control" id="whatsapp_api_url" name="whatsapp_api_url" value="<?php echo sanitize($settings['whatsapp_api_url'] ?? 'https://graph.facebook.com/v17.0'); ?>" placeholder="https://graph.facebook.com/v17.0">
                                </div>
                                
                                <div class="col-12 col-md-4">
                                    <label for="whatsapp_phone_number_id" class="form-label">Phone Number ID</label>
                                    <input type="text" class="form-control" id="whatsapp_phone_number_id" name="whatsapp_phone_number_id" value="<?php echo sanitize($settings['whatsapp_phone_number_id'] ?? ''); ?>" placeholder="e.g. 109382746193847">
                                </div>
                                
                                <div class="col-12">
                                    <label for="whatsapp_access_token" class="form-label">System Access Token (Permanent / Temporary Developer Token)</label>
                                    <textarea class="form-control" id="whatsapp_access_token" name="whatsapp_access_token" rows="3" placeholder="Enter Meta Developer System User Access Token..."><?php echo sanitize($settings['whatsapp_access_token'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="col-12 col-md-6">
                                    <label for="whatsapp_template_name" class="form-label">Default Alert Template Name</label>
                                    <input type="text" class="form-control" id="whatsapp_template_name" name="whatsapp_template_name" value="<?php echo sanitize($settings['whatsapp_template_name'] ?? 'insurance_renewal_alert'); ?>" placeholder="e.g. insurance_renewal_alert">
                                    <div class="form-text text-muted">Must match a pre-registered template name in your WhatsApp API Console.</div>
                                </div>
                            </div>
                        </div>
                        
                    </div> <!-- .tab-content -->
                    
                    <hr class="my-4 text-muted">
                    
                    <div class="d-flex align-items-center justify-content-end gap-3">
                        <button type="reset" class="btn btn-light border">Reset Fields</button>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
                        </button>
                    </div>
                    
                </form>
                
            </div>
        </div>
        
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
