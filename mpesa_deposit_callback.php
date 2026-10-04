<?php
require_once 'config/database.php';

// Get callback data
$callback_data = file_get_contents('php://input');
$callback = json_decode($callback_data);

// Log callback for debugging
file_put_contents('mpesa_deposit_log.txt', date('Y-m-d H:i:s') . " - Callback received: " . $callback_data . "\n", FILE_APPEND);

// For TEST MODE - Simulate successful callback if no real data
$is_test_mode = true; // Set to false for production

if($is_test_mode && (empty($callback_data) || $callback_data == '')) {
    // TEST MODE: Simulate successful payment for testing
    // Get the most recent pending deposit
    $pending_deposit = mysqli_query($conn, "SELECT * FROM mpesa_deposits WHERE status = 'pending' ORDER BY id DESC LIMIT 1");
    if($pending_deposit && mysqli_num_rows($pending_deposit) > 0) {
        $deposit = mysqli_fetch_assoc($pending_deposit);
        $booking_id = $deposit['booking_id'];
        $amount = $deposit['amount'];
        $mpesa_receipt_number = 'TEST' . time() . rand(1000, 9999);
        
        // Update mpesa_deposits
        mysqli_query($conn, "UPDATE mpesa_deposits SET status = 'completed', result_code = 0, 
                            result_desc = 'Success', mpesa_receipt_number = '$mpesa_receipt_number' 
                            WHERE id = {$deposit['id']}");
        
        // Update booking
        mysqli_query($conn, "UPDATE bookings SET deposit_paid = 'paid', deposit_amount = $amount, 
                            mpesa_deposit_code = '$mpesa_receipt_number', deposit_payment_date = NOW(), 
                            status = 'confirmed', payment_status = 'deposit_paid' 
                            WHERE id = $booking_id");
    }
}

if(isset($callback->Body->stkCallback)) {
    $result_code = $callback->Body->stkCallback->ResultCode;
    $result_desc = $callback->Body->stkCallback->ResultDesc;
    $merchant_request_id = $callback->Body->stkCallback->MerchantRequestID;
    $checkout_request_id = $callback->Body->stkCallback->CheckoutRequestID;
    
    if($result_code == 0) {
        $mpesa_receipt_number = $callback->Body->stkCallback->CallbackMetadata->Item[0]->Value;
        $amount = $callback->Body->stkCallback->CallbackMetadata->Item[1]->Value;
        $transaction_date = $callback->Body->stkCallback->CallbackMetadata->Item[3]->Value;
        $phone_number = $callback->Body->stkCallback->CallbackMetadata->Item[4]->Value;
        
        $trans_query = "SELECT booking_id FROM mpesa_deposits WHERE checkout_request_id = '$checkout_request_id'";
        $trans_result = mysqli_query($conn, $trans_query);
        $transaction = mysqli_fetch_assoc($trans_result);
        if($transaction) {
            $booking_id = $transaction['booking_id'];
            
            mysqli_query($conn, "UPDATE mpesa_deposits SET status = 'completed', result_code = $result_code, 
                                result_desc = '$result_desc', mpesa_receipt_number = '$mpesa_receipt_number' 
                                WHERE checkout_request_id = '$checkout_request_id'");
            
            mysqli_query($conn, "UPDATE bookings SET deposit_paid = 'paid', deposit_amount = $amount, 
                                mpesa_deposit_code = '$mpesa_receipt_number', deposit_payment_date = NOW(), 
                                status = 'confirmed', payment_status = 'deposit_paid' 
                                WHERE id = $booking_id");
        }
    } else {
        $trans_query = "SELECT booking_id FROM mpesa_deposits WHERE checkout_request_id = '$checkout_request_id'";
        $trans_result = mysqli_query($conn, $trans_query);
        $transaction = mysqli_fetch_assoc($trans_result);
        if($transaction) {
            mysqli_query($conn, "UPDATE mpesa_deposits SET status = 'failed', result_code = $result_code, 
                                result_desc = '$result_desc' WHERE checkout_request_id = '$checkout_request_id'");
            mysqli_query($conn, "UPDATE bookings SET deposit_paid = 'failed' WHERE id = {$transaction['booking_id']}");
        }
    }
}

echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
?>