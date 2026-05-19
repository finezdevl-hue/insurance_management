<?php
/**
 * Master Vehicle List (Super Admin View)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();

$pageTitle = 'Master Vehicle Registry';
$pageHeading = 'Vehicles Registry';
$activePage = 'vehicles';

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-car-side text-primary fs-5"></i>
            <h5 class="m-0 font-weight-700">All Registered Vehicles</h5>
        </div>
    </div>
    <div class="card-body">
        
        <table class="table table-hover align-middle datatable w-100">
            <thead>
                <tr>
                    <th>Vehicle Details</th>
                    <th>Owner / Agent</th>
                    <th>RC Expiry</th>
                    <th>Insurance Details</th>
                    <th>Pollution Certificate</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Query all vehicles with their owner, agent, vehicle type, most recent insurance and pollution
                $stmt = $db->query("
                    SELECT 
                        v.*, 
                        vt.name as type_name,
                        c.name as customer_name,
                        c.mobile_number as customer_mobile,
                        u.shop_name,
                        i.policy_number, i.expiry_date as ins_expiry, i.document_path as ins_doc, ic.name as company_name,
                        p.certificate_number, p.expiry_date as puc_expiry, p.document_path as puc_doc
                    FROM vehicles v
                    JOIN vehicle_types vt ON v.vehicle_type_id = vt.id
                    JOIN customers c ON v.customer_id = c.id
                    JOIN users u ON v.agent_id = u.id
                    LEFT JOIN insurances i ON i.id = (
                        SELECT id FROM insurances 
                        WHERE vehicle_id = v.id 
                        ORDER BY expiry_date DESC LIMIT 1
                    )
                    LEFT JOIN insurance_companies ic ON i.insurance_company_id = ic.id
                    LEFT JOIN pollution_certificates p ON p.id = (
                        SELECT id FROM pollution_certificates 
                        WHERE vehicle_id = v.id 
                        ORDER BY expiry_date DESC LIMIT 1
                    )
                    ORDER BY v.id DESC
                ");
                
                while ($vh = $stmt->fetch()):
                    // Expiry calculations
                    $rcStatus = getExpiryStatus($vh['rc_expiry_date']);
                    $insStatus = $vh['ins_expiry'] ? getExpiryStatus($vh['ins_expiry']) : null;
                    $pucStatus = $vh['puc_expiry'] ? getExpiryStatus($vh['puc_expiry']) : null;
                    
                    // Vehicle thumbnail image
                    $imagePath = '../uploads/vehicles/' . $vh['image_path'];
                    if (empty($vh['image_path']) || !file_exists($imagePath)) {
                        $imageSrc = '../assets/css/placeholder_car.png'; // Handled via CSS or custom fallback icon
                    } else {
                        $imageSrc = $imagePath;
                    }
                ?>
                    <tr>
                        <!-- Vehicle Brand, Number & Model -->
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <?php if (!empty($vh['image_path'])): ?>
                                    <img src="../uploads/vehicles/<?php echo $vh['image_path']; ?>" alt="Vehicle" class="img-thumbnail" style="width: 60px; height: 45px; object-fit: cover; border-radius: var(--radius-sm);">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center text-muted" style="width: 60px; height: 45px; border-radius: var(--radius-sm); border: 1px dashed var(--border-color);">
                                        <i class="fa-solid fa-car fs-5"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h6 class="m-0 font-weight-700 text-primary"><?php echo sanitize($vh['vehicle_number']); ?></h6>
                                    <span class="d-block small text-main font-weight-500"><?php echo sanitize($vh['brand'] . ' ' . $vh['model']); ?></span>
                                    <span class="text-muted small" style="font-size: 0.75rem;">
                                        Type: <?php echo sanitize($vh['type_name']); ?> | Fuel: <?php echo sanitize($vh['fuel_type']); ?>
                                    </span>
                                </div>
                            </div>
                        </td>
                        <!-- Owner & Agent -->
                        <td>
                            <p class="m-0 small text-main font-weight-600">Owner: <?php echo sanitize($vh['customer_name']); ?></p>
                            <small class="text-muted d-block" style="font-size: 0.75rem;">Tel: <?php echo sanitize($vh['customer_mobile']); ?></small>
                            <span class="badge bg-light text-muted border mt-1" style="font-size: 0.7rem;">
                                Agent: <?php echo sanitize($vh['shop_name']); ?>
                            </span>
                        </td>
                        <!-- RC Expiry -->
                        <td>
                            <span class="badge <?php echo $rcStatus['badge']; ?> mb-1">
                                <?php echo sanitize($rcStatus['text']); ?>
                            </span>
                            <small class="d-block text-muted" style="font-size: 0.75rem;">
                                Date: <?php echo date('d-M-Y', strtotime($vh['rc_expiry_date'])); ?>
                            </small>
                        </td>
                        <!-- Insurance Details -->
                        <td>
                            <?php if ($vh['policy_number']): ?>
                                <span class="d-block small text-main font-weight-600"><?php echo sanitize($vh['company_name']); ?></span>
                                <span class="d-block small text-muted" style="font-size: 0.75rem;">Pol: <?php echo sanitize($vh['policy_number']); ?></span>
                                <span class="badge <?php echo $insStatus['badge']; ?> mt-1">
                                    <?php echo sanitize($insStatus['text']); ?>
                                </span>
                                <?php if ($vh['ins_doc']): ?>
                                    <a href="../uploads/insurances/<?php echo $vh['ins_doc']; ?>" target="_blank" class="ms-2 small text-primary text-decoration-none">
                                        <i class="fa-solid fa-file-pdf"></i> PDF
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary">No Policy Found</span>
                            <?php endif; ?>
                        </td>
                        <!-- Pollution Certificate -->
                        <td>
                            <?php if ($vh['certificate_number']): ?>
                                <span class="d-block small text-muted" style="font-size: 0.75rem;">PUC: <?php echo sanitize($vh['certificate_number']); ?></span>
                                <span class="badge <?php echo $pucStatus['badge']; ?> mt-1">
                                    <?php echo sanitize($pucStatus['text']); ?>
                                </span>
                                <?php if ($vh['puc_doc']): ?>
                                    <a href="../uploads/pollution/<?php echo $vh['puc_doc']; ?>" target="_blank" class="ms-2 small text-primary text-decoration-none">
                                        <i class="fa-solid fa-file-pdf"></i> PDF
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-secondary">No PUC Found</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
