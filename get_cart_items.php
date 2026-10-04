<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'items' => []]);
    exit();
}

$items = isset($_SESSION['service_cart']) ? $_SESSION['service_cart'] : [];
echo json_encode(['success' => true, 'items' => $items]);
?>