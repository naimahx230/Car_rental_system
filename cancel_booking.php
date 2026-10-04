<?php
// Use customer session name
session_name('CUSTOMER_SESSION');
session_start();

require_once 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// If admin is logged in, redirect to admin dashboard
if($_SESSION['user_role'] == 'admin') {
    header("Location: admin/dashboard.php");
    exit();
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_admin = (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin');

// Get booking details
$booking_query = "SELECT b.*, v.brand, v.model, v.daily_rate, v.id as vehicle_id 
                  FROM bookings b 
                  JOIN vehicles v ON b.vehicle_id = v.id 
                  WHERE b.id = ?";
                  
if(!$is_admin) {
    $booking_query .= " AND b.user_id = ?";
    $stmt = mysqli_prepare($conn, $booking_query);
    mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION['user_id']);
} else {
    $stmt = mysqli_prepare($conn, $booking_query);
    mysqli_stmt_bind_param($stmt, "i", $booking_id);
}
mysqli_stmt_execute($stmt);
$booking_result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($booking_result);

if(!$booking) {
    header("Location: my_bookings.php");
    exit();
}

$error = '';
$success = '';

// Check if booking can be cancelled
$can_cancel = false;
$cancel_message = '';
$refund_amount = 0;
$cancellation_fee = 0;

// Customer cancellation rules
if($booking['status'] == 'cancelled') {
    $can_cancel = false;
    $cancel_message = "This booking has already been cancelled.";
} elseif($booking['status'] == 'completed') {
    $can_cancel = false;
    $cancel_message = "Cannot cancel a completed booking.";
} elseif($booking['status'] == 'active') {
    $can_cancel = true;
    $cancel_message = "Your booking is currently active. Cancellation may incur a fee.";
} elseif(strtotime($booking['pickup_date']) < time()) {
    $can_cancel = false;
    $cancel_message = "Cannot cancel a booking after the pickup date has passed.";
} elseif($booking['status'] == 'pending' || $booking['status'] == 'confirmed') {
    $can_cancel = true;
}

// Calculate cancellation fee based on days before pickup
if($can_cancel && !$is_admin && strtotime($booking['pickup_date']) > time()) {
    $days_before_pickup = floor((strtotime($booking['pickup_date']) - time()) / (60 * 60 * 24));
    
    if($days_before_pickup >= 7) {
        $cancellation_fee = 0;
        $refund_amount = $booking['total_amount'];
        $cancel_message = "✓ Full refund available (" . $days_before_pickup . " days before pickup).";
    } elseif($days_before_pickup >= 3) {
        $cancellation_fee = $booking['total_amount'] * 0.25;
        $refund_amount = $booking['total_amount'] - $cancellation_fee;
        $cancel_message = "⚠️ 25% cancellation fee applies (" . $days_before_pickup . " days before pickup). Refund: KES " . number_format($refund_amount, 2);
    } elseif($days_before_pickup >= 1) {
        $cancellation_fee = $booking['total_amount'] * 0.5;
        $refund_amount = $booking['total_amount'] - $cancellation_fee;
        $cancel_message = "⚠️ 50% cancellation fee applies (" . $days_before_pickup . " days before pickup). Refund: KES " . number_format($refund_amount, 2);
    } else {
        $cancellation_fee = $booking['total_amount'];
        $refund_amount = 0;
        $cancel_message = "❌ No refund available for last-minute cancellation.";
    }
}

// Handle cancellation confirmation
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_cancel'])) {
    $cancel_reason = mysqli_real_escape_string($conn, $_POST['cancel_reason']);
    
    mysqli_begin_transaction($conn);
    
    try {
        // Update booking status
        $update_booking = "UPDATE bookings SET status = 'cancelled', 
                           cancellation_reason = ?, 
                           cancelled_at = NOW(),
                           cancellation_fee = ?,
                           refund_amount = ?
                           WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_booking);
        mysqli_stmt_bind_param($update_stmt, "sddi", $cancel_reason, $cancellation_fee, $refund_amount, $booking_id);
        mysqli_stmt_execute($update_stmt);
        
        // If payment was made, process refund
        if(($booking['payment_status'] == 'paid' || $booking['deposit_paid'] == 'paid') && $refund_amount > 0) {
            // Create refund record
            $refund_query = "INSERT INTO refunds (booking_id, original_amount, refund_amount, cancellation_fee, reason, status) 
                             VALUES (?, ?, ?, ?, ?, 'processed')";
            $refund_stmt = mysqli_prepare($conn, $refund_query);
            mysqli_stmt_bind_param($refund_stmt, "iddds", $booking_id, $booking['total_amount'], $refund_amount, $cancellation_fee, $cancel_reason);
            mysqli_stmt_execute($refund_stmt);
            
            // Update payment status
            mysqli_query($conn, "UPDATE payments SET status = 'refunded' WHERE booking_id = $booking_id");
            mysqli_query($conn, "UPDATE bookings SET payment_status = 'refunded' WHERE id = $booking_id");
        }
        
        // Make vehicle available again
        mysqli_query($conn, "UPDATE vehicles SET status = 'available' WHERE id = {$booking['vehicle_id']}");
        
        mysqli_commit($conn);
        
        // Set success message in session - CUSTOMER specific
        $_SESSION['cancel_success'] = "Booking #{$booking['booking_number']} has been cancelled successfully.";
        
        // Redirect to customer my_bookings page (NOT admin)
        header("Location: my_bookings.php");
        exit();
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "Cancellation failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Booking - Urban Wheels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        
        .navbar { background: rgba(0,0,0,0.95); padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .logo { font-size: 24px; font-weight: 800; background: linear-gradient(135deg, #FFD700, #FFA500); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .back-link { color: white; text-decoration: none; padding: 8px 20px; background: rgba(255,255,255,0.2); border-radius: 25px; }
        
        .container { max-width: 800px; margin: 40px auto; padding: 0 20px; }
        .cancel-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .cancel-header { background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 30px; text-align: center; }
        .cancel-header i { font-size: 60px; margin-bottom: 15px; }
        .cancel-header h2 { font-size: 28px; margin-bottom: 10px; }
        
        .cancel-body { padding: 30px; }
        .booking-summary { background: #f8f9fa; padding: 20px; border-radius: 12px; margin-bottom: 25px; }
        .summary-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #dee2e6; }
        .summary-row:last-child { border-bottom: none; }
        
        .warning-box { background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .refund-box { background: #d4edda; border: 1px solid #28a745; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .no-refund-box { background: #f8d7da; border: 1px solid #dc3545; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #333; }
        .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; resize: vertical; }
        .form-group textarea:focus { outline: none; border-color: #dc3545; }
        
        .btn-cancel { width: 100%; padding: 14px; background: #dc3545; color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-cancel:hover { background: #c82333; transform: translateY(-2px); }
        .btn-back { width: 100%; padding: 12px; background: #6c757d; color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; text-decoration: none; display: block; text-align: center; margin-top: 10px; }
        
        .payment-paid { color: #28a745; font-weight: 600; }
        .payment-pending { color: #ffc107; font-weight: 600; }
        
        .refund-table { width: 100%; margin-top: 10px; border-collapse: collapse; }
        .refund-table th, .refund-table td { padding: 8px; text-align: left; }
        .refund-table th { background: #f8f9fa; }
        
        @media (max-width: 768px) { 
            .summary-row { flex-direction: column; gap: 5px; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">URBAN WHEELS</div>
    <a href="my_bookings.php" class="back-link">← Back to My Bookings</a>
</nav>

<div class="container">
    <div class="cancel-card">
        <div class="cancel-header">
            <i class="fas fa-exclamation-triangle"></i>
            <h2>Cancel Booking</h2>
            <p>Are you sure you want to cancel this booking?</p>
        </div>

        <div class="cancel-body">
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="booking-summary">
                <div class="summary-row">
                    <span><strong>Booking Number:</strong></span>
                    <span><?php echo $booking['booking_number']; ?></span>
                </div>
                <div class="summary-row">
                    <span><strong>Vehicle:</strong></span>
                    <span><?php echo $booking['brand'] . ' ' . $booking['model']; ?></span>
                </div>
                <div class="summary-row">
                    <span><strong>Pickup Date:</strong></span>
                    <span><?php echo date('F d, Y', strtotime($booking['pickup_date'])); ?></span>
                </div>
                <div class="summary-row">
                    <span><strong>Return Date:</strong></span>
                    <span><?php echo date('F d, Y', strtotime($booking['return_date'])); ?></span>
                </div>
                <div class="summary-row">
                    <span><strong>Total Amount:</strong></span>
                    <span><strong>KES <?php echo number_format($booking['total_amount'], 2); ?></strong></span>
                </div>
                <div class="summary-row">
                    <span><strong>Payment Status:</strong></span>
                    <span class="<?php echo $booking['payment_status'] == 'paid' ? 'payment-paid' : 'payment-pending'; ?>">
                        <?php echo $booking['payment_status'] == 'paid' ? '✓ Paid' : '⏳ Pending'; ?>
                    </span>
                </div>
            </div>

            <?php if($can_cancel): ?>
                <?php if($refund_amount > 0): ?>
                    <div class="refund-box">
                        <i class="fas fa-money-bill-wave"></i> 
                        <strong>Refund Information:</strong><br>
                        <?php echo $cancel_message; ?>
                        <?php if($cancellation_fee > 0): ?>
                            <table class="refund-table">
                                <tr>
                                    <th>Original Amount</th>
                                    <th>Cancellation Fee</th>
                                    <th>Refund Amount</th>
                                </tr>
                                <tr>
                                    <td><strong>KES <?php echo number_format($booking['total_amount'], 2); ?></strong></td>
                                    <td><strong>- KES <?php echo number_format($cancellation_fee, 2); ?></strong></td>
                                    <td><strong>KES <?php echo number_format($refund_amount, 2); ?></strong></td>
                                </tr>
                            </table>
                        <?php endif; ?>
                        <p style="margin-top: 10px; font-size: 12px;"><i class="fas fa-info-circle"></i> Refund will be processed within 5-7 business days.</p>
                    </div>
                <?php else: ?>
                    <div class="no-refund-box">
                        <i class="fas fa-exclamation-circle"></i> 
                        <strong>Cancellation Notice:</strong><br>
                        <?php echo $cancel_message; ?>
                    </div>
                <?php endif; ?>

                <div class="warning-box">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Please Note:</strong> Once cancelled, this action cannot be undone.
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label>Reason for Cancellation (Optional)</label>
                        <textarea name="cancel_reason" rows="3" placeholder="Please tell us why you're cancelling..."></textarea>
                    </div>
                    <button type="submit" name="confirm_cancel" class="btn-cancel" onclick="return confirm('Are you absolutely sure you want to cancel this booking? This action cannot be undone.')">
                        <i class="fas fa-trash-alt"></i> Yes, Cancel Booking
                    </button>
                    <a href="my_bookings.php" class="btn-back">No, Go Back</a>
                </form>

            <?php else: ?>
                <div class="no-refund-box">
                    <i class="fas fa-ban"></i> 
                    <strong>Cancellation Not Available</strong><br>
                    <?php echo $cancel_message; ?>
                </div>
                <a href="my_bookings.php" class="btn-back">Back to My Bookings</a>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>