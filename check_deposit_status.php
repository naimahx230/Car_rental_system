<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

// Fixed query - include all deposit-related fields
$query = "SELECT deposit_paid, deposit_amount, remaining_balance, vehicle_id, payment_status, status 
          FROM bookings 
          WHERE id = $booking_id AND user_id = {$_SESSION['user_id']}";
$result = mysqli_query($conn, $query);
$booking = mysqli_fetch_assoc($result);

if($booking) {
    echo json_encode([
        'deposit_paid' => $booking['deposit_paid'],
        'deposit_amount' => $booking['deposit_amount'],
        'remaining_balance' => $booking['remaining_balance'],
        'vehicle_id' => $booking['vehicle_id'],
        'payment_status' => $booking['payment_status'],
        'booking_status' => $booking['status']
    ]);
} else {
    echo json_encode(['deposit_paid' => 'pending', 'error' => 'Booking not found']);
}
?>