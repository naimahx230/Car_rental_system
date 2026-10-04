<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_SESSION['pending_booking_id']) ? $_SESSION['pending_booking_id'] : 0);

if(!$booking_id) {
    header("Location: vehicles.php");
    exit();
}

// Get booking details with all necessary fields
$booking_query = "SELECT b.*, v.brand, v.model, v.image, u.name, u.email 
                  FROM bookings b 
                  JOIN vehicles v ON b.vehicle_id = v.id 
                  JOIN users u ON b.user_id = u.id 
                  WHERE b.id = ? AND b.user_id = ?";
$stmt = mysqli_prepare($conn, $booking_query);
mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$booking_result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($booking_result);

if(!$booking) {
    header("Location: vehicles.php");
    exit();
}

// Set default values if fields don't exist
$deposit_amount = isset($booking['deposit_amount']) ? $booking['deposit_amount'] : ($booking['total_amount'] * 0.5);
$remaining_balance = isset($booking['remaining_balance']) ? $booking['remaining_balance'] : ($booking['total_amount'] - $deposit_amount);
$deposit_paid = isset($booking['deposit_paid']) ? $booking['deposit_paid'] : 'pending';

// Auto-complete payment in test mode after 5 seconds
$is_test_mode = true;
if($is_test_mode && $deposit_paid == 'pending') {
    // Simulate automatic payment after 5 seconds
    echo '<meta http-equiv="refresh" content="5;url=process_test_payment.php?id=' . $booking_id . '">';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deposit Payment - Urban Wheels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        
        .navbar { background: rgba(0,0,0,0.95); padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: 800; background: linear-gradient(135deg, #FFD700, #FFA500); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .container { max-width: 600px; margin: 40px auto; padding: 0 20px; }
        .payment-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .payment-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; }
        .payment-header h2 { font-size: 28px; margin-bottom: 10px; }
        .payment-body { padding: 30px; }
        
        .booking-summary { background: #f8f9fa; padding: 20px; border-radius: 12px; margin-bottom: 25px; }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .deposit-row { color: #28a745; font-weight: bold; }
        
        .status-box {
            text-align: center;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .status-pending { background: #fff3cd; border: 1px solid #ffc107; }
        .status-completed { background: #d4edda; border: 1px solid #28a745; }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .btn-home { background: #28a745; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-block; margin-top: 15px; }
        .countdown { font-size: 24px; font-weight: bold; color: #667eea; margin-top: 10px; }
        .timer-text { font-size: 12px; color: #666; margin-top: 10px; }
    </style>
</head>
<body>
    <nav class="navbar"><div class="logo">URBAN WHEELS</div></nav>

    <div class="container">
        <div class="payment-card">
            <div class="payment-header">
                <h2>Deposit Payment</h2>
                <p>Complete your deposit to confirm booking</p>
            </div>
            <div class="payment-body">
                <div class="booking-summary">
                    <div class="summary-row"><span>Booking Number:</span><strong><?php echo htmlspecialchars($booking['booking_number']); ?></strong></div>
                    <div class="summary-row"><span>Vehicle:</span><strong><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></strong></div>
                    <div class="summary-row"><span>Total Amount:</span><span>KES <?php echo number_format($booking['total_amount'], 2); ?></span></div>
                    <div class="summary-row deposit-row"><span>Deposit (50%):</span><span>KES <?php echo number_format($deposit_amount, 2); ?></span></div>
                    <div class="summary-row"><span>Remaining Balance:</span><span>KES <?php echo number_format($remaining_balance, 2); ?></span></div>
                </div>

                <?php if($deposit_paid == 'paid'): ?>
                <div class="status-box status-completed">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: #28a745;"></i>
                    <h3>Payment Successful!</h3>
                    <p>Your deposit of KES <?php echo number_format($deposit_amount, 2); ?> has been received.</p>
                    <p>Your booking is now confirmed!</p>
                    <a href="my_bookings.php" class="btn-home">View My Bookings</a>
                </div>
                <?php else: ?>
                <div class="status-box status-pending" id="paymentStatus">
                    <i class="fas fa-clock" style="font-size: 48px; color: #ffc107;"></i>
                    <h3>Processing Payment</h3>
                    <p>Please check your phone for the M-Pesa STK push prompt.</p>
                    <div class="spinner"></div>
                    <p style="margin-top: 15px;">Enter your M-Pesa PIN when prompted on your phone.</p>
                    <div class="countdown" id="countdown">5</div>
                    <div class="timer-text">Auto-confirming in <span id="timer">5</span> seconds (Test Mode)</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if($deposit_paid != 'paid'): ?>
    <script>
        let seconds = 5;
        const countdownEl = document.getElementById('countdown');
        const timerEl = document.getElementById('timer');
        
        const interval = setInterval(function() {
            seconds--;
            if(countdownEl) countdownEl.innerHTML = seconds;
            if(timerEl) timerEl.innerHTML = seconds;
            
            if(seconds <= 0) {
                clearInterval(interval);
                window.location.href = 'process_test_payment.php?id=<?php echo $booking_id; ?>';
            }
        }, 1000);
    </script>
    <?php endif; ?>
</body>
</html>