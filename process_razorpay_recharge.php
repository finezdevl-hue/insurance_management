<?php
/**
 * Razorpay Payment Gateway Handler for Message Recharges
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Authentication required.']);
    exit;
}

$agentId = getEffectiveAgentId();
$db = getDBConnection();

$action = $_REQUEST['action'] ?? '';

if ($action === 'create_order') {
    $messageCount = (int)($_POST['message_count'] ?? 0);
    $unitPrice = getSingleMessagePrice();
    
    if ($messageCount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Please select a valid message quantity.']);
        exit;
    }
    
    $amountInRupees = round($messageCount * $unitPrice, 2);
    
    $stmtAgent = $db->prepare("SELECT shop_name, username, email, mobile_number FROM users WHERE id = ?");
    $stmtAgent->execute([$agentId]);
    $agentInfo = $stmtAgent->fetch();
    
    $orderResult = createRazorpayOrder($amountInRupees, 'rcpt_' . $agentId . '_' . time());
    
    if (!empty($orderResult['success'])) {
        echo json_encode([
            'success' => true,
            'is_simulated' => !empty($orderResult['is_simulated']),
            'order_id' => $orderResult['order_id'],
            'key_id' => $orderResult['key_id'],
            'amount' => $orderResult['amount'],
            'amount_rupees' => $amountInRupees,
            'message_count' => $messageCount,
            'unit_price' => $unitPrice,
            'shop_name' => $agentInfo['shop_name'] ?? $agentInfo['username'],
            'email' => $agentInfo['email'] ?? '',
            'contact' => $agentInfo['mobile_number'] ?? ''
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to initialize payment order with Razorpay.']);
    }
    exit;
}

if ($action === 'verify_payment') {
    $orderId = trim($_POST['razorpay_order_id'] ?? '');
    $paymentId = trim($_POST['razorpay_payment_id'] ?? '');
    $signature = trim($_POST['razorpay_signature'] ?? '');
    $messageCount = (int)($_POST['message_count'] ?? 0);
    $amountInRupees = (float)($_POST['amount'] ?? 0);

    if (empty($paymentId)) {
        echo json_encode(['success' => false, 'error' => 'Missing Razorpay payment reference ID.']);
        exit;
    }

    $isValid = verifyRazorpaySignature($orderId, $paymentId, $signature);
    
    if ($isValid) {
        try {
            $creditResult = creditAgentMessagesFromPayment($agentId, $messageCount, $amountInRupees, $paymentId, $orderId);
            $_SESSION['alert_success'] = "Payment Successful! " . number_format($messageCount) . " SMS credited to your wallet.";
            echo json_encode([
                'success' => true,
                'message' => 'Payment verified and credited successfully!',
                'messages_credited' => $messageCount,
                'payment_id' => $paymentId
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => 'Wallet update failed: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Razorpay payment signature verification failed.']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action.']);
