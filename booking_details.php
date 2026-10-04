<?php
// Use customer session name - MUST match my_bookings.php
session_name('CUSTOMER_SESSION');
session_start();

require_once 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';

// Get booking details with complete information
if($is_admin) {
    $booking_query = "SELECT b.*, v.brand, v.model, v.year, v.color, v.registration_number, 
                             v.transmission, v.seats, v.fuel_type, v.daily_rate, v.description,
                             v.features, v.image, u.name as customer_name, u.email, u.phone
                      FROM bookings b 
                      JOIN vehicles v ON b.vehicle_id = v.id 
                      JOIN users u ON b.user_id = u.id 
                      WHERE b.id = ?";
    $stmt = mysqli_prepare($conn, $booking_query);
    mysqli_stmt_bind_param($stmt, "i", $booking_id);
} else {
    $booking_query = "SELECT b.*, v.brand, v.model, v.year, v.color, v.registration_number, 
                             v.transmission, v.seats, v.fuel_type, v.daily_rate, v.description,
                             v.features, v.image, u.name as customer_name, u.email, u.phone
                      FROM bookings b 
                      JOIN vehicles v ON b.vehicle_id = v.id 
                      JOIN users u ON b.user_id = u.id 
                      WHERE b.id = ? AND b.user_id = ?";
    $stmt = mysqli_prepare($conn, $booking_query);
    mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION['user_id']);
}
mysqli_stmt_execute($stmt);
$booking_result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($booking_result);

if(!$booking) {
    if($is_admin) {
        header("Location: admin/bookings.php");
    } else {
        header("Location: my_bookings.php");
    }
    exit();
}

// Get payment details
$payment_query = "SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC LIMIT 1";
$payment_stmt = mysqli_prepare($conn, $payment_query);
mysqli_stmt_bind_param($payment_stmt, "i", $booking_id);
mysqli_stmt_execute($payment_stmt);
$payment_result = mysqli_stmt_get_result($payment_stmt);
$payment = mysqli_fetch_assoc($payment_result);

// Get refund details if cancelled
$refund_query = "SELECT * FROM refunds WHERE booking_id = ? ORDER BY id DESC LIMIT 1";
$refund_stmt = mysqli_prepare($conn, $refund_query);
mysqli_stmt_bind_param($refund_stmt, "i", $booking_id);
mysqli_stmt_execute($refund_stmt);
$refund_result = mysqli_stmt_get_result($refund_stmt);
$refund = mysqli_fetch_assoc($refund_result);

