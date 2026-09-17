<?php
/**
 * Mobile Forwarder for Subscription Plans Management
 */
require_once __DIR__ . '/../../includes/functions.php';
checkAccess('admin');

// Redirect to desktop subscription plans with prefer_desktop view or display responsive desktop view
require_once __DIR__ . '/../../admin/subscription_plans.php';
