<?php
/**
 * Master Reports & Business Intelligence Analytics (Super Admin View)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();

$pageTitle = 'Master Expiry & Renewal Reports';
$pageHeading = 'Expiry & Analytics Reports';
$activePage = 'reports';

$reportType = $_GET['report_type'] ?? 'insurance';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d');
$dateTo = $_GET['date_to'] ?? date('Y-m-d', strtotime('+30 days'));
$selectedAgent = isset($_GET['agent_id']) ? (int)$_GET['agent_id'] : 'all';
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Fetch agents list for filter dropdown
$agentsList = $db->query("SELECT id, shop_name, username FROM users WHERE role = 'agent' ORDER BY shop_name ASC")->fetchAll();

// --- QUERY DATA BASED ON REPORT TYPE ---
$reportData = [];

if ($reportType === 'insurance') {
    // Insurance Expiry Query
    $query = "
        SELECT i.expiry_date, i.policy_number, i.premium_amount, ic.name as company_name,
               v.vehicle_number, 'N/A' as brand, '' as model,
               c.name as customer_name, c.mobile_number,
               u.shop_name
        FROM insurances i
        JOIN vehicles v ON i.vehicle_id = v.id
        JOIN customers c ON v.customer_id = c.id
        JOIN users u ON v.agent_id = u.id
        JOIN insurance_companies ic ON i.insurance_company_id = ic.id
        WHERE i.expiry_date BETWEEN ? AND ?
    ";
    $params = [$dateFrom, $dateTo];
    
    if ($selectedAgent !== 'all') {
        $query .= " AND v.agent_id = ?";
        $params[] = $selectedAgent;
    }
    
    $query .= " ORDER BY i.expiry_date ASC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $reportData = $stmt->fetchAll();
    
} elseif ($reportType === 'pollution') {
    // Pollution Expiry Query
    $query = "
        SELECT p.expiry_date, p.certificate_number,
               v.vehicle_number, 'N/A' as brand, '' as model,
               c.name as customer_name, c.mobile_number,
               u.shop_name
        FROM pollution_certificates p
        JOIN vehicles v ON p.vehicle_id = v.id
        JOIN customers c ON v.customer_id = c.id
        JOIN users u ON v.agent_id = u.id
        WHERE p.expiry_date BETWEEN ? AND ?
    ";
    $params = [$dateFrom, $dateTo];
    
    if ($selectedAgent !== 'all') {
        $query .= " AND v.agent_id = ?";
        $params[] = $selectedAgent;
    }
    
    $query .= " ORDER BY p.expiry_date ASC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $reportData = $stmt->fetchAll();
    
} elseif ($reportType === 'agent_performance') {
    // Agent Business Performance Aggregates
    $stmt = $db->query("
        SELECT 
            u.id, u.shop_name, u.shop_owner_name, u.city, u.mobile_number,
            COUNT(DISTINCT c.id) as customer_count,
            COUNT(DISTINCT v.id) as vehicle_count,
            (SELECT COUNT(*) FROM reminder_history WHERE sent_by_user_id = u.id) as reminders_sent
        FROM users u
        LEFT JOIN customers c ON c.agent_id = u.id
        LEFT JOIN vehicles v ON v.agent_id = u.id
        WHERE u.role = 'agent'
        GROUP BY u.id
        ORDER BY vehicle_count DESC
    ");
    $reportData = $stmt->fetchAll();
    
} elseif ($reportType === 'monthly_renewal') {
    // Monthly renewals summary for selected calendar year
    // Query monthly count of insurance expiries
    $insStmt = $db->prepare("
        SELECT MONTH(expiry_date) as month, COUNT(*) as count, SUM(premium_amount) as total_premium
        FROM insurances 
        WHERE YEAR(expiry_date) = ?
        GROUP BY MONTH(expiry_date)
    ");
    $insStmt->execute([$selectedYear]);
    $monthlyInsData = array_fill(1, 12, ['count' => 0, 'premium' => 0.00]);
    while ($row = $insStmt->fetch()) {
        $monthlyInsData[(int)$row['month']] = [
            'count' => (int)$row['count'],
            'premium' => (float)$row['total_premium']
        ];
    }
    
    // Query monthly count of pollution expiries
    $pucStmt = $db->prepare("
        SELECT MONTH(expiry_date) as month, COUNT(*) as count 
        FROM pollution_certificates 
        WHERE YEAR(expiry_date) = ?
        GROUP BY MONTH(expiry_date)
    ");
    $pucStmt->execute([$selectedYear]);
    $monthlyPucData = array_fill(1, 12, 0);
    while ($row = $pucStmt->fetch()) {
        $monthlyPucData[(int)$row['month']] = (int)$row['count'];
    }
    
    // Merge into reportData array
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    
    foreach ($months as $num => $name) {
        $reportData[] = [
            'month_num' => $num,
            'month_name' => $name,
            'insurance_count' => $monthlyInsData[$num]['count'],
            'total_premium' => $monthlyInsData[$num]['premium'],
            'pollution_count' => $monthlyPucData[$num]
        ];
    }
} elseif ($reportType === 'message_wallet') {
    $query = "
        SELECT
            daily.activity_date,
            u.shop_name,
            u.username,
            SUM(daily.total_recharge_amount) AS total_recharge_amount,
            SUM(daily.total_messages_credited) AS total_messages_credited,
            SUM(daily.messages_sent) AS messages_sent,
            SUM(daily.messages_failed) AS messages_failed,
            u.message_balance
        FROM (
            SELECT
                agent_id,
                DATE(created_at) AS activity_date,
                SUM(recharge_amount) AS total_recharge_amount,
                SUM(messages_credited) AS total_messages_credited,
                0 AS messages_sent,
                0 AS messages_failed
            FROM agent_message_recharges
            WHERE created_at BETWEEN ? AND ?
            GROUP BY agent_id, DATE(created_at)

            UNION ALL

            SELECT
                sent_by_user_id AS agent_id,
                DATE(sent_date) AS activity_date,
                0 AS total_recharge_amount,
                0 AS total_messages_credited,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS messages_sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS messages_failed
            FROM reminder_history
            WHERE sent_date BETWEEN ? AND ?
            GROUP BY sent_by_user_id, DATE(sent_date)
        ) daily
        INNER JOIN users u ON u.id = daily.agent_id
        WHERE u.role = 'agent'
    ";
    $params = [
        $dateFrom . ' 00:00:00', $dateTo . ' 23:59:59',
        $dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'
    ];

    if ($selectedAgent !== 'all') {
        $query .= " AND u.id = ?";
        $params[] = $selectedAgent;
    }

    $query .= "
        GROUP BY daily.activity_date, u.id, u.shop_name, u.username, u.message_balance
        ORDER BY daily.activity_date DESC, u.shop_name ASC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $reportData = $stmt->fetchAll();
}

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Print Styles (Premium layout, hides sidebar and top banner) -->
<style>
@media print {
    #sidebar, #header-nav, .filter-card, .btn, .dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate {
        display: none !important;
    }
    #main-content {
        margin-left: 0 !important;
        padding-top: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .table th {
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
    }
    .print-header {
        display: block !important;
        margin-bottom: 20px;
    }
}
.print-header {
    display: none;
}
</style>

<!-- Filter Console -->
<div class="card filter-card shadow-sm border-0 mb-3">
    <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-filter text-primary fs-5"></i>
            <h5 class="m-0 font-weight-700">Filter Expiry Reports</h5>
        </div>
    </div>
    <div class="card-body">
        
        <form action="" method="GET" class="row g-3">
            <div class="col-12 col-md-3">
                <label for="report_type" class="form-label font-weight-600">Select Report Type</label>
                <select class="form-select" id="report_type" name="report_type" onchange="this.form.submit()">
                    <option value="insurance" <?php echo $reportType === 'insurance' ? 'selected' : ''; ?>>Insurance Expiry Report</option>
                    <option value="pollution" <?php echo $reportType === 'pollution' ? 'selected' : ''; ?>>Pollution Certificate Expiry</option>
                    <option value="agent_performance" <?php echo $reportType === 'agent_performance' ? 'selected' : ''; ?>>Agent Performance Report</option>
                    <option value="monthly_renewal" <?php echo $reportType === 'monthly_renewal' ? 'selected' : ''; ?>>Monthly Renewal Forecast</option>
                    <option value="message_wallet" <?php echo $reportType === 'message_wallet' ? 'selected' : ''; ?>>Message Wallet Report</option>
                </select>
            </div>
            
            <?php if ($reportType === 'insurance' || $reportType === 'pollution' || $reportType === 'message_wallet'): ?>
                <div class="col-12 col-sm-6 col-md-2">
                    <label for="date_from" class="form-label font-weight-600">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo sanitize($dateFrom); ?>">
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label for="date_to" class="form-label font-weight-600">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo sanitize($dateTo); ?>">
                </div>
                <div class="col-12 col-md-3">
                    <label for="agent_id" class="form-label font-weight-600">Filter By Agent</label>
                    <select class="form-select" id="agent_id" name="agent_id">
                        <option value="all" <?php echo $selectedAgent === 'all' ? 'selected' : ''; ?>>All Agents (Master List)</option>
                        <?php foreach ($agentsList as $ag): ?>
                            <option value="<?php echo $ag['id']; ?>" <?php echo $selectedAgent === $ag['id'] ? 'selected' : ''; ?>>
                                <?php echo sanitize($ag['shop_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php elseif ($reportType === 'monthly_renewal'): ?>
                <div class="col-12 col-sm-6 col-md-2">
                    <label for="year" class="form-label font-weight-600">Calendar Year</label>
                    <input type="number" class="form-control" id="year" name="year" min="2020" max="2050" value="<?php echo $selectedYear; ?>">
                </div>
            <?php endif; ?>
            
            <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100 py-2.5">
                    <i class="fa-solid fa-magnifying-glass"></i> Filter
                </button>
            </div>
        </form>
        
    </div>
</div>

<!-- Print-Only Page Banner -->
<div class="print-header text-center">
    <h3><?php echo sanitize($settings['system_name']); ?></h3>
    <h5>
        <?php 
            if ($reportType === 'insurance') echo 'Insurance Expiry Audit Report';
            elseif ($reportType === 'pollution') echo 'Pollution Expiry Audit Report';
            elseif ($reportType === 'agent_performance') echo 'Agent Performance Analysis';
            elseif ($reportType === 'monthly_renewal') echo "Monthly Renewal Overview ($selectedYear)";
            else echo 'Message Wallet Audit Report';
        ?>
    </h5>
    <?php if ($reportType === 'insurance' || $reportType === 'pollution' || $reportType === 'message_wallet'): ?>
        <p class="m-0">Period: <?php echo date('d-M-Y', strtotime($dateFrom)); ?> to <?php echo date('d-M-Y', strtotime($dateTo)); ?></p>
    <?php endif; ?>
    <hr>
</div>

<!-- Report Results -->
<div class="card shadow-sm border-0 mt-3">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <h5 class="card-title"><i class="fa-solid fa-file-lines text-primary me-2"></i>Report Statement</h5>
        
        <div class="d-flex align-items-center gap-2">
            <button onclick="window.print()" class="btn btn-sm btn-light border">
                <i class="fa-solid fa-print me-1"></i> Print / PDF
            </button>
            <button onclick="exportToExcel('report-table', '<?php echo $reportType; ?>_report')" class="btn btn-sm btn-light border text-success">
                <i class="fa-regular fa-file-excel me-1"></i> Export Excel
            </button>
        </div>
    </div>
    
    <div class="card-body">
        <table class="table table-hover align-middle datatable w-100" id="report-table">
            
            <?php if ($reportType === 'insurance'): ?>
                <!-- A. Insurance Expiry Headers -->
                <thead>
                    <tr>
                        <th>Expiry Date</th>
                        <th>Vehicle Number</th>
                        <th>Brand / Model</th>
                        <th>Policy Carrier / Number</th>
                        <th>Premium (₹)</th>
                        <th>Customer Name</th>
                        <th>Phone Number</th>
                        <th>Agent Shop</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                        <tr>
                            <td><strong class="text-danger"><?php echo date('d-M-Y', strtotime($row['expiry_date'])); ?></strong></td>
                            <td><strong class="text-main"><?php echo sanitize($row['vehicle_number']); ?></strong></td>
                            <td><?php echo sanitize($row['brand'] . ' ' . $row['model']); ?></td>
                            <td>
                                <span class="d-block font-weight-500 text-main"><?php echo sanitize($row['company_name']); ?></span>
                                <small class="text-muted">Pol: <?php echo sanitize($row['policy_number']); ?></small>
                            </td>
                            <td><strong>₹<?php echo number_format($row['premium_amount'], 2); ?></strong></td>
                            <td><?php echo sanitize($row['customer_name']); ?></td>
                            <td><?php echo sanitize($row['mobile_number']); ?></td>
                            <td><span class="badge bg-light text-muted border"><?php echo sanitize($row['shop_name']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                
            <?php elseif ($reportType === 'pollution'): ?>
                <!-- B. Pollution Expiry Headers -->
                <thead>
                    <tr>
                        <th>Expiry Date</th>
                        <th>Vehicle Number</th>
                        <th>Brand / Model</th>
                        <th>PUC Certificate No.</th>
                        <th>Customer Name</th>
                        <th>Phone Number</th>
                        <th>Agent Shop</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                        <tr>
                            <td><strong class="text-danger"><?php echo date('d-M-Y', strtotime($row['expiry_date'])); ?></strong></td>
                            <td><strong class="text-main"><?php echo sanitize($row['vehicle_number']); ?></strong></td>
                            <td><?php echo sanitize($row['brand'] . ' ' . $row['model']); ?></td>
                            <td><span class="font-weight-500 font-monospace text-main"><?php echo sanitize($row['certificate_number']); ?></span></td>
                            <td><?php echo sanitize($row['customer_name']); ?></td>
                            <td><?php echo sanitize($row['mobile_number']); ?></td>
                            <td><span class="badge bg-light text-muted border"><?php echo sanitize($row['shop_name']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                
            <?php elseif ($reportType === 'agent_performance'): ?>
                <!-- C. Agent Performance Headers -->
                <thead>
                    <tr>
                        <th>Agent / Shop Name</th>
                        <th>Owner</th>
                        <th>Contact Mobile</th>
                        <th>Location (City)</th>
                        <th class="text-center">Customers Enrolled</th>
                        <th class="text-center">Vehicles Registered</th>
                        <th class="text-center">Notifications Dispatched</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                        <tr>
                            <td><strong class="text-main"><?php echo sanitize($row['shop_name']); ?></strong></td>
                            <td><?php echo sanitize($row['shop_owner_name'] ?: 'N/A'); ?></td>
                            <td><?php echo sanitize($row['mobile_number'] ?: 'N/A'); ?></td>
                            <td><?php echo sanitize($row['city'] ?: 'N/A'); ?></td>
                            <td class="text-center"><span class="badge bg-light text-primary border font-weight-600 px-2.5 py-1.5"><?php echo $row['customer_count']; ?></span></td>
                            <td class="text-center"><span class="badge bg-light text-success border font-weight-600 px-2.5 py-1.5"><?php echo $row['vehicle_count']; ?></span></td>
                            <td class="text-center"><span class="badge bg-light text-warning border font-weight-600 px-2.5 py-1.5"><?php echo $row['reminders_sent']; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                
            <?php elseif ($reportType === 'monthly_renewal'): ?>
                <!-- D. Monthly Renewal Headers -->
                <thead>
                    <tr>
                        <th>Month</th>
                        <th class="text-center">Insurance Renewals Scheduled</th>
                        <th>Expected Premium Volume (₹)</th>
                        <th class="text-center">Pollution Renewals Scheduled</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                        <tr>
                            <td><strong class="text-main"><?php echo $row['month_name']; ?></strong></td>
                            <td class="text-center"><span class="badge bg-primary px-2.5 py-1.5"><?php echo $row['insurance_count']; ?></span></td>
                            <td><strong class="text-success">₹<?php echo number_format($row['total_premium'], 2); ?></strong></td>
                            <td class="text-center"><span class="badge bg-info text-dark px-2.5 py-1.5"><?php echo $row['pollution_count']; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            <?php elseif ($reportType === 'message_wallet'): ?>
                <!-- E. Message Wallet Headers -->
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Agent Shop</th>
                        <th>Username</th>
                        <th>Recharge Amount (Rs)</th>
                        <th class="text-center">Messages Credited</th>
                        <th class="text-center">Messages Sent</th>
                        <th class="text-center">Messages Failed</th>
                        <th class="text-center">Current Balance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                        <tr>
                            <td><strong><?php echo date('d-M-Y', strtotime($row['activity_date'])); ?></strong></td>
                            <td><strong class="text-main"><?php echo sanitize($row['shop_name']); ?></strong></td>
                            <td><?php echo sanitize($row['username']); ?></td>
                            <td><strong class="text-success"><?php echo number_format((float)$row['total_recharge_amount'], 2); ?></strong></td>
                            <td class="text-center"><span class="badge bg-primary px-2.5 py-1.5"><?php echo (int)$row['total_messages_credited']; ?></span></td>
                            <td class="text-center"><span class="badge bg-success px-2.5 py-1.5"><?php echo (int)$row['messages_sent']; ?></span></td>
                            <td class="text-center"><span class="badge bg-danger px-2.5 py-1.5"><?php echo (int)$row['messages_failed']; ?></span></td>
                            <td class="text-center"><span class="badge bg-light text-primary border font-weight-600 px-2.5 py-1.5"><?php echo (int)$row['message_balance']; ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            <?php endif; ?>
            
        </table>
    </div>
</div>

<!-- Excel Exporter Client-Side Script -->
<script>
function exportToExcel(tableId, filename) {
    let table = document.getElementById(tableId);
    let rows = table.rows;
    let csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].cells;
        for (let j = 0; j < cols.length; j++) {
            // Strip commas and HTML whitespace
            let text = cols[j].innerText.trim().replace(/,/g, '');
            // Strip currency symbols if present
            text = text.replace(/₹/g, 'INR ');
            row.push(text);
        }
        csv.push(row.join(","));
    }
    
    let csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
    let encodedUri = encodeURI(csvContent);
    let link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", filename + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
