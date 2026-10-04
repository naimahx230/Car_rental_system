<?php
// Use customer session name
session_name('CUSTOMER_SESSION');
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'config/database.php';

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($booking_id == 0) {
    die("Invalid booking ID. Please go back and try again.");
}

// Get booking details with proper joins
$query = "SELECT b.*, 
                 v.brand, 
                 v.model, 
                 v.registration_number,
                 v.daily_rate,
                 v.year,
                 v.transmission,
                 v.fuel_type,
                 v.seats,
                 v.image,
                 p.*, 
                 u.name, 
                 u.email, 
                 u.phone 
          FROM bookings b 
          JOIN vehicles v ON b.vehicle_id = v.id 
          LEFT JOIN payments p ON b.id = p.booking_id 
          JOIN users u ON b.user_id = u.id 
          WHERE b.id = ? AND b.user_id = ?";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if(!$data) {
    die("Receipt not found. The booking may not exist or you don't have permission to view it. <a href='my_bookings.php'>Go back to My Bookings</a>");
}

// Get additional services for this booking
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

// Generate receipt number if not exists
if(empty($data['receipt_number'])) {
    $receipt_number = 'RCP-' . strtoupper($data['booking_number']) . '-' . date('Ymd');
} else {
    $receipt_number = $data['receipt_number'];
}

// Set payment method
$payment_method = !empty($data['payment_method']) ? $data['payment_method'] : 'Cash on Pickup';

// Set payment date
$payment_date = !empty($data['payment_date']) ? $data['payment_date'] : $data['created_at'];

// Set safe values for all variables
$booking_number = $data['booking_number'];
$customer_name = $data['name'];
$customer_email = $data['email'];
$customer_phone = !empty($data['phone']) ? $data['phone'] : 'Not provided';
$vehicle_brand = $data['brand'];
$vehicle_model = $data['model'];
$vehicle_reg = $data['registration_number'];
$vehicle_year = $data['year'];
$vehicle_transmission = !empty($data['transmission']) ? $data['transmission'] : 'Automatic';
$vehicle_fuel = !empty($data['fuel_type']) ? $data['fuel_type'] : 'Petrol';
$vehicle_seats = $data['seats'];
$pickup_date = $data['pickup_date'];
$return_date = $data['return_date'];
$pickup_location = $data['pickup_location'];
$total_days = $data['total_days'];
$total_amount = $data['total_amount'];
$daily_rate = $data['daily_rate'];
$payment_status = $data['payment_status'];

// Calculate deposit
$deposit_amount = $total_amount * 0.5;
$remaining_balance = $total_amount - $deposit_amount;

// Calculate vehicle total (without services)
$vehicle_total = $daily_rate * $total_days;

