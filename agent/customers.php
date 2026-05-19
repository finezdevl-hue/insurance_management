<?php
/**
 * Customer Management (Agent CRUD Panel)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce Agent Access
checkAccess('agent');

$db = getDBConnection();
$agentId = $_SESSION['user_id'];

$pageTitle = 'Manage Customers Directory';
$activePage = 'customers';

$action = $_GET['action'] ?? 'list';
$error = '';

// --- PROCESS CONTROLLER ACTIONS ---

// A. Handle Delete Customer
if ($action === 'delete' && isset($_GET['id'])) {
    $custId = (int)$_GET['id'];
    try {
        // Double check ownership before delete
        $stmtC = $db->prepare("SELECT name FROM customers WHERE id = ? AND agent_id = ?");
        $stmtC->execute([$custId, $agentId]);
        $cust = $stmtC->fetchColumn();
        
        if ($cust) {
            $stmt = $db->prepare("DELETE FROM customers WHERE id = ? AND agent_id = ?");
            $stmt->execute([$custId, $agentId]);
            logActivity('Delete Customer', "Deleted customer: $cust (ID: $custId)");
            $_SESSION['alert_success'] = 'Customer deleted successfully!';
        } else {
            $_SESSION['alert_error'] = 'Customer not found or access denied.';
        }
    } catch (PDOException $e) {
        $_SESSION['alert_error'] = 'Cannot delete customer! They might have active vehicles in the database.';
    }
    redirect('customers.php');
}

// B. Handle Add / Edit Form POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $custId = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $name = trim($_POST['name']);
    $mobile = trim($_POST['mobile_number']);
    $whatsapp = trim($_POST['whatsapp_number']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    
    if (empty($name) || empty($mobile) || empty($whatsapp)) {
        $error = 'Customer Name, Mobile Number, and WhatsApp Number are required fields.';
    } else {
        try {
            // Handle ID Proof file upload
            $idProofPath = null;
            if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] === UPLOAD_ERR_OK) {
                $uploadedProof = handleFileUpload($_FILES['id_proof'], 'proofs', ['jpg', 'jpeg', 'png', 'pdf']);
                if ($uploadedProof) {
                    $idProofPath = $uploadedProof;
                }
            }
            
            if ($custId) {
                // --- UPDATE OPERATION ---
                // Validate ownership
                $stmtC = $db->prepare("SELECT id FROM customers WHERE id = ? AND agent_id = ?");
                $stmtC->execute([$custId, $agentId]);
                if (!$stmtC->fetch()) {
                    $_SESSION['alert_error'] = 'Unauthorized access.';
                    redirect('customers.php');
                }
                
                $query = "
                    UPDATE customers 
                    SET name = ?, mobile_number = ?, whatsapp_number = ?, email = ?, address = ?
                ";
                $params = [$name, $mobile, $whatsapp, $email, $address];
                
                if ($idProofPath) {
                    $query .= ", id_proof_path = ?";
                    $params[] = $idProofPath;
                }
                
                $query .= " WHERE id = ? AND agent_id = ?";
                $params[] = $custId;
                $params[] = $agentId;
                
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                
                logActivity('Update Customer', "Updated customer details: $name");
                $_SESSION['alert_success'] = 'Customer details updated successfully!';
                redirect('customers.php');
                
            } else {
                // --- CREATE OPERATION ---
                $stmt = $db->prepare("
                    INSERT INTO customers (agent_id, name, mobile_number, whatsapp_number, email, address, id_proof_path)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$agentId, $name, $mobile, $whatsapp, $email, $address, $idProofPath]);
                
                logActivity('Create Customer', "Created new customer: $name");
                $_SESSION['alert_success'] = 'New customer enrolled successfully!';
                redirect('customers.php');
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// --- CONTROLLER RENDERING ---

$pageHeading = 'Manage Customers';
include_once __DIR__ . '/../includes/header.php';
?>

<?php if ($action === 'list'): ?>
    <!-- LIST CUSTOMERS VIEW -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-users-viewfinder text-success fs-5"></i>
                <h5 class="m-0 font-weight-700">Customers Directory</h5>
            </div>
            <a href="customers.php?action=add" class="btn btn-success btn-sm">
                <i class="fa-solid fa-plus"></i> Add Customer
            </a>
        </div>
        <div class="card-body">
            
            <table class="table table-hover align-middle datatable w-100">
                <thead>
                    <tr>
                        <th>Customer Name</th>
                        <th>Contact Details</th>
                        <th>Address</th>
                        <th>Vehicles Enrolled</th>
                        <th>ID Proof</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch agent's customers and count how many vehicles they own
                    $stmt = $db->prepare("
                        SELECT c.*, COUNT(v.id) as vehicle_count 
                        FROM customers c 
                        LEFT JOIN vehicles v ON c.id = v.customer_id 
                        WHERE c.agent_id = ?
                        GROUP BY c.id 
                        ORDER BY c.id DESC
                    ");
                    $stmt->execute([$agentId]);
                    while ($cust = $stmt->fetch()):
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="user-avatar text-uppercase bg-light text-success font-weight-600 border">
                                        <?php echo substr(sanitize($cust['name']), 0, 2); ?>
                                    </div>
                                    <div>
                                        <h6 class="m-0 font-weight-600 text-main"><?php echo sanitize($cust['name']); ?></h6>
                                        <small class="text-muted">ID: #<?php echo $cust['id']; ?></small>
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
                                    <?php echo sanitize($cust['address'] ?: 'Not specified'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-success border px-2.5 py-1.5" style="border-radius: 20px;">
                                    <i class="fa-solid fa-car-side me-1"></i> <?php echo $cust['vehicle_count']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($cust['id_proof_path']): ?>
                                    <a href="../uploads/proofs/<?php echo $cust['id_proof_path']; ?>" target="_blank" class="btn btn-sm btn-light border text-success">
                                        <i class="fa-solid fa-file-pdf me-1"></i> View Doc
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">None</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-2">
                                    <a href="customers.php?action=edit&id=<?php echo $cust['id']; ?>" class="btn btn-sm btn-light border text-primary" title="Edit Customer">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </a>
                                    <a href="#" data-url="customers.php?action=delete&id=<?php echo $cust['id']; ?>" class="btn btn-sm btn-light border text-danger delete-btn" title="Delete Customer">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </a>
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
                    title: 'Are you sure?',
                    text: "You want to delete this customer? All their vehicle records and documents will be lost!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, delete customer!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = url;
                    }
                });
            });
        });
    </script>

<?php elseif ($action === 'add' || $action === 'edit'): 
    // ADD or EDIT LAYOUTS
    $cust = [];
    $isEdit = ($action === 'edit' && isset($_GET['id']));
    
    if ($isEdit) {
        $custId = (int)$_GET['id'];
        $stmt = $db->prepare("SELECT * FROM customers WHERE id = ? AND agent_id = ?");
        $stmt->execute([$custId, $agentId]);
        $cust = $stmt->fetch();
        if (!$cust) {
            $_SESSION['alert_error'] = 'Customer record not found.';
            redirect('customers.php');
        }
    }
?>

    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-user-gear text-success fs-5"></i>
                        <h5 class="m-0 font-weight-700"><?php echo $isEdit ? 'Edit Customer Info' : 'Enroll New Customer'; ?></h5>
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

                    <form action="" method="POST" enctype="multipart/form-data">
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="id" value="<?php echo $cust['id']; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : ($isEdit ? sanitize($cust['name']) : ''); ?>" placeholder="Enter customer full name">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12 col-sm-6">
                                <label for="mobile_number" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mobile_number" name="mobile_number" required value="<?php echo isset($_POST['mobile_number']) ? sanitize($_POST['mobile_number']) : ($isEdit ? sanitize($cust['mobile_number']) : ''); ?>" placeholder="Phone number">
                            </div>
                            
                            <div class="col-12 col-sm-6">
                                <label for="whatsapp_number" class="form-label">WhatsApp Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="whatsapp_number" name="whatsapp_number" required value="<?php echo isset($_POST['whatsapp_number']) ? sanitize($_POST['whatsapp_number']) : ($isEdit ? sanitize($cust['whatsapp_number']) : ''); ?>" placeholder="Include country code, e.g. 919876543210">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ($isEdit ? sanitize($cust['email']) : ''); ?>" placeholder="customer@gmail.com">
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" placeholder="Full residential physical address..."><?php echo isset($_POST['address']) ? sanitize($_POST['address']) : ($isEdit ? sanitize($cust['address']) : ''); ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="id_proof" class="form-label">Upload ID Proof <span class="text-muted small">(PDF, JPG, PNG, Max 5MB)</span></label>
                            <input type="file" class="form-control" id="id_proof" name="id_proof">
                            <?php if ($isEdit && $cust['id_proof_path']): ?>
                                <div class="mt-2 text-muted small">
                                    <i class="fa-solid fa-file-shield me-1"></i> Registered Proof: <a href="../uploads/proofs/<?php echo $cust['id_proof_path']; ?>" target="_blank" class="text-success"><?php echo sanitize($cust['id_proof_path']); ?></a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <hr class="my-4">
                        
                        <div class="d-flex align-items-center justify-content-end gap-3">
                            <a href="customers.php" class="btn btn-light border">Cancel</a>
                            <button type="submit" class="btn btn-success px-4">
                                <i class="fa-solid fa-floppy-disk me-1"></i> <?php echo $isEdit ? 'Update Customer' : 'Enroll Customer'; ?>
                            </button>
                        </div>
                    </form>
                    
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
