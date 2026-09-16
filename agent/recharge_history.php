<?php
/**
 * Message Recharge History Log & Instant Razorpay Recharge (Agent View)
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

// Enforce agent login
checkAccess('agent');

$db = getDBConnection();
$agentId = $_SESSION['user_id'];
$settings = getSystemSettings();
$unitPrice = getSingleMessagePrice();

$pageTitle = 'My Recharge History & SMS Wallet';
$pageHeading = 'Wallet Recharge & Razorpay Payment';
$activePage = 'recharge_history';

// Calculate summary stats
$totalRecharges = $db->query("SELECT SUM(recharge_amount) FROM agent_message_recharges WHERE agent_id = $agentId")->fetchColumn() ?: 0;
$totalMessagesCredited = $db->query("SELECT SUM(messages_credited) FROM agent_message_recharges WHERE agent_id = $agentId")->fetchColumn() ?: 0;
$rechargeCount = $db->query("SELECT COUNT(*) FROM agent_message_recharges WHERE agent_id = $agentId")->fetchColumn();
$messageSummary = getAgentMessageSummary($agentId);

include_once __DIR__ . '/../includes/header.php';
?>

<!-- Include Razorpay Checkout SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<!-- Statistics Overview widgets -->
<div class="row">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-success">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <p class="stat-title">Current Balance</p>
            <h3 class="stat-value text-success"><?php echo number_format($messageSummary['balance']); ?></h3>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-primary">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-indian-rupee-sign"></i>
            </div>
            <p class="stat-title">Total Spend (₹)</p>
            <h3 class="stat-value text-primary">₹<?php echo number_format($totalRecharges, 2); ?></h3>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-info">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                <i class="fa-solid fa-message"></i>
            </div>
            <p class="stat-title">Total Messages Bought</p>
            <h3 class="stat-value text-info"><?php echo number_format($totalMessagesCredited); ?></h3>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card border-start border-4 border-warning">
            <div class="stat-icon-wrapper bg-warning bg-opacity-10 text-warning">
                <i class="fa-solid fa-money-bill-transfer"></i>
            </div>
            <p class="stat-title">Recharge Count</p>
            <h3 class="stat-value text-warning"><?php echo number_format($rechargeCount); ?></h3>
        </div>
    </div>
</div>

<!-- Instant Razorpay Recharge Card -->
<div class="card shadow-sm border-0 mt-3 bg-light">
    <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-bolt text-warning fs-5"></i>
            <h5 class="m-0 font-weight-700">Instant Razorpay SMS Message Recharge</h5>
        </div>
        <span class="badge bg-primary px-3 py-2">Rate: ₹<?php echo number_format($unitPrice, 2); ?> / SMS</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php 
            $packages = [
                10 => round(10 * $unitPrice, 2),
                500 => round(500 * $unitPrice, 2),
                1000 => round(1000 * $unitPrice, 2),
                2500 => round(2500 * $unitPrice, 2),
                5000 => round(5000 * $unitPrice, 2)
            ];
            foreach ($packages as $count => $price): 
                $formattedPrice = ($price < 1) ? number_format($price, 2) : number_format($price, (floor($price) == $price ? 0 : 2));
            ?>
                <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                    <div class="card h-100 border-1 shadow-sm text-center p-3 position-relative">
                        <?php if ($count === 10): ?>
                            <span class="badge bg-info position-absolute top-0 end-0 m-2">Test / Starter</span>
                        <?php elseif ($count === 1000): ?>
                            <span class="badge bg-primary position-absolute top-0 end-0 m-2">Popular</span>
                        <?php elseif ($count === 5000): ?>
                            <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2">Best Value</span>
                        <?php endif; ?>
                        <h4 class="font-weight-700 text-dark mb-1 mt-2"><?php echo number_format($count); ?> SMS</h4>
                        <p class="text-success fw-bold fs-4 mb-2">₹<?php echo $formattedPrice; ?></p>
                        <button type="button" onclick="payWithRazorpay(<?php echo $count; ?>)" class="btn btn-success font-weight-600 w-100">
                            <i class="fa-solid fa-credit-card me-1"></i> Pay
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Custom Quantity Card -->
            <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                <div class="card h-100 border-1 shadow-sm text-center p-3">
                    <h6 class="font-weight-700 text-dark mb-1">Custom SMS</h6>
                    <div class="input-group input-group-sm mb-2">
                        <input type="number" id="desktopCustomCount" class="form-control text-center fw-bold" placeholder="Qty" min="1" step="1" value="10" oninput="calcDesktopCustomPrice(this.value)">
                    </div>
                    <p class="text-success fw-bold fs-5 mb-2" id="desktopCustomPriceDisplay">₹<?php echo number_format(10 * $unitPrice, 2); ?></p>
                    <button type="button" onclick="payDesktopCustomRazorpay()" class="btn btn-outline-success font-weight-600 w-100 btn-sm py-2">
                        <i class="fa-solid fa-credit-card me-1"></i> Pay Custom
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- History Log Card -->
<div class="card shadow-sm border-0 mt-3">
    <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-primary fs-5"></i>
            <h5 class="m-0 font-weight-700">My Recharge History</h5>
        </div>
    </div>
    <div class="card-body">
        
        <table class="table table-hover align-middle datatable w-100">
            <thead>
                <tr>
                    <th>Recharge Date</th>
                    <th>Recharge Amount</th>
                    <th>Message Unit Price</th>
                    <th>Messages Credited</th>
                    <th>Payment Reference</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch full recharge history for agent
                $stmt = $db->prepare("
                    SELECT * FROM agent_message_recharges 
                    WHERE agent_id = ?
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$agentId]);
                
                while ($log = $stmt->fetch()):
                ?>
                    <tr>
                        <!-- Date -->
                        <td>
                            <strong class="d-block"><?php echo date('d-M-Y', strtotime($log['created_at'])); ?></strong>
                            <small class="text-muted"><?php echo date('h:i A', strtotime($log['created_at'])); ?></small>
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
                        <!-- Payment Ref -->
                        <td>
                            <?php if (!empty($log['razorpay_payment_id'])): ?>
                                <span class="badge bg-light text-dark border font-monospace"><?php echo sanitize($log['razorpay_payment_id']); ?></span>
                            <?php else: ?>
                                <span class="text-muted small">Admin Credit</span>
                            <?php endif; ?>
                        </td>
                        <!-- Status -->
                        <td>
                            <span class="badge bg-success">
                                SUCCESS
                            </span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
    </div>
</div>

<script>
const unitPrice = <?php echo (float)$unitPrice; ?>;

function calcDesktopCustomPrice(val) {
    const count = parseInt(val) || 0;
    const total = (count * unitPrice).toFixed(2);
    document.getElementById('desktopCustomPriceDisplay').innerText = '₹' + total;
}

function payDesktopCustomRazorpay() {
    const val = parseInt(document.getElementById('desktopCustomCount').value) || 0;
    if (val < 1) {
        Swal.fire('Invalid Quantity', 'Please enter at least 1 message count.', 'warning');
        return;
    }
    payWithRazorpay(val);
}

function payWithRazorpay(messageCount) {
    fetch('../process_razorpay_recharge.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'create_order',
            message_count: messageCount
        })
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            Swal.fire('Payment Error', res.error, 'error');
            return;
        }

        const options = {
            key: res.key_id,
            amount: res.amount,
            currency: "INR",
            name: "SMS Message Recharge",
            description: res.message_count + " SMS Credits for " + res.shop_name,
            handler: function (response) {
                fetch('../process_razorpay_recharge.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'verify_payment',
                        razorpay_order_id: response.razorpay_order_id || (res.order_id || ''),
                        razorpay_payment_id: response.razorpay_payment_id || ('pay_' + Date.now()),
                        razorpay_signature: response.razorpay_signature || '',
                        message_count: res.message_count,
                        amount: res.amount_rupees
                    })
                })
                .then(r => r.json())
                .then(verifyRes => {
                    if (verifyRes.success) {
                        Swal.fire('Payment Successful!', verifyRes.message, 'success').then(() => window.location.reload());
                    } else {
                        Swal.fire('Payment Verification Error', verifyRes.error, 'error');
                    }
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
        alert('Network or server error initializing payment.');
    });
}
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
