<?php
// Customer logout - use the same session name as customer pages
session_name('CUSTOMER_SESSION');
session_start();

// Destroy the session
session_destroy();

// Also clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Redirect to homepage
header("Location: index.php");
exit();
?>