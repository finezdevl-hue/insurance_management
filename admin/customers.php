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

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-users text-primary fs-5"></i>
            <h5 class="m-0 font-weight-700">All Registered Customers</h5>
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

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
