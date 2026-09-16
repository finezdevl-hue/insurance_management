<?php
/**
 * Mobile Gateway Router
 * Vehicle Details & Insurance Renewal Management System
 */

require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    if ($_SESSION['role'] === 'admin') {
        redirect('admin/index.php');
    } else {
        redirect('agent/index.php');
    }
} else {
    redirect('../index.php');
}
?>
