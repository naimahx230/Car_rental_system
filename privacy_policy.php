<?php
session_start();
require_once 'config/database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Urban Wheels</title>
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
        .login-btn {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #1a1a2e !important;
            padding: 8px 25px;
            border-radius: 25px;
        }
        .logout-btn {
            background: #dc3545;
            padding: 8px 20px;
            border-radius: 25px;
        }
        
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        
        .privacy-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .privacy-header h1 { font-size: 36px; margin-bottom: 10px; }
        
        .terms-nav {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 30px;
            justify-content: center;
        }
        .terms-nav a {
            background: white;
            padding: 12px 25px;
            border-radius: 50px;
            text-decoration: none;
            color: #667eea;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .terms-nav a:hover, .terms-nav a.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .privacy-content {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .section {
            margin-bottom: 35px;
            border-bottom: 1px solid #eee;
            padding-bottom: 25px;
        }
        .section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .section h2 {
            color: #667eea;
            font-size: 24px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .section p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        .section ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }
        .section li {
            color: #666;
            line-height: 1.6;
            margin-bottom: 8px;
        }
        .info-box {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid #2196F3;
        }
        
        .footer {
            background: #1a1a2e;
            color: white;
            text-align: center;
            padding: 30px;
            margin-top: 60px;
        }
        
        @media (max-width: 768px) {
            .privacy-header h1 { font-size: 28px; }
            .privacy-content { padding: 25px; }
            .section h2 { font-size: 20px; }
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
        <a href="terms_conditions.php">Terms</a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="my_bookings.php">My Bookings</a>
            <a href="cart.php">Cart</a>
            <span style="color: white;">Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        <?php else: ?>
            <a href="index.php" class="login-btn">Sign Up / Login</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
    <div class="privacy-header">
        <h1><i class="fas fa-lock"></i> Privacy Policy</h1>
        <p>How we collect, use, and protect your information</p>
    </div>

    <div class="terms-nav">
        <a href="terms_conditions.php">Terms & Conditions</a>
        <a href="privacy_policy.php" class="active">Privacy Policy</a>
        <a href="refund_policy.php">Refund Policy</a>
    </div>

    <div class="privacy-content">
        <!-- Section 1: Information Collection -->
        <div class="section">
            <h2><i class="fas fa-database"></i> Information Collection</h2>
            <p>The system may collect:</p>
            <ul>
                <li>Full name</li>
                <li>Email address</li>
                <li>Phone number</li>
                <li>Booking information</li>
                <li>Payment details</li>
            </ul>
        </div>

        <!-- Section 2: Purpose of Data Collection -->
        <div class="section">
            <h2><i class="fas fa-bullseye"></i> Purpose of Data Collection</h2>
            <p>Collected information is used for:</p>
            <ul>
                <li>Vehicle booking management</li>
                <li>Payment processing</li>
                <li>Customer support</li>
                <li>System security</li>
            </ul>
        </div>

        <!-- Section 3: Data Protection -->
        <div class="section">
            <h2><i class="fas fa-shield-alt"></i> Data Protection</h2>
            <div class="info-box">
                <i class="fas fa-info-circle"></i> Urban Wheels implements reasonable security measures to protect user information from unauthorized access.
            </div>
        </div>

        <!-- Section 4: Third-Party Sharing -->
        <div class="section">
            <h2><i class="fas fa-share-alt"></i> Third-Party Sharing</h2>
            <p>User information shall not be sold or shared with unauthorized third parties unless required by law.</p>
        </div>

        <!-- Section 5: Cookies and Tracking -->
        <div class="section">
            <h2><i class="fas fa-cookie-bite"></i> Cookies and Tracking</h2>
            <p>The system may use cookies to improve user experience and maintain login sessions.</p>
        </div>

        <!-- Section 6: User Rights -->
        <div class="section">
            <h2><i class="fas fa-user-check"></i> User Rights</h2>
            <p>Users may request:</p>
            <ul>
                <li>Account updates</li>
                <li>Data correction</li>
                <li>Account deletion</li>
            </ul>
        </div>
    </div>
</div>

<footer class="footer">
    <p>&copy; 2026 Urban Wheels Car Rental. All rights reserved. | Nairobi, Kenya</p>
</footer>

</body>
</html>