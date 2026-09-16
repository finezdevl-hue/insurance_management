<?php
/**
 * Message Recharge History Log (Super Admin View)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();

$pageTitle = 'Message Recharge History';
$pageHeading = 'Message Recharges Log';
$activePage = 'recharge_history';

// Calculate summary stats
$totalRecharges = $db->query("SELECT SUM(recharge_amount) FROM agent_message_recharges")->fetchColumn() ?: 0;
$totalMessagesCredited = $db->query("SELECT SUM(messages_credited) FROM agent_message_recharges")->fetchColumn() ?: 0;
$totalAgentsRecharged = $db->query("SELECT COUNT(DISTINCT agent_id) FROM agent_message_recharges")->fetchColumn();
$rechargeCount = $db->query("SELECT COUNT(*) FROM agent_message_recharges")->fetchColumn();

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Statistics Overview widgets -->
<div class="row">
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card stat-card">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-indian-rupee-sign"></i>
            </div>
            <p class="stat-title">Total Revenue (₹)</p>
            <h3 class="stat-value text-success">₹<?php echo number_format($totalRecharges, 2); ?></h3>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card stat-card">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-message"></i>
            </div>
            <p class="stat-title">Messages Credited</p>
            <h3 class="stat-value text-primary"><?php echo number_format($totalMessagesCredited); ?></h3>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card stat-card">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                <i class="fa-solid fa-store"></i>
            </div>
            <p class="stat-title">Agents Recharged</p>
            <h3 class="stat-value text-info"><?php echo number_format($totalAgentsRecharged); ?></h3>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-md-3">
        <div class="card stat-card">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                <i class="fa-solid fa-money-bill-transfer"></i>
            </div>
            <p class="stat-title">Total Transactions</p>
            <h3 class="stat-value text-warning"><?php echo number_format($rechargeCount); ?></h3>
        </div>
    </div>
</div>

<!-- History Log Card -->
<div class="card shadow-sm border-0 mt-3">
    <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-money-bill-transfer text-primary fs-5"></i>
            <h5 class="m-0 font-weight-700">Detailed Message Recharge Transmissions</h5>
        </div>
    </div>
    <div class="card-body">
        
        <table class="table table-hover align-middle datatable w-100">
            <thead>
                <tr>
                    <th>Recharge Date</th>
                    <th>Agent / Shop</th>
                    <th>Recharge Amount</th>
                    <th>Message Unit Price</th>
                    <th>Messages Credited</th>
                    <th>Recharged By</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch full recharge history joining agent and admin
                $stmt = $db->query("
                    SELECT r.*, a.shop_name, a.username as agent_username, u.username as admin_username 
                    FROM agent_message_recharges r
                    JOIN users a ON r.agent_id = a.id
                    LEFT JOIN users u ON r.recharged_by = u.id
                    ORDER BY r.created_at DESC
                ");
                
                while ($log = $stmt->fetch()):
                ?>
                    <tr>
                        <!-- Date -->
                        <td>
                            <strong class="d-block"><?php echo date('d-M-Y', strtotime($log['created_at'])); ?></strong>
                            <small class="text-muted"><?php echo date('h:i A', strtotime($log['created_at'])); ?></small>
                        </td>
                        <!-- Agent -->
                        <td>
                            <strong class="text-main d-block"><?php echo sanitize($log['shop_name'] ?: $log['agent_username']); ?></strong>
                            <small class="text-muted">(@<?php echo sanitize($log['agent_username']); ?>)</small>
                        </td>
                        <!-- Amount -->
                        <td>
                            <strong class="text-success fs-5">₹<?php echo number_format($log['recharge_amount'], 2); ?></strong>
                        </td>
                        <!-- Unit Price -->
                        <td>
                            <span class="d-block small font-weight-600">
                                ₹<?php echo number_format($log['message_unit_price'], 2); ?>
                            </span>
                        </td>
                        <!-- Messages -->
                        <td>
                            <span class="badge bg-primary px-3 py-2" style="font-size: 0.9rem;">
                                +<?php echo number_format($log['messages_credited']); ?> msg
                            </span>
                        </td>
                        <!-- Admin -->
                        <td>
                            <strong class="small d-block text-main">@<?php echo sanitize($log['admin_username'] ?? 'System'); ?></strong>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