// Get additional services for this booking from database
$services_query = "SELECT * FROM booking_services WHERE booking_id = ?";
$services_stmt = mysqli_prepare($conn, $services_query);
mysqli_stmt_bind_param($services_stmt, "i", $booking_id);
mysqli_stmt_execute($services_stmt);
$services_result = mysqli_stmt_get_result($services_stmt);
$services = [];
$services_total = 0;
while($service = mysqli_fetch_assoc($services_result)) {
    $services[] = $service;
    $services_total += $service['total_price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Urban Wheels</title>
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
            position: sticky;
            top: 0;
            z-index: 1000;
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

        .back-link {
            color: white;
            text-decoration: none;
            padding: 8px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 25px;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        /* Status Banner */
        .status-banner {
            background: white;
            border-radius: 15px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .status-badge {
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
        }

        .status-pending { background: #ffc107; color: #000; }
        .status-confirmed { background: #17a2b8; color: #fff; }
        .status-active { background: #28a745; color: #fff; }
        .status-completed { background: #6c757d; color: #fff; }
        .status-cancelled { background: #dc3545; color: #fff; }

        .payment-badge {
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
        }

        .payment-paid { background: #28a745; color: #fff; }
        .payment-pending { background: #ffc107; color: #000; }
        .payment-refunded { background: #6c757d; color: #fff; }

        /* Details Grid */
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .details-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .details-card h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .details-card h3 i {
            color: #667eea;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 500;
            color: #666;
        }

        .detail-value {
            font-weight: 600;
            color: #333;
        }

        /* Service Item Styles */
        .service-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .service-item:last-child {
            border-bottom: none;
        }
        .service-name {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .service-name i {
            color: #28a745;
        }
        .service-price {
            font-weight: 600;
            color: #28a745;
        }
        .service-type {
            font-size: 11px;
            color: #666;
            margin-left: 5px;
        }
        .services-total {
            margin-top: 15px;
            padding-top: 12px;
            border-top: 2px solid #dee2e6;
            font-weight: 700;
            color: #28a745;
            display: flex;
            justify-content: space-between;
        }

        /* Vehicle Card */
        .vehicle-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .vehicle-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px 25px;
        }

        .vehicle-header h3 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .vehicle-body {
            padding: 25px;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 25px;
            flex-wrap: wrap;
        }

        .vehicle-image {
            width: 150px;
            height: 150px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .vehicle-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .vehicle-image i {
            font-size: 70px;
            color: rgba(255,255,255,0.8);
        }

        .vehicle-specs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .spec-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
            font-size: 14px;
        }

        .spec-item i {
            color: #667eea;
            width: 20px;
        }

        /* Features List */
        .features-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .feature-tag {
            background: #f0f0f0;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: #666;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-warning {
            background: #ffc107;
            color: #000;
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-receipt {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-receipt:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.4);
        }

        @media (max-width: 768px) {
            .vehicle-body {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .vehicle-image {
                margin: 0 auto;
            }
            .status-banner {
                flex-direction: column;
                text-align: center;
            }
            .action-buttons {
                justify-content: center;
            }
            .details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">URBAN WHEELS</div>
    <?php if($is_admin): ?>
        <a href="admin/bookings.php" class="back-link">← Back to Bookings</a>
    <?php else: ?>
        <a href="my_bookings.php" class="back-link">← Back to My Bookings</a>
    <?php endif; ?>
</nav>

<div class="container">
    <!-- Status Banner -->
    <div class="status-banner">
        <div>
            <strong>Booking #<?php echo htmlspecialchars($booking['booking_number']); ?></strong>
            <div style="color: #666; font-size: 14px; margin-top: 5px;">
                Booked on <?php echo date('F d, Y', strtotime($booking['created_at'])); ?>
            </div>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <span class="status-badge status-<?php echo $booking['status']; ?>">
                <i class="fas fa-circle"></i> <?php echo ucfirst($booking['status']); ?>
            </span>
            <span class="payment-badge payment-<?php echo $booking['payment_status']; ?>">
                <i class="fas fa-credit-card"></i> <?php echo ucfirst($booking['payment_status']); ?>
            </span>
        </div>
    </div>

    <!-- Booking Details Grid -->
    <div class="details-grid">
        <!-- Trip Information -->
        <div class="details-card">
            <h3><i class="fas fa-calendar-alt"></i> Trip Information</h3>
            <div class="detail-row">
                <span class="detail-label">Pickup Date:</span>
                <span class="detail-value"><?php echo date('F d, Y (l)', strtotime($booking['pickup_date'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Return Date:</span>
                <span class="detail-value"><?php echo date('F d, Y (l)', strtotime($booking['return_date'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Duration:</span>
                <span class="detail-value"><?php echo $booking['total_days']; ?> days</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Pickup Location:</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['pickup_location']); ?></span>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="details-card">
            <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
            <div class="detail-row">
                <span class="detail-label">Daily Rate:</span>
                <span class="detail-value">KES <?php echo number_format($booking['daily_rate'], 2); ?> / day</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Number of Days:</span>
                <span class="detail-value"><?php echo $booking['total_days']; ?> days</span>
            </div>
            <?php if($services_total > 0): ?>
            <div class="detail-row">
                <span class="detail-label">Services Total:</span>
                <span class="detail-value" style="color: #28a745;">KES <?php echo number_format($services_total, 2); ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-row">
                <span class="detail-label">Total Amount:</span>
                <span class="detail-value" style="color: #28a745; font-size: 18px;">KES <?php echo number_format($booking['total_amount'], 2); ?></span>
            </div>
            
            <?php if($payment): ?>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value"><?php echo isset($payment['payment_method']) ? ucfirst($payment['payment_method']) : 'N/A'; ?></span>
            </div>
            
            <?php if(isset($payment['receipt_number']) && !empty($payment['receipt_number'])): ?>
            <div class="detail-row">
                <span class="detail-label">Receipt Number:</span>
                <span class="detail-value"><?php echo htmlspecialchars($payment['receipt_number']); ?></span>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Customer Information -->
        <div class="details-card">
            <h3><i class="fas fa-user"></i> Customer Information</h3>
            <div class="detail-row">
                <span class="detail-label">Full Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['customer_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email Address:</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['email']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone Number:</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['phone'] ?: 'Not provided'); ?></span>
            </div>
        </div>

        <!-- Driver Information -->
        <div class="details-card">
            <h3><i class="fas fa-id-card"></i> Driver Information</h3>
            <div class="detail-row">
                <span class="detail-label">Driver's License:</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['driver_license'] ?: 'Not provided'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">ID/Passport Number:</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['id_number'] ?: 'Not provided'); ?></span>
            </div>
            <?php if($booking['special_requests']): ?>
            <div class="detail-row">
                <span class="detail-label">Special Requests:</span>
                <span class="detail-value"><?php echo nl2br(htmlspecialchars($booking['special_requests'])); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Additional Services Section -->
        <div class="details-card">
            <h3><i class="fas fa-concierge-bell"></i> Additional Services</h3>
            <?php if(!empty($services)): ?>
                <?php foreach($services as $service): ?>
                <div class="service-item">
                    <div class="service-name">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($service['service_name']); ?>
                        <span class="service-type">(<?php echo $service['price_type'] == 'per_day' ? 'per day' : 'one time'; ?>)</span>
                    </div>
                    <div class="service-price">KES <?php echo number_format($service['total_price'], 2); ?></div>
                </div>
                <?php endforeach; ?>
                <div class="services-total">
                    <span>Services Total:</span>
                    <span>KES <?php echo number_format($services_total, 2); ?></span>
                </div>
            <?php else: ?>
                <div class="detail-row">
                    <span class="detail-label">No additional services</span>
                    <span class="detail-value">-</span>
                </div>
            <?php endif; ?>
        </div>

        <?php if($booking['status'] == 'cancelled'): ?>
        <!-- Cancellation Information -->
        <div class="details-card">
            <h3><i class="fas fa-ban"></i> Cancellation Information</h3>
            <?php if($booking['cancellation_reason']): ?>
            <div class="detail-row">
                <span class="detail-label">Reason:</span>
                <span class="detail-value"><?php echo htmlspecialchars($booking['cancellation_reason']); ?></span>
            </div>
            <?php endif; ?>
            <?php if($booking['cancelled_at']): ?>
            <div class="detail-row">
                <span class="detail-label">Cancelled On:</span>
                <span class="detail-value"><?php echo date('F d, Y H:i', strtotime($booking['cancelled_at'])); ?></span>
            </div>
            <?php endif; ?>
            <?php if($refund): ?>
            <div class="detail-row">
                <span class="detail-label">Original Amount:</span>
                <span class="detail-value">KES <?php echo number_format($refund['original_amount'], 2); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Refund Amount:</span>
                <span class="detail-value" style="color: #28a745;">KES <?php echo number_format($refund['refund_amount'], 2); ?></span>
            </div>
            <?php if($refund['cancellation_fee'] > 0): ?>
            <div class="detail-row">
                <span class="detail-label">Cancellation Fee:</span>
                <span class="detail-value" style="color: #dc3545;">KES <?php echo number_format($refund['cancellation_fee'], 2); ?></span>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Vehicle Details -->
    <div class="vehicle-card">
        <div class="vehicle-header">
            <h3><i class="fas fa-car"></i> Vehicle Details</h3>
            <p><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></p>
        </div>
        <div class="vehicle-body">
            <div class="vehicle-image">
                <?php if($booking['image'] && file_exists($booking['image'])): ?>
                    <img src="<?php echo $booking['image']; ?>" alt="Vehicle">
                <?php else: ?>
                    <i class="fas fa-car"></i>
                <?php endif; ?>
            </div>
            <div>
                <div class="vehicle-specs-grid">
                    <div class="spec-item"><i class="fas fa-calendar"></i> Year: <?php echo $booking['year']; ?></div>
                    <div class="spec-item"><i class="fas fa-palette"></i> Color: <?php echo htmlspecialchars($booking['color'] ?: 'Not specified'); ?></div>
                    <div class="spec-item"><i class="fas fa-id-card"></i> Registration: <?php echo htmlspecialchars($booking['registration_number']); ?></div>
                    <div class="spec-item"><i class="fas fa-cog"></i> Transmission: <?php echo htmlspecialchars($booking['transmission'] ?: 'Automatic'); ?></div>
                    <div class="spec-item"><i class="fas fa-users"></i> Seats: <?php echo $booking['seats']; ?></div>
                    <div class="spec-item"><i class="fas fa-gas-pump"></i> Fuel: <?php echo htmlspecialchars($booking['fuel_type'] ?: 'Petrol'); ?></div>
                    <div class="spec-item"><i class="fas fa-money-bill-wave"></i> Daily Rate: KES <?php echo number_format($booking['daily_rate'], 2); ?></div>
                </div>
                <?php if($booking['description']): ?>
                <div style="margin-top: 15px;">
                    <p style="color: #666; font-size: 14px;"><?php echo htmlspecialchars($booking['description']); ?></p>
                </div>
                <?php endif; ?>
                <?php if($booking['features']): ?>
                <div class="features-list">
                    <?php 
                    $features = explode(',', $booking['features']);
                    foreach($features as $feature): 
                    ?>
                    <span class="feature-tag"><i class="fas fa-check-circle" style="color: #28a745;"></i> <?php echo htmlspecialchars(trim($feature)); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <?php if($is_admin): ?>
            <a href="admin/bookings.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Bookings
            </a>
        <?php else: ?>
            <a href="my_bookings.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Bookings
            </a>
        <?php endif; ?>
        
        <!-- Download Receipt Button - Show for paid bookings -->
        <?php if($booking['payment_status'] == 'paid'): ?>
        <a href="download_receipt.php?id=<?php echo $booking['id']; ?>" class="btn-receipt" target="_blank">
            <i class="fas fa-download"></i> Download Receipt
        </a>
        <?php endif; ?>
        
        <?php if(($booking['status'] == 'pending' || $booking['status'] == 'confirmed') && $booking['payment_status'] != 'paid'): ?>
        <a href="payment.php?id=<?php echo $booking['id']; ?>" class="btn-primary">
            <i class="fas fa-credit-card"></i> Pay Now
        </a>
        <?php endif; ?>
        
        <?php if(($booking['status'] == 'pending' || $booking['status'] == 'confirmed') && $booking['payment_status'] != 'paid'): ?>
        <a href="cancel_booking.php?id=<?php echo $booking['id']; ?>" class="btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?')">
            <i class="fas fa-times-circle"></i> Cancel Booking
        </a>
        <?php endif; ?>
        
        <?php if($booking['status'] == 'completed' && !$is_admin): ?>
        <a href="add_review.php?booking_id=<?php echo $booking['id']; ?>" class="btn-warning">
            <i class="fas fa-star"></i> Write a Review
        </a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>