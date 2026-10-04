<?php
session_start();
require_once 'customer/auth_check.php';
require_once 'config/database.php';
// ... rest of your process_booking.php code
?>
<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $vehicle_id = (int)$_POST['vehicle_id'];
    $pickup_date = $_POST['pickup_date'];
    $return_date = $_POST['return_date'];
    $pickup_location = mysqli_real_escape_string($conn, $_POST['pickup_location']);
    $driver_license = mysqli_real_escape_string($conn, $_POST['driver_license']);
    $id_number = mysqli_real_escape_string($conn, $_POST['id_number']);
    $special_requests = mysqli_real_escape_string($conn, $_POST['special_requests']);
    $daily_rate = (float)$_POST['daily_rate'];
    $selected_services = json_decode($_POST['selected_services'], true);
    
    // Calculate days and total amount
    $pickup = new DateTime($pickup_date);
    $return = new DateTime($return_date);
    $total_days = $pickup->diff($return)->days;
    $vehicle_total = $total_days * $daily_rate;
    
    // Calculate services total
    $services_total = 0;
    if($selected_services) {
        foreach($selected_services as $service) {
            if($service['priceType'] == 'per_day') {
                $services_total += $service['price'] * $total_days;
            } else {
                $services_total += $service['price'];
            }
        }
    }
    
    $total_amount = $vehicle_total + $services_total;
    
    // Check vehicle availability
    $check = mysqli_query($conn, "SELECT * FROM bookings WHERE vehicle_id = $vehicle_id 
        AND status IN ('pending', 'confirmed', 'active') 
        AND ((pickup_date BETWEEN '$pickup_date' AND '$return_date') 
        OR (return_date BETWEEN '$pickup_date' AND '$return_date'))");
    
    if(mysqli_num_rows($check) > 0) {
        $_SESSION['booking_error'] = "Vehicle not available for selected dates.";
        header("Location: vehicles.php");
        exit();
    }
    
    // Generate booking number
    $booking_number = 'BK' . date('Ymd') . rand(1000, 9999);
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
        
        // Insert selected services
        if($selected_services) {
            foreach($selected_services as $service) {
                $service_id = $service['id'];
                $service_price = $service['price'];
                $service_total = ($service['priceType'] == 'per_day') ? $service_price * $total_days : $service_price;
                
                mysqli_query($conn, "INSERT INTO booking_services (booking_id, service_id, quantity, price, total_price) 
                                    VALUES ($booking_id, $service_id, 1, $service_price, $service_total)");
            }
        }
        
        // Store in session for payment
        $_SESSION['pending_booking_id'] = $booking_id;
        $_SESSION['pending_booking_amount'] = $total_amount;
        $_SESSION['pending_booking_number'] = $booking_number;
        $_SESSION['pending_booking_days'] = $total_days;
        
        // Clear selected services from session
        unset($_SESSION['selected_services']);
        
        // Redirect to payment page
        header("Location: proceed_payment.php?id=" . $booking_id);
        exit();
    } else {
        $_SESSION['booking_error'] = "Booking failed: " . mysqli_error($conn);
        header("Location: vehicles.php");
        exit();
    }
}
?>