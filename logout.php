<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Log logout activity if admin is logged in
if (isset($_SESSION['admin_id'])) {
    logActivity('Admin logout', $_SESSION['admin_id']);
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
