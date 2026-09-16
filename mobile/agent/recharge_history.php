<?php
/**
 * Dedicated Mobile Agent Wallet & Razorpay Message Recharge Control
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../../includes/functions.php';
checkAccess('agent');

$db = getDBConnection();
$agentId = getEffectiveAgentId();
$messageSummary = getAgentMessageSummary($agentId);
$settings = getSystemSettings();
$unitPrice = getSingleMessagePrice();

// Handle Manual Request POST (Fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_recharge'])) {
    $requestedMessages = (int)($_POST['requested_messages'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    
    if ($requestedMessages > 0) {
        $stmtAgent = $db->prepare("SELECT shop_name, username FROM users WHERE id = ?");
        $stmtAgent->execute([$agentId]);
        $agentUser = $stmtAgent->fetch();
        $shopName = $agentUser['shop_name'] ?? $agentUser['username'];
        
        logActivity('Recharge Request', "Agency '{$shopName}' (ID: {$agentId}) requested a recharge of {$requestedMessages} SMS messages. Notes: {$notes}");
        $_SESSION['alert_success'] = "Recharge request for " . number_format($requestedMessages) . " SMS submitted to Super Admin!";
    } else {
        $_SESSION['alert_error'] = "Please select or enter a valid message count.";
    }
    redirect('recharge_history.php');
}

// Fetch Admin contact info for WhatsApp request
$stmtAdmin = $db->query("SELECT mobile_number, whatsapp_number FROM users WHERE role = 'admin' LIMIT 1");
$adminContact = $stmtAdmin->fetch();
$adminMobile = !empty($adminContact['whatsapp_number']) ? $adminContact['whatsapp_number'] : ($adminContact['mobile_number'] ?? '');

$stmtAgent = $db->prepare("SELECT shop_name, username FROM users WHERE id = ?");
$stmtAgent->execute([$agentId]);
$agentUser = $stmtAgent->fetch();
$shopName = $agentUser['shop_name'] ?? $agentUser['username'];

// Fetch agent recharge history
$stmt = $db->prepare("SELECT * FROM agent_message_recharges WHERE agent_id = ? ORDER BY id DESC LIMIT 50");
$stmt->execute([$agentId]);
$recharges = $stmt->fetchAll();

$totalCredited = $db->prepare("SELECT SUM(messages_credited) FROM agent_message_recharges WHERE agent_id = ?");
$totalCredited->execute([$agentId]);
$sumMessages = $totalCredited->fetchColumn() ?: 0;

$pageTitle = 'SMS Wallet & Recharges';
$activePage = 'recharge_history';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Include Razorpay Checkout SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<div class="mb-3">
    <h2 class="h5 font-weight-700 m-0 text-dark">SMS Wallet & Recharges</h2>
    <p class="small text-muted m-0">Instant Razorpay recharge & transaction history</p>
</div>

<!-- SMS Wallet Balance Banner -->
<div class="mobile-card p-3 text-white mb-3 shadow-sm" style="background: linear-gradient(135deg, #0f766e 0%, #10b981 100%);">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <p class="m-0 small opacity-85 text-uppercase font-weight-600" style="letter-spacing: 0.5px;">SMS Wallet Balance</p>
            <h3 class="m-0 font-weight-800 fs-1"><?php echo number_format($messageSummary['balance'] ?? 0); ?> <span class="fs-6 font-weight-500">SMS</span></h3>
            <span class="small opacity-75">Rate: ₹<?php echo number_format($unitPrice, 2); ?> per SMS</span>
        </div>
        <div class="text-end">
            <span class="badge bg-white text-success font-weight-700 px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.8rem;">
                <i class="fa-solid fa-bolt text-warning me-1"></i> Instant Recharge
            </span>
        </div>
    </div>
</div>

<!-- Razorpay Instant Recharge Packages Section -->
<div class="mobile-card p-3 mb-3 border-0 shadow-sm bg-white">
    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
        <div>
            <h6 class="m-0 font-weight-700 text-dark">
                <i class="fa-solid fa-bolt text-warning me-1"></i> Razorpay Recharge Packages
            </h6>
            <span class="small text-muted" style="font-size: 0.75rem;">Instant auto-credit into your wallet</span>
        </div>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size: 0.7rem;">
            UPI / Cards / Netbanking
        </span>
    </div>

    <!-- Package Cards List -->
    <div class="d-flex flex-column gap-2">
        <?php 
        $packageList = [
            ['count' => 10, 'tag' => 'Starter (₹1)', 'badge_cls' => 'bg-info text-white'],
            ['count' => 500, 'tag' => '', 'badge_cls' => ''],
            ['count' => 1000, 'tag' => 'Popular', 'badge_cls' => 'bg-primary text-white'],
            ['count' => 2500, 'tag' => '', 'badge_cls' => ''],
            ['count' => 5000, 'tag' => 'Best Value', 'badge_cls' => 'bg-warning text-dark']
        ];
        
        foreach ($packageList as $pkg): 
            $cnt = $pkg['count'];
            $price = round($cnt * $unitPrice, 2);
            $formattedPrice = ($price < 1) ? number_format($price, 2) : number_format($price, (floor($price) == $price ? 0 : 2));
        ?>
            <div class="p-3 border rounded-3 bg-light bg-opacity-50 d-flex align-items-center justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-1 mb-1">
                        <span class="fw-bold text-dark fs-6"><?php echo number_format($cnt); ?> SMS</span>
                        <?php if (!empty($pkg['tag'])): ?>
                            <span class="badge <?php echo $pkg['badge_cls']; ?>" style="font-size: 0.65rem;"><?php echo $pkg['tag']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="small text-muted" style="font-size: 0.75rem;">
                        <i class="fa-solid fa-check-circle text-success me-1"></i>Instant Credit
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-success fs-6">₹<?php echo $formattedPrice; ?></span>
                    <button type="button" onclick="payWithRazorpay(<?php echo $cnt; ?>)" class="btn btn-success rounded-2 px-2.5 py-1 font-weight-700 shadow-sm text-nowrap" style="font-size: 0.74rem; white-space: nowrap;">
                        Pay Now <i class="fa-solid fa-arrow-right ms-1" style="font-size: 0.68rem;"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Custom Recharge Card -->
<div class="mobile-card p-3 mb-3 border-0 shadow-sm bg-white">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="m-0 font-weight-700 text-dark">
            <i class="fa-solid fa-sliders text-primary me-1"></i> Custom Message Quantity
        </h6>
    </div>
    <div class="mb-2">
        <label class="form-label small text-muted font-weight-500 mb-1">Enter custom SMS count (e.g. 10 for ₹1)</label>
        <div class="input-group">
            <input type="number" id="customMsgCount" class="form-control" placeholder="e.g. 10" min="1" step="1" oninput="calcCustomPrice(this.value)">
            <span class="input-group-text fw-bold text-success bg-light" id="customPriceDisplay">₹0.00</span>
        </div>
    </div>
    <button type="button" onclick="payCustomRazorpay()" class="btn btn-outline-success w-100 font-weight-700 py-2 rounded-3">
        <i class="fa-solid fa-credit-card me-1"></i> Pay Custom Amount with Razorpay
    </button>
</div>

<!-- Contact Admin via WhatsApp Fallback Card -->
<?php if (!empty($adminMobile)): 
    $waText = rawurlencode("Hello Super Admin,\n\nI need an SMS Message Recharge for my Agency:\nAgency Name: {$shopName}\nAgent ID: {$agentId}\n\nPlease credit my account. Thank you!");
    $cleanMobile = preg_replace('/[^0-9]/', '', $adminMobile);
?>
    <div class="mobile-card p-3 mb-3 border-0 shadow-sm bg-white">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h6 class="m-0 font-weight-700 text-dark">Need Manual Credit?</h6>
                <p class="small text-muted m-0" style="font-size: 0.78rem;">Request recharge directly from Super Admin</p>
            </div>
            <a href="https://wa.me/<?php echo $cleanMobile; ?>?text=<?php echo $waText; ?>" target="_blank" class="btn btn-sm btn-outline-success rounded-3 px-3 font-weight-600 text-nowrap">
                <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Transaction History Header -->
<div class="d-flex align-items-center justify-content-between mb-2 px-1">
    <h6 class="m-0 font-weight-700 text-dark"><i class="fa-solid fa-clock-rotate-left me-1 text-primary"></i> Recharge Logs</h6>
    <span class="small text-muted"><?php echo count($recharges); ?> Records</span>
</div>

<!-- Recharge History Card List -->
<?php if (!empty($recharges)): ?>
    <?php foreach ($recharges as $rec): ?>
        <div class="mobile-card p-3 mb-2 border-0 shadow-sm bg-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="m-0 font-weight-700 text-success">+<?php echo number_format($rec['messages_credited'] ?? $rec['messages_added'] ?? 0); ?> SMS</h6>
                    <span class="small text-muted d-block" style="font-size: 0.72rem;"><?php echo date('d M Y, h:i A', strtotime($rec['created_at'])); ?></span>
                    <?php if (!empty($rec['razorpay_payment_id'])): ?>
                        <span class="small text-primary font-weight-600 d-block mt-1" style="font-size: 0.7rem;"><i class="fa-solid fa-shield-check me-1"></i>ID: <?php echo sanitize($rec['razorpay_payment_id']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="text-end">
                    <?php if (!empty($rec['recharge_amount']) && $rec['recharge_amount'] > 0): ?>
                        <span class="fw-bold text-dark fs-6 d-block">₹<?php echo number_format($rec['recharge_amount'], 2); ?></span>
                    <?php endif; ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">Completed</span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="mobile-card p-4 text-center text-muted border-0 shadow-sm bg-white">
        <i class="fa-solid fa-wallet fs-2 mb-2 d-block text-secondary opacity-50"></i>
        <p class="m-0 font-weight-500">No recharge history recorded yet.</p>
    </div>
<?php endif; ?>

<script>
const unitPrice = <?php echo (float)$unitPrice; ?>;

function calcCustomPrice(val) {
    const count = parseInt(val) || 0;
    const total = (count * unitPrice).toFixed(2);
    document.getElementById('customPriceDisplay').innerText = '₹' + total;
}

function payCustomRazorpay() {
    const val = parseInt(document.getElementById('customMsgCount').value) || 0;
    if (val < 1) {
        alert('Please enter at least 1 message count for custom recharge.');
        return;
    }
    payWithRazorpay(val);
}

function payWithRazorpay(messageCount) {
    fetch('../../process_razorpay_recharge.php', {
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
                fetch('../../process_razorpay_recharge.php', {
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
            theme: { color: "#10b981" }
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
