<?php
// includes/customer_auth.php - Customer authentication check
require_once __DIR__ . '/session_helper.php';

// Start customer session
SessionManager::startCustomerSession();

// Check if customer is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'customer') {
    header("Location: ../index.php");
    exit();
}

// Session timeout (30 minutes)
$timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    SessionManager::destroyCustomerSession();
    header("Location: ../index.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();

// Verify customer still exists in database
require_once __DIR__ . '/../config/database.php';
$stmt = mysqli_prepare($conn, "SELECT status FROM users WHERE id = ? AND role = 'customer'");
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user || $user['status'] !== 'active') {
    SessionManager::destroyCustomerSession();
    header("Location: ../index.php?account_disabled=1");
    exit();
}
?>