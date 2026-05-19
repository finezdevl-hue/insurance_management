<?php
/**
 * Agent Shop Details & Profile Management
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce Agent Access
checkAccess('agent');

$db = getDBConnection();
$agentId = $_SESSION['user_id'];

$pageTitle = 'My Business Shop Profile';
$pageHeading = 'Business Shop Profile';
$activePage = 'profile';

$error = '';

// Load agent business details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'agent'");
$stmt->execute([$agentId]);
$agent = $stmt->fetch();

if (!$agent) {
    $_SESSION['alert_error'] = 'Agent profile records not found.';
    redirect('index.php');
}

// Processing Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shopName = trim($_POST['shop_name']);
    $shopOwnerName = trim($_POST['shop_owner_name']);
    $shopAddress = trim($_POST['shop_address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);
    $mobileNumber = trim($_POST['mobile_number']);
    $whatsappNumber = trim($_POST['whatsapp_number']);
    $gstNumber = trim($_POST['gst_number']);
    $licenseNumber = trim($_POST['license_number']);
    $panNumber = trim($_POST['pan_number']);
    $businessType = trim($_POST['business_type']);
    
    // Core parameters (email cannot be modified if we want to restrict it or we can allow updating)
    $email = trim($_POST['email']);
    
    if (empty($shopName) || empty($email)) {
        $error = 'Shop Name and Email are required.';
    } else {
        try {
            // Check unique email (excluding itself)
            $stmtEmail = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmtEmail->execute([$email, $agentId]);
            if ($stmtEmail->fetchColumn() > 0) {
                $error = 'Email is already used by another account.';
            } else {
                
                // File uploads
                $shopLogo = null;
                $shopBanner = null;
                
                if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] === UPLOAD_ERR_OK) {
                    $uploadedLogo = handleFileUpload($_FILES['shop_logo'], 'logos', ['jpg', 'jpeg', 'png']);
                    if ($uploadedLogo) {
                        $shopLogo = $uploadedLogo;
                    }
                }
                
                if (isset($_FILES['shop_banner']) && $_FILES['shop_banner']['error'] === UPLOAD_ERR_OK) {
                    $uploadedBanner = handleFileUpload($_FILES['shop_banner'], 'banners', ['jpg', 'jpeg', 'png']);
                    if ($uploadedBanner) {
                        $shopBanner = $uploadedBanner;
                    }
                }
                
                // Dynamic query
                $query = "
                    UPDATE users 
                    SET email = ?, shop_name = ?, shop_owner_name = ?, shop_address = ?, 
                        city = ?, state = ?, pincode = ?, mobile_number = ?, whatsapp_number = ?, 
                        gst_number = ?, license_number = ?, pan_number = ?, business_type = ?
                ";
                $params = [
                    $email, $shopName, $shopOwnerName, $shopAddress, 
                    $city, $state, $pincode, $mobileNumber, $whatsappNumber, 
                    $gstNumber, $licenseNumber, $panNumber, $businessType
                ];
                
                if ($shopLogo) {
                    $query .= ", shop_logo = ?";
                    $params[] = $shopLogo;
                }
                if ($shopBanner) {
                    $query .= ", shop_banner = ?";
                    $params[] = $shopBanner;
                }
                
                // Check if user changed password
                $newPassword = trim($_POST['password']);
                if (!empty($newPassword)) {
                    $query .= ", password = ?";
                    $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
                }
                
                $query .= " WHERE id = ?";
                $params[] = $agentId;
                
                $stmtUpdate = $db->prepare($query);
                $stmtUpdate->execute($params);
                
                logActivity('Update Shop Details', "Agent updated shop profile: $shopName");
                $_SESSION['alert_success'] = 'Shop profile details updated successfully!';
                
                redirect('profile.php');
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        
        <!-- Premium Branding Banner -->
        <div class="card shadow-sm border-0 mb-4 overflow-hidden position-relative" style="border-radius: var(--radius-md);">
            <div style="height: 180px; width: 100%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); position: relative;">
                <?php if ($agent['shop_banner'] && file_exists(__DIR__ . '/../uploads/banners/' . $agent['shop_banner'])): ?>
                    <img src="../uploads/banners/<?php echo $agent['shop_banner']; ?>" alt="Banner" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.65;">
                <?php endif; ?>
                <div class="position-absolute bottom-0 start-0 p-4 d-flex align-items-end gap-3 text-white">
                    <div class="bg-white p-1" style="border-radius: var(--radius-sm); box-shadow: var(--shadow-md);">
                        <?php if ($agent['shop_logo'] && file_exists(__DIR__ . '/../uploads/logos/' . $agent['shop_logo'])): ?>
                            <img src="../uploads/logos/<?php echo $agent['shop_logo']; ?>" alt="Logo" style="width: 90px; height: 90px; object-fit: cover; border-radius: var(--radius-sm);">
                        <?php else: ?>
                            <div class="bg-light text-primary d-flex align-items-center justify-content-center font-weight-700 fs-2 text-uppercase" style="width: 90px; height: 90px; border-radius: var(--radius-sm);">
                                <?php echo substr(sanitize($agent['shop_name']), 0, 2); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h4 class="m-0 font-weight-700"><?php echo sanitize($agent['shop_name']); ?></h4>
                        <p class="m-0 small opacity-90"><i class="fa-solid fa-user-tie me-1"></i> Owner: <?php echo sanitize($agent['shop_owner_name'] ?: 'Not Specified'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Edit Form -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-store text-success fs-5"></i>
                    <h5 class="m-0 font-weight-700">Modify Shop & Account Settings</h5>
                </div>
            </div>
            <div class="card-body">
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: 8px;">
                        <i class="fa-solid fa-circle-exclamation fs-5"></i>
                        <div class="small"><?php echo sanitize($error); ?></div>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data">
                    
                    <!-- Form 1: Portal Access -->
                    <h6 class="border-bottom pb-2 font-weight-600 mb-3 text-success"><i class="fa-solid fa-lock me-2"></i>1. Account Portal Security</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label for="username" class="form-label">Username <span class="text-muted small">(Read Only)</span></label>
                            <input type="text" class="form-control bg-light" id="username" readonly value="<?php echo sanitize($agent['username']); ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?php echo sanitize($agent['email']); ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="password" class="form-label">Change Password <span class="text-muted small">(Leave empty to keep current)</span></label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password">
                        </div>
                    </div>

                    <!-- Form 2: Shop Branding and Details -->
                    <h6 class="border-bottom pb-2 font-weight-600 mb-3 text-success"><i class="fa-solid fa-store me-2"></i>2. Shop & Branding Metadata</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label for="shop_name" class="form-label">Agency / Shop Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="shop_name" name="shop_name" required value="<?php echo sanitize($agent['shop_name']); ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="shop_owner_name" class="form-label">Owner Full Name</label>
                            <input type="text" class="form-control" id="shop_owner_name" name="shop_owner_name" value="<?php echo sanitize($agent['shop_owner_name']); ?>">
                        </div>
                        
                        <div class="col-12 col-md-4">
                            <label for="mobile_number" class="form-label">Contact Mobile</label>
                            <input type="text" class="form-control" id="mobile_number" name="mobile_number" value="<?php echo sanitize($agent['mobile_number']); ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="whatsapp_number" class="form-label">WhatsApp Channel Number</label>
                            <input type="text" class="form-control" id="whatsapp_number" name="whatsapp_number" value="<?php echo sanitize($agent['whatsapp_number']); ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="business_type" class="form-label">Business Line Type</label>
                            <input type="text" class="form-control" id="business_type" name="business_type" value="<?php echo sanitize($agent['business_type']); ?>">
                        </div>
                        
                        <div class="col-12">
                            <label for="shop_address" class="form-label">Physical Shop Address</label>
                            <textarea class="form-control" id="shop_address" name="shop_address" rows="2"><?php echo sanitize($agent['shop_address']); ?></textarea>
                        </div>
                        
                        <div class="col-12 col-md-4">
                            <label for="city" class="form-label">City</label>
                            <input type="text" class="form-control" id="city" name="city" value="<?php echo sanitize($agent['city']); ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="state" class="form-label">State</label>
                            <input type="text" class="form-control" id="state" name="state" value="<?php echo sanitize($agent['state']); ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="pincode" class="form-label">Pincode</label>
                            <input type="text" class="form-control" id="pincode" name="pincode" value="<?php echo sanitize($agent['pincode']); ?>">
                        </div>
                    </div>

                    <!-- Form 3: statutory and uploads -->
                    <h6 class="border-bottom pb-2 font-weight-600 mb-3 text-success"><i class="fa-solid fa-file-shield me-2"></i>3. statutory & Branding uploads</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label for="gst_number" class="form-label">GSTIN Number</label>
                            <input type="text" class="form-control" id="gst_number" name="gst_number" value="<?php echo sanitize($agent['gst_number']); ?>" placeholder="22AAAAA1111A1Z1">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="license_number" class="form-label">Agent License / RTO Code</label>
                            <input type="text" class="form-control" id="license_number" name="license_number" value="<?php echo sanitize($agent['license_number']); ?>" placeholder="LIC-9087-A1">
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="pan_number" class="form-label">Business / Personal PAN</label>
                            <input type="text" class="form-control" id="pan_number" name="pan_number" value="<?php echo sanitize($agent['pan_number']); ?>" placeholder="ABCDE1234F">
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label for="shop_logo" class="form-label">Update Shop Logo <span class="text-muted small">(PNG/JPG, Max 2MB)</span></label>
                            <input type="file" class="form-control" id="shop_logo" name="shop_logo">
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label for="shop_banner" class="form-label">Update Shop Banner <span class="text-muted small">(PNG/JPG, Max 2MB)</span></label>
                            <input type="file" class="form-control" id="shop_banner" name="shop_banner">
                        </div>
                    </div>

                    <hr class="my-4">
                    
                    <div class="d-flex align-items-center justify-content-end gap-3">
                        <a href="index.php" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-success px-4">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Update Shop Profile
                        </button>
                    </div>
                    
                </form>
                
            </div>
        </div>
        
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
