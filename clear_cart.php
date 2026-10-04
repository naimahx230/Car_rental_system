<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if(isset($_SESSION['service_cart'])) {
    unset($_SESSION['service_cart']);
    echo json_encode(['success' => true, 'message' => 'Cart cleared']);
} else {
    echo json_encode(['success' => true, 'message' => 'Cart already empty']);
}
?>