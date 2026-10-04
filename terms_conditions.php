<?php
session_start();
require_once 'config/database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions - Urban Wheels</title>
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
        
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        
        .terms-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
        }
        .terms-header h1 { font-size: 36px; margin-bottom: 10px; }
        
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
        
        .terms-content {
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
        .section h3 {
            color: #333;
            font-size: 18px;
            margin: 15px 0 10px;
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
        .fee-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid #28a745;
        }
        .warning-box {
            background: #fff3cd;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid #ffc107;
        }
        .danger-box {
            background: #f8d7da;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid #dc3545;
        }
        .info-box {
            background: #d1ecf1;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid #17a2b8;
        }
        
        .table-fees {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .table-fees th, .table-fees td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .table-fees th {
            background: #667eea;
            color: white;
        }
        
        .footer {
            background: #1a1a2e;
            color: white;
            text-align: center;
            padding: 30px;
            margin-top: 60px;
        }
        
        @media (max-width: 768px) {
            .terms-header h1 { font-size: 28px; }
            .terms-content { padding: 25px; }
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
        <a href="terms_conditions.php" style="color: #FFD700;">Terms</a>
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
    <div class="terms-header">
        <h1><i class="fas fa-file-contract"></i> Terms and Conditions</h1>
        <p>Please read our terms carefully before booking a vehicle</p>
        <p><small>Last Updated: <?php echo date('F d, Y'); ?> | Version: 2.0</small></p>
    </div>

    <div class="terms-nav">
        <a href="terms_conditions.php" class="active">Terms & Conditions</a>
        <a href="privacy_policy.php">Privacy Policy</a>
        <a href="refund_policy.php">Refund Policy</a>
    </div>

    <div class="terms-content">
        <!-- Section 1: Acceptance of Terms -->
        <div class="section">
            <h2><i class="fas fa-check-circle"></i> 1. Acceptance of Terms</h2>
            <p>By accessing or using the Urban Wheels Online Car Rental Management System, users agree to comply with these Terms and Conditions.</p>
        </div>

        <!-- Section 2: User Registration -->
        <div class="section">
            <h2><i class="fas fa-user-plus"></i> 2. User Registration</h2>
            <ul>
                <li>Users must provide accurate registration details.</li>
                <li>Users are responsible for protecting their account credentials.</li>
                <li>False or misleading information may result in account suspension.</li>
            </ul>
        </div>

        <!-- Section 3: Vehicle Booking Policy -->
        <div class="section">
            <h2><i class="fas fa-calendar-check"></i> 3. Vehicle Booking Policy</h2>
            <ul>
                <li>Vehicle reservations depend on availability.</li>
                <li>Bookings are confirmed after successful payment.</li>
                <li>Double booking and fraudulent reservations are prohibited.</li>
            </ul>
        </div>

        <!-- Section 4: Rental Duration and Late Return Charges -->
        <div class="section">
            <h2><i class="fas fa-clock"></i> 4. Rental Duration and Late Return Charges</h2>
            <p>Vehicles must be returned on the agreed date and time.</p>
            <div class="info-box">
                <i class="fas fa-info-circle"></i> <strong>Grace Period:</strong> A grace period of 30 minutes may be allowed.
            </div>
            <div class="fee-box">
                <strong>⏰ Late Return Charges:</strong>
                <table class="table-fees">
                    <tr><th>Delay Duration</th><th>Charge</th></tr>
                    <tr><td>Per Hour</td><td>KES 500</td></tr>
                    <tr><td>More than 24 Hours</td><td>Full extra day rental fee</td></tr>
                </table>
            </div>
            <div class="warning-box">
                <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> If the customer informs the company before the return deadline and pays for an additional rental day in advance, hourly penalties shall not apply.
            </div>
        </div>

        <!-- Section 5: Vehicle Damage and Liability -->
        <div class="section">
            <h2><i class="fas fa-car-crash"></i> 5. Vehicle Damage and Liability</h2>
            <p>Renters are responsible for damages during the rental period.</p>
            <div class="danger-box">
                <strong>Damage Fee Structure:</strong>
                <ul>
                    <li>Minor Scratch/Dent: KES 2,000 - 5,000</li>
                    <li>Major Dent: KES 5,000 - 15,000</li>
                    <li>Broken Mirror/Light: KES 3,000 - 10,000</li>
                    <li>Windshield Crack: KES 8,000 - 20,000</li>
                    <li>Accident Damage: Full repair cost + admin fee (KES 5,000)</li>
                </ul>
            </div>
        </div>

        <!-- Section 6: Fuel Policy -->
        <div class="section">
            <h2><i class="fas fa-gas-pump"></i> 6. Fuel Policy</h2>
            <p>Vehicles should be returned with the agreed fuel level.</p>
            <div class="fee-box">
                <strong>⛽ Fuel Charges:</strong> Additional refueling charges may apply if fuel is insufficient (10% of daily rate + KES 500 service fee).
            </div>
        </div>

        <!-- Section 7: Traffic Offenses -->
        <div class="section">
            <h2><i class="fas fa-gavel"></i> 7. Traffic Offenses</h2>
            <p>Users are responsible for:</p>
            <ul>
                <li>Parking fines</li>
                <li>Traffic violations</li>
                <li>Legal penalties incurred during rental</li>
            </ul>
        </div>

        <!-- Section 8: Additional Charges -->
        <div class="section">
            <h2><i class="fas fa-receipt"></i> 8. Additional Charges</h2>
            <ul>
                <li>Late returns (KES 500/hour)</li>
                <li>Excessive dirt or cleaning (KES 1,500 - 5,000)</li>
                <li>Lost keys or accessories (Actual replacement cost)</li>
                <li>Vehicle misuse (Full repair cost)</li>
            </ul>
        </div>

        <!-- Section 9: System Misuse -->
        <div class="section">
            <h2><i class="fas fa-shield-alt"></i> 9. System Misuse</h2>
            <ul>
                <li>Hack or manipulate the system</li>
                <li>Upload harmful software</li>
                <li>Access unauthorized areas</li>
            </ul>
        </div>

        <!-- Section 10: Administrator Rights -->
        <div class="section">
            <h2><i class="fas fa-user-shield"></i> 10. Administrator Rights</h2>
            <ul>
                <li>Suspend suspicious accounts</li>
                <li>Approve or reject bookings</li>
                <li>Modify system settings</li>
            </ul>
        </div>

        <!-- Section 11: Limitation of Liability -->
        <div class="section">
            <h2><i class="fas fa-exclamation-triangle"></i> 11. Limitation of Liability</h2>
            <ul>
                <li>Technical interruptions</li>
                <li>Internet failures</li>
                <li>Unauthorized access caused by user negligence</li>
            </ul>
        </div>

        <!-- Section 12: Contact Information -->
        <div class="section">
            <h2><i class="fas fa-phone-alt"></i> 12. Contact Information</h2>
            <div class="info-box">
                <p><strong>Urban Wheels Car Rental Services</strong></p>
                <p>📍 Nairobi, Kenya</p>
                <p>📞 +254 700 000 000</p>
                <p>📧 support@urbanwheels.com</p>
            </div>
        </div>
    </div>
</div>

<footer class="footer">
    <p>&copy; 2026 Urban Wheels Car Rental. All rights reserved. | Nairobi, Kenya</p>
</footer>

</body>
</html>