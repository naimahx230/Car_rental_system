<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = "SELECT b.*, v.brand, v.model, v.image, p.receipt_number, p.transaction_id 
          FROM bookings b 
          JOIN vehicles v ON b.vehicle_id = v.id 
          LEFT JOIN payments p ON b.id = p.booking_id 
          WHERE b.id = ? AND b.user_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($result);

if(!$booking) {
    header("Location: my_bookings.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - Urban Wheels</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-card {
            background: white;
            max-width: 550px;
            margin: 20px;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }

        .success-icon i {
            font-size: 40px;
            color: white;
        }

        h2 {
            color: #28a745;
            margin-bottom: 10px;
        }

        .booking-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            text-align: left;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn-primary {
            flex: 1;
            padding: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-secondary {
            flex: 1;
            padding: 12px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-print {
            background: #17a2b8;
            margin-bottom: 10px;
        }

        @media (max-width: 480px) {
            .success-card {
                padding: 25px;
            }
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        
        <h2>Booking Confirmed! 🎉</h2>
        <p>Your vehicle has been successfully booked.</p>
        
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
                <span>Pickup Location:</span>
                <span><?php echo htmlspecialchars($booking['pickup_location']); ?></span>
            </div>
            <div class="detail-row">
                <span>Amount Paid:</span>
                <strong style="color: #28a745;">KES <?php echo number_format($booking['total_amount'], 2); ?></strong>
            </div>
            <?php if($booking['receipt_number']): ?>
            <div class="detail-row">
                <span>Receipt Number:</span>
                <span><?php echo htmlspecialchars($booking['receipt_number']); ?></span>
            </div>
            <?php endif; ?>
            <?php if($booking['transaction_id']): ?>
            <div class="detail-row">
                <span>Transaction ID:</span>
                <span><?php echo htmlspecialchars($booking['transaction_id']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <p>A confirmation email has been sent to <strong><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'your email'); ?></strong></p>
        
        <div class="btn-group">
            <a href="my_bookings.php" class="btn-primary"><i class="fas fa-calendar-alt"></i> View My Bookings</a>
            <a href="vehicles.php" class="btn-secondary"><i class="fas fa-car"></i> Browse More Vehicles</a>
        </div>
        
        <button onclick="window.print()" class="btn-primary btn-print" style="margin-top: 10px; background: #17a2b8;">
            <i class="fas fa-print"></i> Print Confirmation
        </button>
    </div>
</body>
</html>