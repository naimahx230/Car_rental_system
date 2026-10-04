<?php
// Use customer session name
session_name('CUSTOMER_SESSION');
session_start();

// Customer authentication check
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

$user_id = $_SESSION["user_id"];

// Get all bookings for the logged-in user with services info
$bookings_query = "SELECT b.*, v.brand, v.model, v.image, v.registration_number,
                   (SELECT COUNT(*) FROM booking_services WHERE booking_id = b.id) as services_count,
                   (SELECT SUM(total_price) FROM booking_services WHERE booking_id = b.id) as services_total
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

$cancel_success = isset($_SESSION['cancel_success']) ? $_SESSION['cancel_success'] : '';
unset($_SESSION['cancel_success']);
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
        .nav-links {
            display: flex;
            gap: 25px;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }
        .nav-links a:hover { color: #FFD700; }
        .logout-btn {
            background: #dc3545;
            padding: 8px 20px;
            border-radius: 25px;
        }
        .cart-link { position: relative; }
        .cart-count {
            position: absolute;
            top: -8px;
            right: -12px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: 600;
            display: none;
        }
        
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 32px; color: #333; }
        .page-header p { color: #666; }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-number { font-size: 32px; font-weight: 700; color: #667eea; }
        .stat-label { color: #666; font-size: 14px; margin-top: 5px; }
        
        .bookings-grid { display: grid; gap: 25px; }
        .booking-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .booking-card:hover { transform: translateY(-3px); }
        .booking-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .booking-number { font-weight: 600; font-size: 16px; }
        .status-badge { padding: 6px 15px; border-radius: 25px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #ffc107; color: #000; }
        .status-confirmed { background: #17a2b8; color: #fff; }
        .status-active { background: #28a745; color: #fff; }
        .status-completed { background: #6c757d; color: #fff; }
        .status-cancelled { background: #dc3545; color: #fff; }
        
        .booking-body { padding: 25px; display: flex; gap: 25px; flex-wrap: wrap; }
        .vehicle-image {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .vehicle-image img { width: 100%; height: 100%; object-fit: cover; }
        .vehicle-image i { font-size: 40px; color: white; }
        .booking-details { flex: 1; }
        .vehicle-name { font-size: 20px; font-weight: 700; margin-bottom: 10px; }
        .booking-info { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 8px; }
        .info-item { display: flex; align-items: center; gap: 5px; font-size: 13px; color: #666; }
        .services-info { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 5px; padding-top: 8px; border-top: 1px solid #eee; }
        .services-info .info-item { color: #28a745; }
        .amount { font-size: 24px; font-weight: 700; color: #28a745; }
        
        .booking-footer {
            background: #f8f9fa;
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .btn-view {
            background: #667eea;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-view:hover { background: #5a67d8; transform: translateY(-2px); }
        .btn-cancel {
            background: #dc3545;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-cancel:hover { background: #c82333; transform: translateY(-2px); }
        .btn-review {
            background: #ffc107;
            color: #000;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-review:hover { background: #e0a800; transform: translateY(-2px); }
        .btn-pay {
            background: #28a745;
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-pay:hover { background: #218838; transform: translateY(-2px); }
        
        .empty-state {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 20px;
        }
        .empty-state i { font-size: 80px; color: #ccc; margin-bottom: 20px; }
        .empty-state h3 { font-size: 24px; color: #333; margin-bottom: 10px; }
        .btn-browse {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .footer {
            background: #1a1a2e;
            color: white;
            text-align: center;
            padding: 25px;
            margin-top: 60px;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .booking-body { flex-direction: column; text-align: center; }
            .booking-info { justify-content: center; }
            .services-info { justify-content: center; }
            .booking-footer { flex-direction: column; align-items: center; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .navbar { flex-direction: column; text-align: center; }
            .nav-links { justify-content: center; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">URBAN WHEELS</div>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="vehicles.php">Vehicles</a>
        <a href="services.php">Services</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        <a href="my_bookings.php" style="color: #FFD700;">My Bookings</a>
        <a href="cart.php" class="cart-link">
            <i class="fas fa-shopping-cart"></i> Cart
            <span class="cart-count" id="cartCount">0</span>
        </a>
        <span style="color: white;">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>My Bookings</h1>
        <p>View and manage all your vehicle rental bookings</p>
    </div>
    
    <?php if($cancel_success): ?>
    <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $cancel_success; ?></div>
    <?php endif; ?>
    
    <?php if(count($bookings) > 0): 
        $total_bookings = count($bookings);
        $total_spent = 0;
        $upcoming_bookings = 0;
        foreach($bookings as $booking) {
            if($booking['payment_status'] == 'paid') $total_spent += $booking['total_amount'];
            if($booking['status'] == 'confirmed' || $booking['status'] == 'active') $upcoming_bookings++;
        }
    ?>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-number"><?php echo $total_bookings; ?></div><div class="stat-label">Total Bookings</div></div>
        <div class="stat-card"><div class="stat-number"><?php echo $upcoming_bookings; ?></div><div class="stat-label">Upcoming Bookings</div></div>
        <div class="stat-card"><div class="stat-number">KES <?php echo number_format($total_spent, 0); ?></div><div class="stat-label">Total Spent</div></div>
    </div>
    
    <div class="bookings-grid">
        <?php foreach($bookings as $booking): ?>
        <div class="booking-card">
            <div class="booking-header">
                <div class="booking-number"><i class="fas fa-ticket-alt"></i> Booking #<?php echo $booking["booking_number"]; ?></div>
                <div class="status-badge status-<?php echo $booking["status"]; ?>"><?php echo ucfirst($booking["status"]); ?></div>
            </div>
            <div class="booking-body">
                <div class="vehicle-image">
                    <?php if($booking['image'] && file_exists($booking['image'])): ?>
                        <img src="<?php echo $booking['image']; ?>" alt="<?php echo $booking['brand']; ?>">
                    <?php else: ?>
                        <i class="fas fa-car"></i>
                    <?php endif; ?>
                </div>
                <div class="booking-details">
                    <h3 class="vehicle-name"><?php echo $booking["brand"] . " " . $booking["model"]; ?></h3>
                    <div class="booking-info">
                        <span class="info-item"><i class="fas fa-calendar"></i> Pickup: <?php echo date("M d, Y", strtotime($booking["pickup_date"])); ?></span>
                        <span class="info-item"><i class="fas fa-calendar-check"></i> Return: <?php echo date("M d, Y", strtotime($booking["return_date"])); ?></span>
                        <span class="info-item"><i class="fas fa-map-marker-alt"></i> <?php echo $booking["pickup_location"]; ?></span>
                        <span class="info-item"><i class="fas fa-clock"></i> <?php echo $booking["total_days"]; ?> days</span>
                    </div>
                    <?php if($booking['services_count'] > 0): ?>
                    <div class="services-info">
                        <span class="info-item"><i class="fas fa-concierge-bell"></i> <?php echo $booking['services_count']; ?> additional service(s)</span>
                        <span class="info-item"><i class="fas fa-coins"></i> +KES <?php echo number_format($booking['services_total'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="booking-amount"><div class="amount">KES <?php echo number_format($booking["total_amount"], 2); ?></div><small>Total amount</small></div>
            </div>
            <div class="booking-footer">
                <div><i class="fas fa-calendar-alt"></i> Booked on: <?php echo date("M d, Y", strtotime($booking["created_at"])); ?></div>
                <div class="action-buttons">
                    <a href="booking_details.php?id=<?php echo $booking["id"]; ?>" class="btn-view"><i class="fas fa-eye"></i> View Details</a>
                    <?php if(($booking["status"] == "pending" || $booking["status"] == "confirmed") && $booking["payment_status"] != "paid"): ?>
                        <a href="payment.php?id=<?php echo $booking["id"]; ?>" class="btn-pay"><i class="fas fa-credit-card"></i> Pay Now</a>
                    <?php endif; ?>
                    <?php if($booking["status"] == "pending" || $booking["status"] == "confirmed"): ?>
                        <a href="cancel_booking.php?id=<?php echo $booking["id"]; ?>" class="btn-cancel" onclick="return confirm('Are you sure you want to cancel this booking?')"><i class="fas fa-times-circle"></i> Cancel</a>
                    <?php endif; ?>
                    <?php if($booking["status"] == "completed"): ?>
                        <a href="add_review.php?booking_id=<?php echo $booking["id"]; ?>" class="btn-review"><i class="fas fa-star"></i> Write Review</a>
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
        <p>You haven't made any vehicle bookings. Browse our fleet and book your first ride!</p>
        <a href="vehicles.php" class="btn-browse">Browse Vehicles →</a>
    </div>
    <?php endif; ?>
</div>

<footer class="footer">
    <p>© 2026 Urban Wheels Car Rental. All rights reserved. | Nairobi, Kenya</p>
</footer>

<script>
function updateCartCount() {
    fetch('get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            const count = data.count;
            const cartCountSpan = document.getElementById('cartCount');
            if(cartCountSpan) {
                cartCountSpan.textContent = count;
                cartCountSpan.style.display = count > 0 ? 'inline-block' : 'none';
            }
        });
}
document.addEventListener('DOMContentLoaded', function() { updateCartCount(); });
</script>

</body>
</html>