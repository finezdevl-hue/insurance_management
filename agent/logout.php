<?php
/**
 * Agent Logout
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    logActivity('Agent Logout', 'Agent logged out successfully.');
}

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to login
redirect('../index.php');
