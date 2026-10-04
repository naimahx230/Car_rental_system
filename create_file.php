<?php
// This script will create my_bookings.php
$filename = 'my_bookings.php';

$content = '<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION["user_id"];

// Get all bookings for the logged-in user
$bookings_query = "SELECT b.*, v.brand, v.model, v.image, v.registration_number 
                   FROM bookings b 
                   JOIN vehicles v ON b.vehicle_id = v.id 
                   WHERE b.user_id = ? 
                   ORDER BY b.created_at DESC";
$stmt = mysqli_prepare($conn, $bookings_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$bookings_result = mysqli_stmt_get_result($stmt);
$bookings = [];

while($row = mysqli_fetch_assoc($bookings_result)) {
    $payment_query = "SELECT * FROM payments WHERE booking_id = ? ORDER BY id DESC LIMIT 1";
    $payment_stmt = mysqli_prepare($conn, $payment_query);
    mysqli_stmt_bind_param($payment_stmt, "i", $row["id"]);
    mysqli_stmt_execute($payment_stmt);
    $payment_result = mysqli_stmt_get_result($payment_stmt);
    $row["payment"] = mysqli_fetch_assoc($payment_result);
    $bookings[] = $row;
    mysqli_stmt_close($payment_stmt);
}
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Urban Wheels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: "Poppins", sans-serif; background: #f5f5f5; }
        .navbar { background: #1a1a2e; padding: 15px 5%; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: 800; background: linear-gradient(135deg, #FFD700, #FFA500); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-links { display: flex; gap: 25px; align-items: center; }
        .nav-links a { color: white; text-decoration: none; font-weight: 500; }
        .logout-btn { background: #dc3545; padding: 8px 20px; border-radius: 25px; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 32px; color: #333; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 20px; border-radius: 15px; text-align: center; }
        .stat-number { font-size: 32px; font-weight: 700; color: #667eea; }
        .bookings-grid { display: grid; gap: 25px; }
        .booking-card { background: white; border-radius: 20px; overflow: hidden; }
        .booking-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px 25px; display: flex; justify-content: space-between; }
        .status-badge { padding: 6px 15px; border-radius: 25px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #ffc107; color: #000; }
        .status-confirmed { background: #17a2b8; color: #fff; }
        .status-active { background: #28a745; color: #fff; }
        .status-completed { background: #6c757d; color: #fff; }
        .status-cancelled { background: #dc3545; color: #fff; }
        .booking-body { padding: 25px; display: flex; gap: 25px; flex-wrap: wrap; }
        .vehicle-image { width: 100px; height: 100px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 12px; display: flex; align-items: center; justify-content: center; }
        .vehicle-image i { font-size: 40px; color: white; }
        .booking-details { flex: 1; }
        .vehicle-name { font-size: 20px; font-weight: 700; margin-bottom: 10px; }
        .booking-info { display: flex; gap: 15px; flex-wrap: wrap; }
        .info-item { display: flex; align-items: center; gap: 5px; font-size: 13px; color: #666; }
        .amount { font-size: 24px; font-weight: 700; color: #28a745; }
        .booking-footer { background: #f8f9fa; padding: 15px 25px; display: flex; justify-content: space-between; }
        .btn-view { background: #667eea; color: white; padding: 8px 20px; border-radius: 8px; text-decoration: none; }
        .btn-cancel { background: #dc3545; color: white; padding: 8px 20px; border-radius: 8px; text-decoration: none; }
        .empty-state { text-align: center; padding: 60px; background: white; border-radius: 20px; }
        .empty-state i { font-size: 80px; color: #ccc; margin-bottom: 20px; }
        .btn-browse { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; text-decoration: none; border-radius: 10px; }
        @media (max-width: 768px) { .booking-body { flex-direction: column; text-align: center; } .booking-info { justify-content: center; } .booking-footer { flex-direction: column; align-items: center; gap: 10px; } }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="logo">URBAN WHEELS</div>
    <div class="nav-links">
        <a href="vehicles.php">Vehicles</a>
        <a href="my_bookings.php" style="color: #FFD700;">My Bookings</a>
        <a href="contact.php">Contact</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>
<div class="container">
    <div class="page-header">
        <h1>My Bookings</h1>
        <p>View and manage all your vehicle rental bookings</p>
    </div>
    <?php if(count($bookings) > 0): 
        $total_bookings = count($bookings);
    ?>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-number"><?php echo $total_bookings; ?></div><div class="stat-label">Total Bookings</div></div>
    </div>
    <div class="bookings-grid">
        <?php foreach($bookings as $booking): ?>
        <div class="booking-card">
            <div class="booking-header">
                <div class="booking-number">Booking #<?php echo $booking["booking_number"]; ?></div>
                <div class="status-badge status-<?php echo $booking["status"]; ?>"><?php echo ucfirst($booking["status"]); ?></div>
            </div>
            <div class="booking-body">
                <div class="vehicle-image"><i class="fas fa-car"></i></div>
                <div class="booking-details">
                    <h3 class="vehicle-name"><?php echo $booking["brand"] . " " . $booking["model"]; ?></h3>
                    <div class="booking-info">
                        <span class="info-item"><i class="fas fa-calendar"></i> Pickup: <?php echo date("M d, Y", strtotime($booking["pickup_date"])); ?></span>
                        <span class="info-item"><i class="fas fa-calendar-check"></i> Return: <?php echo date("M d, Y", strtotime($booking["return_date"])); ?></span>
                        <span class="info-item"><i class="fas fa-map-marker-alt"></i> <?php echo $booking["pickup_location"]; ?></span>
                    </div>
                </div>
                <div class="booking-amount">
                    <div class="amount">KES <?php echo number_format($booking["total_amount"], 2); ?></div>
                </div>
            </div>
            <div class="booking-footer">
                <div>Booked on: <?php echo date("M d, Y", strtotime($booking["created_at"])); ?></div>
                <div class="action-buttons">
                    <a href="booking_details.php?id=<?php echo $booking["id"]; ?>" class="btn-view">View Details</a>
                    <?php if($booking["status"] == "pending" || $booking["status"] == "confirmed"): ?>
                        <a href="cancel_booking.php?id=<?php echo $booking["id"]; ?>" class="btn-cancel" onclick="return confirm(\'Cancel this booking?\')">Cancel</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-calendar-alt"></i>
        <h3>No Bookings Yet</h3>
        <p>You haven\'t made any vehicle bookings. Browse our fleet and book your first ride!</p>
        <a href="vehicles.php" class="btn-browse">Browse Vehicles →</a>
    </div>
    <?php endif; ?>
</div>
</body>
</html>';

if(file_put_contents($filename, $content)) {
    echo "<h1 style='color:green'>✓ File created successfully: $filename</h1>";
    echo "<p>Location: " . realpath($filename) . "</p>";
    echo "<a href='$filename'>Click here to view my_bookings.php</a>";
} else {
    echo "<h1 style='color:red'>✗ Failed to create file. Check folder permissions.</h1>";
}
?>