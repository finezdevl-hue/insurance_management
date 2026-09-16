<?php
/**
 * Master Customer List (Super Admin View)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();

$pageTitle = 'Master Customer Directory';
$pageHeading = 'Customers Directory';
$activePage = 'customers';

$action = $_GET['action'] ?? 'list';
$error = '';

// --- PROCESS CONTROLLER ACTIONS ---
if ($action === 'export_csv_customers') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=all_customers_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['agent_username', 'name', 'mobile_number', 'whatsapp_number', 'email', 'address'], ',', '"', '\\');
    
    $stmt = $db->query("
        SELECT u.username as agent_username, c.name, c.mobile_number, c.whatsapp_number, c.email, c.address 
        FROM customers c 
        JOIN users u ON c.agent_id = u.id 
        ORDER BY u.username, c.name
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row, ',', '"', '\\');
    }
    fclose($output);
    exit;
}

// C2. Export Sample Customers CSV
if ($action === 'export_sample_csv_customers') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=sample_admin_customers.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['agent_username', 'name', 'mobile_number', 'whatsapp_number', 'email', 'address'], ',', '"', '\\');
    fputcsv($output, ['agent1', 'John Doe', '9876543210', '9876543210', 'john@example.com', '123 Main St, City'], ',', '"', '\\');
    fputcsv($output, ['agent2', 'Jane Smith', '9123456789', '9123456789', '', '456 Oak Ave, City'], ',', '"', '\\');
    fclose($output);
    exit;
}

if ($action === 'import_csv_customers' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Please upload a valid CSV file.');
        }
        
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $headers = fgetcsv($handle, 0, ',', '"', '\\');
        if (!$headers) throw new RuntimeException('Empty CSV file.');
        
        // Clean headers
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]); // Remove BOM
        
        $db->beginTransaction();
        $importedCount = 0;
        
        $stmtInsert = $db->prepare("
            INSERT INTO customers (agent_id, name, mobile_number, whatsapp_number, email, address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        // Cache agents
        $agentCache = [];
        
        while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            $row = array_combine($headers, array_pad($data, count($headers), ''));
            
            $agentUsername = trim($row['agent_username'] ?? '');
            $name = trim($row['name'] ?? '');
            $mobile = trim($row['mobile_number'] ?? '');
            $whatsapp = trim($row['whatsapp_number'] ?? '');
            $email = trim($row['email'] ?? '');
            $address = trim($row['address'] ?? '');
            
            if (empty($name) || empty($mobile) || empty($whatsapp) || empty($agentUsername)) continue;
            
            if (!isset($agentCache[$agentUsername])) {
                $stmtA = $db->prepare("SELECT id FROM users WHERE username = ? AND role = 'agent'");
                $stmtA->execute([$agentUsername]);
                $aId = $stmtA->fetchColumn();
                if (!$aId) continue; // skip if agent not found
                $agentCache[$agentUsername] = $aId;
            }
            
            $stmtInsert->execute([$agentCache[$agentUsername], $name, $mobile, $whatsapp, $email, $address]);
            $importedCount++;
        }
        
        fclose($handle);
        $db->commit();
        
        logActivity('Import Customers Admin', "Imported $importedCount customers via CSV");
        $_SESSION['alert_success'] = "$importedCount customers imported successfully!";
        redirect('customers.php');
        
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        $error = $e->getMessage();
    }
}

include_once __DIR__ . '/../includes/header.php';
?>

<?php if ($action === 'list'): ?>
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-users text-primary fs-5"></i>
            <h5 class="m-0 font-weight-700">All Registered Customers</h5>
        </div>
        <div class="d-flex gap-2">
            <a href="customers.php?action=import_csv_customers" class="btn btn-light border btn-sm text-primary">
                <i class="fa-solid fa-file-csv me-1"></i> CSV Import
            </a>
            <a href="customers.php?action=export_csv_customers" class="btn btn-light border btn-sm text-success">
                <i class="fa-solid fa-download me-1"></i> Export All
            </a>
        </div>
    </div>
    <div class="card-body">
        
        <table class="table table-hover align-middle datatable w-100">
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Contact Info</th>
                    <th>Full Address</th>
                    <th>Associated Agent</th>
                    <th>Vehicles Owned</th>
                    <th>ID Proof Doc</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Query all customers and join agent shop details, count vehicles
                $stmt = $db->query("
                    SELECT c.*, u.shop_name, u.username as agent_username, COUNT(v.id) as vehicle_count
                    FROM customers c
                    JOIN users u ON c.agent_id = u.id
                    LEFT JOIN vehicles v ON c.id = v.customer_id
                    GROUP BY c.id
                    ORDER BY c.id DESC
                ");
                
                while ($cust = $stmt->fetch()):
                ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="user-avatar text-uppercase bg-light text-primary font-weight-600 border">
                                    <?php echo substr(sanitize($cust['name']), 0, 2); ?>
                                </div>
                                <div>
                                    <h6 class="m-0 font-weight-600 text-main"><?php echo sanitize($cust['name']); ?></h6>
                                    <small class="text-muted">Registered: <?php echo date('d-M-Y', strtotime($cust['created_at'])); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="d-block small text-main font-weight-500">
                                <i class="fa-solid fa-phone text-muted me-1 fs-7"></i> <?php echo sanitize($cust['mobile_number']); ?>
                            </span>
                            <span class="d-block small text-success font-weight-500">
                                <i class="fa-brands fa-whatsapp me-1"></i> <?php echo sanitize($cust['whatsapp_number']); ?>
                            </span>
                            <?php if ($cust['email']): ?>
                                <span class="d-block small text-muted">
                                    <i class="fa-solid fa-envelope me-1 fs-7"></i> <?php echo sanitize($cust['email']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="small text-muted text-wrap d-inline-block" style="max-width: 250px;">
                                <?php echo sanitize($cust['address'] ?: 'No address specified'); ?>
                            </span>
                        </td>
                        <td>
                            <strong class="text-main d-block small"><?php echo sanitize($cust['shop_name']); ?></strong>
                            <small class="text-muted">Agent: @<?php echo sanitize($cust['agent_username']); ?></small>
                        </td>
                        <td>
                            <span class="badge bg-primary fs-7 px-2.5 py-1.5" style="border-radius: 20px;">
                                <i class="fa-solid fa-car-side me-1"></i> <?php echo $cust['vehicle_count']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($cust['id_proof_path']): ?>
                                <a href="../uploads/proofs/<?php echo $cust['id_proof_path']; ?>" target="_blank" class="btn btn-sm btn-light border text-primary">
                                    <i class="fa-solid fa-file-arrow-down me-1"></i> Download
                                </a>
                            <?php else: ?>
                                <span class="text-muted small">Not Uploaded</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
    </div>
</div>
<?php elseif ($action === 'import_csv_customers'): ?>
    <div class="row justify-content-center">
        <div class="col-12 col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-file-csv text-primary fs-5"></i>
                        <h5 class="m-0 font-weight-700">Import Customers From CSV (Admin)</h5>
                    </div>
                    <a href="customers.php" class="btn btn-light border btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert" style="border-radius: 8px;">
                            <i class="fa-solid fa-circle-exclamation fs-5"></i>
                            <div class="small"><?php echo sanitize($error); ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-info bg-light border-info border-start border-4 border-0 mb-4 rounded-0">
                        <h6 class="font-weight-700 text-info mb-2"><i class="fa-solid fa-circle-info me-1"></i> CSV Format Guide</h6>
                        <p class="small mb-2">Please ensure your CSV file has the following columns in exactly this order (or with these headers):</p>
                        <code class="d-block bg-white p-2 border rounded text-dark mb-3">agent_username, name, mobile_number, whatsapp_number, email, address</code>
                        <p class="small text-muted mb-3">The <code>agent_username</code> must perfectly match an existing agent in the system.</p>
                        <a href="customers.php?action=export_sample_csv_customers" class="btn btn-light border text-primary btn-sm">
                            <i class="fa-solid fa-file-csv me-1"></i> Download Sample CSV
                        </a>
                    </div>

                    <form action="customers.php?action=import_csv_customers" method="POST" enctype="multipart/form-data">
                        <div class="mb-4">
                            <label for="csv_file" class="form-label">CSV File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                        </div>
                        <div class="d-flex align-items-center justify-content-end gap-3">
                            <a href="customers.php" class="btn btn-light border">Cancel</a>
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
