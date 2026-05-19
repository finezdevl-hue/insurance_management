<?php
/**
 * Vehicle Type Management (Super Admin CRUD)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();

$pageTitle = 'Manage Vehicle Types';
$pageHeading = 'Vehicle Types';
$activePage = 'types';

$error = '';
$action = $_GET['action'] ?? 'list';

// --- PROCESS CONTROLLER ACTIONS ---

// A. Add Type
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $status = $_POST['status'] ?? 'active';
    
    if (empty($name)) {
        $_SESSION['alert_error'] = 'Vehicle type name is required.';
    } else {
        try {
            // Check unique
            $stmtUnique = $db->prepare("SELECT COUNT(*) FROM vehicle_types WHERE name = ?");
            $stmtUnique->execute([$name]);
            if ($stmtUnique->fetchColumn() > 0) {
                $_SESSION['alert_error'] = 'Vehicle type category already exists.';
            } else {
                $stmt = $db->prepare("INSERT INTO vehicle_types (name, status) VALUES (?, ?)");
                $stmt->execute([$name, $status]);
                logActivity('Add Vehicle Type', "Created new vehicle classification: $name");
                $_SESSION['alert_success'] = 'Vehicle classification added successfully!';
            }
        } catch (PDOException $e) {
            $_SESSION['alert_error'] = 'Database error: ' . $e->getMessage();
        }
    }
    redirect('types.php');
}

// B. Edit Type
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $status = $_POST['status'] ?? 'active';
    
    if (empty($name)) {
        $_SESSION['alert_error'] = 'Vehicle type name is required.';
    } else {
        try {
            // Check unique excluding itself
            $stmtUnique = $db->prepare("SELECT COUNT(*) FROM vehicle_types WHERE name = ? AND id != ?");
            $stmtUnique->execute([$name, $id]);
            if ($stmtUnique->fetchColumn() > 0) {
                $_SESSION['alert_error'] = 'Vehicle type classification name already exists.';
            } else {
                $stmt = $db->prepare("UPDATE vehicle_types SET name = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $status, $id]);
                logActivity('Edit Vehicle Type', "Updated vehicle type ID $id to $name ($status)");
                $_SESSION['alert_success'] = 'Vehicle classification updated successfully!';
            }
        } catch (PDOException $e) {
            $_SESSION['alert_error'] = 'Database error: ' . $e->getMessage();
        }
    }
    redirect('types.php');
}

// C. Delete Type
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        // Check if there are vehicles referencing this type
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM vehicles WHERE vehicle_type_id = ?");
        $stmtCheck->execute([$id]);
        $referenced = $stmtCheck->fetchColumn();
        
        if ($referenced > 0) {
            $_SESSION['alert_error'] = 'Cannot delete! There are vehicles registered under this classification category.';
        } else {
            // Fetch name for log
            $stmtName = $db->prepare("SELECT name FROM vehicle_types WHERE id = ?");
            $stmtName->execute([$id]);
            $typeName = $stmtName->fetchColumn();
            
            $stmt = $db->prepare("DELETE FROM vehicle_types WHERE id = ?");
            $stmt->execute([$id]);
            logActivity('Delete Vehicle Type', "Deleted vehicle classification: $typeName");
            $_SESSION['alert_success'] = 'Vehicle classification deleted successfully!';
        }
    } catch (PDOException $e) {
        $_SESSION['alert_error'] = 'Database error: ' . $e->getMessage();
    }
    redirect('types.php');
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <!-- List Column -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-truck-pickup text-primary fs-5"></i>
                    <h5 class="m-0 font-weight-700">Vehicle Classifications</h5>
                </div>
            </div>
            <div class="card-body">
                
                <table class="table table-hover align-middle datatable w-100">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Classification Category</th>
                            <th>Status</th>
                            <th class="text-end" style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $db->query("SELECT * FROM vehicle_types ORDER BY name ASC");
                        while ($type = $stmt->fetch()):
                            $statusClass = ($type['status'] === 'active') ? 'badge-active' : 'badge-suspended';
                        ?>
                            <tr>
                                <td><?php echo $type['id']; ?></td>
                                <td><strong class="text-main"><?php echo sanitize($type['name']); ?></strong></td>
                                <td>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo strtoupper(sanitize($type['status'])); ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light border edit-btn" 
                                            data-id="<?php echo $type['id']; ?>" 
                                            data-name="<?php echo sanitize($type['name']); ?>" 
                                            data-status="<?php echo $type['status']; ?>">
                                        <i class="fa-regular fa-pen-to-square"></i>
                                    </button>
                                    <a class="btn btn-sm btn-light border text-danger delete-btn" 
                                       href="#" 
                                       data-url="types.php?action=delete&id=<?php echo $type['id']; ?>">
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
                <h5 class="m-0 font-weight-700" id="form-title"><i class="fa-solid fa-plus text-primary me-2"></i>Add Vehicle Type</h5>
            </div>
            <div class="card-body">
                
                <form action="types.php?action=add" method="POST" id="main-form">
                    <input type="hidden" name="id" id="type-id" value="">
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Classification Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="type-name" name="name" required placeholder="e.g. Heavy Goods Vehicle">
                    </div>
                    
                    <div class="mb-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="type-status" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Type
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
    // Edit Click Handlers
    $('.edit-btn').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const status = $(this).data('status');
        
        // Transform form to Edit state
        $('#form-title').html('<i class="fa-regular fa-pen-to-square text-primary me-2"></i>Edit Vehicle Type');
        $('#main-form').attr('action', 'types.php?action=edit');
        $('#type-id').val(id);
        $('#type-name').val(name).focus();
        $('#type-status').val(status);
        $('#submit-btn').html('<i class="fa-solid fa-floppy-disk me-1"></i> Update Type');
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
        $('#form-title').html('<i class="fa-solid fa-plus text-primary me-2"></i>Add Vehicle Type');
        $('#main-form').attr('action', 'types.php?action=add');
        $('#type-id').val('');
        $('#type-name').val('');
        $('#type-status').val('active');
        $('#submit-btn').html('<i class="fa-solid fa-floppy-disk me-1"></i> Save Type');
        $(this).addClass('d-none');
    });

    // Delete carrier prompt
    $('.delete-btn').on('click', function(e) {
        e.preventDefault();
        const url = $(this).data('url');
        Swal.fire({
            title: 'Are you sure?',
            text: "You want to delete this vehicle classification? This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete classification'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
