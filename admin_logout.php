<?php
// Admin logout
require_once '../includes/session_helper.php';

// Destroy admin session only
SessionManager::destroyAdminSession();

header("Location: simple_login.php");
exit();
?>