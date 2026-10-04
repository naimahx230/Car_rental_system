<?php
// Use customer session name
session_name('CUSTOMER_SESSION');
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: index.php");
    exit();
}

// If admin is logged in, redirect to admin dashboard
if($_SESSION['user_role'] == 'admin') {
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

$vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$vehicle = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM vehicles WHERE id = $vehicle_id AND status = 'available'"));

if(!$vehicle) {
    header("Location: vehicles.php");
    exit();
}

$error = '';

// Get cart items from session
$cart_items = isset($_SESSION['service_cart']) ? $_SESSION['service_cart'] : [];

// Handle booking submission
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if terms are accepted
    if(!isset($_POST['accept_terms'])) {
        $error = "You must agree to the Terms and Conditions, Privacy Policy, and Refund Policy to proceed.";
    } else {
        $pickup_date = mysqli_real_escape_string($conn, $_POST['pickup_date']);
        $return_date = mysqli_real_escape_string($conn, $_POST['return_date']);
        $pickup_location = mysqli_real_escape_string($conn, $_POST['pickup_location']);
        $driver_license = mysqli_real_escape_string($conn, $_POST['driver_license']);
        $id_number = mysqli_real_escape_string($conn, $_POST['id_number']);
        $special_requests = mysqli_real_escape_string($conn, $_POST['special_requests']);
        
        // Validate dates
        if(strtotime($pickup_date) < strtotime(date('Y-m-d'))) {
            $error = "Pickup date cannot be in the past.";
        } elseif(strtotime($return_date) <= strtotime($pickup_date)) {
            $error = "Return date must be after pickup date.";
        }
        
        if(empty($error)) {
            // Calculate days and total
            $pickup = new DateTime($pickup_date);
            $return = new DateTime($return_date);
            $total_days = $pickup->diff($return)->days;
            $total_amount = $total_days * $vehicle['daily_rate'];
            
            // Add services total if any services are selected
            $selected_services = [];
            if(isset($_SESSION['service_cart']) && !empty($_SESSION['service_cart'])) {
                $selected_services = $_SESSION['service_cart'];
                foreach($selected_services as $service) {
                    if($service['priceType'] == 'per_day') {
                        $total_amount += $service['price'] * $total_days;
                    } else {
                        $total_amount += $service['price'];
                    }
                }
            }
            
            // Check availability
            $check = mysqli_query($conn, "SELECT * FROM bookings WHERE vehicle_id = $vehicle_id 
                AND status NOT IN ('cancelled', 'completed')
                AND ((pickup_date BETWEEN '$pickup_date' AND '$return_date') 
                OR (return_date BETWEEN '$pickup_date' AND '$return_date')
                OR (pickup_date <= '$pickup_date' AND return_date >= '$return_date'))");
            
            if(mysqli_num_rows($check) > 0) {
                $error = "Vehicle is not available for the selected dates. Please choose different dates.";
            } else {
                $booking_number = generateBookingNumber();
                $payment_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Insert booking
                $insert = "INSERT INTO bookings (booking_number, user_id, vehicle_id, pickup_date, return_date, 
                           total_days, total_amount, status, payment_status, pickup_location, driver_license, 
                           id_number, special_requests, payment_expiry) 
                           VALUES ('$booking_number', {$_SESSION['user_id']}, $vehicle_id, '$pickup_date', '$return_date', 
                           $total_days, $total_amount, 'pending', 'pending', '$pickup_location', '$driver_license', 
                           '$id_number', '$special_requests', '$payment_expiry')";
                
                if(mysqli_query($conn, $insert)) {
                    $booking_id = mysqli_insert_id($conn);
                    
                    // Save services to booking_services table
                    if(!empty($selected_services)) {
                        foreach($selected_services as $service) {
                            $service_total = ($service['priceType'] == 'per_day') ? $service['price'] * $total_days : $service['price'];
                            $service_query = "INSERT INTO booking_services (booking_id, service_id, service_name, price, price_type, quantity, total_price) 
                                             VALUES ($booking_id, {$service['id']}, '{$service['name']}', {$service['price']}, '{$service['priceType']}', 1, $service_total)";
                            mysqli_query($conn, $service_query);
                        }
                        unset($_SESSION['service_cart']);
                    }
                    
                    $_SESSION['pending_booking_id'] = $booking_id;
                    $_SESSION['pending_booking_amount'] = $total_amount;
                    $_SESSION['pending_booking_number'] = $booking_number;
                    
                    header("Location: payment.php?id=" . $booking_id);
                    exit();
                } else {
                    $error = "Booking failed: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Vehicle - Urban Wheels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        
        .navbar { background: rgba(0,0,0,0.95); padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .logo { font-size: 24px; font-weight: 800; background: linear-gradient(135deg, #FFD700, #FFA500); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .back-link { color: white; text-decoration: none; padding: 8px 20px; background: rgba(255,255,255,0.2); border-radius: 25px; }
        .cart-link { position: relative; color: white; text-decoration: none; padding: 8px 20px; background: rgba(255,255,255,0.2); border-radius: 25px; }
        .cart-count { position: absolute; top: -8px; right: -8px; background: #dc3545; color: white; border-radius: 50%; padding: 2px 6px; font-size: 10px; font-weight: 600; display: none; }
        
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .booking-wrapper { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        
        .vehicle-details { background: linear-gradient(135deg, #667eea, #764ba2); padding: 40px; color: white; }
        .vehicle-image { text-align: center; margin-bottom: 30px; }
        .vehicle-image img { max-width: 100%; height: auto; border-radius: 10px; }
        .vehicle-image i { font-size: 120px; color: rgba(255,255,255,0.9); }
        .vehicle-title { font-size: 28px; margin-bottom: 15px; }
        .vehicle-specs { display: flex; flex-wrap: wrap; gap: 15px; margin: 20px 0; }
        .spec { background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 25px; font-size: 14px; }
        .vehicle-price { font-size: 36px; font-weight: 700; margin: 20px 0; }
        
        .booking-form { padding: 40px; }
        .booking-form h2 { color: #333; margin-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #333; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px 15px; border: 2px solid #eee; border-radius: 10px; font-family: inherit; transition: border-color 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; }
        .date-range { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        
        .price-breakdown { background: #f8f9fa; padding: 20px; border-radius: 12px; margin: 25px 0; }
        .price-row { display: flex; justify-content: space-between; padding: 10px 0; }
        .total-price { font-size: 20px; font-weight: 700; color: #28a745; border-top: 2px solid #dee2e6; padding-top: 15px; }
        
        .services-section { background: #f8f9fa; padding: 20px; border-radius: 12px; margin: 20px 0; }
        .services-section h4 { margin-bottom: 15px; color: #667eea; }
        .service-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #dee2e6; }
        .service-item:last-child { border-bottom: none; }
        
        .btn-book { width: 100%; padding: 14px; background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; border-radius: 10px; font-size: 18px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin-top: 20px; }
        .btn-book:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(40,167,69,0.4); }
        
        .alert { padding: 12px; border-radius: 10px; margin-bottom: 20px; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
        }
        .checkbox-label input { width: auto; margin-top: 3px; }
        .checkbox-label a { color: #28a745; text-decoration: none; }
        .checkbox-label a:hover { text-decoration: underline; }
        
        @media (max-width: 768px) {
            .booking-wrapper { grid-template-columns: 1fr; }
            .date-range { grid-template-columns: 1fr; }
            .navbar { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">URBAN WHEELS</div>
    <div>
        <a href="vehicles.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Vehicles</a>
        <a href="cart.php" class="cart-link">
            <i class="fas fa-shopping-cart"></i> Cart
            <span class="cart-count" id="cartCount"><?php echo count($cart_items); ?></span>
        </a>
    </div>
</nav>

<div class="container">
    <div class="booking-wrapper">
        <div class="vehicle-details">
            <div class="vehicle-image">
                <?php if($vehicle['image'] && file_exists($vehicle['image'])): ?>
                    <img src="<?php echo $vehicle['image']; ?>" alt="<?php echo $vehicle['brand']; ?>">
                <?php else: ?>
                    <i class="fas fa-car"></i>
                <?php endif; ?>
            </div>
            <h1 class="vehicle-title"><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></h1>
            <div class="vehicle-specs">
                <span class="spec"><i class="fas fa-calendar"></i> <?php echo $vehicle['year']; ?></span>
                <span class="spec"><i class="fas fa-cog"></i> <?php echo $vehicle['transmission'] ?: 'Automatic'; ?></span>
                <span class="spec"><i class="fas fa-users"></i> <?php echo $vehicle['seats']; ?> Seats</span>
                <span class="spec"><i class="fas fa-gas-pump"></i> <?php echo $vehicle['fuel_type'] ?: 'Petrol'; ?></span>
            </div>
            <div class="vehicle-price">
                KES <?php echo number_format($vehicle['daily_rate'], 2); ?> <span>/ day</span>
            </div>
        </div>

        <div class="booking-form">
            <h2>Complete Your Booking</h2>
            <p>Fill in the details below to reserve this vehicle</p>

            <?php if($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" id="bookingForm">
                <div class="date-range">
                    <div class="form-group">
                        <label>Pickup Date</label>
                        <input type="date" name="pickup_date" id="pickup_date" required min="<?php echo date('Y-m-d'); ?>" onchange="calculateTotal()">
                    </div>
                    <div class="form-group">
                        <label>Return Date</label>
                        <input type="date" name="return_date" id="return_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" onchange="calculateTotal()">
                    </div>
                </div>

                <div class="form-group">
                    <label>Pickup Location</label>
                    <select name="pickup_location" required>
                        <option value="">Select pickup location</option>
                        <option value="Nairobi CBD">Nairobi CBD</option>
                        <option value="JKIA Airport">JKIA Airport</option>
                        <option value="Westlands">Westlands</option>
                        <option value="Karen">Karen</option>
                        <option value="Mombasa Road">Mombasa Road</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Driver's License Number</label>
                    <input type="text" name="driver_license" placeholder="Enter your driver's license number" required>
                </div>

                <div class="form-group">
                    <label>ID/Passport Number</label>
                    <input type="text" name="id_number" placeholder="National ID or Passport Number" required>
                </div>

                <div class="form-group">
                    <label>Special Requests</label>
                    <textarea name="special_requests" rows="2" placeholder="Any special requirements? (e.g., child seat, GPS, etc.)"></textarea>
                </div>

                <div class="services-section" id="servicesSection" style="<?php echo !empty($cart_items) ? 'display: block;' : 'display: none;'; ?>">
                    <h4><i class="fas fa-concierge-bell"></i> Selected Services</h4>
                    <div id="selectedServicesList">
                        <?php if(!empty($cart_items)): ?>
                            <?php foreach($cart_items as $service): ?>
                            <div class="service-item">
                                <span><i class="fas fa-check-circle" style="color: #28a745;"></i> <?php echo htmlspecialchars($service['name']); ?></span>
                                <span>KES <?php echo number_format($service['price'], 2); ?> <?php echo $service['priceType'] == 'per_day' ? '/day' : 'one time'; ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="price-breakdown">
                    <div class="price-row">
                        <span>Daily Rate:</span>
                        <span>KES <?php echo number_format($vehicle['daily_rate'], 2); ?></span>
                    </div>
                    <div class="price-row">
                        <span>Number of Days:</span>
                        <span id="daysCount">0</span>
                    </div>
                    <?php if(!empty($cart_items)): ?>
                    <div class="price-row" id="servicesTotalRow">
                        <span>Services Total:</span>
                        <span id="servicesTotal">KES 0</span>
                    </div>
                    <?php endif; ?>
                    <div class="price-row total-price">
                        <span>Total Amount:</span>
                        <span id="totalAmount">KES 0</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="accept_terms" id="accept_terms" required>
                        <span>I have read and agree to the <a href="terms_conditions.php" target="_blank">Terms and Conditions</a>, <a href="privacy_policy.php" target="_blank">Privacy Policy</a>, and <a href="refund_policy.php" target="_blank">Refund Policy</a></span>
                    </label>
                </div>

                <button type="submit" class="btn-book">
                    <i class="fas fa-credit-card"></i> Proceed to Payment
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const dailyRate = <?php echo $vehicle['daily_rate']; ?>;
    const cartServices = <?php echo json_encode($cart_items); ?>;
    
    function calculateTotal() {
        const pickupDate = document.getElementById('pickup_date').value;
        const returnDate = document.getElementById('return_date').value;
        
        if(pickupDate && returnDate) {
            const pickup = new Date(pickupDate);
            const returned = new Date(returnDate);
            const diffTime = Math.abs(returned - pickup);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if(diffDays > 0) {
                let vehicleTotal = diffDays * dailyRate;
                let servicesTotal = 0;
                
                if(cartServices.length > 0) {
                    cartServices.forEach(service => {
                        if(service.priceType === 'per_day') {
                            servicesTotal += service.price * diffDays;
                        } else {
                            servicesTotal += service.price;
                        }
                    });
                }
                
                const grandTotal = vehicleTotal + servicesTotal;
                
                document.getElementById('daysCount').innerHTML = diffDays;
                document.getElementById('totalAmount').innerHTML = 'KES ' + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2});
                
                const servicesTotalSpan = document.getElementById('servicesTotal');
                if(servicesTotalSpan && servicesTotal > 0) {
                    servicesTotalSpan.innerHTML = 'KES ' + servicesTotal.toLocaleString('en-US', {minimumFractionDigits: 2});
                }
            } else {
                document.getElementById('daysCount').innerHTML = '0';
                document.getElementById('totalAmount').innerHTML = 'KES 0';
            }
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        calculateTotal();
        
        const cartCount = document.getElementById('cartCount');
        if(cartCount && cartServices.length > 0) {
            cartCount.style.display = 'inline-block';
        }
    });
</script>

</body>
</html>