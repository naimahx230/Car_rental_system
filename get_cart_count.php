<?php
// Use consistent session name
session_name('CUSTOMER_SESSION');
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is customer
if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'customer') {
    echo json_encode(['count' => 0]);
    exit();
}

// Get cart count
$count = isset($_SESSION['service_cart']) ? count($_SESSION['service_cart']) : 0;
echo json_encode(['count' => $count]);
?>