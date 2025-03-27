<?php
// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'openlibrary');




// Site Configuration
define('SITE_NAME', 'Open Library');

// Dynamic site URL that works on both local and Heroku environments
$site_url = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? 
            $_SERVER['HTTP_X_FORWARDED_PROTO'] . '://' . $_SERVER['HTTP_HOST'] :
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

// Remove any trailing slash
$site_url = rtrim($site_url, '/');

define('SITE_URL', $site_url);

// DB Connection 
function getConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        
        return $conn;
    } catch (Exception $e) {
        die("Connection error: " . $e->getMessage());
    }
}

// Session Start
session_start();

// Helper Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    // Check if user is logged in
    if (!isLoggedIn()) {
        return false;
    }
    
    // Get user from database to check is_admin flag
    $conn = getConnection();
    $user_id = getCurrentUserId();
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $is_admin = (bool)$user['is_admin'];
        $stmt->close();
        $conn->close();
        return $is_admin;
    }
    
    $stmt->close();
    $conn->close();
    return false;
}

function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

function getUserName() {
    return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Guest';
}

function redirectTo($path) {
    header("Location: " . SITE_URL . "/" . $path);
    exit;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?> 