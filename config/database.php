<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'krishimitra');
define('DB_USER', 'root');
define('DB_PASS', '');

// 🔐 ADMIN PASSKEY (Same as in registration)
define('ADMIN_PASSKEY', 'ADMIN2026');

// Create connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn($role = null) {
    if ($role === 'admin') {
        return isset($_SESSION['admin_id']);
    } elseif ($role === 'farmer') {
        return isset($_SESSION['farmer_id']);
    }
    return isset($_SESSION['admin_id']) || isset($_SESSION['farmer_id']);
}

// Function to redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Function to sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Function to validate phone number
function validatePhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to validate password (min 6 chars)
function validatePassword($password) {
    return strlen($password) >= 6;
}

// 🔐 Function to verify admin passkey
function verifyAdminPasskey($passkey) {
    return $passkey === ADMIN_PASSKEY;
}
?>