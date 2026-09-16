<?php
/**
 * Sub-Shops & Testing Centers Management (Super Admin)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();

$pageTitle = 'Sub-Shops & Testing Centers';
$activePage = 'shops';

$action = $_GET['action'] ?? 'list';
$error = '';
$parentFilter = isset($_GET['agent_id']) ? (int)$_GET['agent_id'] : 0;

// A. Handle Delete Shop
if ($action === 'delete' && isset($_GET['id'])) {
    $shopId = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'shop'");
        $stmt->execute([$shopId]);
        $_SESSION['alert_success'] = 'Sub-shop center deleted successfully!';
    } catch (PDOException $e) {
        $_SESSION['alert_error'] = 'Failed to delete sub-shop: ' . $e->getMessage();
    }
    redirect('shops.php' . ($parentFilter ? '?agent_id=' . $parentFilter : ''));
}

// B1. Handle Status Toggle
if ($action === 'toggle_status' && isset($_GET['id'])) {
    $shopId = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("SELECT status FROM users WHERE id = ? AND role = 'shop'");
        $stmt->execute([$shopId]);
        $status = $stmt->fetchColumn();
        if ($status) {
            $newStatus = ($status === 'active') ? 'suspended' : 'active';
            $stmtUp = $db->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'shop'");
            $stmtUp->execute([$newStatus, $shopId]);
            $_SESSION['alert_success'] = "Sub-shop status updated to $newStatus successfully!";
        }
    } catch (PDOException $e) {
        $_SESSION['alert_error'] = 'Database error: ' . $e->getMessage();
    }
    redirect('shops.php' . ($parentFilter ? '?agent_id=' . $parentFilter : ''));
}

// B2. Handle Renew License
if ($action === 'renew_license' && isset($_GET['id'])) {
    $shopId = (int)$_GET['id'];
    $renewDays = isset($_GET['days']) ? (int)$_GET['days'] : 365;
    try {
        $stmt = $db->prepare("SELECT expiry_date, shop_name FROM users WHERE id = ? AND role = 'shop'");
        $stmt->execute([$shopId]);
        $shop = $stmt->fetch();
        if ($shop) {
            $currentExp = (!empty($shop['expiry_date']) && $shop['expiry_date'] >= date('Y-m-d')) ? $shop['expiry_date'] : date('Y-m-d');
            $newExpDate = date('Y-m-d', strtotime($currentExp . " + {$renewDays} days"));
            
            $stmtUp = $db->prepare("UPDATE users SET expiry_date = ? WHERE id = ? AND role = 'shop'");
            $stmtUp->execute([$newExpDate, $shopId]);
            logActivity('Renew Shop License', "Renewed shop license for {$shop['shop_name']} until $newExpDate");
            $_SESSION['alert_success'] = "License for {$shop['shop_name']} renewed until " . date('d-M-Y', strtotime($newExpDate)) . "!";
        }
    } catch (PDOException $e) {
        $_SESSION['alert_error'] = 'Failed to renew license: ' . $e->getMessage();
    }
    redirect('shops.php' . ($parentFilter ? '?agent_id=' . $parentFilter : ''));
}

// B3. Handle Message Balance Recharge for Shop Center
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['recharge_shop_messages'])) {
    $shopId = (int)$_POST['shop_id'];
    $rechargeCount = (int)($_POST['recharge_count'] ?? $_POST['recharge_amount'] ?? 0);
    
    try {
        if (isset($_POST['recharge_count'])) {
            $res = rechargeAgentMessagesByCount($shopId, $rechargeCount, $_SESSION['user_id']);
        } else {
            $res = rechargeAgentMessages($shopId, (float)$_POST['recharge_amount'], $_SESSION['user_id']);
        }
        $_SESSION['alert_success'] = "Successfully credited " . number_format($res['messages_credited']) . " messages to {$res['shop_name']}!";
    } catch (Exception $e) {
        $_SESSION['alert_error'] = 'Recharge failed: ' . $e->getMessage();
    }
    redirect('shops.php' . ($parentFilter ? '?agent_id=' . $parentFilter : ''));
}

// C. Handle Add / Edit Shop Form Post
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['save_shop'])) {
    $shopId = (int)($_POST['shop_id'] ?? 0);
    $parentAgentId = (int)$_POST['parent_agent_id'];
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    $shopName = trim($_POST['shop_name']);
    $shopOwnerName = trim($_POST['shop_owner_name']);
    $shopAddress = trim($_POST['shop_address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);
    $mobileNumber = trim($_POST['mobile_number']);
    $whatsappNumber = trim($_POST['whatsapp_number']);
    $expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : date('Y-m-d', strtotime('+1 year'));

    if (empty($parentAgentId) || empty($username) || empty($email) || empty($shopName) || empty($mobileNumber)) {
        $error = 'Parent Agent, Username, Email, Shop Name, and Mobile Number are required.';
    } elseif ($shopId === 0 && empty($password)) {
        $error = 'Password is required when creating a new shop.';
    } else {
        try {
            // Check username uniqueness
            $stmtChk = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmtChk->execute([$username, $email, $shopId]);
            if ($stmtChk->fetch()) {
                $error = 'Username or Email is already in use by another account.';
            } else {
                if ($shopId > 0) {
                    // Update
                    if (!empty($password)) {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $stmtU = $db->prepare("UPDATE users SET parent_agent_id = ?, username = ?, email = ?, password = ?, shop_name = ?, shop_owner_name = ?, shop_address = ?, city = ?, state = ?, pincode = ?, mobile_number = ?, whatsapp_number = ?, expiry_date = ? WHERE id = ? AND role = 'shop'");
                        $stmtU->execute([$parentAgentId, $username, $email, $hash, $shopName, $shopOwnerName, $shopAddress, $city, $state, $pincode, $mobileNumber, $whatsappNumber, $expiryDate, $shopId]);
                    } else {
                        $stmtU = $db->prepare("UPDATE users SET parent_agent_id = ?, username = ?, email = ?, shop_name = ?, shop_owner_name = ?, shop_address = ?, city = ?, state = ?, pincode = ?, mobile_number = ?, whatsapp_number = ?, expiry_date = ? WHERE id = ? AND role = 'shop'");
                        $stmtU->execute([$parentAgentId, $username, $email, $shopName, $shopOwnerName, $shopAddress, $city, $state, $pincode, $mobileNumber, $whatsappNumber, $expiryDate, $shopId]);
                    }
                    $_SESSION['alert_success'] = "Sub-shop '$shopName' updated successfully!";
                } else {
                    // Create New
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmtI = $db->prepare("INSERT INTO users (role, parent_agent_id, username, email, password, shop_name, shop_owner_name, shop_address, city, state, pincode, mobile_number, whatsapp_number, expiry_date, status) VALUES ('shop', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                    $stmtI->execute([$parentAgentId, $username, $email, $hash, $shopName, $shopOwnerName, $shopAddress, $city, $state, $pincode, $mobileNumber, $whatsappNumber, $expiryDate]);
                    $_SESSION['alert_success'] = "New Sub-shop '$shopName' created successfully!";
                }
                redirect('shops.php' . ($parentFilter ? '?agent_id=' . $parentFilter : ''));
            }
        } catch (PDOException $e) {
            $error = 'Database Error: ' . $e->getMessage();
        }
    }
}

// Fetch all Parent Agents for dropdown
$agentsList = $db->query("SELECT id, username, shop_name, shop_owner_name FROM users WHERE role = 'agent' ORDER BY shop_name ASC")->fetchAll();

// Fetch Sub-Shops
$sql = "SELECT s.*, p.shop_name as parent_shop_name, p.username as parent_username 
        FROM users s 
        JOIN users p ON s.parent_agent_id = p.id 
        WHERE s.role = 'shop'";
$params = [];
if ($parentFilter > 0) {
    $sql .= " AND s.parent_agent_id = ?";
    $params[] = $parentFilter;
}
$sql .= " ORDER BY s.id DESC";
$stmtS = $db->prepare($sql);
$stmtS->execute($params);
$shopsList = $stmtS->fetchAll();

// KPI Calculations
$totalShopsCount = count($shopsList);
$activeShopsCount = count(array_filter($shopsList, fn($s) => $s['status'] === 'active'));
$totalMessageBalance = array_sum(array_column($shopsList, 'message_balance'));
$expiringCount = count(array_filter($shopsList, fn($s) => !empty($s['expiry_date']) && getDaysUntil($s['expiry_date']) <= 30));

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
                    <h3 class="m-0 font-weight-700 text-main">Sub-Shops & Testing Centers</h3>
                    <p class="text-muted small m-0 mt-1">Manage individual outlet licenses, location details, credentials & message balances</p>
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
        <div class="col-12 col-sm-6 col-xl-3">
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

        <div class="col-12 col-sm-6 col-xl-3">
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

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small font-weight-600 text-uppercase tracking-wide d-block mb-1">Total Message Pool</span>
                        <h2 class="m-0 font-weight-700 text-info"><?php echo number_format($totalMessageBalance); ?> <small class="fs-6 text-muted font-normal">msgs</small></h2>
                    </div>
                    <div class="p-3 rounded-3 bg-info-subtle text-info">
                        <i class="fa-solid fa-comments fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small font-weight-600 text-uppercase tracking-wide d-block mb-1">Expiring Soon (30d)</span>
                        <h2 class="m-0 font-weight-700 <?php echo $expiringCount > 0 ? 'text-warning' : 'text-dark'; ?>"><?php echo number_format($expiringCount); ?></h2>
                    </div>
                    <div class="p-3 rounded-3 bg-warning-subtle text-warning">
                        <i class="fa-solid fa-triangle-exclamation fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Live Search Toolbar -->
    <div class="card mb-4 border-0 shadow-sm rounded-4">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center justify-content-between">
                <div class="col-12 col-md-6 col-lg-5">
                    <form method="GET" action="shops.php" class="d-flex align-items-center gap-2">
                        <span class="small font-weight-600 text-muted text-nowrap"><i class="fa-solid fa-filter me-1"></i>Parent Agent:</span>
                        <select name="agent_id" class="form-select form-select-sm rounded-3 border-secondary-subtle" onchange="this.form.submit()">
                            <option value="0">All Parent Agents</option>
                            <?php foreach ($agentsList as $ag): ?>
                                <option value="<?php echo $ag['id']; ?>" <?php echo $parentFilter === (int)$ag['id'] ? 'selected' : ''; ?>>
                                    <?php echo sanitize($ag['shop_name'] ?: $ag['username']); ?> (<?php echo sanitize($ag['username']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-secondary-subtle"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" id="shopSearchInput" class="form-control border-secondary-subtle" placeholder="Search by shop name, username, phone or city..." onkeyup="filterShopsTable()">
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
                            <th>Parent Agent</th>
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
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1" style="font-size: 0.72rem;">
                                            <i class="fa-solid fa-building me-1"></i><?php echo sanitize($sh['parent_shop_name'] ?: $sh['parent_username']); ?>
                                        </span>
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
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-xs btn-outline-success rounded-2 font-weight-600 px-2 py-1 dropdown-toggle" style="font-size: 0.72rem;" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Renew License Validity">
                                                    <i class="fa-solid fa-calendar-plus me-1"></i>Renew
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                                    <li><a class="dropdown-item small py-1.5" href="shops.php?action=renew_license&id=<?php echo $sh['id']; ?>&days=365<?php echo $parentFilter ? '&agent_id=' . $parentFilter : ''; ?>"><i class="fa-solid fa-calendar-check me-2 text-success"></i>+1 Year (365 Days)</a></li>
                                                    <li><a class="dropdown-item small py-1.5" href="shops.php?action=renew_license&id=<?php echo $sh['id']; ?>&days=180<?php echo $parentFilter ? '&agent_id=' . $parentFilter : ''; ?>"><i class="fa-solid fa-clock me-2 text-primary"></i>+6 Months (180 Days)</a></li>
                                                    <li><a class="dropdown-item small py-1.5" href="shops.php?action=renew_license&id=<?php echo $sh['id']; ?>&days=90<?php echo $parentFilter ? '&agent_id=' . $parentFilter : ''; ?>"><i class="fa-solid fa-hourglass-half me-2 text-warning"></i>+3 Months (90 Days)</a></li>
                                                </ul>
                                            </div>

                                            <button class="btn btn-xs btn-outline-primary rounded-2 font-weight-600 px-2 py-1" style="font-size: 0.72rem;" onclick="editShop(<?php echo htmlspecialchars(json_encode($sh)); ?>)" title="Edit Shop">
                                                <i class="fa-solid fa-pen me-1"></i>Edit
                                            </button>
                                            <a href="shops.php?action=toggle_status&id=<?php echo $sh['id']; ?><?php echo $parentFilter ? '&agent_id=' . $parentFilter : ''; ?>" class="btn btn-xs btn-outline-warning rounded-2 px-2 py-1" style="font-size: 0.72rem;" onclick="return confirm('Change shop status?');" title="Toggle Status">
                                                <i class="fa-solid fa-power-off"></i>
                                            </a>
                                            <a href="shops.php?action=delete&id=<?php echo $sh['id']; ?><?php echo $parentFilter ? '&agent_id=' . $parentFilter : ''; ?>" class="btn btn-xs btn-outline-danger rounded-2 px-2 py-1" style="font-size: 0.72rem;" onclick="return confirm('Are you sure you want to delete this shop center?');" title="Delete Shop">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-store-slash fs-1 d-block mb-3 text-secondary-subtle"></i>
                                    <h6 class="font-weight-600 text-dark">No sub-shops found</h6>
                                    <p class="small text-muted">Click "+ Add New Sub-Shop" above to register an outlet center.</p>
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
            <form method="POST" action="shops.php<?php echo $parentFilter ? '?agent_id=' . $parentFilter : ''; ?>">
                <input type="hidden" name="save_shop" value="1">
                <input type="hidden" name="shop_id" id="modal_shop_id" value="0">
                
                <div class="modal-header bg-light py-3 px-4">
                    <h5 class="modal-title font-weight-700" id="modalTitle"><i class="fa-solid fa-store me-2 text-primary"></i>Add Sub-Shop Center</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label font-weight-600">Parent Agent Partner <span class="text-danger">*</span></label>
                            <select name="parent_agent_id" id="modal_parent_agent_id" class="form-select rounded-3" required>
                                <option value="">Select Parent Agent</option>
                                <?php foreach ($agentsList as $ag): ?>
                                    <option value="<?php echo $ag['id']; ?>" <?php echo $parentFilter === (int)$ag['id'] ? 'selected' : ''; ?>>
                                        <?php echo sanitize($ag['shop_name'] ?: $ag['username']); ?> (Owner: <?php echo sanitize($ag['shop_owner_name'] ?: $ag['username']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small text-muted">All vehicles added by this shop will be visible across all shops under this Parent Agent.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-weight-600">Sub-Shop / Center Name <span class="text-danger">*</span></label>
                            <input type="text" name="shop_name" id="modal_shop_name" class="form-control rounded-3" placeholder="e.g. Speedy Testing Center - West Branch" required>
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
                            <input type="email" name="email" id="modal_email" class="form-control rounded-3" placeholder="e.g. shopwest@speedy.com" required>
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
                            <label class="form-label font-weight-600">Shop License Expiry Date <span class="text-danger">*</span></label>
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
            <form method="POST" action="shops.php<?php echo $parentFilter ? '?agent_id=' . $parentFilter : ''; ?>">
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
                        <input type="number" step="1" min="1" name="recharge_count" id="recharge_count" class="form-control form-control-lg rounded-3" placeholder="e.g. 1000" required oninput="calculateCost(this.value)">
                        <div class="form-text mt-2" id="cost_preview">Rate: ₹<?php echo getSingleMessagePrice(); ?> per message.</div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <button type="button" class="btn btn-outline-primary btn-sm flex-fill rounded-3" onclick="setRechargeCount(500)">+500 Msgs</button>
                        <button type="button" class="btn btn-outline-primary btn-sm flex-fill rounded-3" onclick="setRechargeCount(1000)">+1,000 Msgs</button>
                        <button type="button" class="btn btn-outline-primary btn-sm flex-fill rounded-3" onclick="setRechargeCount(2000)">+2,000 Msgs</button>
                        <button type="button" class="btn btn-outline-primary btn-sm flex-fill rounded-3" onclick="setRechargeCount(5000)">+5,000 Msgs</button>
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
const unitPrice = <?php echo (float)getSingleMessagePrice(); ?>;

function filterShopsTable() {
    const query = document.getElementById('shopSearchInput').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#shopsTable tbody tr.shop-row');
    
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        if (text.includes(query)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function setRechargeCount(count) {
    document.getElementById('recharge_count').value = count;
    calculateCost(count);
}

function calculateCost(count) {
    const qty = parseInt(count) || 0;
    const preview = document.getElementById('cost_preview');
    if (qty > 0) {
        const totalCost = (qty * unitPrice).toFixed(2);
        preview.innerHTML = '<span class="text-success font-weight-600"><i class="fa-solid fa-calculator me-1"></i>Total Cost: ₹' + totalCost + '</span> <span class="text-muted">(' + qty.toLocaleString() + ' msgs @ ₹' + unitPrice + '/msg)</span>';
    } else {
        preview.innerHTML = 'Rate: ₹' + unitPrice + ' per message.';
    }
}

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
    document.getElementById('modal_parent_agent_id').value = sh.parent_agent_id;
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
    document.getElementById('cost_preview').innerHTML = 'Rate: ₹' + unitPrice + ' per message.';
    
    var modal = new bootstrap.Modal(document.getElementById('rechargeModal'));
    modal.show();
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
