<?php
// Use consistent session name
session_name('CUSTOMER_SESSION');
session_start();
require_once 'config/database.php';
header('Content-Type: application/json');

// Check if user is logged in and is customer
if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'customer') {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $service_id = (int)$_POST['service_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $price = (float)$_POST['price'];
    $price_type = $_POST['price_type'];
    
    // Initialize cart if not exists
    if(!isset($_SESSION['service_cart'])) {
        $_SESSION['service_cart'] = [];
    }
    
    // Check if service already in cart
    $exists = false;
    foreach($_SESSION['service_cart'] as $item) {
        if($item['id'] == $service_id) {
            $exists = true;
            break;
        }
    }
    
    if($exists) {
        echo json_encode(['success' => false, 'message' => 'Service already in cart']);
        exit();
    }
    
    // Add to cart
    $_SESSION['service_cart'][] = [
        'id' => $service_id,
        'name' => $name,
        'price' => $price,
        'priceType' => $price_type,
        'quantity' => 1
    ];
    
    echo json_encode(['success' => true, 'count' => count($_SESSION['service_cart'])]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>