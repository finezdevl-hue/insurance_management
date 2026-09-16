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

$action = $_GET['action'] ?? 'list';



if ($action === 'export_csv_pollution') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=all_pollution_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['agent_username', 'customer_name', 'customer_mobile', 'vehicle_number', 'certificate_number', 'start_date', 'expiry_date'], ',', '"', '\\');
    
    $stmt = $db->query("
        SELECT u.username as agent_username, c.name as customer_name, c.mobile_number, v.vehicle_number, p.certificate_number, p.start_date, p.expiry_date
        FROM pollution_certificates p
        JOIN vehicles v ON p.vehicle_id = v.id
        JOIN customers c ON v.customer_id = c.id
        JOIN users u ON p.agent_id = u.id
        ORDER BY u.username, v.vehicle_number
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row, ',', '"', '\\');
    }
    fclose($output);
    exit;
}

if ($action === 'export_csv_insurance') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=all_insurances_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['agent_username', 'customer_name', 'customer_mobile', 'vehicle_number', 'insurance_company', 'policy_number', 'insurance_type', 'start_date', 'expiry_date', 'premium_amount'], ',', '"', '\\');
    
    $stmt = $db->query("
        SELECT u.username as agent_username, c.name as customer_name, c.mobile_number, v.vehicle_number, ic.name as company, i.policy_number, i.insurance_type, i.start_date, i.expiry_date, i.premium_amount
        FROM insurances i
        JOIN vehicles v ON i.vehicle_id = v.id
        JOIN customers c ON v.customer_id = c.id
        JOIN insurance_companies ic ON i.insurance_company_id = ic.id
        JOIN users u ON i.agent_id = u.id
        ORDER BY u.username, v.vehicle_number
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row, ',', '"', '\\');
    }
    fclose($output);
    exit;
}

include_once __DIR__ . '/../includes/header.php';
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-car-side text-primary fs-5"></i>
            <h5 class="m-0 font-weight-700">All Registered Vehicles</h5>
        </div>
        <div class="d-flex gap-2">

            <a href="vehicles.php?action=export_csv_pollution" class="btn btn-light border btn-sm text-info">
                <i class="fa-solid fa-download me-1"></i> Export Pollution
            </a>
            <a href="vehicles.php?action=export_csv_insurance" class="btn btn-light border btn-sm text-warning">
                <i class="fa-solid fa-download me-1"></i> Export Insurances
            </a>
        </div>
    </div>
    <div class="card-body">
        
        <table class="table table-hover align-middle datatable w-100">
            <thead>
                <tr>
                    <th>Vehicle Registration Number</th>
                    <th>Owner / Agent Partner</th>
                    <th>Insurance Details</th>
                    <th>Pollution Certificate</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Query all vehicles with their owner, agent, most recent insurance and pollution
                $stmt = $db->query("
                    SELECT 
                        v.id,
                        v.vehicle_number,
                        v.created_at,
                        c.name as customer_name,
                        c.mobile_number as customer_mobile,
                        u.shop_name,
                        i.policy_number, i.expiry_date as ins_expiry, i.document_path as ins_doc, ic.name as company_name,
                        p.certificate_number, p.expiry_date as puc_expiry, p.document_path as puc_doc
                    FROM vehicles v
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
                    $insStatus = $vh['ins_expiry'] ? getExpiryStatus($vh['ins_expiry']) : null;
                    $pucStatus = $vh['puc_expiry'] ? getExpiryStatus($vh['puc_expiry']) : null;
                ?>
                    <tr>
                        <!-- Vehicle Number -->
                        <td>
                            <h6 class="m-0 font-weight-700 text-primary fs-6"><?php echo sanitize($vh['vehicle_number']); ?></h6>
                        </td>
                        <!-- Owner & Agent -->
                        <td>
                            <p class="m-0 small text-main font-weight-600">Owner: <?php echo sanitize($vh['customer_name']); ?></p>
                            <small class="text-muted d-block" style="font-size: 0.75rem;">Tel: <?php echo sanitize($vh['customer_mobile']); ?></small>
                            <span class="badge bg-light text-muted border mt-1" style="font-size: 0.7rem;">
                                Agent: <?php echo sanitize($vh['shop_name']); ?>
                            </span>
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
