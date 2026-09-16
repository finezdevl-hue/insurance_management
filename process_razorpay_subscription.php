<?php
/**
 * Razorpay Payment Gateway Handler for Agent Subscriptions & Renewals
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
    $planId = (int)($_POST['plan_id'] ?? 0);
    
    if ($planId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Please select a valid subscription plan.']);
        exit;
    }
    
    $plan = getSubscriptionPlanById($planId);
    if (!$plan || $plan['status'] !== 'active') {
        echo json_encode(['success' => false, 'error' => 'Selected subscription plan is unavailable or inactive.']);
        exit;
    }
    
    $amountInRupees = (float)$plan['price'];
    
    $stmtAgent = $db->prepare("SELECT shop_name, username, email, mobile_number FROM users WHERE id = ?");
    $stmtAgent->execute([$agentId]);
    $agentInfo = $stmtAgent->fetch();
    
    $orderResult = createRazorpayOrder($amountInRupees, 'sub_' . $agentId . '_' . $planId . '_' . time());
    
    if (!empty($orderResult['success'])) {
        echo json_encode([
            'success' => true,
            'is_simulated' => !empty($orderResult['is_simulated']),
            'order_id' => $orderResult['order_id'],
            'key_id' => $orderResult['key_id'],
            'amount' => $orderResult['amount'],
            'amount_rupees' => $amountInRupees,
            'plan_id' => $plan['id'],
            'plan_name' => $plan['name'],
            'duration' => $plan['duration_value'] . ' ' . ucfirst($plan['duration_type']),
            'sms_credits' => (int)$plan['sms_credits'],
            'shop_name' => $agentInfo['shop_name'] ?? $agentInfo['username'],
            'email' => $agentInfo['email'] ?? '',
            'contact' => $agentInfo['mobile_number'] ?? ''
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to initialize subscription order with Razorpay.']);
    }
    exit;
}

if ($action === 'verify_payment') {
    $orderId = trim($_POST['razorpay_order_id'] ?? '');
    $paymentId = trim($_POST['razorpay_payment_id'] ?? '');
    $signature = trim($_POST['razorpay_signature'] ?? '');
    $planId = (int)($_POST['plan_id'] ?? 0);

    if (empty($paymentId)) {
        echo json_encode(['success' => false, 'error' => 'Missing Razorpay payment reference ID.']);
        exit;
    }

    $plan = getSubscriptionPlanById($planId);
    if (!$plan) {
        echo json_encode(['success' => false, 'error' => 'Invalid subscription plan reference.']);
        exit;
    }

    $amountInRupees = (float)$plan['price'];
    $isValid = verifyRazorpaySignature($orderId, $paymentId, $signature);
    
    if ($isValid) {
        try {
            $subResult = activateAgentSubscription(
                $agentId, 
                $planId, 
                $amountInRupees, 
                $paymentId, 
                $orderId, 
                $agentId, 
                'Razorpay'
            );

            $formattedExpiry = date('d-M-Y', strtotime($subResult['expiry_date']));
            $_SESSION['alert_success'] = "Subscription Activated! '{$subResult['plan_name']}' is now active until {$formattedExpiry}.";
            
            echo json_encode([
                'success' => true,
                'message' => "Subscription successfully activated! Valid until {$formattedExpiry}.",
                'subscription_id' => $subResult['subscription_id'],
                'plan_name' => $subResult['plan_name'],
                'expiry_date' => $formattedExpiry,
                'sms_credited' => $subResult['sms_credited'],
                'payment_id' => $paymentId
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => 'Subscription activation failed: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Razorpay payment signature verification failed.']);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action.']);
