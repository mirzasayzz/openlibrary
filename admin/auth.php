<?php
// Admin Authentication Helper
include_once '../config.php';

// Check if user is logged in as admin
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Get admin name
function getAdminName() {
    return isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';
}

// Redirect if not logged in as admin
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

// Logout admin
function logoutAdmin() {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_name']);
    header("Location: login.php");
    exit;
}
?> 