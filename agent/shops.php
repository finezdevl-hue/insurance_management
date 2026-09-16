<?php
/**
 * Sub-Shops & Outlets Management (Agent View)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

checkAccess('agent');
$db = getDBConnection();
$agentId = $_SESSION['user_id'];

// Enforce agent role
if ($_SESSION['role'] !== 'agent') {
    redirect('index.php');
}

$pageTitle = 'Sub-Shops & Outlets';
$activePage = 'shops';

$action = $_GET['action'] ?? 'list';
$error = '';

// A. Handle Delete Sub-Shop
if ($action === 'delete' && isset($_GET['id'])) {
    $shopId = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND parent_agent_id = ? AND role = 'shop'");
        $stmt->execute([$shopId, $agentId]);
        logActivity('Delete Sub-Shop', "Deleted sub-shop ID $shopId");
        $_SESSION['alert_success'] = 'Sub-shop outlet deleted successfully!';
    } catch (PDOException $e) {
        $_SESSION['alert_error'] = 'Failed to delete sub-shop: ' . $e->getMessage();
    }
    redirect('shops.php');
}

// B. Toggle Status
if ($action === 'toggle_status' && isset($_GET['id'])) {
    $shopId = (int)$_GET['id'];
    $stmtCurrent = $db->prepare("SELECT status, username FROM users WHERE id = ? AND parent_agent_id = ? AND role = 'shop'");
    $stmtCurrent->execute([$shopId, $agentId]);
    $sh = $stmtCurrent->fetch();
    
    if ($sh) {
        $newStatus = ($sh['status'] === 'active') ? 'suspended' : 'active';
        $stmtUp = $db->prepare("UPDATE users SET status = ? WHERE id = ? AND parent_agent_id = ?");
        $stmtUp->execute([$newStatus, $shopId, $agentId]);
        logActivity('Toggle Sub-Shop Status', "Updated status for sub-shop {$sh['username']} to $newStatus");
        $_SESSION['alert_success'] = "Sub-shop status updated to $newStatus!";
    }
    redirect('shops.php');
}

// C. Save / Update Sub-Shop POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_shop'])) {
    $shopId = (int)$_POST['shop_id'];
    $shopName = trim($_POST['shop_name']);
    $shopOwnerName = trim($_POST['shop_owner_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $mobile = trim($_POST['mobile_number']);
    $whatsapp = trim($_POST['whatsapp_number']);
    $address = trim($_POST['shop_address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);
    $expiryDate = $_POST['expiry_date'];
    
    if (empty($shopName) || empty($username) || empty($email) || empty($mobile) || empty($expiryDate)) {
        $error = 'All required fields must be filled.';
    } else {
        try {
            if ($shopId > 0) {
                // Update
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmtU = $db->prepare("UPDATE users SET username = ?, email = ?, password = ?, shop_name = ?, shop_owner_name = ?, shop_address = ?, city = ?, state = ?, pincode = ?, mobile_number = ?, whatsapp_number = ?, expiry_date = ? WHERE id = ? AND parent_agent_id = ? AND role = 'shop'");
                    $stmtU->execute([$username, $email, $hash, $shopName, $shopOwnerName, $address, $city, $state, $pincode, $mobile, $whatsapp, $expiryDate, $shopId, $agentId]);
                } else {
                    $stmtU = $db->prepare("UPDATE users SET username = ?, email = ?, shop_name = ?, shop_owner_name = ?, shop_address = ?, city = ?, state = ?, pincode = ?, mobile_number = ?, whatsapp_number = ?, expiry_date = ? WHERE id = ? AND parent_agent_id = ? AND role = 'shop'");
                    $stmtU->execute([$username, $email, $shopName, $shopOwnerName, $address, $city, $state, $pincode, $mobile, $whatsapp, $expiryDate, $shopId, $agentId]);
                }
                logActivity('Update Sub-Shop', "Updated sub-shop: $shopName");
                $_SESSION['alert_success'] = "Sub-shop '$shopName' updated successfully!";
            } else {
                // Insert
                $hash = password_hash($password ?: '123456', PASSWORD_BCRYPT);
                $stmtI = $db->prepare("INSERT INTO users (role, parent_agent_id, username, email, password, shop_name, shop_owner_name, shop_address, city, state, pincode, mobile_number, whatsapp_number, expiry_date, status) VALUES ('shop', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmtI->execute([$agentId, $username, $email, $hash, $shopName, $shopOwnerName, $address, $city, $state, $pincode, $mobile, $whatsapp, $expiryDate]);
                logActivity('Create Sub-Shop', "Created new sub-shop: $shopName");
                $_SESSION['alert_success'] = "New Sub-shop '$shopName' registered successfully!";
            }
            redirect('shops.php');
        } catch (PDOException $e) {
            $error = 'Database Error: ' . $e->getMessage();
        }
    }
}

// D. Message Recharge Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recharge_shop_messages'])) {
    $shopId = (int)$_POST['shop_id'];
    $count = (int)$_POST['recharge_count'];
    
    if ($shopId > 0 && $count > 0) {
        $result = rechargeAgentMessagesByCount($shopId, $count, $_SESSION['user_id']);
        if ($result['success']) {
            $_SESSION['alert_success'] = "Successfully credited {$count} messages to sub-shop!";
        } else {
            $_SESSION['alert_error'] = "Recharge failed: " . $result['message'];
        }
    }
    redirect('shops.php');
}

// Fetch Sub-Shops under logged in agent
$stmtS = $db->prepare("SELECT * FROM users WHERE parent_agent_id = ? AND role = 'shop' ORDER BY id DESC");
$stmtS->execute([$agentId]);
$shopsList = $stmtS->fetchAll();

// KPI Calculations
$totalShopsCount = count($shopsList);
$activeShopsCount = count(array_filter($shopsList, fn($s) => $s['status'] === 'active'));
$totalMessageBalance = array_sum(array_column($shopsList, 'message_balance'));

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/sidebar.php';
?>

<div class="container-fluid px-4 py-4">
    <!-- Top Header Banner -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
        <div class="card-body p-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-4 bg-primary-subtle text-primary shadow-xs">
                    <i class="fa-solid fa-store fs-2"></i>
                </div>
                <div>
                    <h3 class="m-0 font-weight-700 text-main">My Sub-Shops & Testing Centers</h3>
                    <p class="text-muted small m-0 mt-1">Manage sub-shop outlets, locations, login credentials and message balances</p>
                </div>
            </div>
            <button class="btn btn-primary btn-lg px-4 py-2.5 font-weight-600 rounded-3 shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#shopModal" onclick="resetForm()">
                <i class="fa-solid fa-plus-circle fs-5"></i> Add New Sub-Shop
            </button>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-xs" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2 fs-5 align-middle"></i><?php echo sanitize($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- KPI Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small font-weight-600 text-uppercase tracking-wide d-block mb-1">Total Sub-Shops</span>
                        <h2 class="m-0 font-weight-700 text-dark"><?php echo number_format($totalShopsCount); ?></h2>
                    </div>
                    <div class="p-3 rounded-3 bg-primary-subtle text-primary">
                        <i class="fa-solid fa-shop fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small font-weight-600 text-uppercase tracking-wide d-block mb-1">Active Outlets</span>
                        <h2 class="m-0 font-weight-700 text-success"><?php echo number_format($activeShopsCount); ?></h2>
                    </div>
                    <div class="p-3 rounded-3 bg-success-subtle text-success">
                        <i class="fa-solid fa-circle-check fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small font-weight-600 text-uppercase tracking-wide d-block mb-1">Total Messages Pool</span>
                        <h2 class="m-0 font-weight-700 text-info"><?php echo number_format($totalMessageBalance); ?> <small class="fs-6 text-muted font-normal">msgs</small></h2>
                    </div>
                    <div class="p-3 rounded-3 bg-info-subtle text-info">
                        <i class="fa-solid fa-comments fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Shops Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0" id="shopsTable">
                    <thead class="table-light border-bottom">
                        <tr>
                            <th style="width: 50px;" class="ps-3 text-center">#</th>
                            <th>Outlet / Owner</th>
                            <th>Credentials</th>
                            <th style="max-width: 140px;">Address</th>
                            <th>Contact</th>
                            <th class="text-center">Message Pool</th>
                            <th>License Expiry</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($shopsList) > 0): ?>
                            <?php foreach ($shopsList as $sh): ?>
                                <tr class="shop-row">
                                    <td class="ps-3 text-center">
                                        <span class="badge bg-light text-secondary border font-monospace px-1.5 py-1">#<?php echo $sh['id']; ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size: 0.85rem;"><?php echo sanitize($sh['shop_name']); ?></div>
                                        <div class="text-muted" style="font-size: 0.73rem;"><i class="fa-solid fa-user me-1 text-primary"></i><?php echo sanitize($sh['shop_owner_name'] ?: 'N/A'); ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark" style="font-size: 0.78rem;"><i class="fa-solid fa-user-gear me-1 text-secondary"></i><?php echo sanitize($sh['username']); ?></div>
                                        <div class="text-muted text-truncate" style="font-size: 0.72rem; max-width: 150px;" title="<?php echo sanitize($sh['email']); ?>"><i class="fa-regular fa-envelope me-1"></i><?php echo sanitize($sh['email']); ?></div>
                                    </td>
                                    <td style="max-width: 140px;">
                                        <?php if (!empty($sh['shop_address'])): ?>
                                            <div class="text-truncate text-dark font-weight-500" style="font-size: 0.75rem; max-width: 130px;" title="<?php echo sanitize($sh['shop_address']); ?>"><i class="fa-solid fa-location-dot me-1 text-danger"></i><?php echo sanitize($sh['shop_address']); ?></div>
                                            <div class="text-muted" style="font-size: 0.68rem;"><?php echo sanitize(($sh['city'] ? $sh['city'] . ', ' : '') . $sh['state']); ?></div>
                                        <?php else: ?>
                                            <span class="text-muted small">--</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="tel:<?php echo sanitize($sh['mobile_number']); ?>" class="text-decoration-none text-dark fw-semibold" style="font-size: 0.78rem;">
                                            <i class="fa-solid fa-phone me-1 text-success"></i><?php echo sanitize($sh['mobile_number']); ?>
                                        </a>
                                    </td>
                                    <td class="text-center" style="min-width: 130px;">
                                        <div class="d-flex align-items-center gap-1 justify-content-center">
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-2.5 py-1.5 font-weight-700" style="font-size: 0.73rem;">
                                                <i class="fa-solid fa-comments me-1"></i><?php echo number_format($sh['message_balance'] ?? 0); ?>
                                            </span>
                                            <button class="btn btn-xs btn-outline-success rounded-pill px-2 py-1 font-weight-600 shadow-xs text-nowrap" style="font-size: 0.7rem;" onclick="openRechargeModal(<?php echo $sh['id']; ?>, '<?php echo sanitize(addslashes($sh['shop_name'])); ?>', <?php echo (int)($sh['message_balance'] ?? 0); ?>)">
                                                <i class="fa-solid fa-plus me-1"></i>TopUp
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($sh['expiry_date'])): 
                                            $expStatus = getExpiryStatus($sh['expiry_date']);
                                        ?>
                                            <span class="badge <?php echo $expStatus['badge']; ?> rounded-pill px-2 py-1" style="font-size: 0.7rem;">
                                                <?php echo sanitize($expStatus['text']); ?>
                                            </span>
                                            <small class="d-block text-muted font-weight-500" style="font-size: 0.68rem;">
                                                Exp: <?php echo date('d-M-Y', strtotime($sh['expiry_date'])); ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill" style="font-size: 0.7rem;">No Expiry</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($sh['status'] === 'active'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5 font-weight-600" style="font-size: 0.7rem;">
                                                <i class="fa-solid fa-circle fs-9 me-1"></i>Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-0.5 font-weight-600" style="font-size: 0.7rem;">
                                                <i class="fa-solid fa-circle fs-9 me-1"></i>Suspended
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3 text-nowrap">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <button class="btn btn-xs btn-outline-primary rounded-2 font-weight-600 px-2 py-1" style="font-size: 0.72rem;" onclick="editShop(<?php echo htmlspecialchars(json_encode($sh)); ?>)" title="Edit Sub-Shop">
                                                <i class="fa-solid fa-pen me-1"></i>Edit
                                            </button>
                                            <a href="shops.php?action=toggle_status&id=<?php echo $sh['id']; ?>" class="btn btn-xs btn-outline-warning rounded-2 px-2 py-1" style="font-size: 0.72rem;" onclick="return confirm('Change sub-shop status?');" title="Toggle Status">
                                                <i class="fa-solid fa-power-off"></i>
                                            </a>
                                            <a href="shops.php?action=delete&id=<?php echo $sh['id']; ?>" class="btn btn-xs btn-outline-danger rounded-2 px-2 py-1" style="font-size: 0.72rem;" onclick="return confirm('Are you sure you want to delete this sub-shop?');" title="Delete Sub-Shop">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-store-slash fs-1 d-block mb-3 text-secondary-subtle"></i>
                                    <h6 class="font-weight-600 text-dark">No sub-shops registered yet</h6>
                                    <p class="small text-muted">Click "+ Add New Sub-Shop" above to create your outlet centers.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add / Edit Sub-Shop -->
<div class="modal fade" id="shopModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="POST" action="shops.php">
                <input type="hidden" name="save_shop" value="1">
                <input type="hidden" name="shop_id" id="modal_shop_id" value="0">
                
                <div class="modal-header bg-light py-3 px-4">
                    <h5 class="modal-title font-weight-700" id="modalTitle"><i class="fa-solid fa-store me-2 text-primary"></i>Add Sub-Shop Center</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label font-weight-600">Sub-Shop / Center Name <span class="text-danger">*</span></label>
                            <input type="text" name="shop_name" id="modal_shop_name" class="form-control rounded-3" placeholder="e.g. West Branch Testing Center" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label font-weight-600">Shop In-Charge / Owner Name</label>
                            <input type="text" name="shop_owner_name" id="modal_shop_owner_name" class="form-control rounded-3" placeholder="e.g. Ramesh Kumar">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-weight-600">Login Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" id="modal_username" class="form-control rounded-3" placeholder="e.g. shop_west" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label font-weight-600">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="modal_email" class="form-control rounded-3" placeholder="e.g. shopwest@domain.com" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-weight-600">Password <span id="pwdNotice" class="text-muted font-normal">(Required)</span></label>
                            <input type="password" name="password" id="modal_password" class="form-control rounded-3" placeholder="Account password">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-weight-600">Mobile Number <span class="text-danger">*</span></label>
                            <input type="text" name="mobile_number" id="modal_mobile_number" class="form-control rounded-3" placeholder="e.g. 9876543210" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-weight-600">WhatsApp Contact Number</label>
                            <input type="text" name="whatsapp_number" id="modal_whatsapp_number" class="form-control rounded-3" placeholder="e.g. 9876543210">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-weight-600">License Expiry Date <span class="text-danger">*</span></label>
                            <input type="date" name="expiry_date" id="modal_expiry_date" class="form-control rounded-3" value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label font-weight-600">Testing Center Address (Appears on WhatsApp alerts)</label>
                            <textarea name="shop_address" id="modal_shop_address" class="form-control rounded-3" rows="2" placeholder="Full street address of testing center outlet..."></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-weight-600">City</label>
                            <input type="text" name="city" id="modal_city" class="form-control rounded-3" placeholder="e.g. Mumbai">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-weight-600">State</label>
                            <input type="text" name="state" id="modal_state" class="form-control rounded-3" placeholder="e.g. Maharashtra">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-weight-600">Pincode</label>
                            <input type="text" name="pincode" id="modal_pincode" class="form-control rounded-3" placeholder="e.g. 400001">
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light px-4">
                    <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 font-weight-600"><i class="fa-solid fa-floppy-disk me-1"></i> Save Sub-Shop</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Recharge Shop Message Balance -->
<div class="modal fade" id="rechargeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form method="POST" action="shops.php">
                <input type="hidden" name="recharge_shop_messages" value="1">
                <input type="hidden" name="shop_id" id="recharge_shop_id" value="0">
                
                <div class="modal-header bg-light py-3 px-4">
                    <h5 class="modal-title font-weight-700"><i class="fa-solid fa-wallet me-2 text-success"></i>Recharge Message Balance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Recharging messages for: <strong id="recharge_shop_name" class="text-dark">--</strong></p>
                    <div class="alert alert-primary-subtle border border-primary-subtle rounded-3 small mb-3">
                        <i class="fa-solid fa-comments me-2 text-primary"></i>Current Message Balance: <strong id="recharge_current_balance" class="text-primary font-weight-700">0</strong> messages
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-600">Number of Messages to Add <span class="text-danger">*</span></label>
                        <input type="number" step="1" min="1" name="recharge_count" id="recharge_count" class="form-control form-control-lg rounded-3" placeholder="e.g. 1000" required>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <button type="button" class="btn btn-outline-primary btn-sm flex-fill rounded-3" onclick="document.getElementById('recharge_count').value=500">+500 Msgs</button>
                        <button type="button" class="btn btn-outline-primary btn-sm flex-fill rounded-3" onclick="document.getElementById('recharge_count').value=1000">+1,000 Msgs</button>
                        <button type="button" class="btn btn-outline-primary btn-sm flex-fill rounded-3" onclick="document.getElementById('recharge_count').value=2000">+2,000 Msgs</button>
                        <button type="button" class="btn btn-outline-primary btn-sm flex-fill rounded-3" onclick="document.getElementById('recharge_count').value=5000">+5,000 Msgs</button>
                    </div>
                </div>
                
                <div class="modal-footer bg-light px-4">
                    <button type="button" class="btn btn-light border px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4 font-weight-600"><i class="fa-solid fa-check-circle me-1"></i>Confirm Recharge</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-store me-2 text-primary"></i>Add Sub-Shop Center';
    document.getElementById('modal_shop_id').value = '0';
    document.getElementById('modal_shop_name').value = '';
    document.getElementById('modal_shop_owner_name').value = '';
    document.getElementById('modal_username').value = '';
    document.getElementById('modal_email').value = '';
    document.getElementById('modal_password').value = '';
    document.getElementById('modal_mobile_number').value = '';
    document.getElementById('modal_whatsapp_number').value = '';
    
    var today = new Date();
    today.setFullYear(today.getFullYear() + 1);
    document.getElementById('modal_expiry_date').value = today.toISOString().split('T')[0];
    
    document.getElementById('modal_shop_address').value = '';
    document.getElementById('modal_city').value = '';
    document.getElementById('modal_state').value = '';
    document.getElementById('modal_pincode').value = '';
    document.getElementById('pwdNotice').innerText = '(Required)';
    document.getElementById('modal_password').required = true;
}

function editShop(sh) {
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen me-2 text-primary"></i>Edit Sub-Shop Center';
    document.getElementById('modal_shop_id').value = sh.id;
    document.getElementById('modal_shop_name').value = sh.shop_name || '';
    document.getElementById('modal_shop_owner_name').value = sh.shop_owner_name || '';
    document.getElementById('modal_username').value = sh.username || '';
    document.getElementById('modal_email').value = sh.email || '';
    document.getElementById('modal_password').value = '';
    document.getElementById('modal_mobile_number').value = sh.mobile_number || '';
    document.getElementById('modal_whatsapp_number').value = sh.whatsapp_number || '';
    document.getElementById('modal_expiry_date').value = sh.expiry_date || '';
    document.getElementById('modal_shop_address').value = sh.shop_address || '';
    document.getElementById('modal_city').value = sh.city || '';
    document.getElementById('modal_state').value = sh.state || '';
    document.getElementById('modal_pincode').value = sh.pincode || '';
    document.getElementById('pwdNotice').innerText = '(Leave empty to keep current)';
    document.getElementById('modal_password').required = false;
    
    var modal = new bootstrap.Modal(document.getElementById('shopModal'));
    modal.show();
}

function openRechargeModal(shopId, shopName, currentBalance) {
    document.getElementById('recharge_shop_id').value = shopId;
    document.getElementById('recharge_shop_name').innerText = shopName;
    document.getElementById('recharge_current_balance').innerText = parseInt(currentBalance).toLocaleString();
    document.getElementById('recharge_count').value = '';
    
    var modal = new bootstrap.Modal(document.getElementById('rechargeModal'));
    modal.show();
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>