// Check if vehicle has image
$vehicle_image = !empty($data['image']) && file_exists($data['image']) ? $data['image'] : '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Receipt - Urban Wheels</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            padding: 40px;
            background: #f5f5f5;
        }
        
        .receipt {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .receipt-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .receipt-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .receipt-header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .receipt-body {
            padding: 30px;
        }
        
        .section {
            margin-bottom: 25px;
        }
        
        .section h3 {
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-bottom: 8px;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .row:last-child {
            border-bottom: none;
        }
        
        .label {
            font-weight: 600;
            color: #666;
        }
        
        .value {
            color: #333;
        }
        
        .total-row {
            font-size: 18px;
            font-weight: 700;
            color: #28a745;
            border-top: 2px solid #dee2e6;
            padding-top: 15px;
            margin-top: 5px;
        }
        
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #dee2e6;
        }
        
        .status-paid {
            color: #28a745;
            font-weight: 600;
        }
        
        .receipt-badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 10px;
        }
        
        @media print {
            body {
                padding: 0;
                background: white;
            }
            .receipt {
                box-shadow: none;
            }
            .no-print {
                display: none;
            }
        }
        
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px;
            font-size: 14px;
        }
        
        button:hover {
            background: #5a67d8;
        }
        
        .vehicle-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .vehicle-icon i {
            font-size: 30px;
            color: white;
        }
        
        .vehicle-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .service-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
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
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="receipt-header">
            <div class="vehicle-icon">
                <?php if($vehicle_image): ?>
                    <img src="<?php echo $vehicle_image; ?>" alt="Vehicle">
                <?php else: ?>
                    <i class="fas fa-car"></i>
                <?php endif; ?>
            </div>
            <h1>URBAN WHEELS</h1>
            <p>Premium Car Rental Services</p>
            <p>Nairobi, Kenya | +254 700 000 000</p>
            <div class="receipt-badge">
                ✓ PAYMENT CONFIRMED
            </div>
        </div>
        
        <div class="receipt-body">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2>PAYMENT RECEIPT</h2>
                <p><strong>Receipt Number:</strong> <?php echo htmlspecialchars($receipt_number); ?></p>
                <p><strong>Date:</strong> <?php echo date('F d, Y H:i:s', strtotime($payment_date)); ?></p>
                <p><strong>Booking Number:</strong> <?php echo htmlspecialchars($booking_number); ?></p>
            </div>
            
            <!-- Customer Information -->
            <div class="section">
                <h3>📋 Customer Information</h3>
                <div class="row">
                    <span class="label">Name:</span>
                    <span class="value"><?php echo htmlspecialchars($customer_name); ?></span>
                </div>
                <div class="row">
                    <span class="label">Email:</span>
                    <span class="value"><?php echo htmlspecialchars($customer_email); ?></span>
                </div>
                <div class="row">
                    <span class="label">Phone:</span>
                    <span class="value"><?php echo htmlspecialchars($customer_phone); ?></span>
                </div>
            </div>
            
            <!-- Booking Details -->
            <div class="section">
                <h3>🚗 Booking Details</h3>
                <div class="row">
                    <span class="label">Vehicle:</span>
                    <span class="value"><?php echo htmlspecialchars($vehicle_brand . ' ' . $vehicle_model); ?> (<?php echo htmlspecialchars($vehicle_reg); ?>)</span>
                </div>
                <div class="row">
                    <span class="label">Vehicle Specs:</span>
                    <span class="value"><?php echo $vehicle_year; ?> | <?php echo $vehicle_transmission; ?> | <?php echo $vehicle_seats; ?> seats | <?php echo $vehicle_fuel; ?></span>
                </div>
                <div class="row">
                    <span class="label">Pickup Date:</span>
                    <span class="value"><?php echo date('F d, Y', strtotime($pickup_date)); ?></span>
                </div>
                <div class="row">
                    <span class="label">Return Date:</span>
                    <span class="value"><?php echo date('F d, Y', strtotime($return_date)); ?></span>
                </div>
                <div class="row">
                    <span class="label">Duration:</span>
                    <span class="value"><?php echo $total_days; ?> days</span>
                </div>
                <div class="row">
                    <span class="label">Pickup Location:</span>
                    <span class="value"><?php echo htmlspecialchars($pickup_location); ?></span>
                </div>
            </div>
            
            <!-- Additional Services Section -->
            <?php if(!empty($services)): ?>
            <div class="section">
                <h3>🛎️ Additional Services</h3>
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
            </div>
            <?php endif; ?>
            
            <!-- Payment Details -->
            <div class="section">
                <h3>💰 Payment Details</h3>
                <div class="row">
                    <span class="label">Daily Rate:</span>
                    <span class="value">KES <?php echo number_format($daily_rate, 2); ?> × <?php echo $total_days; ?> days</span>
                </div>
                <div class="row">
                    <span class="label">Vehicle Rental Total:</span>
                    <span class="value">KES <?php echo number_format($vehicle_total, 2); ?></span>
                </div>
                <?php if($services_total > 0): ?>
                <div class="row">
                    <span class="label">Additional Services:</span>
                    <span class="value">KES <?php echo number_format($services_total, 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="row total-row">
                    <span class="label">Total Amount:</span>
                    <span class="value">KES <?php echo number_format($total_amount, 2); ?></span>
                </div>
                
                <div class="row">
                    <span class="label">Deposit Paid (50%):</span>
                    <span class="value">KES <?php echo number_format($deposit_amount, 2); ?></span>
                </div>
                <div class="row">
                    <span class="label">Remaining Balance:</span>
                    <span class="value">KES <?php echo number_format($remaining_balance, 2); ?></span>
                </div>
                
                <div class="row">
                    <span class="label">Payment Method:</span>
                    <span class="value"><?php echo ucfirst($payment_method); ?></span>
                </div>
                
                <div class="row">
                    <span class="label">Payment Status:</span>
                    <span class="value status-paid">✓ Paid</span>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Thank you for choosing Urban Wheels!</p>
            <p>For inquiries, contact us at info@urbanwheels.com | +254 700 000 000</p>
            <p style="margin-top: 10px;">This is a computer-generated receipt. No signature required.</p>
            <div class="no-print">
                <button onclick="window.print()">🖨️ Print Receipt</button>
                <button onclick="window.location.href='my_bookings.php'">← Back to My Bookings</button>
            </div>
        </div>
    </div>
</body>
</html>