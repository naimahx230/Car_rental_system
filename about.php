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

// Get about us content
$about_content = [];
$content_result = mysqli_query($conn, "SELECT * FROM about_us ORDER BY display_order");
while($row = mysqli_fetch_assoc($content_result)) {
    $about_content[$row['section_name']] = $row;
}

// Get company statistics
$stats = mysqli_query($conn, "SELECT * FROM company_stats ORDER BY display_order");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Urban Wheels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f5f5; }
        
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
        
        .hero {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 60px 5%;
            text-align: center;
        }
        .hero h1 { font-size: 42px; margin-bottom: 15px; }
        
        .container { max-width: 1200px; margin: 60px auto; padding: 0 20px; }
        
        .mv-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 40px;
            margin-bottom: 60px;
        }
        .mv-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .mv-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .mv-icon i { font-size: 35px; color: white; }
        .mv-card h3 { font-size: 24px; margin-bottom: 15px; color: #333; }
        .mv-card p { color: #666; line-height: 1.6; }
        
        .stats-section {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            padding: 60px 5%;
            border-radius: 30px;
            margin-bottom: 60px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }
        .stat-number { font-size: 42px; font-weight: 800; color: #FFD700; }
        .stat-label { color: rgba(255,255,255,0.8); margin-top: 10px; }
        
        .footer {
            background: #1a1a2e;
            color: white;
            text-align: center;
            padding: 25px;
            margin-top: 60px;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .hero h1 { font-size: 32px; }
            .navbar { flex-direction: column; text-align: center; }
            .nav-links { justify-content: center; }
            .mv-section { grid-template-columns: 1fr; }
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
        <a href="about.php" style="color: #FFD700;">About</a>
        <a href="contact.php">Contact</a>
        <a href="my_bookings.php">My Bookings</a>
        <a href="cart.php" class="cart-link">
            <i class="fas fa-shopping-cart"></i> Cart
            <span class="cart-count" id="cartCount">0</span>
        </a>
        <span style="color: white;">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<div class="hero">
    <h1>About Urban Wheels</h1>
    <p>Kenya's Premier Car Rental Service</p>
</div>

<div class="container">
    <div class="mv-section">
        <div class="mv-card">
            <div class="mv-icon"><i class="fas fa-bullseye"></i></div>
            <h3>Our Mission</h3>
            <p>To provide reliable, affordable, and convenient car rental services that exceed customer expectations.</p>
        </div>
        <div class="mv-card">
            <div class="mv-icon"><i class="fas fa-eye"></i></div>
            <h3>Our Vision</h3>
            <p>To become the most trusted and preferred car rental company in Kenya.</p>
        </div>
    </div>

    <div class="stats-section">
        <div class="stats-grid">
            <?php while($stat = mysqli_fetch_assoc($stats)): ?>
            <div>
                <div class="stat-number"><?php echo $stat['stat_number']; ?></div>
                <div class="stat-label"><?php echo $stat['stat_label']; ?></div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
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