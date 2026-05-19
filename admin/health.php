<?php
/**
 * Master Health Insurance List (Super Admin View)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();

$pageTitle = 'Master Health Insurance Registry';
$pageHeading = 'Health Insurance Master Registry';
$activePage = 'health';

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card shadow-sm border-0 animate-fade-in">
    <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-heart-pulse text-success fs-5"></i>
            <h5 class="m-0 font-weight-700">All Registered Health Insurance Policies</h5>
        </div>
    </div>
    <div class="card-body">
        
        <table class="table table-hover align-middle datatable w-100">
            <thead>
                <tr>
                    <th>Policy Information</th>
                    <th>Insured Customer</th>
                    <th>Agent Partner</th>
                    <th>Company / Product</th>
                    <th>Premium</th>
                    <th>Dates / Duration</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Query all health insurances with owner, agent, company name
                $stmt = $db->query("
                    SELECT 
                        h.*, 
                        c.name as customer_name,
                        c.mobile_number as customer_mobile,
                        u.shop_name,
                        ic.name as company_name
                    FROM health_insurances h
                    JOIN customers c ON h.customer_id = c.id
                    JOIN users u ON h.agent_id = u.id
                    JOIN insurance_companies ic ON h.insurance_company_id = ic.id
                    ORDER BY h.id DESC
                ");
                
                while ($row = $stmt->fetch()):
                    $expStatus = getExpiryStatus($row['expiry_date']);
                ?>
                    <tr>
                        <!-- Policy Number & Scheme Name -->
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="stat-icon-wrapper bg-success-light text-success m-0 p-2 rounded-circle" style="width:36px; height:36px; font-size: 0.9rem;">
                                    <i class="fa-solid fa-file-medical"></i>
                                </div>
                                <div>
                                    <h6 class="m-0 font-weight-700 text-main"><?php echo sanitize($row['policy_number']); ?></h6>
                                    <span class="d-block small text-muted"><?php echo sanitize($row['policy_name']); ?></span>
                                </div>
                            </div>
                        </td>
                        <!-- Insured Customer & Contact -->
                        <td>
                            <p class="m-0 small text-main font-weight-600"><?php echo sanitize($row['customer_name']); ?></p>
                            <small class="text-muted d-block" style="font-size: 0.75rem;"><i class="fa-solid fa-phone me-1"></i> <?php echo sanitize($row['customer_mobile']); ?></small>
                            <small class="text-muted d-block" style="font-size: 0.75rem;"><i class="fa-solid fa-users me-1 text-muted"></i> Member(s): <?php echo sanitize($row['insured_persons']); ?></small>
                        </td>
                        <!-- Agent shop name -->
                        <td>
                            <span class="badge bg-light text-muted border" style="font-size: 0.72rem;">
                                <i class="fa-solid fa-store me-1"></i> <?php echo sanitize($row['shop_name']); ?>
                            </span>
                        </td>
                        <!-- Company -->
                        <td>
                            <span class="badge bg-light text-dark border font-weight-500"><?php echo sanitize($row['company_name']); ?></span>
                        </td>
                        <!-- Premium Amount -->
                        <td>
                            <span class="font-weight-600 text-main">₹<?php echo number_format($row['premium_amount'], 2); ?></span>
                        </td>
                        <!-- Dates & View Doc -->
                        <td>
                            <span class="d-block small font-weight-500 text-main">Expires: <?php echo date('d-M-Y', strtotime($row['expiry_date'])); ?></span>
                            <small class="text-muted d-block" style="font-size: 0.72rem;">Starts: <?php echo date('d-M-Y', strtotime($row['start_date'])); ?></small>
                            <?php if ($row['document_path']): ?>
                                <a href="../uploads/health/<?php echo $row['document_path']; ?>" target="_blank" class="small text-success text-decoration-none mt-1 d-inline-block font-weight-600">
                                    <i class="fa-solid fa-file-pdf me-1"></i> View Policy Doc
                                </a>
                            <?php endif; ?>
                        </td>
                        <!-- Status Badge -->
                        <td>
                            <span class="badge <?php echo $expStatus['badge']; ?>">
                                <?php echo sanitize($expStatus['text']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
