<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get booking details
$booking = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM bookings WHERE id = $booking_id AND user_id = {$_SESSION['user_id']}"));

if($booking && $booking['deposit_paid'] == 'pending') {
    $mpesa_receipt = 'TEST' . time() . rand(1000, 9999);
    $amount = $booking['deposit_amount'];
    
    // Update booking
    mysqli_query($conn, "UPDATE bookings SET deposit_paid = 'paid', deposit_amount = $amount, 
                        mpesa_deposit_code = '$mpesa_receipt', deposit_payment_date = NOW(), 
                        status = 'confirmed', payment_status = 'deposit_paid' 
                        WHERE id = $booking_id");
    
    // Update vehicle status
    mysqli_query($conn, "UPDATE vehicles SET status = 'rented' WHERE id = {$booking['vehicle_id']}");
    
    $_SESSION['payment_success'] = "Deposit payment successful! Your booking is confirmed.";
}

header("Location: deposit_payment_status.php?id=" . $booking_id);
exit();
?>