<?php
/**
 * Dedicated Mobile Vehicle Records & Rapid Enrollment
 * Vehicle Details & Insurance Renewal Management System
 * Exactly matching user specified form layout & fields
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('agent');

if (!hasAgentAccess('vehicle') && !hasAgentAccess('pollution')) {
    $_SESSION['alert_error'] = 'Access denied. You do not have permissions for Vehicle or Pollution services.';
    redirect('index.php');
}

$db = getDBConnection();
$agentId = getEffectiveAgentId();
$creatorShopId = $_SESSION['user_id'];

// Handle Delete Vehicle Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $vehicleId = (int)$_GET['id'];
    try {
        $stmtV = $db->prepare("SELECT vehicle_number FROM vehicles WHERE id = ? AND agent_id = ?");
        $stmtV->execute([$vehicleId, $agentId]);
        $vehNum = $stmtV->fetchColumn();
        
        if ($vehNum) {
            $stmt = $db->prepare("DELETE FROM vehicles WHERE id = ? AND agent_id = ?");
            $stmt->execute([$vehicleId, $agentId]);
            logActivity('Delete Vehicle', "Deleted vehicle record: $vehNum (ID: $vehicleId)");
            $_SESSION['alert_success'] = 'Vehicle and associated documents deleted successfully!';
        } else {
            $_SESSION['alert_error'] = 'Vehicle not found or unauthorized access.';
        }
    } catch (PDOException $e) {
        $_SESSION['alert_error'] = 'Database error: ' . $e->getMessage();
    }
    redirect('vehicles.php');
}

// Handle Unified Save Vehicle, Customer, Insurance & PUC POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_vehicle'])) {
    $vehicleId = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : null;
    
    $vehNumber = formatVehicleNumber($_POST['vehicle_number'] ?? '');
    $ownerMobile = trim($_POST['owner_mobile'] ?? '');
    $ownerName = trim($_POST['owner_name'] ?? '');
    if (empty($ownerName)) $ownerName = 'Customer';
    $ownerWhatsapp = $ownerMobile;
    
    if (empty($vehNumber) || empty($ownerMobile)) {
        $_SESSION['alert_error'] = 'Vehicle Registration Number and Phone Number are required.';
        redirect('vehicles.php');
    }
    
    try {
        $db->beginTransaction();
        
        // 1. Customer Record
        $stmtC = $db->prepare("SELECT id FROM customers WHERE mobile_number = ? AND agent_id = ? LIMIT 1");
        $stmtC->execute([$ownerMobile, $agentId]);
        $existingCust = $stmtC->fetch();
        
        if ($existingCust) {
            $custId = (int)$existingCust['id'];
            $db->prepare("UPDATE customers SET name = ?, whatsapp_number = ? WHERE id = ?")->execute([$ownerName, $ownerWhatsapp, $custId]);
        } else {
            $stmtInstCust = $db->prepare("INSERT INTO customers (agent_id, created_by_shop_id, name, mobile_number, whatsapp_number) VALUES (?, ?, ?, ?, ?)");
            $stmtInstCust->execute([$agentId, $creatorShopId, $ownerName, $ownerMobile, $ownerWhatsapp]);
            $custId = (int)$db->lastInsertId();
        }
        
        // 2. Vehicle Record
        $stmtFind = $db->prepare("SELECT id, agent_id FROM vehicles WHERE vehicle_number = ? LIMIT 1");
        $stmtFind->execute([$vehNumber]);
        $existingVeh = $stmtFind->fetch();
        
        if ($existingVeh) {
            if ((int)$existingVeh['agent_id'] !== (int)$agentId) {
                throw new Exception("Vehicle number $vehNumber is already registered under a different agency account.");
            }
            $activeVehicleId = (int)$existingVeh['id'];
            $db->prepare("UPDATE vehicles SET customer_id = ?, created_by_shop_id = ? WHERE id = ? AND agent_id = ?")
               ->execute([$custId, $creatorShopId, $activeVehicleId, $agentId]);
        } else {
            if ($vehicleId) {
                $stmt = $db->prepare("UPDATE vehicles SET customer_id = ?, vehicle_number = ? WHERE id = ? AND agent_id = ?");
                $stmt->execute([$custId, $vehNumber, $vehicleId, $agentId]);
                $activeVehicleId = $vehicleId;
            } else {
                $stmt = $db->prepare("INSERT INTO vehicles (customer_id, agent_id, created_by_shop_id, vehicle_number) VALUES (?, ?, ?, ?)");
                $stmt->execute([$custId, $agentId, $creatorShopId, $vehNumber]);
                $activeVehicleId = (int)$db->lastInsertId();
            }
        }
        
        // 3. Insurance Policy
        if (hasAgentAccess('vehicle') && !empty(trim($_POST['policy_number'] ?? ''))) {
            $companyId = (int)($_POST['insurance_company_id'] ?? 0);
            $policyNum = trim($_POST['policy_number']);
            $insType = $_POST['insurance_type'] ?? 'Comprehensive';
            $insStartDate = !empty($_POST['ins_start_date']) ? $_POST['ins_start_date'] : date('Y-m-d');
            $insExpiryDate = !empty($_POST['ins_expiry_date']) ? $_POST['ins_expiry_date'] : date('Y-m-d', strtotime('+1 year', strtotime($insStartDate)));
            $premium = (float)($_POST['premium_amount'] ?? 0);
            
            $docPath = null;
            if (isset($_FILES['ins_document']) && $_FILES['ins_document']['error'] === UPLOAD_ERR_OK) {
                $uploadedDoc = handleFileUpload($_FILES['ins_document'], 'insurances', ['pdf', 'jpg', 'jpeg', 'png']);
                if ($uploadedDoc) $docPath = $uploadedDoc;
            }
            
            $stmtIns = $db->prepare("SELECT id FROM insurances WHERE vehicle_id = ?");
            $stmtIns->execute([$activeVehicleId]);
            $existingIns = $stmtIns->fetch();
            
            if ($existingIns) {
                $query = "UPDATE insurances SET insurance_company_id = ?, policy_number = ?, insurance_type = ?, start_date = ?, expiry_date = ?, premium_amount = ?";
                $params = [$companyId, $policyNum, $insType, $insStartDate, $insExpiryDate, $premium];
                if ($docPath) {
                    $query .= ", document_path = ?";
                    $params[] = $docPath;
                }
                $query .= " WHERE id = ?";
                $params[] = $existingIns['id'];
                $db->prepare($query)->execute($params);
            } else {
                $db->prepare("
                    INSERT INTO insurances (vehicle_id, agent_id, created_by_shop_id, insurance_company_id, policy_number, insurance_type, start_date, expiry_date, premium_amount, document_path)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([$activeVehicleId, $agentId, $creatorShopId, $companyId, $policyNum, $insType, $insStartDate, $insExpiryDate, $premium, $docPath]);
            }
        }
        
        // 4. Pollution Certificate (PUC)
        if (hasAgentAccess('pollution') && (!empty(trim($_POST['certificate_number'] ?? '')) || !empty($_POST['puc_start_date']) || !empty($_POST['puc_expiry_date']))) {
            $certNum = trim($_POST['certificate_number'] ?? '');
            if (empty($certNum)) {
                $certNum = 'PUC-' . preg_replace('/[^A-Z0-9]/i', '', $vehNumber);
            }
            $pucStartDate = !empty($_POST['puc_start_date']) ? $_POST['puc_start_date'] : date('Y-m-d');
            $pucExpiryDate = !empty($_POST['puc_expiry_date']) ? $_POST['puc_expiry_date'] : date('Y-m-d', strtotime('+6 months', strtotime($pucStartDate)));
            
            $docPath = null;
            if (isset($_FILES['puc_document']) && $_FILES['puc_document']['error'] === UPLOAD_ERR_OK) {
                $uploadedDoc = handleFileUpload($_FILES['puc_document'], 'pollution', ['pdf', 'jpg', 'jpeg', 'png']);
                if ($uploadedDoc) $docPath = $uploadedDoc;
            }
            
            $stmtPuc = $db->prepare("SELECT id FROM pollution_certificates WHERE vehicle_id = ?");
            $stmtPuc->execute([$activeVehicleId]);
            $existingPuc = $stmtPuc->fetch();
            
            if ($existingPuc) {
                $query = "UPDATE pollution_certificates SET certificate_number = ?, start_date = ?, expiry_date = ?";
                $params = [$certNum, $pucStartDate, $pucExpiryDate];
                if ($docPath) {
                    $query .= ", document_path = ?";
                    $params[] = $docPath;
                }
                $query .= " WHERE id = ?";
                $params[] = $existingPuc['id'];
                $db->prepare($query)->execute($params);
            } else {
                $db->prepare("
                    INSERT INTO pollution_certificates (vehicle_id, agent_id, created_by_shop_id, certificate_number, start_date, expiry_date, document_path)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ")->execute([$activeVehicleId, $agentId, $creatorShopId, $certNum, $pucStartDate, $pucExpiryDate, $docPath]);
            }
        }
        
        $db->commit();
        logActivity('Save Vehicle (Mobile)', "Vehicle record saved: $vehNumber for Customer: $ownerName");
        $_SESSION['alert_success'] = "Vehicle $vehNumber saved successfully!";
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['alert_error'] = 'Error: ' . $e->getMessage();
    }
    redirect('vehicles.php');
}

// Fetch Insurance Companies Dropdown List
$insuranceCompanies = $db->query("SELECT * FROM insurance_companies ORDER BY name ASC")->fetchAll();

// Search Filter & Database Query matching agent/vehicles.php
$search = trim($_GET['search'] ?? '');
$params = [$agentId];
$where = "WHERE v.agent_id = ?";

if (!empty($search)) {
    $where .= " AND (v.vehicle_number LIKE ? OR c.name LIKE ? OR c.mobile_number LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql = "
    SELECT 
        v.id,
        v.vehicle_number,
        v.created_at,
        c.name as customer_name,
        c.mobile_number as customer_mobile,
        c.whatsapp_number as customer_whatsapp,
        i.insurance_company_id, i.policy_number, i.insurance_type, i.start_date as ins_start_date, i.expiry_date as ins_expiry, i.premium_amount,
        ic.name as company_name,
        p.certificate_number, p.start_date as puc_start_date, p.expiry_date as puc_expiry,
        sh.shop_name as creator_shop_name
    FROM vehicles v
    JOIN customers c ON v.customer_id = c.id
    LEFT JOIN users sh ON v.created_by_shop_id = sh.id
    LEFT JOIN insurances i ON i.id = (
        SELECT id FROM insurances WHERE vehicle_id = v.id ORDER BY expiry_date DESC LIMIT 1
    )
    LEFT JOIN insurance_companies ic ON i.insurance_company_id = ic.id
    LEFT JOIN pollution_certificates p ON p.id = (
        SELECT id FROM pollution_certificates WHERE vehicle_id = v.id ORDER BY expiry_date DESC LIMIT 1
    )
    $where
    ORDER BY v.id DESC
    LIMIT 50
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

$pageTitle = 'Vehicle Records';
$activePage = 'vehicles';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Header Title & Register Vehicle Button -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h2 class="h5 font-weight-700 m-0 text-dark">Vehicle Records</h2>
        <p class="small text-muted m-0">Manage vehicle details & renewals</p>
    </div>
    <button type="button" class="btn btn-success btn-sm rounded-3 px-3 py-2 font-weight-700 shadow-sm" data-bs-toggle="modal" data-bs-target="#registerVehicleModal" onclick="resetVehicleForm()">
        <i class="fa-solid fa-plus me-1"></i> Add Vehicle
    </button>
</div>

<!-- Mobile Search Bar -->
<div class="mobile-card p-2 mb-3">
    <form action="" method="GET">
        <div class="input-group">
            <span class="input-group-text bg-transparent border-0"><i class="fa-solid fa-search text-muted"></i></span>
            <input type="text" name="search" class="form-control border-0 ps-0" placeholder="Search vehicle number or customer..." value="<?php echo sanitize($search); ?>" inputmode="search">
            <?php if (!empty($search)): ?>
                <a href="vehicles.php" class="btn btn-link text-muted"><i class="fa-solid fa-xmark"></i></a>
            <?php endif; ?>
            <button type="submit" class="btn btn-success px-3 rounded-3">Search</button>
        </div>
    </form>
</div>

<!-- Vehicle Card List -->
<?php if (!empty($vehicles)): ?>
    <?php foreach ($vehicles as $veh): ?>
        <div class="mobile-card mb-3">
            <div class="mobile-card-header d-flex align-items-center justify-content-between p-3 border-bottom">
                <div>
                    <h6 class="m-0 font-weight-800 text-dark fs-6"><?php echo sanitize($veh['vehicle_number']); ?></h6>
                    <span class="small text-muted"><i class="fa-solid fa-user me-1"></i><?php echo sanitize($veh['customer_name']); ?></span>
                </div>
                <?php if (!empty($veh['creator_shop_name'])): ?>
                    <span class="badge bg-light text-dark border"><?php echo sanitize($veh['creator_shop_name']); ?></span>
                <?php endif; ?>
            </div>
            
            <div class="mobile-card-body p-3">
                <div class="row g-2 mb-3">
                    <?php if (hasAgentAccess('vehicle')): ?>
                        <div class="col-6">
                            <div class="p-2 border rounded-3 bg-light">
                                <span class="small text-muted d-block" style="font-size: 0.72rem;">INSURANCE COVER</span>
                                <?php if (!empty($veh['ins_expiry'])): ?>
                                    <span class="fw-bold small d-block <?php echo ($veh['ins_expiry'] < date('Y-m-d')) ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo date('d M Y', strtotime($veh['ins_expiry'])); ?>
                                    </span>
                                    <span class="small text-muted d-block text-truncate" style="font-size: 0.68rem;"><?php echo sanitize($veh['company_name'] ?? 'Insurance'); ?></span>
                                <?php else: ?>
                                    <span class="small text-muted font-weight-600">N/A</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (hasAgentAccess('pollution')): ?>
                        <div class="col-6">
                            <div class="p-2 border rounded-3 bg-light">
                                <span class="small text-muted d-block" style="font-size: 0.72rem;">POLLUTION STATUS</span>
                                <?php if (!empty($veh['puc_expiry'])): ?>
                                    <span class="fw-bold small d-block <?php echo ($veh['puc_expiry'] < date('Y-m-d')) ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo date('d M Y', strtotime($veh['puc_expiry'])); ?>
                                    </span>
                                    <span class="small text-muted d-block text-truncate" style="font-size: 0.68rem;"><?php echo sanitize($veh['certificate_number'] ?? 'PUC'); ?></span>
                                <?php else: ?>
                                    <span class="small text-muted font-weight-600">N/A</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center justify-content-between gap-2">
                    <?php if (!empty($veh['customer_mobile'])): ?>
                        <a href="tel:<?php echo sanitize($veh['customer_mobile']); ?>" class="btn btn-outline-secondary btn-sm flex-grow-1 font-weight-600">
                            <i class="fa-solid fa-phone me-1"></i> Call
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($veh['customer_whatsapp'])): 
                        $cleanWA = preg_replace('/[^0-9]/', '', $veh['customer_whatsapp']);
                    ?>
                        <a href="https://wa.me/<?php echo $cleanWA; ?>" target="_blank" class="btn btn-success btn-sm flex-grow-1 font-weight-600">
                            <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp
                        </a>
                    <?php endif; ?>

                    <button type="button" class="btn btn-light btn-sm text-primary font-weight-600 border" onclick='editVehicle(<?php echo json_encode($veh, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <a href="vehicles.php?action=delete&id=<?php echo $veh['id']; ?>" class="btn btn-light btn-sm text-danger font-weight-600 border" onclick="return confirm('Are you sure you want to delete vehicle <?php echo sanitize($veh['vehicle_number']); ?>?')">
                        <i class="fa-solid fa-trash"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="mobile-card p-4 text-center text-muted border-0 shadow-sm bg-white">
        <i class="fa-solid fa-car-side fs-2 mb-2 d-block text-secondary opacity-50"></i>
        <p class="m-0 font-weight-500">No vehicle records found.</p>
        <button type="button" class="btn btn-success btn-sm mt-3 font-weight-700 rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#registerVehicleModal" onclick="resetVehicleForm()">
            <i class="fa-solid fa-plus me-1"></i> Register First Vehicle
        </button>
    </div>
<?php endif; ?>

<!-- Clean Vehicle & PUC Registration Modal (Exact Match to User Screenshot) -->
<div class="modal fade" id="registerVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-white border-bottom py-3">
                <h5 class="modal-title font-weight-700 text-dark fs-6" id="modalTitle">
                    <i class="fa-solid fa-car text-success me-2"></i> 1. Vehicle & Owner Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="vehicles.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="save_vehicle" value="1">
                <input type="hidden" name="id" id="v_id" value="">

                <div class="modal-body p-3">
                    <!-- 1. Vehicle & Owner Details -->
                    <div class="mb-3">
                        <label class="form-label font-weight-600 small text-dark mb-1">Vehicle Registration Number <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle_number" id="v_vehicle_number" class="form-control text-uppercase font-weight-700 mb-1" placeholder="e.g. KL-07-A-1515, KL-05-AA-7090" required style="letter-spacing: 0.5px;">
                        <small class="text-muted d-block mb-2" style="font-size: 0.72rem;">Format: e.g. KL-07-A-1515, KL-05-AA-7090</small>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label font-weight-600 small text-dark mb-1">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" name="owner_mobile" id="v_owner_mobile" class="form-control" placeholder="e.g. 9876543210" required inputmode="tel">
                            </div>
                            <div class="col-6">
                                <label class="form-label font-weight-600 small text-dark mb-1">Owner Name <span class="text-muted fw-normal">(Optional)</span></label>
                                <input type="text" name="owner_name" id="v_owner_name" class="form-control" placeholder="e.g. Rahul Sharma (Optional)">
                            </div>
                        </div>
                    </div>

                    <hr class="my-3 text-secondary opacity-25">

                    <!-- 2. Optional Pollution Certificate (PUC) Details -->
                    <?php if (hasAgentAccess('pollution')): ?>
                        <div class="mb-3">
                            <h6 class="font-weight-700 text-success small mb-3"><i class="fa-solid fa-wind me-1"></i> 5. Optional Pollution Certificate (PUC) Details</h6>
                            
                            <div class="mb-2">
                                <label class="form-label font-weight-600 small text-dark mb-1">PUC Certificate Number</label>
                                <input type="text" name="certificate_number" id="v_certificate_number" class="form-control" placeholder="e.g. PUC-9081273">
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <label class="form-label font-weight-600 small text-dark mb-1">PUC Issue Date</label>
                                    <input type="date" name="puc_start_date" id="v_puc_start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label font-weight-600 small text-dark mb-1">PUC Expiry Date</label>
                                    <input type="date" name="puc_expiry_date" id="v_puc_expiry_date" class="form-control">
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label font-weight-600 small text-dark mb-1">Upload Pollution Document <span class="text-muted fw-normal">(PDF/Image)</span></label>
                                <input type="file" name="puc_document" id="v_puc_document" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 3. Optional Insurance Details (If Access Granted) -->
                    <?php if (hasAgentAccess('vehicle')): ?>
                        <hr class="my-3 text-secondary opacity-25">
                        <div class="mb-2">
                            <h6 class="font-weight-700 text-success small mb-2"><i class="fa-solid fa-shield-halved me-1"></i> Optional Vehicle Insurance Details</h6>
                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <select name="insurance_company_id" id="v_company_id" class="form-select form-select-sm">
                                        <option value="">Select Provider</option>
                                        <?php foreach ($insuranceCompanies as $ic): ?>
                                            <option value="<?php echo $ic['id']; ?>"><?php echo sanitize($ic['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <input type="text" name="policy_number" id="v_policy_number" class="form-control form-control-sm" placeholder="Policy Number">
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="small text-muted form-label m-0">Insurance Issue Date</label>
                                    <input type="date" name="ins_start_date" id="v_ins_start_date" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-6">
                                    <label class="small text-muted form-label m-0">Insurance Expiry Date</label>
                                    <input type="date" name="ins_expiry_date" id="v_ins_expiry_date" class="form-control form-control-sm">
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="modal-footer bg-light p-2 rounded-bottom-4">
                    <button type="button" class="btn btn-light btn-sm font-weight-600" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm font-weight-700 px-4">
                        <i class="fa-solid fa-check me-1"></i> Save Vehicle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetVehicleForm() {
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-car text-success me-2"></i> 1. Vehicle & Owner Details';
    document.getElementById('v_id').value = '';
    document.getElementById('v_vehicle_number').value = '';
    document.getElementById('v_owner_mobile').value = '';
    document.getElementById('v_owner_name').value = '';
    
    if (document.getElementById('v_certificate_number')) document.getElementById('v_certificate_number').value = '';
    if (document.getElementById('v_puc_start_date')) document.getElementById('v_puc_start_date').value = '<?php echo date('Y-m-d'); ?>';
    if (document.getElementById('v_puc_expiry_date')) document.getElementById('v_puc_expiry_date').value = '';
    
    if (document.getElementById('v_company_id')) document.getElementById('v_company_id').value = '';
    if (document.getElementById('v_policy_number')) document.getElementById('v_policy_number').value = '';
    if (document.getElementById('v_ins_start_date')) document.getElementById('v_ins_start_date').value = '<?php echo date('Y-m-d'); ?>';
    if (document.getElementById('v_ins_expiry_date')) document.getElementById('v_ins_expiry_date').value = '';
}

function editVehicle(data) {
    document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square text-success me-2"></i> Edit Vehicle ' + (data.vehicle_number || '');
    document.getElementById('v_id').value = data.id || '';
    document.getElementById('v_vehicle_number').value = data.vehicle_number || '';
    document.getElementById('v_owner_mobile').value = data.customer_mobile || '';
    document.getElementById('v_owner_name').value = data.customer_name || '';
    
    if (document.getElementById('v_certificate_number')) document.getElementById('v_certificate_number').value = data.certificate_number || '';
    if (document.getElementById('v_puc_start_date')) document.getElementById('v_puc_start_date').value = data.puc_start_date || '';
    if (document.getElementById('v_puc_expiry_date')) document.getElementById('v_puc_expiry_date').value = data.puc_expiry || '';

    if (document.getElementById('v_company_id')) document.getElementById('v_company_id').value = data.insurance_company_id || '';
    if (document.getElementById('v_policy_number')) document.getElementById('v_policy_number').value = data.policy_number || '';
    if (document.getElementById('v_ins_start_date')) document.getElementById('v_ins_start_date').value = data.ins_start_date || '';
    if (document.getElementById('v_ins_expiry_date')) document.getElementById('v_ins_expiry_date').value = data.ins_expiry || '';

    const modal = new bootstrap.Modal(document.getElementById('registerVehicleModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
