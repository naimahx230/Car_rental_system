<?php
session_start();
require_once 'config/database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Policy - Urban Wheels</title>
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
        
        .refund-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .refund-header h1 { font-size: 36px; margin-bottom: 10px; }
        
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
        
        .refund-content {
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
            color: #28a745;
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
        .refund-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .refund-table th, .refund-table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #dee2e6;
        }
        .refund-table th {
            background: #28a745;
            color: white;
        }
        .refund-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .warning-box {
            background: #fff3cd;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
        }
        .info-box {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid #2196F3;
        }
        .danger-box {
            background: #f8d7da;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid #dc3545;
        }
        
        .footer {
            background: #1a1a2e;
            color: white;
            text-align: center;
            padding: 30px;
            margin-top: 60px;
        }
        
        @media (max-width: 768px) {
            .refund-header h1 { font-size: 28px; }
            .refund-content { padding: 25px; }
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
    <div class="refund-header">
        <h1><i class="fas fa-money-bill-wave"></i> Refund Policy</h1>
        <p>Understanding our cancellation and refund process</p>
    </div>

    <div class="terms-nav">
        <a href="terms_conditions.php">Terms & Conditions</a>
        <a href="privacy_policy.php">Privacy Policy</a>
        <a href="refund_policy.php" class="active">Refund Policy</a>
    </div>

    <div class="refund-content">
        <!-- Section 1: Booking Cancellation -->
        <div class="section">
            <h2><i class="fas fa-calendar-times"></i> Booking Cancellation</h2>
            <p>Customers may cancel bookings before the scheduled pickup time.</p>
        </div>

        <!-- Section 2: Refund Eligibility -->
        <div class="section">
            <h2><i class="fas fa-check-circle"></i> Refund Eligibility</h2>
            <table class="refund-table">
                <thead>
                    <tr><th>Situation</th><th>Refund</th></tr>
                </thead>
                <tbody>
                    <tr><td>Cancellation 24+ hours before pickup</td><td><strong>100% refund</strong></td></tr>
                    <tr><td>Cancellation within 12-24 hours</td><td><strong>50% refund</strong></td></tr>
                    <tr><td>Cancellation within 12 hours</td><td><strong>25% refund</strong></td></tr>
                    <tr><td>After pickup time</td><td><strong style="color: #dc3545;">No refund</strong></td></tr>
                </tbody>
            </table>
            <div class="warning-box">
                <i class="fas fa-exclamation-triangle"></i> <strong>Late Cancellation Fee:</strong> For cancellations within 12 hours of pickup, a late cancellation fee of <strong>KES 1,000</strong> applies on top of the refund deduction.
            </div>
        </div>

        <!-- Section 3: Refund Processing -->
        <div class="section">
            <h2><i class="fas fa-clock"></i> Refund Processing</h2>
            <div class="info-box">
                <i class="fas fa-info-circle"></i> <strong>Processing Time:</strong> Approved refunds may take 1–5 business days depending on payment provider.
            </div>
        </div>

        <!-- Section 4: Non-Refundable Situations -->
        <div class="section">
            <h2><i class="fas fa-ban"></i> Non-Refundable Situations</h2>
            <div class="danger-box">
                <strong>No refund shall be issued for:</strong>
                <ul>
                    <li>Vehicle misuse</li>
                    <li>Policy violations</li>
                    <li>Late cancellations (within 12 hours of pickup)</li>
                    <li>Traffic fines incurred during rental</li>
                    <li>No-show without prior notification</li>
                    <li>Early returns (no partial refund for unused days)</li>
                </ul>
            </div>
        </div>

        <!-- Section 5: Deposit Refund -->
        <div class="section">
            <h2><i class="fas fa-hand-holding-usd"></i> Deposit Refund</h2>
            <p>The security deposit (50% of total amount) is refundable under the following conditions:</p>
            <ul>
                <li>Vehicle returned on time</li>
                <li>No damage to the vehicle</li>
                <li>Fuel tank filled to agreed level</li>
                <li>No traffic fines incurred</li>
                <li>Cancellation made 7+ days before pickup</li>
            </ul>
            <div class="info-box">
                <i class="fas fa-clock"></i> Deposit refunds are processed within 5-7 business days after vehicle inspection.
            </div>
        </div>

        <!-- Section 6: How to Request a Refund -->
        <div class="section">
            <h2><i class="fas fa-envelope"></i> How to Request a Refund</h2>
            <ol style="margin-left: 20px;">
                <li>Contact our support team at <strong>support@urbanwheels.com</strong></li>
                <li>Provide your booking number and reason for cancellation</li>
                <li>Allow 1-2 business days for response</li>
                <li>Refunds will be processed to the original payment method</li>
            </ol>
        </div>
    </div>
</div>

<footer class="footer">
    <p>&copy; 2026 Urban Wheels Car Rental. All rights reserved. | Nairobi, Kenya</p>
</footer>

</body>
</html>