<?php
session_start();
require_once 'config/database.php';

// Only allow admin access
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    die("Admin access only");
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

echo "<h1>Check Booking Services</h1>";

if($booking_id > 0) {
    // Check if booking exists
    $booking_check = mysqli_query($conn, "SELECT * FROM bookings WHERE id = $booking_id");
    if(mysqli_num_rows($booking_check) == 0) {
        echo "<p style='color:red'>Booking ID $booking_id not found!</p>";
    } else {
        $booking = mysqli_fetch_assoc($booking_check);
        echo "<h2>Booking #{$booking['booking_number']}</h2>";
        
        // Check booking_services table
        $services_query = "SELECT * FROM booking_services WHERE booking_id = $booking_id";
        $services_result = mysqli_query($conn, $services_query);
        
        if(mysqli_num_rows($services_result) > 0) {
            echo "<h3 style='color:green'>Services found in booking_services table:</h3>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>ID</th><th>Service Name</th><th>Price</th><th>Price Type</th><th>Total Price</th></tr>";
            while($service = mysqli_fetch_assoc($services_result)) {
                echo "<tr>";
                echo "<td>{$service['id']}</td>";
                echo "<td>{$service['service_name']}</td>";
                echo "<td>KES {$service['price']}</td>";
                echo "<td>{$service['price_type']}</td>";
                echo "<td>KES {$service['total_price']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<h3 style='color:red'>No services found in booking_services table for this booking!</h3>";
        }
    }
}

echo "<br><br>";
echo "<form method='GET'>";
echo "<label>Enter Booking ID: </label>";
echo "<input type='number' name='id' value='$booking_id'>";
echo "<button type='submit'>Check</button>";
echo "</form>";

echo "<br><br>";
echo "<a href='admin/bookings.php'>Back to Admin Bookings</a>";
?>