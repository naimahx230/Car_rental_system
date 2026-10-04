
<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Get booking details
$booking_query = "SELECT b.*, v.brand, v.model, v.daily_rate 
                  FROM bookings b 
                  JOIN vehicles v ON b.vehicle_id = v.id 
                  WHERE b.id = ? AND b.user_id = ?";
$stmt = mysqli_prepare($conn, $booking_query);
mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$booking_result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($booking_result);

if(!$booking) {
    header("Location: my_bookings.php");
    exit();
}

// Get payment information
$payment_query = "SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC LIMIT 1";
$payment_stmt = mysqli_prepare($conn, $payment_query);
mysqli_stmt_bind_param($payment_stmt, "i", $booking_id);
mysqli_stmt_execute($payment_stmt);
$payment_result = mysqli_stmt_get_result($payment_stmt);
$payment = mysqli_fetch_assoc($payment_result);

// Handle payment retry
if($action == 'retry' && $booking['status'] == 'pending') {
    // Reset payment session
    $_SESSION['pending_booking_id'] = $booking_id;
    $_SESSION['pending_booking_amount'] = $booking['total_amount'];
    $_SESSION['pending_booking_number'] = $booking['booking_number'];
    header("Location: payment.php?retry=1&id=" . $booking_id);
    exit();
}

