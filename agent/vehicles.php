<?php
/**
 * Unified Vehicle, Customer, Insurance & Pollution Enrollment Center (Agent View)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce Agent Access
checkAccess('agent');
if (!hasAgentAccess('vehicle') && !hasAgentAccess('pollution')) {
    $_SESSION['alert_error'] = 'Access denied. You do not have permissions for Vehicle or Pollution services.';
    redirect('index.php');
}

$db = getDBConnection();
$agentId = getEffectiveAgentId();
$creatorShopId = $_SESSION['user_id'];

$pageTitle = 'Vehicles & Owners Registry';
$activePage = 'vehicles';

$action = $_GET['action'] ?? 'list';
$error = '';

// --- PROCESS CONTROLLER ACTIONS ---

// A. Handle Delete Vehicle
if ($action === 'delete' && isset($_GET['id'])) {
    $vehicleId = (int)$_GET['id'];
    try {
        // Double check ownership
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

// B. Unified Save Vehicle, Customer, Insurance & PUC POST
if ($action === 'save_vehicle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $custId = $_POST['customer_id'];
    
    $vehNumber = strtoupper(trim($_POST['vehicle_number'] ?? ''));
    $ownerMobile = trim($_POST['owner_mobile'] ?? '');
    $ownerName = trim($_POST['owner_name'] ?? '');
    if (empty($ownerName)) {
        $ownerName = 'Customer';
    }
    
    $ownerWhatsapp = trim($_POST['owner_whatsapp'] ?? '');
    if (empty($ownerWhatsapp)) {
        $ownerWhatsapp = $ownerMobile;
    }
    
    if (empty($vehNumber) || empty($ownerMobile)) {
        throw new Exception('Vehicle Registration Number and Phone Number are required.');
    }
    
    try {
        $db->beginTransaction();
        
        // Find existing customer by mobile or create new customer automatically
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
        
        // Check if vehicle number exists in the database
        $stmtFind = $db->prepare("SELECT id, agent_id FROM vehicles WHERE vehicle_number = ? LIMIT 1");
        $stmtFind->execute([$vehNumber]);
        $existingVeh = $stmtFind->fetch();
        
        if ($existingVeh) {
            // If vehicle belongs to a different Parent Agent agency network, block creation
            if ((int)$existingVeh['agent_id'] !== (int)$agentId) {
                throw new Exception("Vehicle number $vehNumber is already registered under a different agency account.");
            }
            // If vehicle belongs to the SAME Parent Agent agency network, use existing vehicle ID
            $activeVehicleId = (int)$existingVeh['id'];
            $db->prepare("UPDATE vehicles SET customer_id = ?, created_by_shop_id = ? WHERE id = ? AND agent_id = ?")
               ->execute([$custId, $creatorShopId, $activeVehicleId, $agentId]);
            logActivity('Update Vehicle', "Updated existing agency vehicle record: $vehNumber (ID: $activeVehicleId)");
        } else {
            // New vehicle registration
            if ($vehicleId) {
                $stmt = $db->prepare("UPDATE vehicles SET customer_id = ?, vehicle_number = ? WHERE id = ? AND agent_id = ?");
                $stmt->execute([$custId, $vehNumber, $vehicleId, $agentId]);
                $activeVehicleId = $vehicleId;
                logActivity('Update Vehicle', "Updated vehicle: $vehNumber");
            } else {
                $stmt = $db->prepare("INSERT INTO vehicles (customer_id, agent_id, created_by_shop_id, vehicle_number) VALUES (?, ?, ?, ?)");
                $stmt->execute([$custId, $agentId, $creatorShopId, $vehNumber]);
                $activeVehicleId = (int)$db->lastInsertId();
                logActivity('Create Vehicle', "Created vehicle record: $vehNumber");
            }
        }
        
        // 2. Handle Optional Insurance Policy fields
        if (hasAgentAccess('vehicle') && isset($_POST['policy_number']) && !empty(trim($_POST['policy_number']))) {
            $companyId = (int)$_POST['insurance_company_id'];
            $policyNum = trim($_POST['policy_number']);
            $insType = $_POST['insurance_type'];
            $insStartDate = $_POST['ins_start_date'];
            $insExpiryDate = $_POST['ins_expiry_date'];
            $premium = (float)$_POST['premium_amount'];
            
            $docPath = null;
            if (isset($_FILES['ins_document']) && $_FILES['ins_document']['error'] === UPLOAD_ERR_OK) {
                $uploadedDoc = handleFileUpload($_FILES['ins_document'], 'insurances', ['pdf', 'jpg', 'jpeg', 'png']);
                if ($uploadedDoc) {
                    $docPath = $uploadedDoc;
                }
            }
            
            $stmtIns = $db->prepare("SELECT id FROM insurances WHERE vehicle_id = ?");
            $stmtIns->execute([$activeVehicleId]);
            $existingIns = $stmtIns->fetch();
            
            if ($existingIns) {
                $query = "
                    UPDATE insurances 
                    SET insurance_company_id = ?, policy_number = ?, insurance_type = ?, 
                        start_date = ?, expiry_date = ?, premium_amount = ?
                ";
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
            logActivity('Save Insurance', "Added/Updated insurance policy: $policyNum");
        }
        
        // 3. Handle Optional Pollution Certificate (PUC) fields
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
                if ($uploadedDoc) {
                    $docPath = $uploadedDoc;
                }
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
            logActivity('Save Pollution', "Added/Updated pollution certificate: $certNum");
        }
        
        $db->commit();
        $_SESSION['alert_success'] = 'Registration and policy profiles successfully enrolled!';
        redirect('vehicles.php');
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['alert_error'] = $e->getMessage();
        redirect('vehicles.php?action=' . ($vehicleId ? 'edit&id='.$vehicleId : 'add'));
    }
}

// C. Quick Renew / Save Pollution Certificate
if ($action === 'renew_puc' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hasAgentAccess('pollution')) {
        $_SESSION['alert_error'] = 'Access denied. You do not have permissions for Pollution services.';
        redirect('vehicles.php');
    }

    $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
    $certificateNumber = trim($_POST['certificate_number'] ?? '');
    $pucStartDate = !empty($_POST['puc_start_date']) ? $_POST['puc_start_date'] : date('Y-m-d');
    $pucExpiryDate = !empty($_POST['puc_expiry_date']) ? $_POST['puc_expiry_date'] : date('Y-m-d', strtotime('+6 months', strtotime($pucStartDate)));

    try {
        if ($vehicleId <= 0) {
            throw new Exception('Invalid vehicle selected.');
        }

        if ($certificateNumber === '' || $pucExpiryDate === '') {
            throw new Exception('PUC certificate number and expiry date are required.');
        }

        $stmtVehicle = $db->prepare("SELECT vehicle_number FROM vehicles WHERE id = ? AND agent_id = ?");
        $stmtVehicle->execute([$vehicleId, $agentId]);
        $vehicle = $stmtVehicle->fetch();

        if (!$vehicle) {
            throw new Exception('Vehicle not found or unauthorized access.');
        }

        $docPath = null;
        if (isset($_FILES['puc_document']) && $_FILES['puc_document']['error'] === UPLOAD_ERR_OK) {
            $uploadedDoc = handleFileUpload($_FILES['puc_document'], 'pollution', ['pdf', 'jpg', 'jpeg', 'png']);
            if ($uploadedDoc) {
                $docPath = $uploadedDoc;
            }
        }

        $stmtPuc = $db->prepare("SELECT id FROM pollution_certificates WHERE vehicle_id = ? AND agent_id = ?");
        $stmtPuc->execute([$vehicleId, $agentId]);
        $existingPuc = $stmtPuc->fetch();

        if ($existingPuc) {
            $query = "UPDATE pollution_certificates SET certificate_number = ?, start_date = ?, expiry_date = ?";
            $params = [$certificateNumber, $pucStartDate ?: null, $pucExpiryDate];

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
            ")->execute([$vehicleId, $agentId, $creatorShopId, $certificateNumber, $pucStartDate ?: null, $pucExpiryDate, $docPath]);
        }

        logActivity('Renew Pollution', "Saved pollution certificate for vehicle {$vehicle['vehicle_number']}.");
        $_SESSION['alert_success'] = "Pollution certificate renewed successfully for {$vehicle['vehicle_number']}.";
    } catch (Exception $e) {
        $_SESSION['alert_error'] = 'Pollution renewal failed: ' . $e->getMessage();
    }

    redirect('vehicles.php');
}



// E. Import Pollution CSV
if ($action === 'import_csv_pollution' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Please upload a valid CSV file.');
        }
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $headers = fgetcsv($handle, 0, ',', '"', '\\');
        if (!$headers) throw new RuntimeException('Empty CSV file.');
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        
        $req = ['customer_name', 'customer_mobile', 'vehicle_number', 'certificate_number', 'start_date', 'expiry_date'];
        foreach ($req as $r) {
            if (!in_array($r, $headers)) throw new RuntimeException("Missing column: $r");
        }
        $db->beginTransaction();
        
        $db->prepare("DELETE FROM pollution_certificates WHERE agent_id = ?")->execute([$agentId]);
        
        // Clean up orphaned vehicles (those that now have neither insurance nor pollution)
        $db->prepare("
            DELETE FROM vehicles 
            WHERE agent_id = ? 
            AND id NOT IN (SELECT vehicle_id FROM insurances WHERE agent_id = ?) 
            AND id NOT IN (SELECT vehicle_id FROM pollution_certificates WHERE agent_id = ?)
        ")->execute([$agentId, $agentId, $agentId]);
        
        $imported = 0;
        $skipped = 0;
        while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if (!array_filter($data)) continue;
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $data[$index] ?? '';
            }
            $custName = trim($row['customer_name'] ?? '');
            $custMobile = trim($row['customer_mobile'] ?? '');
            $vehNumber = strtoupper(trim($row['vehicle_number'] ?? ''));
            $certNum = trim($row['certificate_number'] ?? '');
            $startDate = trim($row['start_date'] ?? '');
            $expiryDate = trim($row['expiry_date'] ?? '');
            
            if (empty($custName) || empty($custMobile) || empty($vehNumber) || empty($certNum) || empty($expiryDate)) {
                $skipped++;
                continue;
            }
            
            $stmtC = $db->prepare("SELECT id FROM customers WHERE mobile_number = ? AND agent_id = ?");
            $stmtC->execute([$custMobile, $agentId]);
            $custId = $stmtC->fetchColumn();
            if (!$custId) {
                $db->prepare("INSERT INTO customers (agent_id, name, mobile_number, whatsapp_number) VALUES (?, ?, ?, ?)")
                   ->execute([$agentId, $custName, $custMobile, $custMobile]);
                $custId = $db->lastInsertId();
            }
            
            $stmtV = $db->prepare("SELECT id FROM vehicles WHERE vehicle_number = ? AND agent_id = ?");
            $stmtV->execute([$vehNumber, $agentId]);
            $vId = $stmtV->fetchColumn();
            if (!$vId) {
                $db->prepare("INSERT INTO vehicles (agent_id, customer_id, created_by_shop_id, vehicle_number) VALUES (?, ?, ?, ?)")
                   ->execute([$agentId, $custId, $creatorShopId, $vehNumber]);
                $vId = $db->lastInsertId();
            }
            
            $db->prepare("INSERT INTO pollution_certificates (vehicle_id, agent_id, certificate_number, start_date, expiry_date) VALUES (?, ?, ?, ?, ?)")
               ->execute([$vId, $agentId, $certNum, $startDate ?: null, $expiryDate]);
            $imported++;
        }
        fclose($handle);
        $db->commit();
        $msg = "Imported $imported Pollution Certificates successfully.";
        if ($skipped > 0) $msg .= " (Skipped $skipped rows due to missing data).";
        $_SESSION['alert_success'] = $msg;
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $_SESSION['alert_error'] = "Import failed: " . $e->getMessage();
    }
    redirect('vehicles.php');
}

// F. Import Insurance CSV
if ($action === 'import_csv_insurance' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Please upload a valid CSV file.');
        }
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $headers = fgetcsv($handle, 0, ',', '"', '\\');
        if (!$headers) throw new RuntimeException('Empty CSV file.');
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        
        $req = ['customer_name', 'customer_mobile', 'vehicle_number', 'insurance_company', 'policy_number', 'insurance_type', 'start_date', 'expiry_date', 'premium_amount'];
        foreach ($req as $r) {
            if (!in_array($r, $headers)) throw new RuntimeException("Missing column: $r");
        }
        $db->beginTransaction();
        
        $db->prepare("DELETE FROM insurances WHERE agent_id = ?")->execute([$agentId]);
        
        // Clean up orphaned vehicles (those that now have neither insurance nor pollution)
        $db->prepare("
            DELETE FROM vehicles 
            WHERE agent_id = ? 
            AND id NOT IN (SELECT vehicle_id FROM insurances WHERE agent_id = ?) 
            AND id NOT IN (SELECT vehicle_id FROM pollution_certificates WHERE agent_id = ?)
        ")->execute([$agentId, $agentId, $agentId]);
        
        $imported = 0;
        $skipped = 0;
        while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if (!array_filter($data)) continue;
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $data[$index] ?? '';
            }
            $custName = trim($row['customer_name'] ?? '');
            $custMobile = trim($row['customer_mobile'] ?? '');
            $vehNumber = strtoupper(trim($row['vehicle_number'] ?? ''));
            $insComp = trim($row['insurance_company'] ?? '');
            $policyNum = trim($row['policy_number'] ?? '');
            $insType = trim($row['insurance_type'] ?? '');
            $startDate = trim($row['start_date'] ?? '');
            $expiryDate = trim($row['expiry_date'] ?? '');
            $premium = (float)($row['premium_amount'] ?? 0);
            
            if (empty($custName) || empty($custMobile) || empty($vehNumber) || empty($policyNum) || empty($expiryDate) || empty($insComp)) {
                $skipped++;
                continue;
            }
            
            $stmtC = $db->prepare("SELECT id FROM customers WHERE mobile_number = ? AND agent_id = ?");
            $stmtC->execute([$custMobile, $agentId]);
            $custId = $stmtC->fetchColumn();
            if (!$custId) {
                $db->prepare("INSERT INTO customers (agent_id, name, mobile_number, whatsapp_number) VALUES (?, ?, ?, ?)")
                   ->execute([$agentId, $custName, $custMobile, $custMobile]);
                $custId = $db->lastInsertId();
            }
            
            $stmtV = $db->prepare("SELECT id FROM vehicles WHERE vehicle_number = ? AND agent_id = ?");
            $stmtV->execute([$vehNumber, $agentId]);
            $vId = $stmtV->fetchColumn();
            if (!$vId) {
                $db->prepare("INSERT INTO vehicles (agent_id, customer_id, created_by_shop_id, vehicle_number) VALUES (?, ?, ?, ?)")
                   ->execute([$agentId, $custId, $creatorShopId, $vehNumber]);
                $vId = $db->lastInsertId();
            }
            
            $stmtComp = $db->prepare("SELECT id FROM insurance_companies WHERE name = ?");
            $stmtComp->execute([$insComp]);
            $cId = $stmtComp->fetchColumn();
            if (!$cId) {
                $db->prepare("INSERT INTO insurance_companies (name) VALUES (?)")->execute([$insComp]);
                $cId = $db->lastInsertId();
            }
            
            $db->prepare("INSERT INTO insurances (vehicle_id, agent_id, insurance_company_id, policy_number, insurance_type, start_date, expiry_date, premium_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
               ->execute([$vId, $agentId, $cId, $policyNum, $insType, $startDate ?: null, $expiryDate, $premium]);
            $imported++;
        }
        fclose($handle);
        $db->commit();
        $msg = "Imported $imported Vehicle Insurances successfully.";
        if ($skipped > 0) $msg .= " (Skipped $skipped rows).";
        $_SESSION['alert_success'] = $msg;
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $_SESSION['alert_error'] = "Import failed: " . $e->getMessage();
    }
    redirect('vehicles.php');
}

// G. Export CSVs


if ($action === 'export_csv_pollution') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=pollution_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['customer_name', 'customer_mobile', 'vehicle_number', 'certificate_number', 'start_date', 'expiry_date'], ',', '"', '\\');
    
    $stmt = $db->prepare("
        SELECT c.name, c.mobile_number, v.vehicle_number, p.certificate_number, p.start_date, p.expiry_date
        FROM pollution_certificates p
        JOIN vehicles v ON p.vehicle_id = v.id
        JOIN customers c ON v.customer_id = c.id 
        WHERE p.agent_id = ?
    ");
    $stmt->execute([$agentId]);
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($output, $row, ',', '"', '\\');
    }
    fclose($output);
    exit;
}

if ($action === 'export_csv_insurance') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=insurances_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['customer_name', 'customer_mobile', 'vehicle_number', 'insurance_company', 'policy_number', 'insurance_type', 'start_date', 'expiry_date', 'premium_amount'], ',', '"', '\\');
    
    $stmt = $db->prepare("
        SELECT c.name, c.mobile_number, v.vehicle_number, ic.name as comp, i.policy_number, i.insurance_type, i.start_date, i.expiry_date, i.premium_amount
        FROM insurances i
        JOIN vehicles v ON i.vehicle_id = v.id
        JOIN customers c ON v.customer_id = c.id 
        JOIN insurance_companies ic ON i.insurance_company_id = ic.id
        WHERE i.agent_id = ?
    ");
    $stmt->execute([$agentId]);
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($output, $row, ',', '"', '\\');
    }
    fclose($output);
    exit;
}

$pageHeading = 'Unified Enroller';
include_once __DIR__ . '/../includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <!-- A. VEHICLES & POLICIES INDEX DIRECTORY -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-car text-success fs-5"></i>
                <h5 class="m-0 font-weight-700">Unified Vehicles Registry & Renewal Badges</h5>
            </div>
            <div class="d-flex align-items-center gap-2">

                <?php if (hasAgentAccess('vehicle')): ?>
                <a href="vehicles.php?action=import_csv_insurance" class="btn btn-light border btn-sm text-warning">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV: Insurances
                </a>
                <?php endif; ?>
                <?php if (hasAgentAccess('pollution')): ?>
                <a href="vehicles.php?action=import_csv_pollution" class="btn btn-light border btn-sm text-info">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV: Pollution
                </a>
                <?php endif; ?>
                <a href="vehicles.php?action=add" class="btn btn-success btn-sm">
                    <i class="fa-solid fa-plus-circle me-1"></i> Register New Vehicle / Owner
                </a>
            </div>
        </div>
        <div class="card-body">
            
            <table class="table table-hover align-middle datatable w-100">
                <thead>
                    <tr>
                        <th>Vehicle Registration Number</th>
                        <th>Owner Details</th>
                        <th>Outlet Center</th>
                        <?php if (hasAgentAccess('vehicle')): ?>
                            <th>Insurance Cover</th>
                        <?php endif; ?>
                        <?php if (hasAgentAccess('pollution')): ?>
                            <th>Pollution Status</th>
                        <?php endif; ?>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $db->prepare("
                        SELECT 
                            v.id,
                            v.vehicle_number,
                            v.created_at,
                            c.name as customer_name,
                            c.mobile_number as customer_mobile,
                            c.whatsapp_number as customer_whatsapp,
                            i.policy_number, i.expiry_date as ins_expiry, ic.name as company_name,
                            p.certificate_number, p.expiry_date as puc_expiry,
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
                        WHERE v.agent_id = ?
                        ORDER BY v.id DESC
                    ");
                    $stmt->execute([$agentId]);
                    
                    while ($vh = $stmt->fetch()):
                        $insStatus = $vh['ins_expiry'] ? getExpiryStatus($vh['ins_expiry']) : null;
                        $pucStatus = $vh['puc_expiry'] ? getExpiryStatus($vh['puc_expiry']) : null;
                    ?>
                        <tr>
                            <td>
                                <h6 class="m-0 font-weight-700 text-success fs-6"><?php echo sanitize($vh['vehicle_number']); ?></h6>
                            </td>
                            <td>
                                <strong class="small text-main d-block"><?php echo sanitize($vh['customer_name']); ?></strong>
                                <small class="text-muted" style="font-size: 0.75rem;"><i class="fa-solid fa-phone"></i> <?php echo sanitize($vh['customer_mobile']); ?></small>
                            </td>
                            <td>
                                <?php if (!empty($vh['creator_shop_name'])): ?>
                                    <span class="badge bg-light text-secondary border" style="font-size: 0.75rem;" title="Added by Shop Center">
                                        <i class="fa-solid fa-store me-1"></i><?php echo sanitize($vh['creator_shop_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <?php if (hasAgentAccess('vehicle')): ?>
                            <td>
                                <?php if ($vh['policy_number']): ?>
                                    <span class="d-block small font-weight-600 text-main" style="font-size: 0.8rem;"><?php echo sanitize($vh['company_name']); ?></span>
                                    <span class="badge <?php echo $insStatus['badge']; ?> mb-1">
                                        <?php echo sanitize($insStatus['text']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">No Active Policy</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <?php if (hasAgentAccess('pollution')): ?>
                            <td>
                                <?php if ($vh['certificate_number']): ?>
                                    <span class="d-block small text-muted font-monospace" style="font-size: 0.75rem;">PUC: <?php echo sanitize($vh['certificate_number']); ?></span>
                                    <span class="badge <?php echo $pucStatus['badge']; ?> mb-1">
                                        <?php echo sanitize($pucStatus['text']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">No PUC Cert</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Actions
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                        <li>
                                            <a class="dropdown-item" href="vehicles.php?action=edit&id=<?php echo $vh['id']; ?>">
                                                <i class="fa-regular fa-pen-to-square me-2 text-muted"></i> Modify Details
                                            </a>
                                        </li>
                                        <?php if (hasAgentAccess('pollution')): ?>
                                        <li>
                                            <a class="dropdown-item" href="vehicles.php?action=renew_puc&id=<?php echo $vh['id']; ?>">
                                                <i class="fa-solid fa-wind me-2 text-success"></i> Renew Pollution
                                            </a>
                                        </li>
                                        <?php endif; ?>
                                        <li>
                                            <a class="dropdown-item" href="vehicles.php?action=print_slip&id=<?php echo $vh['id']; ?>">
                                                <i class="fa-solid fa-print me-2 text-secondary"></i> Print summary Slip
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger delete-btn" href="#" data-url="vehicles.php?action=delete&id=<?php echo $vh['id']; ?>">
                                                <i class="fa-regular fa-trash-can me-2"></i> Remove Record
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

    <script>
        $(document).ready(function() {
            $('.delete-btn').on('click', function(e) {
                e.preventDefault();
                const url = $(this).data('url');
                Swal.fire({
                    title: 'Delete Vehicle Record?',
                    text: "Are you sure? Removing this vehicle wipes all associated insurance and pollution documents as well!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#loader-wrapper').fadeIn(200);
                        window.location.href = url;
                    }
                });
            });
        });
    </script>

<?php elseif ($action === 'renew_puc' && isset($_GET['id'])):
    if (!hasAgentAccess('pollution')) {
        $_SESSION['alert_error'] = 'Access denied. You do not have permissions for Pollution services.';
        redirect('vehicles.php');
    }

    $vehicleId = (int)$_GET['id'];
    $stmtVehicle = $db->prepare("
        SELECT v.id, v.vehicle_number, c.name AS customer_name
        FROM vehicles v
        JOIN customers c ON c.id = v.customer_id
        WHERE v.id = ? AND v.agent_id = ?
    ");
    $stmtVehicle->execute([$vehicleId, $agentId]);
    $vh = $stmtVehicle->fetch();

    if (!$vh) {
        $_SESSION['alert_error'] = 'Vehicle record not found.';
        redirect('vehicles.php');
    }

    $stmtPuc = $db->prepare("SELECT * FROM pollution_certificates WHERE vehicle_id = ? AND agent_id = ?");
    $stmtPuc->execute([$vehicleId, $agentId]);
    $puc = $stmtPuc->fetch() ?: [];
?>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-wind text-success fs-5"></i>
                        <h5 class="m-0 font-weight-700">Renew Pollution Certificate</h5>
                    </div>
                    <a href="vehicles.php" class="btn btn-light border btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    <form action="vehicles.php?action=renew_puc" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="vehicle_id" value="<?php echo (int)$vh['id']; ?>">

                        <div class="mb-3">
                            <label class="form-label">Vehicle Number</label>
                            <input type="text" class="form-control" value="<?php echo sanitize($vh['vehicle_number']); ?>" disabled>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Customer Name</label>
                            <input type="text" class="form-control" value="<?php echo sanitize($vh['customer_name']); ?>" disabled>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label for="certificate_number" class="form-label">PUC Certificate Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="certificate_number" name="certificate_number" required value="<?php echo sanitize($puc['certificate_number'] ?? ''); ?>" placeholder="e.g. PUC-9081273">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="puc_start_date" class="form-label">PUC Issue Date</label>
                                <input type="date" class="form-control" id="puc_start_date" name="puc_start_date" value="<?php echo !empty($puc['start_date']) ? sanitize($puc['start_date']) : date('Y-m-d'); ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="puc_expiry_date" class="form-label">PUC Expiry Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="puc_expiry_date" name="puc_expiry_date" required value="<?php echo sanitize($puc['expiry_date'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label for="puc_document" class="form-label">Upload Pollution Document <span class="text-muted small">(PDF/Image)</span></label>
                                <input type="file" class="form-control" id="puc_document" name="puc_document">
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex align-items-center justify-content-end gap-3">
                            <a href="vehicles.php" class="btn btn-light border">Cancel</a>
                            <button type="submit" class="btn btn-success px-4">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Save Pollution Renewal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($action === 'add' || $action === 'edit'): 
    // B. UNIFIED ADD / EDIT COMPONENT RENDER
    $vh = [];
    $ins = [];
    $puc = [];
    $isEdit = ($action === 'edit' && isset($_GET['id']));
    
    if ($isEdit) {
        $vehicleId = (int)$_GET['id'];
        $stmt = $db->prepare("SELECT v.*, c.name as customer_name, c.mobile_number as customer_mobile FROM vehicles v LEFT JOIN customers c ON v.customer_id = c.id WHERE v.id = ? AND v.agent_id = ?");
        $stmt->execute([$vehicleId, $agentId]);
        $vh = $stmt->fetch();
        if (!$vh) {
            $_SESSION['alert_error'] = 'Vehicle record not found.';
            redirect('vehicles.php');
        }
        
        $stmtIns = $db->prepare("SELECT * FROM insurances WHERE vehicle_id = ?");
        $stmtIns->execute([$vehicleId]);
        $ins = $stmtIns->fetch() ?: [];
        
        $stmtPuc = $db->prepare("SELECT * FROM pollution_certificates WHERE vehicle_id = ?");
        $stmtPuc->execute([$vehicleId]);
        $puc = $stmtPuc->fetch() ?: [];
    }
    
    // Fetch customers
    $stmtCust = $db->prepare("SELECT id, name, mobile_number FROM customers WHERE agent_id = ? ORDER BY name ASC");
    $stmtCust->execute([$agentId]);
    $customers = $stmtCust->fetchAll();
    
    // Fetch active vehicle types & insurance companies
    try {
        $types = $db->query("SELECT id, name FROM vehicle_types WHERE status = 'active' ORDER BY name ASC")->fetchAll();
    } catch (PDOException $e) {
        $types = [];
    }
    $companies = $db->query("SELECT id, name FROM insurance_companies WHERE status = 'active' ORDER BY name ASC")->fetchAll();
?>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-car-rear text-success fs-5"></i>
                        <h5 class="m-0 font-weight-700"><?php echo $isEdit ? 'Modify Enrollment Details' : 'Register Vehicle & Customer Details'; ?></h5>
                    </div>
                    <a href="vehicles.php" class="btn btn-light border btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    
                    <form action="vehicles.php?action=save_vehicle" method="POST" enctype="multipart/form-data" autocomplete="off">
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="id" value="<?php echo $vh['id']; ?>">
                        <?php endif; ?>

                        <!-- Section 1: Required & Basic Details -->
                        <h6 class="border-bottom pb-2 font-weight-600 mb-3 text-success"><i class="fa-solid fa-car me-2"></i>1. Vehicle & Owner Details</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-4">
                                <label for="vehicle_number" class="form-label font-weight-600">Vehicle Registration Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="vehicle_number" name="vehicle_number" required value="<?php echo ($isEdit && isset($vh['vehicle_number'])) ? sanitize($vh['vehicle_number']) : ''; ?>" placeholder="e.g. MH02AB1234">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="owner_mobile" class="form-label font-weight-600">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="owner_mobile" name="owner_mobile" required value="<?php echo ($isEdit && isset($vh['customer_mobile'])) ? sanitize($vh['customer_mobile']) : ''; ?>" placeholder="e.g. 9876543210">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="owner_name" class="form-label font-weight-600">Owner Name <span class="text-muted small font-normal">(Optional)</span></label>
                                <input type="text" class="form-control" id="owner_name" name="owner_name" value="<?php echo ($isEdit && isset($vh['customer_name']) && $vh['customer_name'] !== 'Customer') ? sanitize($vh['customer_name']) : ''; ?>" placeholder="e.g. Rahul Sharma (Optional)">
                            </div>
                        </div>

                        <!-- Section 4: Optional Insurance Policy -->
                        <?php if (hasAgentAccess('vehicle')): ?>
                        <h6 class="border-bottom pb-2 font-weight-600 mb-3 mt-4 text-success"><i class="fa-solid fa-building-shield me-2"></i>4. Optional Insurance Policy Details</h6>
                        <div class="card border-0 bg-light p-3 mb-4" style="border-radius: 12px;">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="insurance_company_id" class="form-label">Insurance Carrier</label>
                                    <select class="form-select" id="insurance_company_id" name="insurance_company_id">
                                        <option value="" disabled selected>-- Choose Company --</option>
                                        <?php foreach ($companies as $c): ?>
                                            <option value="<?php echo $c['id']; ?>" <?php echo (isset($ins['insurance_company_id']) && $ins['insurance_company_id'] == $c['id']) ? 'selected' : ''; ?>>
                                                <?php echo sanitize($c['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="policy_number" class="form-label">Policy Number</label>
                                    <input type="text" class="form-control" id="policy_number" name="policy_number" value="<?php echo isset($ins['policy_number']) ? sanitize($ins['policy_number']) : ''; ?>" placeholder="e.g. POL-109283">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="insurance_type" class="form-label">Coverage Type</label>
                                    <select class="form-select" id="insurance_type" name="insurance_type">
                                        <option value="Comprehensive" <?php echo (isset($ins['insurance_type']) && $ins['insurance_type'] === 'Comprehensive') ? 'selected' : ''; ?>>Comprehensive</option>
                                        <option value="Third Party" <?php echo (isset($ins['insurance_type']) && $ins['insurance_type'] === 'Third Party') ? 'selected' : ''; ?>>Third Party</option>
                                        <option value="Own Damage" <?php echo (isset($ins['insurance_type']) && $ins['insurance_type'] === 'Own Damage') ? 'selected' : ''; ?>>Own Damage</option>
                                        <option value="Zero Dep" <?php echo (isset($ins['insurance_type']) && $ins['insurance_type'] === 'Zero Dep') ? 'selected' : ''; ?>>Zero Dep</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="ins_start_date" class="form-label">Policy Activation Date</label>
                                    <input type="date" class="form-control" id="ins_start_date" name="ins_start_date" value="<?php echo $ins['start_date'] ?? ''; ?>">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="ins_expiry_date" class="form-label">Policy Expiration Date</label>
                                    <input type="date" class="form-control" id="ins_expiry_date" name="ins_expiry_date" value="<?php echo $ins['expiry_date'] ?? ''; ?>">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="premium_amount" class="form-label">Total Premium (₹)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">₹</span>
                                        <input type="number" step="0.01" class="form-control" id="premium_amount" name="premium_amount" value="<?php echo $ins['premium_amount'] ?? ''; ?>" placeholder="0.00">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="ins_document" class="form-label">Upload Policy Document <span class="text-muted small">(PDF/Image)</span></label>
                                    <input type="file" class="form-control" id="ins_document" name="ins_document">
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Section 5: Optional Pollution Certificate -->
                        <?php if (hasAgentAccess('pollution')): ?>
                        <h6 class="border-bottom pb-2 font-weight-600 mb-3 mt-4 text-success"><i class="fa-solid fa-wind me-2"></i>5. Optional Pollution Certificate (PUC) Details</h6>
                        <div class="card border-0 bg-light p-3 mb-4" style="border-radius: 12px;">
                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <label for="certificate_number" class="form-label">PUC Certificate Number</label>
                                    <input type="text" class="form-control" id="certificate_number" name="certificate_number" value="<?php echo isset($puc['certificate_number']) ? sanitize($puc['certificate_number']) : ''; ?>" placeholder="e.g. PUC-9081273">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="puc_start_date" class="form-label">PUC Issue Date</label>
                                    <input type="date" class="form-control" id="puc_start_date" name="puc_start_date" value="<?php echo !empty($puc['start_date']) ? sanitize($puc['start_date']) : date('Y-m-d'); ?>">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="puc_expiry_date" class="form-label">PUC Expiry Date</label>
                                    <input type="date" class="form-control" id="puc_expiry_date" name="puc_expiry_date" value="<?php echo $puc['expiry_date'] ?? ''; ?>">
                                </div>
                                <div class="col-12">
                                    <label for="puc_document" class="form-label">Upload Pollution Document <span class="text-muted small">(PDF/Image)</span></label>
                                    <input type="file" class="form-control" id="puc_document" name="puc_document">
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <hr class="my-4">
                        
                        <div class="d-flex align-items-center justify-content-end gap-3">
                            <a href="vehicles.php" class="btn btn-light border">Cancel</a>
                            <button type="submit" class="btn btn-success px-4 py-2 font-weight-600">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Save Enrollment
                            </button>
                        </div>
                    </form>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- Toggle customer fields panel -->
    <script>
        $(document).ready(function() {
            function toggleNewCustomerFields() {
                var selectedValue = $('#customer_id').val();
                if (selectedValue === 'new') {
                    $('#new_customer_panel').removeClass('d-none');
                    $('#new_customer_name').attr('required', true);
                    $('#new_customer_mobile').attr('required', true);
                } else {
                    $('#new_customer_panel').addClass('d-none');
                    $('#new_customer_name').removeAttr('required');
                    $('#new_customer_mobile').removeAttr('required');
                }
            }
            $('#customer_id').on('change', function() {
                toggleNewCustomerFields();
            });
            toggleNewCustomerFields();

            // Auto-calculate PUC Expiry Date (+6 Months from Issue Date)
            function autoCalcPucExpiry() {
                var startVal = $('#puc_start_date').val();
                if (startVal && startVal.indexOf('-') !== -1) {
                    var parts = startVal.split('-');
                    var year = parseInt(parts[0], 10);
                    var month = parseInt(parts[1], 10) - 1;
                    var day = parseInt(parts[2], 10);
                    
                    var dt = new Date(year, month + 6, day);
                    var yyyy = dt.getFullYear();
                    var mm = String(dt.getMonth() + 1).padStart(2, '0');
                    var dd = String(dt.getDate()).padStart(2, '0');
                    $('#puc_expiry_date').val(yyyy + '-' + mm + '-' + dd);
                }
            }

            $(document).on('change keyup input blur', '#puc_start_date', function() {
                autoCalcPucExpiry();
            });

            // Default start & expiry date on new form if empty
            if ($('#puc_start_date').length && !$('#puc_start_date').val() && !$('#puc_expiry_date').val()) {
                var today = new Date();
                var yyyy = today.getFullYear();
                var mm = String(today.getMonth() + 1).padStart(2, '0');
                var dd = String(today.getDate()).padStart(2, '0');
                $('#puc_start_date').val(yyyy + '-' + mm + '-' + dd);
                autoCalcPucExpiry();
            }
        });
    </script>

<?php elseif ($action === 'print_slip' && isset($_GET['id'])): 
    // E. PRINT SLIP COMPONENT
    $vehicleId = (int)$_GET['id'];
    
    $stmtVH = $db->prepare("
        SELECT 
            v.*, 
            'Vehicle' as type_name,
            c.name as customer_name, c.mobile_number as customer_mobile, c.email as customer_email, c.address as customer_address,
            i.policy_number, i.expiry_date as ins_expiry, ic.name as company_name, i.insurance_type, i.premium_amount,
            p.certificate_number, p.expiry_date as puc_expiry
        FROM vehicles v
        JOIN customers c ON v.customer_id = c.id
        LEFT JOIN insurances i ON i.vehicle_id = v.id
        LEFT JOIN insurance_companies ic ON i.insurance_company_id = ic.id
        LEFT JOIN pollution_certificates p ON p.vehicle_id = v.id
        WHERE v.id = ? AND v.agent_id = ?
        ORDER BY i.expiry_date DESC, p.expiry_date DESC LIMIT 1
    ");
    $stmtVH->execute([$vehicleId, $agentId]);
    $vh = $stmtVH->fetch();
    
    if (!$vh) {
        $_SESSION['alert_error'] = 'Vehicle record not found.';
        redirect('vehicles.php');
    }
    
    $stmtA = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmtA->execute([$agentId]);
    $ag = $stmtA->fetch();
?>
    <style>
    @media print {
        #sidebar, #header-nav, .btn, .filter-card {
            display: none !important;
        }
        #main-content {
            margin-left: 0 !important;
            padding-top: 0 !important;
        }
        body {
            background-color: #ffffff !important;
        }
    }
    .slip-container {
        max-width: 800px;
        margin: 20px auto;
        background-color: #ffffff;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        padding: 40px;
    }
    </style>
    
    <div class="d-flex align-items-center justify-content-end gap-2 mb-3 max-width-800 mx-auto">
        <a href="vehicles.php" class="btn btn-light border"><i class="fa-solid fa-arrow-left"></i> Back</a>
        <button onclick="window.print()" class="btn btn-success"><i class="fa-solid fa-print"></i> Trigger Print</button>
    </div>
    
    <div class="slip-container shadow-sm">
        <div class="row align-items-center pb-4 border-bottom mb-4">
            <div class="col-8">
                <h4 class="m-0 font-weight-700 text-success"><?php echo sanitize($ag['shop_name']); ?></h4>
                <p class="m-0 text-muted small"><?php echo sanitize($ag['business_type']); ?></p>
                <p class="m-0 text-muted small"><?php echo sanitize($ag['shop_address']) . ', ' . sanitize($ag['city']); ?></p>
                <p class="m-0 text-muted small">Tel: <?php echo sanitize($ag['mobile_number']); ?> | Email: <?php echo sanitize($ag['email']); ?></p>
            </div>
            <div class="col-4 text-end">
                <?php if ($ag['shop_logo']): ?>
                    <img src="../uploads/logos/<?php echo $ag['shop_logo']; ?>" alt="Logo" style="max-height: 80px; object-fit: contain;">
                <?php else: ?>
                    <h2 class="text-success font-weight-700 m-0">RTO CARE</h2>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center mb-4">
            <h5 class="text-uppercase font-weight-700 text-main border-bottom d-inline-block pb-1">Customer & Vehicle Care Summary Slip</h5>
        </div>
        
        <div class="row mb-4">
            <div class="col-6">
                <h6 class="font-weight-600 text-success mb-2"><i class="fa-regular fa-user me-1"></i> Customer Profile</h6>
                <table class="table table-sm table-borderless small">
                    <tr><td class="text-muted" style="width: 110px;">Name:</td><td><strong><?php echo sanitize($vh['customer_name']); ?></strong></td></tr>
                    <tr><td class="text-muted">Mobile Number:</td><td><?php echo sanitize($vh['customer_mobile']); ?></td></tr>
                    <tr><td class="text-muted">Email ID:</td><td><?php echo sanitize($vh['customer_email'] ?: 'N/A'); ?></td></tr>
                    <tr><td class="text-muted">Address:</td><td><?php echo sanitize($vh['customer_address'] ?: 'N/A'); ?></td></tr>
                </table>
            </div>
            <div class="col-6">
                <h6 class="font-weight-600 text-success mb-2"><i class="fa-solid fa-car me-1"></i> Vehicle Profile</h6>
                <table class="table table-sm table-borderless small">
                    <tr><td class="text-muted" style="width: 130px;">Vehicle Number:</td><td><strong class="text-primary fs-6"><?php echo sanitize($vh['vehicle_number']); ?></strong></td></tr>
                </table>
            </div>
        </div>
        
        <h6 class="font-weight-600 text-success mb-3 border-bottom pb-2"><i class="fa-solid fa-shield-halved me-1"></i> Active Policy & Renewal Matrix</h6>
        <table class="table table-bordered small">
            <thead class="table-light">
                <tr>
                    <th>Item Description</th>
                    <th>Policy / Certificate Code</th>
                    <th>Premium / Carrier</th>
                    <th>Expiry Date</th>
                </tr>
            </thead>
            <tbody>
                <?php if (hasAgentAccess('vehicle')): ?>
                <tr>
                    <td><strong>Insurance Policy</strong></td>
                    <td><?php echo sanitize($vh['policy_number'] ?: 'No Active Policy'); ?></td>
                    <td><?php echo $vh['policy_number'] ? sanitize($vh['company_name']) . ' (₹' . number_format($vh['premium_amount'], 2) . ')' : 'N/A'; ?></td>
                    <td><strong class="text-danger"><?php echo $vh['ins_expiry'] ? date('d-M-Y', strtotime($vh['ins_expiry'])) : 'N/A'; ?></strong></td>
                </tr>
                <?php endif; ?>
                <?php if (hasAgentAccess('pollution')): ?>
                <tr>
                    <td><strong>Pollution Certificate (PUC)</strong></td>
                    <td><?php echo sanitize($vh['certificate_number'] ?: 'No PUC Registered'); ?></td>
                    <td>RTO Standard PUC</td>
                    <td><strong class="text-danger"><?php echo $vh['puc_expiry'] ? date('d-M-Y', strtotime($vh['puc_expiry'])) : 'N/A'; ?></strong></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="text-center mt-5 pt-4 border-top text-muted small">
            <p class="m-0">Thank you for business partnership with <strong><?php echo sanitize($ag['shop_name']); ?></strong>!</p>
            <p class="m-0" style="font-size: 0.7rem;">Generated on <?php echo date('d-M-Y H:i A'); ?>. System Managed by Vehicle Care.</p>
        </div>
    </div>
<?php elseif ($action === 'import_csv_pollution' || $action === 'import_csv_insurance'): ?>
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-csv text-primary fs-5"></i>
                        <h5 class="m-0 font-weight-700">Import <?php echo ($action === 'import_csv_pollution' ? 'Pollution Certificates' : 'Vehicle Insurances'); ?> From CSV</h5>
                    </div>
                    <a href="vehicles.php" class="btn btn-light border btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back to List
                    </a>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-0 d-flex align-items-start gap-3" style="border-radius: 10px;">
                        <i class="fa-solid fa-circle-info mt-1"></i>
                        <div class="small">
                            <?php if ($action === 'import_csv_pollution'): ?>
                                <strong>Required columns:</strong> <code>customer_name</code>, <code>customer_mobile</code>, <code>vehicle_number</code>, <code>certificate_number</code>, <code>start_date</code>, <code>expiry_date</code>.
                            <?php else: ?>
                                <strong>Required columns:</strong> <code>customer_name</code>, <code>customer_mobile</code>, <code>vehicle_number</code>, <code>insurance_company</code>, <code>policy_number</code>, <code>insurance_type</code>, <code>start_date</code>, <code>expiry_date</code>, <code>premium_amount</code>.
                            <?php endif; ?>
                            <br>The system will automatically link or create records based on the Vehicle Number and Mobile Number.
                        </div>
                    </div>

                    <div class="mb-4 d-flex align-items-center gap-2">
                        <a href="vehicles.php?action=<?php echo ($action === 'import_csv_pollution' ? 'export_csv_pollution' : 'export_csv_insurance'); ?>" class="btn btn-light border text-success">
                            <i class="fa-solid fa-download me-1"></i> Download Existing Data (CSV)
                        </a>
                    </div>

                    <form action="vehicles.php?action=<?php echo $action; ?>" method="POST" enctype="multipart/form-data" onsubmit="return confirm('WARNING: Uploading this CSV will DELETE ALL your existing <?php echo ($action === 'import_csv_pollution' ? 'Pollution Certificates' : 'Vehicle Insurances'); ?>. Are you absolutely sure you want to proceed?');">
                        <div class="mb-3">
                            <label for="csv_file" class="form-label">CSV File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                        </div>
                        <div class="d-flex justify-content-end gap-3">
                            <a href="vehicles.php" class="btn btn-light border">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fa-solid fa-upload me-1"></i> Upload CSV
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
