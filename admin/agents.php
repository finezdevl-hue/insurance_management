<?php
/**
 * Agent Management (Super Admin CRUD) with Portal Account Expiry Control
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();

$pageTitle = 'Agent Partner Management';
$activePage = 'agents';

$action = $_GET['action'] ?? 'list';
$error = '';

function getAgentCsvHeaders() {
    return [
        'username',
        'password',
        'email',
        'status',
        'shop_name',
        'shop_owner_name',
        'shop_address',
        'city',
        'state',
        'pincode',
        'mobile_number',
        'whatsapp_number',
        'expiry_date',
        'access_health_insurance',
        'access_vehicle_insurance',
        'access_pollution',
        'message_balance',
        'shop_logo'
    ];
}

function normalizeCsvBoolean($value, $default = 0) {
    $value = strtolower(trim((string)$value));
    if ($value === '') {
        return (int)$default;
    }

    return in_array($value, ['1', 'true', 'yes', 'y', 'active'], true) ? 1 : 0;
}

function normalizeCsvStatus($value) {
    $value = strtolower(trim((string)$value));
    return $value === 'suspended' ? 'suspended' : 'active';
}

function normalizeCsvDate($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    $timestamp = strtotime($value);
    return $timestamp ? date('Y-m-d', $timestamp) : null;
}

function csvValue($row, $key, $default = '') {
    return isset($row[$key]) ? trim((string)$row[$key]) : $default;
}

// --- CONTROLLER ACTIONS PROCESSING ---

// A. Handle Delete Agent
if ($action === 'delete' && isset($_GET['id'])) {
    $agentId = (int)$_GET['id'];
    try {
        // Fetch details before delete for logging
        $stmtFetch = $db->prepare("SELECT username, shop_name FROM users WHERE id = ? AND role = 'agent'");
        $stmtFetch->execute([$agentId]);
        $agent = $stmtFetch->fetch();
        
        if ($agent) {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'agent'");
            $stmt->execute([$agentId]);
            logActivity('Delete Agent', "Deleted Agent: {$agent['username']} (Shop: {$agent['shop_name']})");
            $_SESSION['alert_success'] = 'Agent partner deleted successfully!';
        } else {
            $_SESSION['alert_error'] = 'Agent not found.';
        }
    } catch (PDOException $e) {
        $_SESSION['alert_error'] = 'Cannot delete agent: ' . $e->getMessage();
    }
    redirect('agents.php');
}

// B. Handle Toggle Active/Suspended Status
if ($action === 'toggle_status' && isset($_GET['id'])) {
    $agentId = (int)$_GET['id'];
    try {
        $stmt = $db->prepare("SELECT status, username, shop_name FROM users WHERE id = ? AND role = 'agent'");
        $stmt->execute([$agentId]);
        $agent = $stmt->fetch();
        
        if ($agent) {
            $newStatus = ($agent['status'] === 'active') ? 'suspended' : 'active';
            $stmtUpdate = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $agentId]);
            
            logActivity('Toggle Agent Status', "Changed status of {$agent['username']} to $newStatus.");
            $_SESSION['alert_success'] = "Agent status updated to $newStatus successfully!";
        } else {
            $_SESSION['alert_error'] = 'Agent not found.';
        }
    } catch (PDOException $e) {
        $_SESSION['alert_error'] = 'Database error: ' . $e->getMessage();
    }
    redirect('agents.php');
}

// C. Handle Quick Renew Expiration Date POST
if ($action === 'renew' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $agentId = (int)$_POST['id'];
    $renewDate = $_POST['renew_date'];
    
    try {
        if (empty($renewDate)) {
            throw new Exception('Renewal date cannot be empty.');
        }
        
        $stmtFetch = $db->prepare("SELECT username, shop_name FROM users WHERE id = ? AND role = 'agent'");
        $stmtFetch->execute([$agentId]);
        $agent = $stmtFetch->fetch();
        
        if ($agent) {
            $stmtUpdate = $db->prepare("UPDATE users SET expiry_date = ? WHERE id = ? AND role = 'agent'");
            $stmtUpdate->execute([$renewDate, $agentId]);
            
            logActivity('Renew Agent Account', "Renewed Agent Expiry: {$agent['username']} to $renewDate");
            $_SESSION['alert_success'] = "Portal access for {$agent['shop_name']} renewed successfully until " . date('d-M-Y', strtotime($renewDate)) . '!';
        } else {
            $_SESSION['alert_error'] = 'Agent partner not found.';
        }
    } catch (Exception $e) {
        $_SESSION['alert_error'] = 'Error renewing portal license: ' . $e->getMessage();
    }
    redirect('agents.php');
}

// D. Handle Message Recharge
if ($action === 'recharge' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $agentId = (int)($_POST['id'] ?? 0);
    $rechargeCount = (int)($_POST['recharge_count'] ?? $_POST['recharge_amount'] ?? 0);

    try {
        if (isset($_POST['recharge_count'])) {
            $result = rechargeAgentMessagesByCount($agentId, $rechargeCount, $_SESSION['user_id'] ?? null);
        } else {
            $result = rechargeAgentMessages($agentId, (float)$_POST['recharge_amount'], $_SESSION['user_id'] ?? null);
        }
        logActivity(
            'Recharge Agent Messages',
            "Recharged {$result['agent']['username']} with {$result['messages_credited']} messages (₹{$result['amount']} at ₹{$result['unit_price']}/msg)."
        );
        $_SESSION['alert_success'] = "Message recharge completed. {$result['agent']['shop_name']} received " . number_format($result['messages_credited']) . " messages.";
    } catch (Throwable $e) {
        $_SESSION['alert_error'] = 'Message recharge failed: ' . $e->getMessage();
    }

    redirect('agents.php');
}

// E. Export Agents CSV
if ($action === 'export_csv') {
    $headers = getAgentCsvHeaders();
    $stmt = $db->query("
        SELECT username, email, status, shop_name, shop_owner_name, shop_address, city, state, pincode,
               mobile_number, whatsapp_number,
               expiry_date, access_health_insurance, access_vehicle_insurance, access_pollution,
               message_balance, shop_logo
        FROM users
        WHERE role = 'agent'
        ORDER BY id ASC
    ");

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=agents_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, $headers, ',', '"', '\\');

    while ($agent = $stmt->fetch()) {
        fputcsv($output, [
            $agent['username'],
            '',
            $agent['email'],
            $agent['status'],
            $agent['shop_name'],
            $agent['shop_owner_name'],
            $agent['shop_address'],
            $agent['city'],
            $agent['state'],
            $agent['pincode'],
            $agent['mobile_number'],
            $agent['whatsapp_number'],
            $agent['expiry_date'],
            (int)$agent['access_health_insurance'],
            (int)$agent['access_vehicle_insurance'],
            (int)$agent['access_pollution'],
            (int)$agent['message_balance'],
            $agent['shop_logo']
        ], ',', '"', '\\');
    }

    fclose($output);
    exit;
}

// F. Import Agents CSV
if ($action === 'import_csv' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['agents_csv']) || $_FILES['agents_csv']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Please choose a valid CSV file to upload.');
        }

        $extension = strtolower(pathinfo($_FILES['agents_csv']['name'], PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            throw new RuntimeException('Only CSV files are supported for agent import.');
        }

        $handle = fopen($_FILES['agents_csv']['tmp_name'], 'r');
        if ($handle === false) {
            throw new RuntimeException('Unable to read the uploaded CSV file.');
        }

        $csvHeaders = fgetcsv($handle, 0, ',', '"', '\\');
        if (!$csvHeaders) {
            fclose($handle);
            throw new RuntimeException('The uploaded CSV file is empty.');
        }

        $csvHeaders = array_map(static function ($header) {
            return trim((string)$header);
        }, $csvHeaders);

        $requiredHeaders = ['username', 'email', 'shop_name', 'expiry_date'];
        foreach ($requiredHeaders as $requiredHeader) {
            if (!in_array($requiredHeader, $csvHeaders, true)) {
                fclose($handle);
                throw new RuntimeException("Missing required CSV column: {$requiredHeader}");
            }
        }

        $createdCount = 0;
        $updatedCount = 0;
        $skippedRows = [];
        $rowNumber = 1;

        $db->beginTransaction();

        while (($rowData = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $rowNumber++;

            if (count(array_filter($rowData, static function ($value) {
                return trim((string)$value) !== '';
            })) === 0) {
                continue;
            }

            $row = array_combine($csvHeaders, array_pad($rowData, count($csvHeaders), ''));
            if ($row === false) {
                $skippedRows[] = "Row {$rowNumber}: Column count mismatch.";
                continue;
            }

            $username = csvValue($row, 'username');
            $password = csvValue($row, 'password');
            $email = csvValue($row, 'email');
            $status = normalizeCsvStatus(csvValue($row, 'status', 'active'));
            $shopName = csvValue($row, 'shop_name');
            $shopOwnerName = csvValue($row, 'shop_owner_name');
            $shopAddress = csvValue($row, 'shop_address');
            $city = csvValue($row, 'city');
            $state = csvValue($row, 'state');
            $pincode = csvValue($row, 'pincode');
            $mobileNumber = csvValue($row, 'mobile_number');
            $whatsappNumber = csvValue($row, 'whatsapp_number');
            $expiryDate = normalizeCsvDate(csvValue($row, 'expiry_date'));
            $accessHealth = normalizeCsvBoolean(csvValue($row, 'access_health_insurance', '1'), 1);
            $accessVehicle = normalizeCsvBoolean(csvValue($row, 'access_vehicle_insurance', '1'), 1);
            $accessPollution = normalizeCsvBoolean(csvValue($row, 'access_pollution', '1'), 1);
            $messageBalance = max(0, (int)csvValue($row, 'message_balance', '0'));
            $shopLogo = csvValue($row, 'shop_logo', 'logo_default.png') ?: 'logo_default.png';

            if ($username === '' || $email === '' || $shopName === '' || $expiryDate === null) {
                $skippedRows[] = "Row {$rowNumber}: username, email, shop_name, and expiry_date are required.";
                continue;
            }

            $stmtExisting = $db->prepare("
                SELECT id
                FROM users
                WHERE role = 'agent' AND (username = ? OR email = ?)
                LIMIT 1
            ");
            $stmtExisting->execute([$username, $email]);
            $existingAgent = $stmtExisting->fetch();

            if ($existingAgent) {
                $query = "
                    UPDATE users
                    SET username = ?, email = ?, status = ?, shop_name = ?, shop_owner_name = ?, shop_address = ?,
                        city = ?, state = ?, pincode = ?, mobile_number = ?, whatsapp_number = ?, expiry_date = ?,
                        access_health_insurance = ?, access_vehicle_insurance = ?, access_pollution = ?,
                        message_balance = ?, shop_logo = ?
                ";
                $params = [
                    $username, $email, $status, $shopName, $shopOwnerName, $shopAddress,
                    $city, $state, $pincode, $mobileNumber, $whatsappNumber, $expiryDate,
                    $accessHealth, $accessVehicle, $accessPollution, $messageBalance,
                    $shopLogo
                ];

                if ($password !== '') {
                    $query .= ", password = ?";
                    $params[] = password_hash($password, PASSWORD_DEFAULT);
                }

                $query .= " WHERE id = ? AND role = 'agent'";
                $params[] = $existingAgent['id'];

                $stmtUpdate = $db->prepare($query);
                $stmtUpdate->execute($params);
                $updatedCount++;
                continue;
            }

            if ($password === '') {
                $skippedRows[] = "Row {$rowNumber}: password is required for new agents.";
                continue;
            }

            $stmtInsert = $db->prepare("
                INSERT INTO users (
                    username, password, email, role, status, shop_name, shop_owner_name, shop_logo,
                    shop_address, city, state, pincode, mobile_number, whatsapp_number,
                    expiry_date, access_health_insurance,
                    access_vehicle_insurance, access_pollution, message_balance
                ) VALUES (?, ?, ?, 'agent', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtInsert->execute([
                $username, password_hash($password, PASSWORD_DEFAULT), $email, $status, $shopName, $shopOwnerName,
                $shopLogo, $shopAddress, $city, $state, $pincode, $mobileNumber,
                $whatsappNumber, $expiryDate, $accessHealth, $accessVehicle, $accessPollution, $messageBalance
            ]);
            $createdCount++;
        }

        fclose($handle);
        $db->commit();

        logActivity('Import Agent CSV', "Imported agent CSV. Created: {$createdCount}, Updated: {$updatedCount}");

        $message = "CSV import completed. Created: {$createdCount}, Updated: {$updatedCount}.";
        if (!empty($skippedRows)) {
            $message .= ' Skipped: ' . count($skippedRows) . '. ' . implode(' ', array_slice($skippedRows, 0, 3));
        }
        $_SESSION['alert_success'] = $message;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['alert_error'] = 'CSV import failed: ' . $e->getMessage();
    }

    redirect('agents.php');
}

// G. Handle Create or Update Form POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !in_array($action, ['renew', 'recharge', 'import_csv'], true)) {
    $agentId = isset($_POST['id']) ? (int)$_POST['id'] : null;
    
    // Core parameters
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Shop details parameters
    $shopName = trim($_POST['shop_name']);
    $shopOwnerName = trim($_POST['shop_owner_name']);
    $shopAddress = trim($_POST['shop_address']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);
    $mobileNumber = trim($_POST['mobile_number']);
    $whatsappNumber = trim($_POST['whatsapp_number']);
    $status = $_POST['status'] ?? 'active';
    $expiryDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : '2099-12-31';
    
    // Service Access Permissions
    $accessHealth = isset($_POST['access_health_insurance']) ? 1 : 0;
    $accessVehicle = isset($_POST['access_vehicle_insurance']) ? 1 : 0;
    $accessPollution = isset($_POST['access_pollution']) ? 1 : 0;
    
    // Validations
    if (empty($username) || empty($email) || empty($shopName)) {
        $error = 'Username, Email, and Agency Name are required.';
    } else {
        try {
            // Check for unique username & email
            if ($agentId) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?");
                $stmt->execute([$username, $email, $agentId]);
            } else {
                $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
            }
            $exists = $stmt->fetchColumn();
            
            if ($exists > 0) {
                $error = 'Username or Email is already registered by another user.';
            } else {
                // File upload
                $shopLogo = null;
                
                if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] === UPLOAD_ERR_OK) {
                    $uploadedLogo = handleFileUpload($_FILES['shop_logo'], 'logos', ['jpg', 'jpeg', 'png']);
                    if ($uploadedLogo) {
                        $shopLogo = $uploadedLogo;
                    }
                }
                
                if ($agentId) {
                    // --- UPDATE OPERATION ---
                    $query = "
                        UPDATE users 
                        SET username = ?, email = ?, shop_name = ?, shop_owner_name = ?, shop_address = ?, 
                            city = ?, state = ?, pincode = ?, mobile_number = ?, whatsapp_number = ?, 
                            status = ?, expiry_date = ?, access_health_insurance = ?, access_vehicle_insurance = ?, access_pollution = ?
                    ";
                    $params = [
                        $username, $email, $shopName, $shopOwnerName, $shopAddress, 
                        $city, $state, $pincode, $mobileNumber, $whatsappNumber, 
                        $status, $expiryDate, $accessHealth, $accessVehicle, $accessPollution
                    ];
                    
                    // If a new password is set
                    if (!empty($password)) {
                        $query .= ", password = ?";
                        $params[] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    
                    // If files uploaded
                    if ($shopLogo) {
                        $query .= ", shop_logo = ?";
                        $params[] = $shopLogo;
                    }
                    
                    $query .= " WHERE id = ? AND role = 'agent'";
                    $params[] = $agentId;
                    
                    $stmt = $db->prepare($query);
                    $stmt->execute($params);
                    
                    logActivity('Update Agent', "Updated agent details: $username (Shop: $shopName)");
                    $_SESSION['alert_success'] = 'Agent details updated successfully!';
                    redirect('agents.php');
                    
                } else {
                    // --- CREATE OPERATION ---
                    if (empty($password)) {
                        $error = 'Password is required when creating a new agent.';
                    } else {
                        $hashedPass = password_hash($password, PASSWORD_DEFAULT);
                        
                        $stmt = $db->prepare("
                            INSERT INTO users (
                                username, password, email, role, status, shop_name, shop_owner_name, 
                                shop_logo, shop_address, city, state, pincode, 
                                mobile_number, whatsapp_number, expiry_date, access_health_insurance, access_vehicle_insurance, access_pollution
                            ) VALUES (?, ?, ?, 'agent', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $username, $hashedPass, $email, $status, $shopName, $shopOwnerName,
                            $shopLogo ?: 'logo_default.png', $shopAddress, $city, $state, $pincode,
                            $mobileNumber, $whatsappNumber, $expiryDate, $accessHealth, $accessVehicle, $accessPollution
                        ]);
                        
                        logActivity('Create Agent', "Created new agent: $username (Shop: $shopName)");
                        $_SESSION['alert_success'] = 'New agent partner created successfully!';
                        redirect('agents.php');
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// --- CONTROLLER RENDERING ---

$pageHeading = 'Agent Partners';
$singleMessagePrice = getSingleMessagePrice();
include_once __DIR__ . '/../includes/header.php';
?>

<!-- Action switcher -->
<?php if ($action === 'list'): ?>
    
    <!-- LIST VIEW -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-user-tie text-primary fs-5"></i>
                <h5 class="m-0 font-weight-700">Registered RTO / Insurance Agents</h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="agents.php?action=export_csv" class="btn btn-light border btn-sm text-success">
                    <i class="fa-solid fa-download me-1"></i> Export CSV
                </a>
                <a href="agents.php?action=import_csv" class="btn btn-light border btn-sm text-primary">
                    <i class="fa-solid fa-upload me-1"></i> Import CSV
                </a>
                <a href="agents.php?action=add" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i> Add New Partner
                </a>
            </div>
        </div>
        <div class="card-body">
            
            <table class="table table-hover align-middle datatable w-100">
                <thead>
                    <tr>
                        <th>Agent / Agency Info</th>
                        <th>Owner / Contact</th>
                        <th>Location</th>
                        <th>Registration Details</th>
                        <th>Message Wallet</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $db->query("SELECT a.*, (SELECT COUNT(*) FROM users s WHERE s.parent_agent_id = a.id AND s.role = 'shop') as shop_count FROM users a WHERE a.role = 'agent' ORDER BY a.id DESC");
                    while ($agent = $stmt->fetch()):
                        $statusBadge = ($agent['status'] === 'active') ? 'badge-active' : 'badge-suspended';
                        $wallet = getAgentMessageSummary((int)$agent['id']);
                        
                        // Fetch sub-shops under this agent
                        $stmtSub = $db->prepare("SELECT * FROM users WHERE parent_agent_id = ? AND role = 'shop' ORDER BY id DESC");
                        $stmtSub->execute([$agent['id']]);
                        $subShops = $stmtSub->fetchAll();
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-3">
                                        <i class="fa-solid fa-building text-primary"></i>
                                    </div>
                                    <div>
                                        <h6 class="m-0 font-weight-600 text-main"><?php echo sanitize($agent['shop_name']); ?></h6>
                                        <small class="text-muted">User: @<?php echo sanitize($agent['username']); ?></small>
                                        <div class="mt-1">
                                            <button class="btn btn-xs btn-outline-primary rounded-pill px-2.5 py-1 font-weight-600 text-nowrap" type="button" data-bs-toggle="collapse" data-bs-target="#subShopsRow_<?php echo $agent['id']; ?>" aria-expanded="false">
                                                <i class="fa-solid fa-store me-1"></i><?php echo count($subShops); ?> Sub-Shops <i class="fa-solid fa-chevron-down ms-1" style="font-size: 0.7rem;"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sub-Shops Collapsible Drawer (Inside Column 1 to avoid DataTables column count errors) -->
                                <div id="subShopsRow_<?php echo $agent['id']; ?>" class="collapse mt-3" style="min-width: 280px;">
                                    <div class="card border border-primary-subtle shadow-sm rounded-3 overflow-hidden">
                                        <div class="card-header bg-primary-subtle py-1.5 px-3 d-flex align-items-center justify-content-between">
                                            <span class="fw-bold text-primary" style="font-size: 0.75rem;">
                                                <i class="fa-solid fa-store me-1"></i>Sub-Shops (<?php echo count($subShops); ?>)
                                            </span>
                                            <a href="shops.php?agent_id=<?php echo $agent['id']; ?>" class="btn btn-xs btn-primary font-weight-600 rounded-pill px-2 py-0.5" style="font-size: 0.68rem;">
                                                <i class="fa-solid fa-gear me-1"></i>Manage
                                            </a>
                                        </div>
                                        <div class="card-body p-0">
                                            <?php if (count($subShops) > 0): ?>
                                                <div class="list-group list-group-flush" style="font-size: 0.75rem;">
                                                    <?php foreach ($subShops as $sh): ?>
                                                        <div class="list-group-item p-2 d-flex align-items-center justify-content-between">
                                                            <div>
                                                                <div class="fw-bold text-dark"><?php echo sanitize($sh['shop_name']); ?></div>
                                                                <div class="text-muted" style="font-size: 0.7rem;">
                                                                    <i class="fa-solid fa-user me-1 text-primary"></i><?php echo sanitize($sh['shop_owner_name'] ?: 'N/A'); ?> | 
                                                                    <i class="fa-solid fa-phone me-1 text-success"></i><?php echo sanitize($sh['mobile_number']); ?>
                                                                </div>
                                                            </div>
                                                            <div class="text-end ms-2">
                                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-2 py-0.5 font-weight-700" style="font-size: 0.68rem;">
                                                                    <?php echo number_format($sh['message_balance']); ?> msgs
                                                                </span>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <div class="p-2.5 text-center text-muted" style="font-size: 0.73rem;">
                                                    No sub-shops registered yet. <a href="shops.php?agent_id=<?php echo $agent['id']; ?>" class="fw-bold text-primary">+ Add Sub-Shop</a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <p class="m-0 font-weight-500 text-main"><?php echo sanitize($agent['shop_owner_name'] ?: 'N/A'); ?></p>
                                <small class="text-muted d-block"><i class="fa-solid fa-phone fs-7"></i> <?php echo sanitize($agent['mobile_number']); ?></small>
                                <small class="text-muted d-block"><i class="fa-solid fa-envelope fs-7"></i> <?php echo sanitize($agent['email']); ?></small>
                            </td>
                            <td>
                                <span class="d-block small font-weight-500"><?php echo sanitize($agent['city'] ?: '--'); ?></span>
                                <span class="text-muted small"><?php echo sanitize($agent['state'] ?: '--'); ?></span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php if ($agent['access_health_insurance']): ?>
                                        <span class="badge bg-success text-white px-2 py-1" style="font-size: 0.72rem;" title="Health Insurance Access"><i class="fa-solid fa-heart-pulse me-1"></i> Health</span>
                                    <?php endif; ?>
                                    <?php if ($agent['access_vehicle_insurance']): ?>
                                        <span class="badge bg-primary text-white px-2 py-1" style="font-size: 0.72rem;" title="Vehicle Insurance Access"><i class="fa-solid fa-car me-1"></i> Vehicle</span>
                                    <?php endif; ?>
                                    <?php if ($agent['access_pollution']): ?>
                                        <span class="badge bg-info text-dark px-2 py-1" style="font-size: 0.72rem;" title="Pollution Access"><i class="fa-solid fa-wind me-1"></i> Pollution</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="d-block fw-semibold text-primary"><?php echo (int)$wallet['balance']; ?> messages left</span>
                                <small class="d-block text-muted">Recharge: Rs <?php echo number_format((float)$wallet['total_recharge_amount'], 2); ?></small>
                                <small class="d-block text-muted">Rate: Rs <?php echo number_format((float)$singleMessagePrice, 2); ?>/message</small>
                            </td>
                            <td>
                                <?php
                                $isExpired = !empty($agent['expiry_date']) && (date('Y-m-d') > $agent['expiry_date']);
                                ?>
                                <span class="badge <?php echo $statusBadge; ?> d-block mb-1" style="width: fit-content;">
                                    <?php echo strtoupper(sanitize($agent['status'])); ?>
                                </span>
                                <?php if (!empty($agent['expiry_date'])): ?>
                                    <small class="d-block <?php echo $isExpired ? 'text-danger font-weight-700' : 'text-muted'; ?>">
                                        <i class="fa-solid fa-clock-rotate-left me-1"></i>
                                        <?php echo $isExpired ? 'Expired: ' : 'Exp: '; ?><?php echo date('d-M-Y', strtotime($agent['expiry_date'])); ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted d-block">No expiry set</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Manage
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <a class="dropdown-item" href="agents.php?action=edit&id=<?php echo $agent['id']; ?>">
                                                <i class="fa-regular fa-pen-to-square me-2 text-muted"></i> Edit Profile
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="subscriptions.php">
                                                <i class="fa-solid fa-crown me-2 text-warning"></i> Subscriptions & Renewal
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="agents.php?action=recharge&id=<?php echo $agent['id']; ?>">
                                                <i class="fa-solid fa-bolt me-2 text-warning"></i> Recharge Messages
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="shops.php?agent_id=<?php echo $agent['id']; ?>">
                                                <i class="fa-solid fa-store me-2 text-primary"></i> Sub-Shops Portal
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="agents.php?action=toggle_status&id=<?php echo $agent['id']; ?>">
                                                <?php if ($agent['status'] === 'active'): ?>
                                                    <i class="fa-solid fa-user-slash me-2 text-danger"></i> Suspend Agent
                                                <?php else: ?>
                                                    <i class="fa-solid fa-user-check me-2 text-success"></i> Approve Agent
                                                <?php endif; ?>
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger delete-btn" href="#" data-url="agents.php?action=delete&id=<?php echo $agent['id']; ?>">
                                                <i class="fa-regular fa-trash-can me-2"></i> Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            
        </div>
    </div>

    <!-- Script for Delete Agent Confirmation -->
    <script>
        $(document).ready(function() {
            // Delete Dialog
            $(document).on('click', '.delete-btn', function(e) {
                e.preventDefault();
                const url = $(this).data('url');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You want to delete this agent partner? All their customers, vehicles, and documents will be permanently lost!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, delete agent!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#loader-wrapper').fadeIn(200);
                        window.location.href = url;
                    }
                });
            });
        });
    </script>

<?php elseif ($action === 'import_csv'): ?>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-csv text-primary fs-5"></i>
                        <h5 class="m-0 font-weight-700">Import Agents From CSV</h5>
                    </div>
                    <a href="agents.php" class="btn btn-light border btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back to List
                    </a>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-0 d-flex align-items-start gap-3" style="border-radius: 10px;">
                        <i class="fa-solid fa-circle-info mt-1"></i>
                        <div class="small">
                            Download the current CSV first, edit it, and upload it here.
                            Existing agents are updated by matching `username` or `email`.
                            New agents require the `password` column to be filled.
                        </div>
                    </div>

                    <div class="mb-4 d-flex align-items-center gap-2">
                        <a href="agents.php?action=export_csv" class="btn btn-light border text-success">
                            <i class="fa-solid fa-download me-1"></i> Download Current CSV
                        </a>
                    </div>

                    <form action="agents.php?action=import_csv" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="agents_csv" class="form-label">CSV File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="agents_csv" name="agents_csv" accept=".csv" required>
                        </div>

                        <div class="small text-muted mb-4">
                            Required columns: `username`, `email`, `shop_name`, `expiry_date`.
                        </div>

                        <div class="d-flex justify-content-end gap-3">
                            <a href="agents.php" class="btn btn-light border">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fa-solid fa-upload me-1"></i> Upload CSV
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($action === 'renew' && isset($_GET['id'])):
    $agentId = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT id, username, shop_name, expiry_date FROM users WHERE id = ? AND role = 'agent'");
    $stmt->execute([$agentId]);
    $agent = $stmt->fetch();

    if (!$agent) {
        $_SESSION['alert_error'] = 'Agent partner not found.';
        redirect('agents.php');
    }
?>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-arrows-rotate text-success fs-5"></i>
                        <h5 class="m-0 font-weight-700">Renew Agent License</h5>
                    </div>
                    <a href="agents.php" class="btn btn-light border btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back to List
                    </a>
                </div>
                <div class="card-body">
                    <form action="agents.php?action=renew" method="POST">
                        <input type="hidden" name="id" value="<?php echo (int)$agent['id']; ?>">

                        <div class="mb-3">
                            <label class="form-label">Agent Shop</label>
                            <input type="text" class="form-control" value="<?php echo sanitize($agent['shop_name']); ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo sanitize($agent['username']); ?>" disabled>
                        </div>

                        <div class="mb-4">
                            <label for="renew_date" class="form-label">New Expiry Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="renew_date" name="renew_date" required value="<?php echo sanitize($agent['expiry_date'] ?? ''); ?>">
                        </div>

                        <div class="d-flex justify-content-end gap-3">
                            <a href="agents.php" class="btn btn-light border">Cancel</a>
                            <button type="submit" class="btn btn-success px-4">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Renew License
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($action === 'recharge' && isset($_GET['id'])):
    $agentId = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT id, username, shop_name FROM users WHERE id = ? AND role = 'agent'");
    $stmt->execute([$agentId]);
    $agent = $stmt->fetch();

    if (!$agent) {
        $_SESSION['alert_error'] = 'Agent partner not found.';
        redirect('agents.php');
    }
?>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-bolt text-warning fs-5"></i>
                        <h5 class="m-0 font-weight-700">Recharge Message Wallet</h5>
                    </div>
                    <a href="agents.php" class="btn btn-light border btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back to List
                    </a>
                </div>
                <div class="card-body">
                    <form action="agents.php?action=recharge" method="POST">
                        <input type="hidden" name="id" value="<?php echo (int)$agent['id']; ?>">

                        <div class="mb-3">
                            <label class="form-label">Agent Shop</label>
                            <input type="text" class="form-control" value="<?php echo sanitize($agent['shop_name']); ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" value="<?php echo sanitize($agent['username']); ?>" disabled>
                        </div>

                        <div class="mb-3">
                            <label for="recharge_count" class="form-label font-weight-600">Number of Messages to Add <span class="text-danger">*</span></label>
                            <input type="number" min="1" step="1" class="form-control form-control-lg" id="recharge_count" name="recharge_count" required placeholder="e.g. 1000" oninput="calculateAgentCost(this.value)">
                            <div class="form-text mt-2" id="agent_cost_preview">Rate: ₹<?php echo number_format((float)$singleMessagePrice, 2); ?> per message</div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <button type="button" class="btn btn-outline-primary btn-sm flex-fill" onclick="setAgentRechargeCount(500)">+500 Msgs</button>
                            <button type="button" class="btn btn-outline-primary btn-sm flex-fill" onclick="setAgentRechargeCount(1000)">+1,000 Msgs</button>
                            <button type="button" class="btn btn-outline-primary btn-sm flex-fill" onclick="setAgentRechargeCount(2000)">+2,000 Msgs</button>
                            <button type="button" class="btn btn-outline-primary btn-sm flex-fill" onclick="setAgentRechargeCount(5000)">+5,000 Msgs</button>
                        </div>

                        <script>
                        const agentUnitPrice = <?php echo (float)$singleMessagePrice; ?>;
                        function setAgentRechargeCount(c) {
                            document.getElementById('recharge_count').value = c;
                            calculateAgentCost(c);
                        }
                        function calculateAgentCost(c) {
                            const qty = parseInt(c) || 0;
                            const prev = document.getElementById('agent_cost_preview');
                            if (qty > 0) {
                                prev.innerHTML = '<span class="text-success font-weight-600"><i class="fa-solid fa-calculator me-1"></i>Total Cost: ₹' + (qty * agentUnitPrice).toFixed(2) + '</span> <span class="text-muted">(' + qty.toLocaleString() + ' msgs @ ₹' + agentUnitPrice + '/msg)</span>';
                            } else {
                                prev.innerHTML = 'Rate: ₹' + agentUnitPrice.toFixed(2) + ' per message';
                            }
                        }
                        </script>

                        <div class="d-flex justify-content-end gap-3">
                            <a href="agents.php" class="btn btn-light border">Cancel</a>
                            <button type="submit" class="btn btn-warning px-4 font-weight-600">
                                <i class="fa-solid fa-wallet me-1"></i> Recharge Now
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($action === 'add' || $action === 'edit'): 
    // ADD or EDIT VIEW
    $agent = [];
    $isEdit = ($action === 'edit' && isset($_GET['id']));
    
    if ($isEdit) {
        $agentId = (int)$_GET['id'];
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'agent'");
        $stmt->execute([$agentId]);
        $agent = $stmt->fetch();
        if (!$agent) {
            $_SESSION['alert_error'] = 'Agent partner not found.';
            redirect('agents.php');
        }
    }
?>
    
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-user-pen text-primary fs-5"></i>
                        <h5 class="m-0 font-weight-700"><?php echo $isEdit ? 'Modify Agent Partner' : 'Register New Agent Partner'; ?></h5>
                    </div>
                    <a href="agents.php" class="btn btn-light border btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back to List
                    </a>
                </div>
                <div class="card-body">
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: 8px;">
                            <i class="fa-solid fa-circle-exclamation fs-5"></i>
                            <div class="small"><?php echo sanitize($error); ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="id" value="<?php echo $agent['id']; ?>">
                        <?php endif; ?>

                        <!-- Section 1: Agent & Partner Information -->
                        <h6 class="border-bottom pb-2 font-weight-600 mb-3 text-primary"><i class="fa-solid fa-user-tie me-2"></i>1. Agent & Partner Information</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <label for="shop_name" class="form-label">Agency / Partner Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="shop_name" name="shop_name" required value="<?php echo isset($_POST['shop_name']) ? sanitize($_POST['shop_name']) : ($isEdit ? sanitize($agent['shop_name']) : ''); ?>" placeholder="e.g. Speedy Agency">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="shop_owner_name" class="form-label">Agent Contact Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="shop_owner_name" name="shop_owner_name" required value="<?php echo isset($_POST['shop_owner_name']) ? sanitize($_POST['shop_owner_name']) : ($isEdit ? sanitize($agent['shop_owner_name']) : ''); ?>" placeholder="e.g. Ramesh Kumar">
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="mobile_number" class="form-label">Phone / Mobile Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mobile_number" name="mobile_number" required value="<?php echo isset($_POST['mobile_number']) ? sanitize($_POST['mobile_number']) : ($isEdit ? sanitize($agent['mobile_number'] ?? '') : ''); ?>" placeholder="+91 98765 43210">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ($isEdit ? sanitize($agent['email']) : ''); ?>" placeholder="agent@speedyagency.com">
                            </div>
                        </div>

                        <!-- Section 2: Login Credentials & Account Status -->
                        <h6 class="border-bottom pb-2 font-weight-600 mb-3 text-primary"><i class="fa-solid fa-key me-2"></i>2. Login Credentials & Status</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-4">
                                <label for="username" class="form-label">Login Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" required value="<?php echo isset($_POST['username']) ? sanitize($_POST['username']) : ($isEdit ? sanitize($agent['username']) : ''); ?>" placeholder="username_tag">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="password" class="form-label">Password <?php echo $isEdit ? '<span class="text-muted small">(Leave empty to keep)</span>' : '<span class="text-danger">*</span>'; ?></label>
                                <input type="password" class="form-control" id="password" name="password" <?php echo $isEdit ? '' : 'required'; ?> placeholder="Min 6 characters">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="status" class="form-label">Account Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') || ($isEdit && $agent['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                    <option value="suspended" <?php echo (isset($_POST['status']) && $_POST['status'] === 'suspended') || ($isEdit && $agent['status'] === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                        </div>

                        <!-- Section 3: Service Access Permissions -->
                        <h6 class="border-bottom pb-2 font-weight-600 mb-3 text-primary"><i class="fa-solid fa-user-shield me-2"></i>3. Service Access Permissions</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-4">
                                <div class="form-check form-switch pt-2">
                                    <input class="form-check-input" type="checkbox" id="access_health_insurance" name="access_health_insurance" value="1" <?php echo (!isset($_POST['username']) && !$isEdit) || (isset($_POST['access_health_insurance'])) || ($isEdit && isset($agent['access_health_insurance']) && $agent['access_health_insurance'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label font-weight-600" for="access_health_insurance">Health Insurance Access</label>
                                    <div class="form-text small text-muted">Allow agent to sell & manage Health Insurance policies.</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-check form-switch pt-2">
                                    <input class="form-check-input" type="checkbox" id="access_vehicle_insurance" name="access_vehicle_insurance" value="1" <?php echo (!isset($_POST['username']) && !$isEdit) || (isset($_POST['access_vehicle_insurance'])) || ($isEdit && isset($agent['access_vehicle_insurance']) && $agent['access_vehicle_insurance'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label font-weight-600" for="access_vehicle_insurance">Vehicle Insurance Access</label>
                                    <div class="form-text small text-muted">Allow agent to register vehicles & manage vehicle insurance.</div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4">
                                <div class="form-check form-switch pt-2">
                                    <input class="form-check-input" type="checkbox" id="access_pollution" name="access_pollution" value="1" <?php echo (!isset($_POST['username']) && !$isEdit) || (isset($_POST['access_pollution'])) || ($isEdit && isset($agent['access_pollution']) && $agent['access_pollution'] == 1) ? 'checked' : ''; ?>>
                                    <label class="form-check-label font-weight-600" for="access_pollution">Pollution Control Access</label>
                                    <div class="form-text small text-muted">Allow agent to manage Pollution certificates (PUC).</div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        
                        <div class="d-flex align-items-center justify-content-end gap-3">
                            <a href="agents.php" class="btn btn-light border">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fa-solid fa-floppy-disk me-1"></i> <?php echo $isEdit ? 'Update Agent Details' : 'Create Agent Account'; ?>
                            </button>
                        </div>
                    </form>
                    
                </div>
            </div>
            
        </div>
    </div>

<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