// Handle cancel booking
if($action == 'cancel' && $booking['status'] == 'pending') {
    mysqli_query($conn, "UPDATE bookings SET status = 'cancelled' WHERE id = $booking_id");
    // Make vehicle available again
    mysqli_query($conn, "UPDATE vehicles SET status = 'available' WHERE id = {$booking['vehicle_id']}");
    header("Location: my_bookings.php?msg=cancelled");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status - Urban Wheels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
        }

        .navbar {
            background: #1a1a2e;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 25px;
        }

        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .payment-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .payment-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .payment-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .payment-body {
            padding: 30px;
        }

        .status-icon {
            text-align: center;
            margin-bottom: 20px;
        }

        .status-icon i {
            font-size: 80px;
        }

        .status-pending i {
            color: #ffc107;
        }

        .status-paid i {
            color: #28a745;
        }

        .status-failed i {
            color: #dc3545;
        }

        .status-expired i {
            color: #6c757d;
        }

        .booking-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .amount {
            font-size: 24px;
            font-weight: 700;
            color: #667eea;
        }

        .countdown {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin: 20px 0;
        }

        .countdown-timer {
            font-size: 32px;
            font-weight: 700;
            color: #dc3545;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn-retry {
            flex: 1;
            padding: 14px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }

        .btn-cancel {
            flex: 1;
            padding: 14px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
        }

        .btn-back {
            padding: 12px 25px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">URBAN WHEELS</div>
    <div class="nav-links">
        <a href="vehicles.php">Vehicles</a>
        <a href="my_bookings.php">My Bookings</a>
        <a href="logout.php" style="background: #dc3545; padding: 8px 20px; border-radius: 25px;">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="payment-card">
        <div class="payment-header">
            <h2>Payment Status</h2>
            <p>Booking #<?php echo htmlspecialchars($booking['booking_number']); ?></p>
        </div>

        <div class="payment-body">
            <?php if($booking['payment_status'] == 'paid'): ?>
                <!-- Payment Successful -->
                <div class="status-icon status-paid">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div style="text-align: center;">
                    <h3 style="color: #28a745; margin-bottom: 10px;">Payment Successful!</h3>
                    <p>Your booking has been confirmed.</p>
                </div>
                
                <div class="booking-details">
                    <div class="detail-row">
                        <span>Booking Number:</span>
                        <strong><?php echo htmlspecialchars($booking['booking_number']); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Vehicle:</span>
                        <strong><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Pickup Date:</span>
                        <span><?php echo date('F d, Y', strtotime($booking['pickup_date'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Return Date:</span>
                        <span><?php echo date('F d, Y', strtotime($booking['return_date'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Amount Paid:</span>
                        <strong class="amount">KES <?php echo number_format($booking['total_amount'], 2); ?></strong>
                    </div>
                </div>

                <?php if($payment && $payment['receipt_number']): ?>
                <div style="text-align: center;">
                    <p><strong>Receipt Number:</strong> <?php echo htmlspecialchars($payment['receipt_number']); ?></p>
                    <p><small>A confirmation email has been sent to your registered email.</small></p>
                </div>
                <?php endif; ?>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="my_bookings.php" class="btn-back">View My Bookings</a>
                </div>

            <?php elseif($booking['payment_status'] == 'pending'): ?>
                <!-- Payment Pending -->
                <div class="status-icon status-pending">
                    <i class="fas fa-clock"></i>
                </div>
                <div style="text-align: center;">
                    <h3 style="color: #ffc107; margin-bottom: 10px;">Payment Pending</h3>
                    <p>Your booking is awaiting payment confirmation.</p>
                </div>

                <div class="booking-details">
                    <div class="detail-row">
                        <span>Booking Number:</span>
                        <strong><?php echo htmlspecialchars($booking['booking_number']); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Vehicle:</span>
                        <strong><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Pickup Date:</span>
                        <span><?php echo date('F d, Y', strtotime($booking['pickup_date'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Return Date:</span>
                        <span><?php echo date('F d, Y', strtotime($booking['return_date'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span>Amount Due:</span>
                        <strong class="amount">KES <?php echo number_format($booking['total_amount'], 2); ?></strong>
                    </div>
                </div>

                <!-- Countdown Timer -->
                <?php if($booking['payment_expiry'] && strtotime($booking['payment_expiry']) > time()): ?>
                <div class="countdown">
                    <p>Complete payment within:</p>
                    <div class="countdown-timer" id="countdown"></div>
                    <p style="font-size: 12px; margin-top: 10px;">Booking will be automatically cancelled if payment not completed.</p>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Payment window has expired. Please retry payment to confirm your booking.
                </div>
                <?php endif; ?>

                <div class="action-buttons">
                    <a href="?id=<?php echo $booking_id; ?>&action=retry" class="btn-retry">
                        <i class="fas fa-credit-card"></i> Retry Payment
                    </a>
                    <a href="?id=<?php echo $booking_id; ?>&action=cancel" class="btn-cancel" onclick="return confirm('Cancel this booking?')">
                        <i class="fas fa-times"></i> Cancel Booking
                    </a>
                </div>

            <?php elseif($booking['payment_status'] == 'failed'): ?>
                <!-- Payment Failed -->
                <div class="status-icon status-failed">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div style="text-align: center;">
                    <h3 style="color: #dc3545; margin-bottom: 10px;">Payment Failed</h3>
                    <p>We couldn't process your payment. Please try again.</p>
                </div>

                <div class="booking-details">
                    <div class="detail-row">
                        <span>Booking Number:</span>
                        <strong><?php echo htmlspecialchars($booking['booking_number']); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Vehicle:</span>
                        <strong><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></strong>
                    </div>
                    <div class="detail-row">
                        <span>Amount:</span>
                        <strong class="amount">KES <?php echo number_format($booking['total_amount'], 2); ?></strong>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Possible reasons: Insufficient funds, incorrect M-Pesa details, or network issues.
                </div>

                <div class="action-buttons">
                    <a href="?id=<?php echo $booking_id; ?>&action=retry" class="btn-retry">
                        <i class="fas fa-sync-alt"></i> Try Again
                    </a>
                    <a href="?id=<?php echo $booking_id; ?>&action=cancel" class="btn-cancel" onclick="return confirm('Cancel this booking?')">
                        <i class="fas fa-times"></i> Cancel Booking
                    </a>
                </div>

            <?php elseif($booking['status'] == 'cancelled'): ?>
                <!-- Booking Cancelled -->
                <div class="status-icon status-expired">
                    <i class="fas fa-ban"></i>
                </div>
                <div style="text-align: center;">
                    <h3 style="color: #6c757d; margin-bottom: 10px;">Booking Cancelled</h3>
                    <p>This booking has been cancelled.</p>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="vehicles.php" class="btn-back">Browse Vehicles</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if($booking['payment_status'] == 'pending' && $booking['payment_expiry'] && strtotime($booking['payment_expiry']) > time()): ?>
<script>
    // Countdown Timer
    function startCountdown() {
        const expiry = new Date("<?php echo $booking['payment_expiry']; ?>").getTime();
        
        const timer = setInterval(function() {
            const now = new Date().getTime();
            const distance = expiry - now;
            
            if (distance < 0) {
                clearInterval(timer);
                document.getElementById('countdown').innerHTML = "EXPIRED";
                location.reload();
            } else {
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                document.getElementById('countdown').innerHTML = 
                    String(hours).padStart(2, '0') + ":" + 
                    String(minutes).padStart(2, '0') + ":" + 
                    String(seconds).padStart(2, '0');
            }
        }, 1000);
    }
    
    startCountdown();
</script>
<?php endif; ?>
</body>
</html>