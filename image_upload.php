<?php
require_once __DIR__ . '/security.php';

function requireAuth() {
    global $conn;
    $security = new Security($conn);
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    
    // Validate session
    if (isset($_SESSION['session_token'])) {
        if (!$security->validateSession($_SESSION['user_id'], $_SESSION['session_token'])) {
            session_destroy();
            header("Location: login.php?session_expired=1");
            exit();
        }
    }
    
    // Check session timeout (30 minutes)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 1800)) {
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
    
    // Update last activity
    $_SESSION['login_time'] = time();
    
    return true;
}

function requireAdmin() {
    requireAuth();
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
        // For admin pages, redirect to admin login
        if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
            header("Location: simple_login.php");
        } else {
            header("Location: ../index.php");
        }
        exit();
    }
}

function generateCSRFField() {
    global $conn;
    $security = new Security($conn);
    $token = $security->generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

function verifyCSRF() {
    global $conn;
    $security = new Security($conn);
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (!isset($_POST['csrf_token']) || !$security->verifyCSRFToken($_POST['csrf_token'])) {
            die("CSRF token validation failed. Please refresh the page and try again.");
        }
    }
}

// Check if user is logged in (for API/JSON responses)
function isLoggedIn() {
    global $conn;
    $security = new Security($conn);
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    if (isset($_SESSION['session_token'])) {
        return $security->validateSession($_SESSION['user_id'], $_SESSION['session_token']);
    }
    
    return false;
}

// Get current user data
function getCurrentUser() {
    global $conn;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    $user_id = (int)$_SESSION['user_id'];
    $result = mysqli_query($conn, "SELECT id, name, email, phone, role, status, created_at FROM users WHERE id = $user_id");
    return mysqli_fetch_assoc($result);
}
?>