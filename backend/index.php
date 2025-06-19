<?php
// Simple redirect file for backend
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Include config
try {
    require_once '../config/config.php';
} catch (Exception $e) {
    die("Config error: " . $e->getMessage());
}

// Check if user is logged in as admin
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
?>
