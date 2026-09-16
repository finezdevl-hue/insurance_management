<?php
/**
 * Insurance Company Management (Super Admin CRUD)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();

$pageTitle = 'Manage Insurance Companies';
$pageHeading = 'Insurance Companies';
$activePage = 'companies';

$error = '';
$action = $_GET['action'] ?? 'list';

// --- PROCESS CONTROLLER ACTIONS ---

// A. Add Company
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $status = $_POST['status'] ?? 'active';
    
    if (empty($name)) {
        $_SESSION['alert_error'] = 'Company name is required.';
    } else {
        try {
            // Check unique
            $stmtUnique = $db->prepare("SELECT COUNT(*) FROM insurance_companies WHERE name = ?");
            $stmtUnique->execute([$name]);
            if ($stmtUnique->fetchColumn() > 0) {
                $_SESSION['alert_error'] = 'Insurance company already exists.';
            } else {
                $stmt = $db->prepare("INSERT INTO insurance_companies (name, status) VALUES (?, ?)");
                $stmt->execute([$name, $status]);
                logActivity('Add Company', "Created new insurance carrier: $name");
                $_SESSION['alert_success'] = 'Insurance company added successfully!';
            }
        } catch (PDOException $e) {
            $_SESSION['alert_error'] = 'Database error: ' . $e->getMessage();
        }
    }
    redirect('companies.php');
}

// B. Edit Company
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $status = $_POST['status'] ?? 'active';
    
    if (empty($name)) {
        $_SESSION['alert_error'] = 'Company name is required.';
    } else {
        try {
            // Check unique excluding itself
            $stmtUnique = $db->prepare("SELECT COUNT(*) FROM insurance_companies WHERE name = ? AND id != ?");
            $stmtUnique->execute([$name, $id]);
            if ($stmtUnique->fetchColumn() > 0) {
                $_SESSION['alert_error'] = 'Insurance company name already exists.';
            } else {
                $stmt = $db->prepare("UPDATE insurance_companies SET name = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $status, $id]);
                logActivity('Edit Company', "Updated carrier ID $id to $name ($status)");
                $_SESSION['alert_success'] = 'Insurance company updated successfully!';
            }
        } catch (PDOException $e) {
            $_SESSION['alert_error'] = 'Database error: ' . $e->getMessage();
        }
    }
    redirect('companies.php');
}

// C. Delete Company
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        // Check if there are active policies referencing this company
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM insurances WHERE insurance_company_id = ?");
        $stmtCheck->execute([$id]);
        $referenced = $stmtCheck->fetchColumn();
        
        if ($referenced > 0) {
            $_SESSION['alert_error'] = 'Cannot delete! This company has active vehicle policies associated with it.';
        } else {
            // Fetch name for log
            $stmtName = $db->prepare("SELECT name FROM insurance_companies WHERE id = ?");
            $stmtName->execute([$id]);
            $compName = $stmtName->fetchColumn();
            
            $stmt = $db->prepare("DELETE FROM insurance_companies WHERE id = ?");
            $stmt->execute([$id]);
            logActivity('Delete Company', "Deleted insurance carrier: $compName");
            $_SESSION['alert_success'] = 'Insurance company deleted successfully!';
        }
    } catch (PDOException $e) {
        $_SESSION['alert_error'] = 'Database error: ' . $e->getMessage();
    }
    redirect('companies.php');
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <!-- List Column -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-building-shield text-primary fs-5"></i>
                    <h5 class="m-0 font-weight-700">Insurance Companies</h5>
                </div>
            </div>
            <div class="card-body">
                
                <table class="table table-hover align-middle datatable w-100">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Company Name</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $db->query("SELECT * FROM insurance_companies ORDER BY name ASC");
                        while ($comp = $stmt->fetch()):
                            $statusClass = ($comp['status'] === 'active') ? 'badge-active' : 'badge-suspended';
                        ?>
                            <tr>
                                <td><?php echo $comp['id']; ?></td>
                                <td><strong class="text-main"><?php echo sanitize($comp['name']); ?></strong></td>
                                <td>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo strtoupper(sanitize($comp['status'])); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border edit-btn" 
                                            data-id="<?php echo $comp['id']; ?>" 
                                            data-name="<?php echo sanitize($comp['name']); ?>" 
                                            data-status="<?php echo $comp['status']; ?>">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <a class="btn btn-sm btn-light border text-danger delete-btn" 
                                       href="#" 
                                       data-url="companies.php?action=delete&id=<?php echo $comp['id']; ?>">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
            </div>
        </div>
    </div>
    
    <!-- Form Add/Edit Column -->
    <div class="col-12 col-lg-4 mt-4 mt-lg-0">
        <div class="card shadow-sm border-0" id="form-card">
            <div class="card-header bg-white py-3">
                <h5 class="m-0 font-weight-700" id="form-title"><i class="fa-solid fa-plus text-primary me-2"></i>Add Company</h5>
            </div>
            <div class="card-body">
                
                <form action="companies.php?action=add" method="POST" id="main-form">
                    <input type="hidden" name="id" id="comp-id" value="">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="comp-name" name="name" required placeholder="e.g. HDFC ERGO">
                    </div>
                    
                    <div class="mb-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="comp-status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Carrier
                        </button>
                        <button type="button" class="btn btn-light border d-none" id="cancel-edit-btn">
                            Cancel Edit
                        </button>
                    </div>
                </form>
                
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Edit Carrier Click Handlers
    $('.edit-btn').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const status = $(this).data('status');
        
        // Transform form to Edit state
        $('#form-title').html('<i class="fa-regular fa-pen-to-square text-primary me-2"></i>Edit Company');
        $('#main-form').attr('action', 'companies.php?action=edit');
        $('#comp-id').val(id);
        $('#comp-name').val(name).focus();
        $('#comp-status').val(status);
        $('#submit-btn').html('<i class="fa-solid fa-floppy-disk me-1"></i> Update Carrier');
        $('#cancel-edit-btn').removeClass('d-none');
        
        // Scroll to form on mobile devices
        if($(window).width() < 992) {
            $('html, body').animate({
                scrollTop: $("#form-card").offset().top - 100
            }, 300);
        }
    });
    
    // Cancel Edit Handler
    $('#cancel-edit-btn').on('click', function() {
        // Reset form to Add state
        $('#form-title').html('<i class="fa-solid fa-plus text-primary me-2"></i>Add Company');
        $('#main-form').attr('action', 'companies.php?action=add');
        $('#comp-id').val('');
        $('#comp-name').val('');
        $('#comp-status').val('active');
        $('#submit-btn').html('<i class="fa-solid fa-floppy-disk me-1"></i> Save Carrier');
        $(this).addClass('d-none');
    });

    // Delete carrier prompt
    $('.delete-btn').on('click', function(e) {
        e.preventDefault();
        const url = $(this).data('url');
        Swal.fire({
            title: 'Are you sure?',
            text: "You want to remove this insurance carrier? This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete carrier'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loader-wrapper').fadeIn(200);
                        window.location.href = url;
            }
        });
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
