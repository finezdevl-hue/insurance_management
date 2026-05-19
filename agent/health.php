<?php
/**
 * Health Insurance Management (Agent View)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce agent login
checkAccess('agent');

// Enforce agent service access for Health Insurance
if (!hasAgentAccess('health')) {
    $_SESSION['alert_error'] = 'You do not have access to the Health Insurance service. Please contact Super Admin.';
    redirect('index.php');
}

$db = getDBConnection();
$agentId = $_SESSION['user_id'];

$pageTitle = 'Health Insurance Policies';
$activePage = 'health';

$action = $_GET['action'] ?? 'list';
$error = '';

// --- CONTROLLER ACTIONS ---

// A. Delete Policy
if ($action === 'delete' && isset($_GET['id'])) {
    $policyId = (int)$_GET['id'];
    try {
        // Verify owner before delete
        $stmtCheck = $db->prepare("SELECT policy_number FROM health_insurances WHERE id = ? AND agent_id = ?");
        $stmtCheck->execute([$policyId, $agentId]);
        $policy = $stmtCheck->fetch();
        
        if ($policy) {
            $stmtDel = $db->prepare("DELETE FROM health_insurances WHERE id = ?");
            $stmtDel->execute([$policyId]);
            
            logActivity('Delete Health Insurance', "Deleted Health Policy: {$policy['policy_number']}");
            $_SESSION['alert_success'] = 'Health insurance policy deleted successfully!';
        } else {
            $_SESSION['alert_error'] = 'Policy not found or access denied.';
        }
    } catch (PDOException $e) {
        $_SESSION['alert_error'] = 'Error deleting policy: ' . $e->getMessage();
    }
    redirect('health.php');
}

// B. Send Reminder (WhatsApp/Simulated Alert)
if ($action === 'send_reminder' && isset($_GET['id'])) {
    $policyId = (int)$_GET['id'];
    try {
        $quotaCheck = canAgentSendMessages($agentId, 1);
        if (!$quotaCheck['allowed']) {
            $_SESSION['alert_error'] = getMessageLimitError($quotaCheck['balance'], $quotaCheck['requested']);
            redirect('health.php');
        }

        $stmtPolicy = $db->prepare("
            SELECT h.*, c.name as customer_name, c.whatsapp_number, c.id as cust_id 
            FROM health_insurances h 
            JOIN customers c ON h.customer_id = c.id 
            WHERE h.id = ? AND h.agent_id = ?
        ");
        $stmtPolicy->execute([$policyId, $agentId]);
        $policy = $stmtPolicy->fetch();
        
        if ($policy) {
            $expDays = getDaysUntil($policy['expiry_date']);
            $expiryFormatted = date('d-M-Y', strtotime($policy['expiry_date']));
            
            $msg = "Hello *{$policy['customer_name']}*, your Health Insurance Policy *{$policy['policy_number']}* ({$policy['policy_name']}) is expiring on *{$expiryFormatted}* ({$expDays} days left). Please contact us to renew it to ensure uninterrupted coverage. Thank you!";
            
            $res = sendWhatsAppMessage($policy['whatsapp_number'], $msg, $policy['customer_name'], $policy['policy_number'], $expiryFormatted);
            
            // Log reminder in history
            $stmtHist = $db->prepare("
                INSERT INTO reminder_history (customer_id, vehicle_id, reminder_type, reminder_period, sent_by_user_id, status, message, api_response)
                VALUES (?, 0, 'Health', ?, ?, ?, ?, ?)
            ");
            $period = ($expDays < 0) ? 'Expired' : $expDays . ' Days';
            $status = $res['success'] ? 'sent' : 'failed';
            $stmtHist->execute([
                $policy['cust_id'],
                $period,
                $agentId,
                $status,
                $msg,
                $res['response']
            ]);
            
            logActivity('Send Health Reminder', "Sent renewal reminder for Health Policy: {$policy['policy_number']}");
            
            if ($res['success']) {
                deductAgentMessages($agentId, 1);
                $_SESSION['alert_success'] = 'Renewal reminder alert sent successfully via WhatsApp!';
            } else {
                $_SESSION['alert_error'] = 'Alert failed to send: ' . sanitize($res['response']);
            }
        } else {
            $_SESSION['alert_error'] = 'Policy not found or access denied.';
        }
    } catch (Exception $e) {
        $_SESSION['alert_error'] = 'Error sending alert: ' . $e->getMessage();
    }
    redirect('health.php');
}

// C. Create or Edit Form Post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $policyId = isset($_POST['id']) ? (int)$_POST['id'] : null;
    
    $customerId = (int)$_POST['customer_id'];
    $companyId = (int)$_POST['insurance_company_id'];
    $policyNumber = trim($_POST['policy_number']);
    $policyName = trim($_POST['policy_name']);
    $insuredPersons = trim($_POST['insured_persons']);
    $startDate = $_POST['start_date'];
    $expiryDate = $_POST['expiry_date'];
    $premiumAmount = (float)$_POST['premium_amount'];
    
    if (empty($customerId) || empty($companyId) || empty($policyNumber) || empty($policyName) || empty($startDate) || empty($expiryDate) || empty($premiumAmount)) {
        $error = 'All fields except document upload are required.';
    } else {
        try {
            // Verify customer belongs to agent
            $stmtCheckCust = $db->prepare("SELECT id FROM customers WHERE id = ? AND agent_id = ?");
            $stmtCheckCust->execute([$customerId, $agentId]);
            if (!$stmtCheckCust->fetch()) {
                throw new Exception('Invalid customer selected.');
            }
            
            // Check for policy uniqueness
            if ($policyId) {
                $stmtUnique = $db->prepare("SELECT COUNT(*) FROM health_insurances WHERE policy_number = ? AND id != ?");
                $stmtUnique->execute([$policyNumber, $policyId]);
            } else {
                $stmtUnique = $db->prepare("SELECT COUNT(*) FROM health_insurances WHERE policy_number = ?");
                $stmtUnique->execute([$policyNumber]);
            }
            if ($stmtUnique->fetchColumn() > 0) {
                throw new Exception('This Policy Number is already registered.');
            }
            
            // Handle Document Upload
            $docPath = null;
            if (isset($_FILES['document']) && $_FILES['document']['error'] === UPLOAD_ERR_OK) {
                $uploaded = handleFileUpload($_FILES['document'], 'health', ['pdf', 'jpg', 'jpeg', 'png']);
                if ($uploaded) {
                    $docPath = $uploaded;
                }
            }
            
            if ($policyId) {
                // UPDATE
                $query = "
                    UPDATE health_insurances 
                    SET customer_id = ?, insurance_company_id = ?, policy_number = ?, 
                        policy_name = ?, insured_persons = ?, start_date = ?, expiry_date = ?, 
                        premium_amount = ?
                ";
                $params = [
                    $customerId, $companyId, $policyNumber, 
                    $policyName, $insuredPersons, $startDate, $expiryDate, 
                    $premiumAmount
                ];
                
                if ($docPath) {
                    $query .= ", document_path = ?";
                    $params[] = $docPath;
                }
                
                $query .= " WHERE id = ? AND agent_id = ?";
                $params[] = $policyId;
                $params[] = $agentId;
                
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                
                logActivity('Update Health Policy', "Updated Health Policy: $policyNumber");
                $_SESSION['alert_success'] = 'Health policy details updated successfully!';
                redirect('health.php');
            } else {
                // INSERT
                $stmt = $db->prepare("
                    INSERT INTO health_insurances (
                        customer_id, agent_id, insurance_company_id, policy_number, 
                        policy_name, insured_persons, start_date, expiry_date, 
                        premium_amount, document_path
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $customerId, $agentId, $companyId, $policyNumber,
                    $policyName, $insuredPersons, $startDate, $expiryDate,
                    $premiumAmount, $docPath
                ]);
                
                logActivity('Create Health Policy', "Created Health Policy: $policyNumber");
                $_SESSION['alert_success'] = 'New Health Insurance Policy added successfully!';
                redirect('health.php');
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

}

// D. Import Health CSV
if ($action === 'import_csv_health' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Please upload a valid CSV file.');
        }
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $headers = fgetcsv($handle);
        if (!$headers) throw new RuntimeException('Empty CSV file.');
        $headers = array_map('trim', $headers);
        $req = ['customer_name', 'customer_mobile', 'insurance_company', 'policy_number', 'policy_name', 'insured_persons', 'start_date', 'expiry_date', 'premium_amount'];
        foreach ($req as $r) {
            if (!in_array($r, $headers)) throw new RuntimeException("Missing column: $r");
        }
        $db->beginTransaction();
        $imported = 0;
        $skipped = 0;
        
        while (($data = fgetcsv($handle)) !== false) {
            if (!array_filter($data)) continue;
            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = $data[$index] ?? '';
            }
            $custName = trim($row['customer_name'] ?? '');
            $custMobile = trim($row['customer_mobile'] ?? '');
            $company = trim($row['insurance_company'] ?? '');
            $policyNumber = trim($row['policy_number'] ?? '');
            $policyName = trim($row['policy_name'] ?? '');
            $insured = trim($row['insured_persons'] ?? '');
            $start = trim($row['start_date'] ?? '');
            $expiry = trim($row['expiry_date'] ?? '');
            $premium = (float)trim($row['premium_amount'] ?? '0');
            
            if (empty($custName) || empty($custMobile) || empty($company) || empty($policyNumber) || empty($expiry)) {
                $skipped++;
                continue;
            }
            
            // 1. Get/Create Customer
            $stmtC = $db->prepare("SELECT id FROM customers WHERE mobile_number = ? AND agent_id = ?");
            $stmtC->execute([$custMobile, $agentId]);
            $custId = $stmtC->fetchColumn();
            if (!$custId) {
                $db->prepare("INSERT INTO customers (agent_id, name, mobile_number, whatsapp_number) VALUES (?, ?, ?, ?)")
                   ->execute([$agentId, $custName, $custMobile, $custMobile]);
                $custId = $db->lastInsertId();
            }
            
            // 2. Get Insurance Company
            $stmtI = $db->prepare("SELECT id FROM insurance_companies WHERE name = ?");
            $stmtI->execute([$company]);
            $companyId = $stmtI->fetchColumn() ?: 1;
            
            // 3. Update/Insert Policy
            $stmtH = $db->prepare("SELECT id FROM health_insurances WHERE policy_number = ?");
            $stmtH->execute([$policyNumber]);
            $hId = $stmtH->fetchColumn();
            
            if ($hId) {
                $db->prepare("UPDATE health_insurances SET customer_id=?, insurance_company_id=?, policy_name=?, insured_persons=?, start_date=?, expiry_date=?, premium_amount=? WHERE id=? AND agent_id=?")
                   ->execute([$custId, $companyId, $policyName, $insured, $start ?: null, $expiry, $premium, $hId, $agentId]);
            } else {
                $db->prepare("INSERT INTO health_insurances (agent_id, customer_id, insurance_company_id, policy_number, policy_name, insured_persons, start_date, expiry_date, premium_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                   ->execute([$agentId, $custId, $companyId, $policyNumber, $policyName, $insured, $start ?: null, $expiry, $premium]);
            }
            $imported++;
        }
        fclose($handle);
        $db->commit();
        $msg = "Imported/Updated $imported health insurance policies successfully.";
        if ($skipped > 0) $msg .= " (Skipped $skipped rows due to missing required data).";
        $_SESSION['alert_success'] = $msg;
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $_SESSION['alert_error'] = "Import failed: " . $e->getMessage();
    }
    redirect('health.php');
}

// E. Export Health CSV
if ($action === 'export_csv_health') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=health_policies_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['customer_name', 'customer_mobile', 'insurance_company', 'policy_number', 'policy_name', 'insured_persons', 'start_date', 'expiry_date', 'premium_amount']);
    
    $stmt = $db->prepare("
        SELECT c.name, c.mobile_number, i.name as company, h.policy_number, h.policy_name, h.insured_persons, h.start_date, h.expiry_date, h.premium_amount 
        FROM health_insurances h 
        JOIN customers c ON h.customer_id = c.id 
        JOIN insurance_companies i ON h.insurance_company_id = i.id 
        WHERE h.agent_id = ?
    ");
    $stmt->execute([$agentId]);
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// --- RENDERING CONFIG ---
$pageHeading = 'Health Insurance Policies';
include_once __DIR__ . '/../includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <!-- LIST VIEW -->
    <div class="card shadow-sm border-0 animate-fade-in">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-heart-pulse text-success fs-5"></i>
                <h5 class="m-0 font-weight-700">Health Insurance Directory</h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="health.php?action=import_csv_health" class="btn btn-light border btn-sm text-primary">
                    <i class="fa-solid fa-file-csv me-1"></i> CSV: Health
                </a>
                <a href="health.php?action=add" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-plus"></i> Add New Health Policy
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive border-0">
                <table class="table table-hover align-middle datatable w-100">
                    <thead>
                        <tr>
                            <th>Policy Info</th>
                            <th>Customer / Member Details</th>
                            <th>Company / Product</th>
                            <th>Dates / Duration</th>
                            <th>Premium</th>
                            <th>Status / Alerts</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $db->prepare("
                            SELECT h.*, c.name as customer_name, c.mobile_number, c.whatsapp_number, ic.name as company_name 
                            FROM health_insurances h 
                            JOIN customers c ON h.customer_id = c.id 
                            JOIN insurance_companies ic ON h.insurance_company_id = ic.id 
                            WHERE h.agent_id = ? 
                            ORDER BY h.id DESC
                        ");
                        $stmt->execute([$agentId]);
                        while ($row = $stmt->fetch()):
                            $expStatus = getExpiryStatus($row['expiry_date']);
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="stat-icon-wrapper bg-success-light text-success m-0 p-2 rounded-circle" style="width:36px; height:36px; font-size: 0.9rem;">
                                            <i class="fa-solid fa-file-medical"></i>
                                        </div>
                                        <div>
                                            <h6 class="m-0 font-weight-600 text-main"><?php echo sanitize($row['policy_number']); ?></h6>
                                            <small class="text-muted"><?php echo sanitize($row['policy_name']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <p class="m-0 font-weight-500 text-main"><?php echo sanitize($row['customer_name']); ?></p>
                                    <small class="text-muted d-block" style="font-size:0.75rem;"><i class="fa-solid fa-users me-1 text-muted"></i> Insured: <?php echo sanitize($row['insured_persons']); ?></small>
                                    <small class="text-muted d-block" style="font-size:0.75rem;"><i class="fa-solid fa-phone me-1"></i> <?php echo sanitize($row['mobile_number']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border font-weight-500"><?php echo sanitize($row['company_name']); ?></span>
                                </td>
                                <td>
                                    <span class="d-block small font-weight-500 text-main">Expires: <?php echo date('d-M-Y', strtotime($row['expiry_date'])); ?></span>
                                    <small class="text-muted style='font-size: 0.72rem;'">Starts: <?php echo date('d-M-Y', strtotime($row['start_date'])); ?></small>
                                </td>
                                <td>
                                    <span class="font-weight-600 text-main">₹<?php echo number_format($row['premium_amount'], 2); ?></span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $expStatus['badge']; ?> mb-1">
                                        <?php echo sanitize($expStatus['text']); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li>
                                                <a class="dropdown-item text-primary" href="health.php?action=send_reminder&id=<?php echo $row['id']; ?>">
                                                    <i class="fa-solid fa-paper-plane me-2"></i> Dispatch Alert
                                                </a>
                                            </li>
                                            <?php if ($row['document_path']): ?>
                                                <li>
                                                    <a class="dropdown-item" href="../uploads/health/<?php echo $row['document_path']; ?>" target="_blank">
                                                        <i class="fa-regular fa-file-pdf me-2 text-danger"></i> View Document
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            <li>
                                                <a class="dropdown-item" href="health.php?action=edit&id=<?php echo $row['id']; ?>">
                                                    <i class="fa-regular fa-pen-to-square me-2 text-muted"></i> Edit Details
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger delete-btn" href="#" data-url="health.php?action=delete&id=<?php echo $row['id']; ?>">
                                                    <i class="fa-regular fa-trash-can me-2"></i> Delete Policy
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
    </div>

    <!-- Script for Delete Confirmation & Alert Dialog -->
    <script>
        $(document).ready(function() {
            $('.delete-btn').on('click', function(e) {
                e.preventDefault();
                const url = $(this).data('url');
                Swal.fire({
                    title: 'Delete Policy?',
                    text: "Are you sure you want to permanently delete this Health Insurance record? This cannot be undone.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });
    </script>

<?php elseif ($action === 'add' || $action === 'edit'):
    $isEdit = ($action === 'edit' && isset($_GET['id']));
    $policy = [];
    
    if ($isEdit) {
        $policyId = (int)$_GET['id'];
        $stmt = $db->prepare("SELECT * FROM health_insurances WHERE id = ? AND agent_id = ?");
        $stmt->execute([$policyId, $agentId]);
        $policy = $stmt->fetch();
        if (!$policy) {
            $_SESSION['alert_error'] = 'Policy not found or access denied.';
            redirect('health.php');
        }
    }
    
    // Fetch dropdown data
    $stmtCust = $db->prepare("SELECT id, name, mobile_number FROM customers WHERE agent_id = ? ORDER BY name ASC");
    $stmtCust->execute([$agentId]);
    $customers = $stmtCust->fetchAll();
    
    $companies = $db->query("SELECT id, name FROM insurance_companies WHERE status = 'active' ORDER BY name ASC")->fetchAll();
?>
    <div class="row justify-content-center animate-fade-in">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-medical text-success fs-5"></i>
                        <h5 class="m-0 font-weight-700"><?php echo $isEdit ? 'Modify Health Insurance Policy' : 'Enroll New Health Insurance Policy'; ?></h5>
                    </div>
                    <a href="health.php" class="btn btn-light border btn-sm">
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

                    <form action="" method="POST" enctype="multipart/form-data">
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="id" value="<?php echo $policy['id']; ?>">
                        <?php endif; ?>

                        <!-- Section 1: Customer & Company Selection -->
                        <h6 class="border-bottom pb-2 font-weight-600 mb-3 text-success"><i class="fa-solid fa-user-shield me-2"></i>1. Primary Insured & Carrier</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <label for="customer_id" class="form-label">Insured Customer <span class="text-danger">*</span></label>
                                <select class="form-select" id="customer_id" name="customer_id" required>
                                    <option value="" disabled selected>-- Select Insured Customer --</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo (isset($_POST['customer_id']) && (int)$_POST['customer_id'] === $c['id']) || ($isEdit && $policy['customer_id'] === $c['id']) ? 'selected' : ''; ?>>
                                            <?php echo sanitize($c['name']); ?> (<?php echo sanitize($c['mobile_number']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="insurance_company_id" class="form-label">Insurance Company <span class="text-danger">*</span></label>
                                <select class="form-select" id="insurance_company_id" name="insurance_company_id" required>
                                    <option value="" disabled selected>-- Select Insurance Company --</option>
                                    <?php foreach ($companies as $comp): ?>
                                        <option value="<?php echo $comp['id']; ?>" <?php echo (isset($_POST['insurance_company_id']) && (int)$_POST['insurance_company_id'] === $comp['id']) || ($isEdit && $policy['insurance_company_id'] === $comp['id']) ? 'selected' : ''; ?>>
                                            <?php echo sanitize($comp['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Section 2: Policy Details -->
                        <h6 class="border-bottom pb-2 font-weight-600 mb-3 text-success"><i class="fa-solid fa-file-invoice-dollar me-2"></i>2. Policy details & Premium</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-6">
                                <label for="policy_number" class="form-label">Policy Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="policy_number" name="policy_number" required value="<?php echo isset($_POST['policy_number']) ? sanitize($_POST['policy_number']) : ($isEdit ? sanitize($policy['policy_number']) : ''); ?>" placeholder="e.g. HLT-10293847">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="policy_name" class="form-label">Policy / Scheme Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="policy_name" name="policy_name" required value="<?php echo isset($_POST['policy_name']) ? sanitize($_POST['policy_name']) : ($isEdit ? sanitize($policy['policy_name']) : ''); ?>" placeholder="e.g. Optima Secure, Star Family Delux">
                            </div>
                            <div class="col-12">
                                <label for="insured_persons" class="form-label">Insured Member Name(s) <span class="text-muted">(Comma separated list)</span></label>
                                <input type="text" class="form-control" id="insured_persons" name="insured_persons" value="<?php echo isset($_POST['insured_persons']) ? sanitize($_POST['insured_persons']) : ($isEdit ? sanitize($policy['insured_persons']) : ''); ?>" placeholder="e.g. John Doe, Sarah Doe, Kid Doe">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="start_date" class="form-label">Policy Start Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required value="<?php echo isset($_POST['start_date']) ? $_POST['start_date'] : ($isEdit ? $policy['start_date'] : ''); ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="expiry_date" class="form-label">Policy Expiry Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="expiry_date" name="expiry_date" required value="<?php echo isset($_POST['expiry_date']) ? $_POST['expiry_date'] : ($isEdit ? $policy['expiry_date'] : ''); ?>">
                            </div>
                            <div class="col-12 col-md-4">
                                <label for="premium_amount" class="form-label">Premium Amount (INR) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" step="0.01" class="form-control" id="premium_amount" name="premium_amount" required value="<?php echo isset($_POST['premium_amount']) ? $_POST['premium_amount'] : ($isEdit ? $policy['premium_amount'] : ''); ?>" placeholder="0.00">
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Document Upload -->
                        <h6 class="border-bottom pb-2 font-weight-600 mb-3 text-success"><i class="fa-solid fa-file-arrow-up me-2"></i>3. Policy Document Copy</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label for="document" class="form-label">Upload Policy Document <span class="text-muted small">(PDF/Image, Max 10MB)</span></label>
                                <input type="file" class="form-control" id="document" name="document">
                                <?php if ($isEdit && $policy['document_path']): ?>
                                    <div class="mt-2 text-muted small">
                                        <i class="fa-regular fa-file-lines me-1"></i> Current Document: 
                                        <a href="../uploads/health/<?php echo $policy['document_path']; ?>" target="_blank" class="text-success font-weight-600"><?php echo sanitize($policy['document_path']); ?></a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <hr class="my-4">
                        
                        <div class="d-flex align-items-center justify-content-end gap-3">
                            <a href="health.php" class="btn btn-light border">Cancel</a>
                            <button type="submit" class="btn btn-success px-4">
                                <i class="fa-solid fa-floppy-disk me-1"></i> <?php echo $isEdit ? 'Update Details' : 'Enroll Policy'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php elseif ($action === 'import_csv_health'): ?>
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-csv text-primary fs-5"></i>
                        <h5 class="m-0 font-weight-700">Import Health Policies From CSV</h5>
                    </div>
                    <a href="health.php" class="btn btn-light border btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back to List
                    </a>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border-0 d-flex align-items-start gap-3" style="border-radius: 10px;">
                        <i class="fa-solid fa-circle-info mt-1"></i>
                        <div class="small">
                            <strong>Required columns:</strong> <code>customer_name</code>, <code>customer_mobile</code>, <code>insurance_company</code>, <code>policy_number</code>, <code>policy_name</code>, <code>insured_persons</code>, <code>start_date</code>, <code>expiry_date</code>, <code>premium_amount</code>.
                            <br>The system will automatically link or update records based on the Policy Number.
                        </div>
                    </div>

                    <div class="mb-4 d-flex align-items-center gap-2">
                        <a href="health.php?action=export_csv_health" class="btn btn-light border text-success">
                            <i class="fa-solid fa-download me-1"></i> Download Existing Data (CSV)
                        </a>
                    </div>

                    <form action="health.php?action=import_csv_health" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="csv_file" class="form-label">CSV File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                        </div>
                        <div class="d-flex justify-content-end gap-3">
                            <a href="health.php" class="btn btn-light border">Cancel</a>
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
