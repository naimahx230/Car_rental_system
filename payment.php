<?php
// Use customer session name
session_name('CUSTOMER_SESSION');
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// If admin is logged in, redirect to admin dashboard
if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') {
    header("Location: admin/dashboard.php");
    exit();
}

// Session timeout check
$timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: index.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();

require_once 'config/database.php';

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_SESSION['pending_booking_id']) ? $_SESSION['pending_booking_id'] : 0);

if(!$booking_id) {
    header("Location: vehicles.php");
    exit();
}

$booking_query = "SELECT b.*, v.brand, v.model, v.registration_number, v.image 
                  FROM bookings b 
                  JOIN vehicles v ON b.vehicle_id = v.id 
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

$error = '';
$success = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $receipt_number = generateReceiptNumber();
    
    // Insert payment record
    $insert_payment = "INSERT INTO payments (booking_id, amount, payment_method, status, receipt_number) 
                       VALUES ($booking_id, {$booking['total_amount']}, 'cash', 'completed', '$receipt_number')";
    
    if(mysqli_query($conn, $insert_payment)) {
        // Update booking status
        mysqli_query($conn, "UPDATE bookings SET status = 'confirmed', payment_status = 'paid' WHERE id = $booking_id");
        
        // Update vehicle status
        mysqli_query($conn, "UPDATE vehicles SET status = 'rented' WHERE id = {$booking['vehicle_id']}");
        
        // Clear session
        unset($_SESSION['pending_booking_id']);
        unset($_SESSION['pending_booking_amount']);
        unset($_SESSION['pending_booking_number']);
        
        // Clear service cart if any
        if(isset($_SESSION['service_cart'])) {
            unset($_SESSION['service_cart']);
        }
        
        header("Location: booking_success.php?id=" . $booking_id);
        exit();
    } else {
        $error = "Payment failed: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Urban Wheels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        
        .navbar { background: rgba(0,0,0,0.95); padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .logo { font-size: 24px; font-weight: 800; background: linear-gradient(135deg, #FFD700, #FFA500); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .back-link { color: white; text-decoration: none; padding: 8px 20px; background: rgba(255,255,255,0.2); border-radius: 25px; }
        
        .container { max-width: 500px; margin: 40px auto; padding: 0 20px; }
        .payment-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .payment-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; }
        .payment-header h2 { font-size: 28px; margin-bottom: 10px; }
        .payment-body { padding: 30px; }
        
        .booking-summary { background: #f8f9fa; padding: 20px; border-radius: 12px; margin-bottom: 25px; }
        .summary-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .summary-row:last-child { border-bottom: none; }
        .total-row { font-size: 18px; font-weight: 700; color: #28a745; border-top: 2px solid #dee2e6; padding-top: 12px; margin-top: 5px; }
        
        .payment-methods { margin-bottom: 25px; }
        .payment-methods h3 { margin-bottom: 15px; color: #333; }
        .method-option { display: flex; align-items: center; gap: 15px; padding: 15px; border: 2px solid #eee; border-radius: 12px; margin-bottom: 10px; cursor: pointer; transition: all 0.3s; }
        .method-option:hover { border-color: #667eea; background: #f8f9fa; }
        .method-option input { width: 20px; height: 20px; cursor: pointer; }
        .method-option label { flex: 1; cursor: pointer; font-weight: 500; color: #333; }
        .method-option i { font-size: 28px; color: #667eea; }
        
        .mpesa-details { display: none; background: #f0f7ff; padding: 20px; border-radius: 12px; margin: 20px 0; }
        .mpesa-details.active { display: block; }
        .mpesa-details p { margin: 8px 0; font-size: 14px; }
        .mpesa-details .highlight { font-weight: 700; color: #28a745; font-size: 18px; }
        
        .btn-pay { width: 100%; padding: 14px; background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.3s; margin-top: 15px; }
        .btn-pay:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(40,167,69,0.4); }
        
        .alert { padding: 12px; border-radius: 10px; margin-bottom: 20px; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        
        .vehicle-image {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .vehicle-image img { width: 100%; height: 100%; object-fit: cover; }
        .vehicle-image i { font-size: 30px; color: white; }
        
        .vehicle-row { display: flex; gap: 15px; align-items: center; margin-bottom: 15px; }
        
        @media (max-width: 480px) {
            .container { margin: 20px auto; }
            .payment-body { padding: 20px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">URBAN WHEELS</div>
        <a href="my_bookings.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Bookings</a>
    </nav>
    
    <div class="container">
        <div class="payment-card">
            <div class="payment-header">
                <h2><i class="fas fa-credit-card"></i> Complete Payment</h2>
                <p>Confirm your booking</p>
            </div>
            <div class="payment-body">
                <?php if($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="booking-summary">
                    <div class="vehicle-row">
                        <div class="vehicle-image">
                            <?php if($booking['image'] && file_exists($booking['image'])): ?>
                                <img src="<?php echo $booking['image']; ?>" alt="Vehicle">
                            <?php else: ?>
                                <i class="fas fa-car"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <strong><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></strong><br>
                            <small><?php echo htmlspecialchars($booking['registration_number']); ?></small>
                        </div>
                    </div>
                    <div class="summary-row">
                        <span>Booking Number:</span>
                        <strong><?php echo htmlspecialchars($booking['booking_number']); ?></strong>
                    </div>
                    <div class="summary-row">
                        <span>Pickup Date:</span>
                        <span><?php echo date('M d, Y', strtotime($booking['pickup_date'])); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Return Date:</span>
                        <span><?php echo date('M d, Y', strtotime($booking['return_date'])); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Duration:</span>
                        <span><?php echo $booking['total_days']; ?> days</span>
                    </div>
                    <div class="summary-row total-row">
                        <span>Total Amount:</span>
                        <strong>KES <?php echo number_format($booking['total_amount'], 2); ?></strong>
                    </div>
                </div>
                
                <div class="payment-methods">
                    <h3>Select Payment Method</h3>
                    
                    <div class="method-option" onclick="selectMethod('cash')">
                        <input type="radio" name="payment_method" id="method_cash" value="cash" checked>
                        <label for="method_cash"><i class="fas fa-money-bill-wave"></i> Cash on Pickup</label>
                        <i class="fas fa-check-circle" style="color: #28a745;"></i>
                    </div>
                    
                    <div class="method-option" onclick="selectMethod('mpesa')">
                        <input type="radio" name="payment_method" id="method_mpesa" value="mpesa">
                        <label for="method_mpesa"><i class="fas fa-mobile-alt"></i> M-Pesa</label>
                        <i class="fas fa-check-circle" style="color: #28a745;"></i>
                    </div>
                    
                    <div class="method-option" onclick="selectMethod('card')">
                        <input type="radio" name="payment_method" id="method_card" value="card">
                        <label for="method_card"><i class="fas fa-credit-card"></i> Credit/Debit Card</label>
                        <i class="fas fa-check-circle" style="color: #28a745;"></i>
                    </div>
                </div>
                
                <div id="mpesaDetails" class="mpesa-details">
                    <p><strong>M-Pesa Payment Instructions:</strong></p>
                    <p>1. Go to M-Pesa on your phone</p>
                    <p>2. Select <strong>Lipa na M-Pesa</strong></p>
                    <p>3. Select <strong>Pay Bill</strong></p>
                    <p>4. Enter Business Number: <strong class="highlight">123456</strong></p>
                    <p>5. Enter Account Number: <strong class="highlight"><?php echo $booking['booking_number']; ?></strong></p>
                    <p>6. Enter Amount: <strong class="highlight">KES <?php echo number_format($booking['total_amount'], 2); ?></strong></p>
                    <p>7. Enter your M-Pesa PIN and confirm</p>
                    <p class="alert-success" style="margin-top: 15px; padding: 10px; text-align: center;">
                        <i class="fas fa-info-circle"></i> After payment, click "Confirm Payment" below
                    </p>
                </div>
                
                <form method="POST" id="paymentForm">
                    <input type="hidden" name="payment_method" id="selectedMethod" value="cash">
                    <button type="submit" class="btn-pay">
                        <i class="fas fa-check-circle"></i> Confirm Booking (Cash on Pickup)
                    </button>
                </form>
                
                <div style="text-align: center; margin-top: 20px;">
                    <small style="color: #666;">
                        <i class="fas fa-lock"></i> Your payment is secure and encrypted
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function selectMethod(method) {
            document.getElementById('method_cash').checked = (method === 'cash');
            document.getElementById('method_mpesa').checked = (method === 'mpesa');
            document.getElementById('method_card').checked = (method === 'card');
            document.getElementById('selectedMethod').value = method;
            
            const mpesaDetails = document.getElementById('mpesaDetails');
            const payButton = document.querySelector('.btn-pay');
            
            if(method === 'mpesa') {
                mpesaDetails.classList.add('active');
                payButton.innerHTML = '<i class="fas fa-check-circle"></i> I Have Made the Payment';
            } else {
                mpesaDetails.classList.remove('active');
                payButton.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Booking (Cash on Pickup)';
            }
        }
        
        document.querySelectorAll('.method-option').forEach(option => {
            option.addEventListener('click', function() {
                const radio = this.querySelector('input');
                if(radio) selectMethod(radio.value);
            });
        });
    </script>
</body>
</html>