<?php
/**
 * Agent Subscription Plans, Online Renewal & Purchase History Log
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce agent login
checkAccess('agent');

$db = getDBConnection();
$agentId = getEffectiveAgentId();
$settings = getSystemSettings();

$pageTitle = 'Subscription Plans & License Renewal';
$pageHeading = 'Agency Subscription & License Renewal';
$activePage = 'subscriptions';

// Fetch current subscription status
$subInfo = getAgentActiveSubscription($agentId);
$plans = getAllSubscriptionPlans(true);
$messageSummary = getAgentMessageSummary($agentId);

// Summary statistics for agent
$totalSpentOnSubs = $db->query("SELECT SUM(amount_paid) FROM agent_subscriptions WHERE agent_id = $agentId")->fetchColumn() ?: 0;
$totalSubCount = $db->query("SELECT COUNT(*) FROM agent_subscriptions WHERE agent_id = $agentId")->fetchColumn() ?: 0;

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Include Razorpay Checkout SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<!-- Current Subscription Status Banner -->
<div class="card shadow-sm border-0 mb-4 overflow-hidden">
    <div class="card-body p-4 <?php echo $subInfo['is_expired'] ? 'bg-danger bg-opacity-10 border-start border-4 border-danger' : ($subInfo['status'] === 'expiring_soon' ? 'bg-warning bg-opacity-10 border-start border-4 border-warning' : 'bg-success bg-opacity-10 border-start border-4 border-success'); ?>">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge <?php echo $subInfo['is_expired'] ? 'bg-danger' : ($subInfo['status'] === 'expiring_soon' ? 'bg-warning text-dark' : 'bg-success'); ?> px-3 py-1.5 text-uppercase font-weight-700" style="letter-spacing: 0.5px;">
                        <i class="fa-solid <?php echo $subInfo['is_expired'] ? 'fa-triangle-exclamation' : 'fa-shield-halved'; ?> me-1"></i>
                        <?php echo $subInfo['is_expired'] ? 'License Expired' : ($subInfo['status'] === 'expiring_soon' ? 'Expiring Soon' : 'Active Subscription'); ?>
                    </span>
                    <span class="text-muted small">Current Plan: <strong class="text-dark"><?php echo sanitize($subInfo['plan_name']); ?></strong></span>
                </div>

                <h4 class="font-weight-800 text-dark mb-1">
                    <?php if ($subInfo['is_expired']): ?>
                        Your Portal License Has Expired
                    <?php else: ?>
                        License Valid Until: <span class="text-primary"><?php echo date('d-M-Y', strtotime($subInfo['expiry_date'])); ?></span>
                    <?php endif; ?>
                </h4>

                <p class="text-muted mb-0 small">
                    <?php if ($subInfo['is_expired']): ?>
                        Please renew or purchase a subscription plan below to keep uninterrupted access to customer reminders and vehicle logs.
                    <?php else: ?>
                        You have <strong class="<?php echo $subInfo['days_left'] <= 15 ? 'text-danger' : 'text-success'; ?> fs-6"><?php echo $subInfo['days_left']; ?> days remaining</strong> on your current subscription. Renewing now will add days to your existing license.
                    <?php endif; ?>
                </p>
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                <div class="bg-white rounded-3 p-3 text-center border shadow-xs">
                    <span class="d-block small text-muted font-weight-600">SMS Wallet</span>
                    <strong class="fs-5 text-success"><i class="fa-solid fa-wallet me-1"></i><?php echo number_format($messageSummary['balance']); ?></strong>
                </div>
                <a href="#plansSection" class="btn btn-primary font-weight-600 px-4 py-2.5 shadow-sm">
                    <i class="fa-solid fa-arrows-rotate me-1"></i> Renew / Extend Plan
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Overview Stats -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-primary">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <p class="stat-title">License Expiry Date</p>
            <h4 class="stat-value text-primary fs-5 mt-1"><?php echo !empty($subInfo['expiry_date']) ? date('d-M-Y', strtotime($subInfo['expiry_date'])) : 'Not Set'; ?></h4>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-info">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <p class="stat-title">Remaining Days</p>
            <h3 class="stat-value text-info"><?php echo $subInfo['days_left']; ?> <span class="fs-6 font-weight-500 text-muted">days</span></h3>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-success">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-indian-rupee-sign"></i>
            </div>
            <p class="stat-title">Total Subscription Spend</p>
            <h3 class="stat-value text-success">₹<?php echo number_format($totalSpentOnSubs, 2); ?></h3>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-warning">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
            <p class="stat-title">Total Renewals</p>
            <h3 class="stat-value text-warning"><?php echo number_format($totalSubCount); ?></h3>
        </div>
    </div>
</div>

<!-- Subscription Plans Pricing Cards -->
<div class="card shadow-sm border-0 mb-4" id="plansSection">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-crown text-warning fs-5"></i>
                <h5 class="m-0 font-weight-700">Choose Subscription Time Period</h5>
            </div>
            <small class="text-muted">All plans give full, unrestricted access to all portal features for the chosen duration.</small>
        </div>
        <span class="badge bg-success-subtle text-success border border-success px-3 py-1.5 font-weight-600">
            <i class="fa-solid fa-shield-check me-1"></i> Razorpay Secured Payment
        </span>
    </div>
    <div class="card-body p-4 bg-light">
        <div class="row g-4 justify-content-center">
            <?php if (empty($plans)): ?>
                <div class="col-12 text-center py-5 text-muted">
                    <i class="fa-solid fa-box-open fs-1 mb-2 text-muted"></i>
                    <p class="m-0">No subscription plans currently available. Please contact Super Admin.</p>
                </div>
            <?php else: ?>
                <?php foreach ($plans as $plan): 
                    $isPopular = ($plan['badge'] === 'Popular' || stripos($plan['name'], '6 Month') !== false);
                    $isBestValue = ($plan['badge'] === 'Best Value' || stripos($plan['name'], '1 Year') !== false);
                    $badgeClass = $isBestValue ? 'bg-warning text-dark' : ($isPopular ? 'bg-primary text-white' : 'bg-secondary text-white');
                    $featureList = array_filter(array_map('trim', explode("\n", (string)$plan['features'])));
                ?>
                    <div class="col-12 col-md-6 col-xl">
                        <div class="card h-100 border-2 <?php echo $isBestValue ? 'border-warning shadow' : ($isPopular ? 'border-primary shadow' : 'border-light shadow-sm'); ?> rounded-3 position-relative overflow-hidden bg-white">
                            <?php if (!empty($plan['badge'])): ?>
                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge <?php echo $badgeClass; ?> font-weight-700 px-3 py-1.5 rounded-pill shadow-xs">
                                        <?php echo sanitize($plan['badge']); ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <div class="card-body p-4 d-flex flex-column text-center">
                                <span class="badge bg-light text-dark border px-3 py-1 mb-2 font-weight-700" style="width: fit-content; margin: 0 auto;">
                                    <i class="fa-solid fa-calendar-days text-primary me-1"></i> <?php echo $plan['duration_value'] . ' ' . ucfirst($plan['duration_type']); ?>
                                </span>

                                <h4 class="font-weight-800 text-dark mb-1"><?php echo sanitize($plan['name']); ?></h4>
                                <p class="text-muted small mb-3"><?php echo sanitize($plan['description'] ?: 'Full agency portal license and access.'); ?></p>
                                
                                <div class="mb-3 pb-3 border-bottom">
                                    <span class="display-6 font-weight-800 text-dark">₹<?php echo number_format($plan['price'], (floor($plan['price']) == $plan['price'] ? 0 : 2)); ?></span>
                                    <span class="text-muted small d-block">for <?php echo $plan['duration_value'] . ' ' . $plan['duration_type']; ?></span>
                                </div>

                                <div class="bg-success-subtle text-success border border-success-subtle rounded-2 p-2 mb-3 small font-weight-600">
                                    <i class="fa-solid fa-circle-check me-1"></i> Full Access to All Features
                                </div>

                                <ul class="list-unstyled mb-4 flex-grow-1 small text-start">
                                    <li class="mb-2 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-check text-success"></i>
                                        <span>Unlimited Vehicle Records</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-check text-success"></i>
                                        <span>Pollution (PUC) Management</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-check text-success"></i>
                                        <span>Health Insurance Management</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-check text-success"></i>
                                        <span>Automated WhatsApp Reminders</span>
                                    </li>
                                    <li class="mb-2 d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-check text-success"></i>
                                        <span>Sub-Shops & Testing Centers</span>
                                    </li>
                                </ul>

                                <button type="button" onclick="paySubscriptionRazorpay(<?php echo $plan['id']; ?>, '<?php echo addslashes($plan['name']); ?>', <?php echo (float)$plan['price']; ?>)" class="btn <?php echo $isBestValue ? 'btn-warning text-dark font-weight-700' : ($isPopular ? 'btn-primary font-weight-700' : 'btn-outline-primary font-weight-600'); ?> w-100 py-2.5 shadow-sm">
                                    <i class="fa-solid fa-credit-card me-1"></i> Subscribe <?php echo $plan['duration_value'] . ' ' . ucfirst($plan['duration_type']); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Agent Subscription History Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-primary fs-5"></i>
            <h5 class="m-0 font-weight-700">My Subscription & Renewal History</h5>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-hover align-middle datatable w-100">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Plan Name</th>
                    <th>Duration</th>
                    <th>Amount Paid</th>
                    <th>Validity Period</th>
                    <th>Payment Reference</th>
                    <th>Status</th>
                    <th class="text-center">Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmtHistory = $db->prepare("
                    SELECT s.*, p.badge 
                    FROM agent_subscriptions s
                    LEFT JOIN subscription_plans p ON s.plan_id = p.id
                    WHERE s.agent_id = ?
                    ORDER BY s.id DESC
                ");
                $stmtHistory->execute([$agentId]);
                
                while ($row = $stmtHistory->fetch()):
                    $isActiveSub = (strtotime($row['expiry_date']) >= strtotime(date('Y-m-d')));
                ?>
                    <tr>
                        <!-- Date -->
                        <td>
                            <strong class="d-block text-dark"><?php echo date('d-M-Y', strtotime($row['created_at'])); ?></strong>
                            <small class="text-muted"><?php echo date('h:i A', strtotime($row['created_at'])); ?></small>
                        </td>

                        <!-- Plan Name -->
                        <td>
                            <strong class="text-primary font-weight-700"><?php echo sanitize($row['plan_name']); ?></strong>
                            <small class="text-muted d-block">Full Agency Access</small>
                        </td>

                        <!-- Duration -->
                        <td>
                            <span class="font-weight-600 text-dark">
                                <?php 
                                if (!empty($row['duration_months']) && $row['duration_months'] >= 12 && ($row['duration_months'] % 12 === 0)) {
                                    echo ($row['duration_months'] / 12) . ' Year(s)';
                                } elseif (!empty($row['duration_months'])) {
                                    echo $row['duration_months'] . ' Month(s)';
                                } else {
                                    echo ($row['duration_days'] ?: 30) . ' Days';
                                }
                                ?>
                            </span>
                        </td>

                        <!-- Amount Paid -->
                        <td>
                            <strong class="text-success fs-6">₹<?php echo number_format($row['amount_paid'], 2); ?></strong>
                        </td>

                        <!-- Validity Period -->
                        <td>
                            <div class="small">
                                <span class="text-muted">From:</span> <strong><?php echo date('d-M-Y', strtotime($row['start_date'])); ?></strong><br>
                                <span class="text-muted">To:</span> <strong class="<?php echo $isActiveSub ? 'text-success' : 'text-danger'; ?>"><?php echo date('d-M-Y', strtotime($row['expiry_date'])); ?></strong>
                            </div>
                        </td>

                        <!-- Payment Ref -->
                        <td>
                            <?php if (!empty($row['razorpay_payment_id'])): ?>
                                <span class="badge bg-light text-dark border font-monospace"><?php echo sanitize($row['razorpay_payment_id']); ?></span>
                                <small class="d-block text-muted mt-0.5"><?php echo sanitize($row['payment_method']); ?></small>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary font-weight-600">Manual / Admin</span>
                            <?php endif; ?>
                        </td>

                        <!-- Status -->
                        <td>
                            <?php if ($isActiveSub): ?>
                                <span class="badge bg-success font-weight-600">ACTIVE</span>
                            <?php else: ?>
                                <span class="badge bg-secondary font-weight-600">EXPIRED</span>
                            <?php endif; ?>
                        </td>

                        <!-- Receipt Action -->
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewReceipt(<?php echo htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>)">
                                <i class="fa-solid fa-receipt me-1"></i> Receipt
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
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
                        <strong class="text-dark"><?php echo sanitize($subInfo['user']['shop_name'] ?? $subInfo['user']['username']); ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Username:</span>
                        <span class="text-dark">@<?php echo sanitize($subInfo['user']['username']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Payment Gateway:</span>
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
function paySubscriptionRazorpay(planId, planName, planPrice) {
    Swal.fire({
        title: 'Initiating Checkout...',
        text: 'Preparing Razorpay secure payment for ' + planName,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('../process_razorpay_subscription.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'create_order',
            plan_id: planId
        })
    })
    .then(r => r.json())
    .then(res => {
        Swal.close();
        if (!res.success) {
            Swal.fire('Error', res.error || 'Failed to initialize subscription checkout.', 'error');
            return;
        }

        const options = {
            key: res.key_id,
            amount: res.amount,
            currency: "INR",
            name: "License Subscription",
            description: res.plan_name + " (" + res.duration + ") for " + res.shop_name,
            handler: function (response) {
                Swal.fire({
                    title: 'Verifying Payment...',
                    text: 'Activating your subscription and license...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('../process_razorpay_subscription.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'verify_payment',
                        plan_id: res.plan_id,
                        razorpay_order_id: response.razorpay_order_id || (res.order_id || ''),
                        razorpay_payment_id: response.razorpay_payment_id || ('pay_' + Date.now()),
                        razorpay_signature: response.razorpay_signature || ''
                    })
                })
                .then(r => r.json())
                .then(verifyRes => {
                    if (verifyRes.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Subscription Activated!',
                            text: verifyRes.message,
                            confirmButtonText: 'Great!'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Verification Failed', verifyRes.error || 'Payment verification failed.', 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'Payment verification failed due to network error.', 'error');
                });
            },
            prefill: {
                name: res.shop_name,
                email: res.email,
                contact: res.contact
            },
            theme: { color: "#0d6efd" }
        };

        if (res.order_id) {
            options.order_id = res.order_id;
        }

        const rzp = new Razorpay(options);
        rzp.open();
    })
    .catch(err => {
        Swal.fire('Error', 'Could not connect to payment gateway.', 'error');
    });
}

function viewReceipt(sub) {
    document.getElementById('rcptNumber').innerText = 'SUB-INV-' + String(sub.id).padStart(5, '0');
    document.getElementById('rcptDate').innerText = new Date(sub.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
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
    const originalContent = document.body.innerHTML;
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
