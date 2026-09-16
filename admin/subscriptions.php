<?php
/**
 * Agent Subscription Logs & Manual Renewal Control (Super Admin View)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce admin login
checkAccess('admin');

$db = getDBConnection();
$settings = getSystemSettings();

$pageTitle = 'Agent Subscriptions & Renewals History';
$pageHeading = 'Agent Subscriptions & License History';
$activePage = 'subscriptions';

// Handle Manual Subscription Assignment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_subscription'])) {
    $agentId = (int)$_POST['agent_id'];
    $planId = (int)$_POST['plan_id'];
    $paymentMethod = trim($_POST['payment_method'] ?? 'Offline / Cash');
    $paymentRef = trim($_POST['payment_ref'] ?? 'MANUAL_' . time());
    $customPrice = isset($_POST['custom_price']) && $_POST['custom_price'] !== '' ? (float)$_POST['custom_price'] : null;

    try {
        if ($agentId <= 0) {
            throw new Exception('Please select an agent partner.');
        }

        $plan = getSubscriptionPlanById($planId);
        if (!$plan) {
            throw new Exception('Please select a valid subscription plan.');
        }

        $priceToCharge = ($customPrice !== null) ? $customPrice : (float)$plan['price'];

        $subResult = activateAgentSubscription(
            $agentId,
            $planId,
            $priceToCharge,
            $paymentRef,
            'ORDER_MANUAL_' . time(),
            $_SESSION['user_id'],
            $paymentMethod
        );

        $_SESSION['alert_success'] = "Subscription '{$subResult['plan_name']}' assigned to {$subResult['shop_name']} successfully! Valid until " . date('d-M-Y', strtotime($subResult['expiry_date'])) . '.';
    } catch (Exception $e) {
        $_SESSION['alert_error'] = 'Failed to assign subscription: ' . $e->getMessage();
    }
    redirect('subscriptions.php');
}

// Summary Statistics
$totalRevenue = $db->query("SELECT SUM(amount_paid) FROM agent_subscriptions")->fetchColumn() ?: 0;
$totalTransactions = $db->query("SELECT COUNT(*) FROM agent_subscriptions")->fetchColumn() ?: 0;
$totalUniqueAgents = $db->query("SELECT COUNT(DISTINCT agent_id) FROM agent_subscriptions")->fetchColumn() ?: 0;
$activeSubscribed = $db->query("SELECT COUNT(DISTINCT agent_id) FROM agent_subscriptions WHERE expiry_date >= CURDATE()")->fetchColumn() ?: 0;

// Fetch all available active plans and agents for manual modal
$availablePlans = getAllSubscriptionPlans(true);
$allAgents = $db->query("SELECT id, username, shop_name, expiry_date FROM users WHERE role = 'agent' ORDER BY shop_name ASC")->fetchAll();

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Statistics Overview -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-success">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-indian-rupee-sign"></i>
            </div>
            <p class="stat-title">Subscription Revenue</p>
            <h3 class="stat-value text-success">₹<?php echo number_format($totalRevenue, 2); ?></h3>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-primary">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <p class="stat-title">Total Renewals</p>
            <h3 class="stat-value text-primary"><?php echo number_format($totalTransactions); ?></h3>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-info">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                <i class="fa-solid fa-users"></i>
            </div>
            <p class="stat-title">Total Agency Partners</p>
            <h3 class="stat-value text-info"><?php echo number_format($totalUniqueAgents); ?></h3>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-warning">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <p class="stat-title">Currently Active Licenses</p>
            <h3 class="stat-value text-warning"><?php echo number_format($activeSubscribed); ?></h3>
        </div>
    </div>
</div>

<!-- Subscription History Log Card -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-primary fs-5"></i>
            <h5 class="m-0 font-weight-700">Agent Subscription Purchases & License Logs</h5>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="subscription_plans.php" class="btn btn-outline-primary font-weight-600">
                <i class="fa-solid fa-layer-group me-1"></i> Manage Plans
            </a>
            <button type="button" class="btn btn-success font-weight-600 shadow-sm" data-bs-toggle="modal" data-bs-target="#manualAssignModal">
                <i class="fa-solid fa-plus-circle me-1"></i> Manual Assign / Renew Plan
            </button>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-hover align-middle datatable w-100">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Agency / Shop</th>
                    <th>Subscribed Plan</th>
                    <th>Amount Paid</th>
                    <th>Validity Period</th>
                    <th>Payment Reference</th>
                    <th>Granted By</th>
                    <th>Status</th>
                    <th class="text-center">Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $db->query("
                    SELECT s.*, a.shop_name, a.username as agent_username, u.username as admin_username
                    FROM agent_subscriptions s
                    JOIN users a ON s.agent_id = a.id
                    LEFT JOIN users u ON s.created_by = u.id
                    ORDER BY s.id DESC
                ");

                while ($row = $stmt->fetch()):
                    $isActive = (strtotime($row['expiry_date']) >= strtotime(date('Y-m-d')));
                ?>
                    <tr>
                        <!-- Date -->
                        <td>
                            <strong class="d-block text-dark"><?php echo date('d-M-Y', strtotime($row['created_at'])); ?></strong>
                            <small class="text-muted"><?php echo date('h:i A', strtotime($row['created_at'])); ?></small>
                        </td>

                        <!-- Agency -->
                        <td>
                            <strong class="text-main d-block"><?php echo sanitize($row['shop_name'] ?: $row['agent_username']); ?></strong>
                            <small class="text-muted">(@<?php echo sanitize($row['agent_username']); ?>)</small>
                        </td>

                        <!-- Plan -->
                        <td>
                            <strong class="text-primary"><?php echo sanitize($row['plan_name']); ?></strong>
                            <small class="text-muted d-block">Full Access</small>
                        </td>

                        <!-- Amount -->
                        <td>
                            <strong class="text-success fs-6">₹<?php echo number_format($row['amount_paid'], 2); ?></strong>
                        </td>

                        <!-- Validity -->
                        <td>
                            <div class="small">
                                <span class="text-muted">From:</span> <strong><?php echo date('d-M-Y', strtotime($row['start_date'])); ?></strong><br>
                                <span class="text-muted">To:</span> <strong class="<?php echo $isActive ? 'text-success' : 'text-danger'; ?>"><?php echo date('d-M-Y', strtotime($row['expiry_date'])); ?></strong>
                            </div>
                        </td>

                        <!-- Payment Ref -->
                        <td>
                            <?php if (!empty($row['razorpay_payment_id'])): ?>
                                <span class="badge bg-light text-dark border font-monospace"><?php echo sanitize($row['razorpay_payment_id']); ?></span>
                                <small class="d-block text-muted mt-0.5"><?php echo sanitize($row['payment_method']); ?></small>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary font-weight-600"><?php echo sanitize($row['payment_method']); ?></span>
                            <?php endif; ?>
                        </td>

                        <!-- Granted By -->
                        <td>
                            <?php if ($row['created_by'] == $row['agent_id']): ?>
                                <span class="badge bg-info-subtle text-info border border-info-subtle font-weight-600">Self (Razorpay)</span>
                            <?php else: ?>
                                <strong class="small text-main">@<?php echo sanitize($row['admin_username'] ?? 'Admin'); ?></strong>
                            <?php endif; ?>
                        </td>

                        <!-- Status -->
                        <td>
                            <?php if ($isActive): ?>
                                <span class="badge bg-success font-weight-600">ACTIVE</span>
                            <?php else: ?>
                                <span class="badge bg-secondary font-weight-600">EXPIRED</span>
                            <?php endif; ?>
                        </td>

                        <!-- Receipt -->
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewAdminReceipt(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)">
                                <i class="fa-solid fa-receipt"></i>
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Manual Assign Modal -->
<div class="modal fade" id="manualAssignModal" tabindex="-1" aria-labelledby="manualAssignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-700" id="manualAssignModalLabel">
                    <i class="fa-solid fa-plus-circle me-2"></i> Manual Assign / Renew Subscription Plan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="assign_subscription" value="1">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Select Agent -->
                        <div class="col-12 col-md-6">
                            <label for="agent_id" class="form-label font-weight-600">Select Agency Partner <span class="text-danger">*</span></label>
                            <select class="form-select" id="agent_id" name="agent_id" required>
                                <option value="">-- Select Agent --</option>
                                <?php foreach ($allAgents as $ag): ?>
                                    <option value="<?php echo $ag['id']; ?>">
                                        <?php echo sanitize($ag['shop_name'] ?: $ag['username']); ?> (@<?php echo sanitize($ag['username']); ?>) - Current Expiry: <?php echo !empty($ag['expiry_date']) ? date('d-M-Y', strtotime($ag['expiry_date'])) : 'None'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Select Plan -->
                        <div class="col-12 col-md-6">
                            <label for="plan_id" class="form-label font-weight-600">Select Subscription Plan <span class="text-danger">*</span></label>
                            <select class="form-select" id="plan_id" name="plan_id" required onchange="updatePlanPrice(this)">
                                <option value="">-- Select Plan --</option>
                                <?php foreach ($availablePlans as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>" data-sms="<?php echo $p['sms_credits']; ?>">
                                        <?php echo sanitize($p['name']); ?> (₹<?php echo number_format($p['price'], 2); ?> - <?php echo $p['duration_value'] . ' ' . $p['duration_type']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Custom Price Override -->
                        <div class="col-12 col-md-6">
                            <label for="custom_price" class="form-label font-weight-600">Amount Charged (₹)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" min="0" class="form-control" id="custom_price" name="custom_price" placeholder="Leave empty for plan default">
                            </div>
                            <div class="form-text">Optional override if discounted or customized.</div>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-12 col-md-6">
                            <label for="payment_method" class="form-label font-weight-600">Payment Mode</label>
                            <select class="form-select" id="payment_method" name="payment_method">
                                <option value="Bank Transfer / NEFT / IMPS">Bank Transfer / NEFT / IMPS</option>
                                <option value="Cash">Cash in Hand</option>
                                <option value="Cheque / DD">Cheque / DD</option>
                                <option value="UPI Direct QR">UPI Direct QR</option>
                                <option value="Promotional Free Grant">Promotional Free Grant (₹0)</option>
                            </select>
                        </div>

                        <!-- Payment Reference / Notes -->
                        <div class="col-12">
                            <label for="payment_ref" class="form-label font-weight-600">Payment Reference / UTR Number / Notes</label>
                            <input type="text" class="form-control" id="payment_ref" name="payment_ref" placeholder="e.g. UTR12345678, Cheque #09988, Cash Receipt #45">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success font-weight-600 px-4">
                        <i class="fa-solid fa-check me-1"></i> Activate Subscription
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-700" id="receiptModalLabel">
                    <i class="fa-solid fa-receipt me-2"></i> Subscription Receipt
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="receiptPrintArea">
                <div class="text-center border-bottom pb-3 mb-3">
                    <h4 class="font-weight-800 text-dark mb-1"><?php echo sanitize($settings['system_name'] ?? 'Vehicle Care System'); ?></h4>
                    <p class="text-muted small mb-0">Subscription & License Renewal Receipt</p>
                </div>

                <div class="row g-2 mb-3 small">
                    <div class="col-6">
                        <span class="text-muted d-block">Receipt No:</span>
                        <strong id="rcptNumber" class="font-monospace text-dark">SUB-0000</strong>
                    </div>
                    <div class="col-6 text-end">
                        <span class="text-muted d-block">Payment Date:</span>
                        <strong id="rcptDate" class="text-dark">--</strong>
                    </div>
                </div>

                <div class="bg-light p-3 rounded-3 mb-3 small border">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Agency / Shop:</span>
                        <strong class="text-dark" id="rcptAgency">--</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Username:</span>
                        <span class="text-dark" id="rcptUsername">--</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Payment Mode:</span>
                        <span class="badge bg-success" id="rcptGateway">Razorpay</span>
                    </div>
                </div>

                <table class="table table-sm table-bordered mb-3 small">
                    <thead class="table-light">
                        <tr>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong id="rcptPlanName" class="text-dark">Plan Name</strong>
                                <div class="text-muted" id="rcptPeriod">Validity: -- to --</div>
                                <div class="text-success small" id="rcptBonusSms"></div>
                            </td>
                            <td class="text-end font-weight-700 text-success fs-6" id="rcptAmount">₹0.00</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th>Total Paid:</th>
                            <th class="text-end text-success fs-6" id="rcptTotalAmount">₹0.00</th>
                        </tr>
                    </tfoot>
                </table>

                <div class="small text-muted border-top pt-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span>Transaction Reference:</span>
                        <span class="font-monospace text-dark" id="rcptTxnId">--</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Status:</span>
                        <span class="badge bg-success">PAID / ACTIVE</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printReceipt()"><i class="fa-solid fa-print me-1"></i> Print Receipt</button>
            </div>
        </div>
    </div>
</div>

<script>
function updatePlanPrice(select) {
    const opt = select.options[select.selectedIndex];
    const price = opt.getAttribute('data-price');
    if (price) {
        document.getElementById('custom_price').value = price;
    }
}

function viewAdminReceipt(sub) {
    document.getElementById('rcptNumber').innerText = 'SUB-INV-' + String(sub.id).padStart(5, '0');
    document.getElementById('rcptDate').innerText = new Date(sub.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    document.getElementById('rcptAgency').innerText = sub.shop_name || sub.agent_username;
    document.getElementById('rcptUsername').innerText = '@' + sub.agent_username;
    document.getElementById('rcptPlanName').innerText = sub.plan_name;
    document.getElementById('rcptPeriod').innerText = 'Validity: ' + sub.start_date + ' to ' + sub.expiry_date;
    
    if (sub.sms_credited && parseInt(sub.sms_credited) > 0) {
        document.getElementById('rcptBonusSms').innerText = '+ ' + sub.sms_credited + ' Bonus SMS message credits included';
    } else {
        document.getElementById('rcptBonusSms').innerText = '';
    }

    const formattedAmount = '₹' + parseFloat(sub.amount_paid).toFixed(2);
    document.getElementById('rcptAmount').innerText = formattedAmount;
    document.getElementById('rcptTotalAmount').innerText = formattedAmount;
    document.getElementById('rcptTxnId').innerText = sub.razorpay_payment_id || 'OFFLINE-MANUAL';
    document.getElementById('rcptGateway').innerText = sub.payment_method || 'Razorpay';

    const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
    modal.show();
}

function printReceipt() {
    const printContent = document.getElementById('receiptPrintArea').innerHTML;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Subscription Receipt</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { font-family: 'Inter', sans-serif; padding: 20px; color: #111; }
                    .badge { border: 1px solid #ddd; padding: 4px 8px; }
                </style>
            </head>
            <body onload="window.print(); window.close();">
                <div style="max-width: 500px; margin: 0 auto; border: 1px solid #eee; padding: 20px; border-radius: 8px;">
                    ${printContent}
                </div>
            </body>
        </html>
    `);
    printWindow.document.close();
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
