<?php
/**
 * Dedicated Mobile Agent Subscription Plans & Renewal
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('agent');

$db = getDBConnection();
$agentId = getEffectiveAgentId();
$subInfo = getAgentActiveSubscription($agentId);
$plans = getAllSubscriptionPlans(true);
$messageSummary = getAgentMessageSummary($agentId);

// Summary stats
$totalSpentOnSubs = $db->query("SELECT SUM(amount_paid) FROM agent_subscriptions WHERE agent_id = $agentId")->fetchColumn() ?: 0;
$totalSubCount = $db->query("SELECT COUNT(*) FROM agent_subscriptions WHERE agent_id = $agentId")->fetchColumn() ?: 0;

$pageTitle = 'My Subscription Plans';
$activePage = 'subscriptions';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Include Razorpay Checkout SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">Subscription & License</h2>
    <p class="small text-muted m-0">Renew license and manage agency membership</p>
</div>

<!-- Current License Status Banner -->
<div class="mobile-card p-3 text-white mb-3 shadow-sm" style="background: <?php echo $subInfo['is_expired'] ? 'linear-gradient(135deg, #dc2626 0%, #ef4444 100%)' : ($subInfo['status'] === 'expiring_soon' ? 'linear-gradient(135deg, #d97706 0%, #f59e0b 100%)' : 'linear-gradient(135deg, #1e40af 0%, #3b82f6 100%)'); ?>;">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="badge bg-white text-dark font-weight-700 px-2.5 py-1 rounded-pill" style="font-size: 0.75rem;">
            <i class="fa-solid <?php echo $subInfo['is_expired'] ? 'fa-circle-xmark text-danger' : 'fa-circle-check text-success'; ?> me-1"></i>
            <?php echo $subInfo['is_expired'] ? 'EXPIRED' : ($subInfo['status'] === 'expiring_soon' ? 'EXPIRING SOON' : 'ACTIVE PLAN'); ?>
        </span>
        <span class="small opacity-85"><?php echo sanitize($subInfo['plan_name']); ?></span>
    </div>

    <div class="my-2">
        <p class="m-0 small opacity-80 text-uppercase font-weight-600" style="font-size: 0.75rem;">License Valid Till</p>
        <h3 class="m-0 font-weight-800 fs-2"><?php echo !empty($subInfo['expiry_date']) ? date('d M Y', strtotime($subInfo['expiry_date'])) : 'Not Set'; ?></h3>
    </div>

    <div class="d-flex align-items-center justify-content-between pt-2 border-top border-white border-opacity-25 small opacity-90">
        <span><i class="fa-solid fa-hourglass-half me-1"></i> <?php echo $subInfo['days_left']; ?> days remaining</span>
        <span><i class="fa-solid fa-wallet me-1"></i> <?php echo number_format($messageSummary['balance']); ?> SMS</span>
    </div>
</div>

<!-- Available Subscription Plans Section -->
<div class="mobile-card p-3 mb-3 border-0 shadow-sm bg-white">
    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
        <div>
            <h6 class="m-0 font-weight-700 text-dark">
                <i class="fa-solid fa-crown text-warning me-1"></i> Select Subscription Plan
            </h6>
            <span class="small text-muted" style="font-size: 0.75rem;">Instant auto-renewal via Razorpay</span>
        </div>
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 0.7rem;">
            UPI / Cards / NetBanking
        </span>
    </div>

    <!-- Plan Cards List -->
    <div class="d-flex flex-column gap-3">
        <?php foreach ($plans as $plan): 
            $isBest = ($plan['badge'] === 'Best Value' || stripos($plan['name'], '1 Year') !== false);
            $isPop = ($plan['badge'] === 'Popular' || stripos($plan['name'], '6 Month') !== false);
            $cardBorder = $isBest ? 'border-warning' : ($isPop ? 'border-primary' : 'border-light-subtle');
        ?>
            <div class="card p-3 border-2 <?php echo $cardBorder; ?> rounded-3 shadow-xs position-relative bg-white">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <span class="badge bg-light text-dark border px-2 py-0.5 mb-1 font-weight-700" style="font-size: 0.72rem;">
                            <i class="fa-solid fa-calendar-days text-primary me-1"></i> <?php echo $plan['duration_value'] . ' ' . ucfirst($plan['duration_type']); ?>
                        </span>
                        <h6 class="font-weight-800 text-dark m-0"><?php echo sanitize($plan['name']); ?></h6>
                    </div>
                    <?php if (!empty($plan['badge'])): ?>
                        <span class="badge <?php echo $isBest ? 'bg-warning text-dark' : ($isPop ? 'bg-primary text-white' : 'bg-secondary text-white'); ?> font-weight-700 px-2.5 py-1 rounded-pill" style="font-size: 0.7rem;">
                            <?php echo sanitize($plan['badge']); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-baseline gap-1 my-1">
                    <span class="fs-4 font-weight-800 text-dark">₹<?php echo number_format($plan['price'], (floor($plan['price']) == $plan['price'] ? 0 : 2)); ?></span>
                    <span class="small text-muted">/ <?php echo $plan['duration_value'] . ' ' . $plan['duration_type']; ?></span>
                </div>

                <div class="bg-success-subtle text-success border border-success-subtle rounded-2 p-1.5 px-2 my-2 small d-flex align-items-center gap-1.5" style="font-size: 0.75rem;">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Full Access to All Portal Features</span>
                </div>

                <button type="button" onclick="paySubscriptionRazorpayMobile(<?php echo $plan['id']; ?>, '<?php echo addslashes($plan['name']); ?>')" class="btn <?php echo $isBest ? 'btn-warning text-dark font-weight-700' : ($isPop ? 'btn-primary font-weight-700' : 'btn-outline-primary font-weight-600'); ?> w-100 py-2 mt-1">
                    <i class="fa-solid fa-credit-card me-1"></i> Subscribe <?php echo $plan['duration_value'] . ' ' . ucfirst($plan['duration_type']); ?>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Subscription History Log -->
<div class="mobile-card p-3 mb-4 border-0 shadow-sm bg-white">
    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
        <h6 class="m-0 font-weight-700 text-dark">
            <i class="fa-solid fa-clock-rotate-left text-primary me-1"></i> Subscription History
        </h6>
        <span class="badge bg-light text-dark border"><?php echo $totalSubCount; ?> Renewals</span>
    </div>

    <?php
    $stmtHistory = $db->prepare("
        SELECT * FROM agent_subscriptions 
        WHERE agent_id = ? 
        ORDER BY id DESC 
        LIMIT 20
    ");
    $stmtHistory->execute([$agentId]);
    $historyLogs = $stmtHistory->fetchAll();

    if (empty($historyLogs)): ?>
        <div class="text-center py-4 text-muted">
            <i class="fa-solid fa-receipt fs-2 mb-2 opacity-50"></i>
            <p class="small m-0">No subscription transactions yet.</p>
        </div>
    <?php else: ?>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($historyLogs as $h): 
                $isActive = (strtotime($h['expiry_date']) >= strtotime(date('Y-m-d')));
            ?>
                <div class="p-2.5 rounded-3 border bg-light d-flex align-items-center justify-content-between">
                    <div>
                        <div class="d-flex align-items-center gap-1.5">
                            <strong class="text-dark small font-weight-700"><?php echo sanitize($h['plan_name']); ?></strong>
                            <span class="badge <?php echo $isActive ? 'bg-success' : 'bg-secondary'; ?>" style="font-size: 0.65rem;">
                                <?php echo $isActive ? 'ACTIVE' : 'EXPIRED'; ?>
                            </span>
                        </div>
                        <div class="text-muted" style="font-size: 0.72rem;">
                            <span><?php echo date('d-M-Y', strtotime($h['created_at'])); ?></span> • 
                            <span>Till <?php echo date('d-M-Y', strtotime($h['expiry_date'])); ?></span>
                        </div>
                        <?php if (!empty($h['razorpay_payment_id'])): ?>
                            <span class="font-monospace text-muted" style="font-size: 0.68rem;"><?php echo sanitize($h['razorpay_payment_id']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="text-end">
                        <span class="d-block font-weight-800 text-success fs-6">₹<?php echo number_format($h['amount_paid'], 2); ?></span>
                        <?php if ($h['sms_credited'] > 0): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">+<?php echo $h['sms_credited']; ?> SMS</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function paySubscriptionRazorpayMobile(planId, planName) {
    Swal.fire({
        title: 'Connecting...',
        text: 'Preparing payment for ' + planName,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('../../process_razorpay_subscription.php', {
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
            Swal.fire('Error', res.error || 'Failed to initialize payment.', 'error');
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
                    text: 'Activating your license...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch('../../process_razorpay_subscription.php', {
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
                            confirmButtonText: 'Done'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Verification Failed', verifyRes.error || 'Payment verification failed.', 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'Network error verifying payment.', 'error');
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
