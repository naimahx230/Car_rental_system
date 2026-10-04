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

$error = '';
$success = '';

// Handle contact form submission
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    if(empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "Please fill in all required fields.";
    } else {
        $insert = "INSERT INTO contact_messages (name, email, phone, subject, message, status) 
                   VALUES ('$name', '$email', '$phone', '$subject', '$message', 'unread')";
        
        if(mysqli_query($conn, $insert)) {
            $success = "Thank you for contacting us! We will get back to you within 24 hours.";
            $_POST = array();
        } else {
            $error = "Failed to send message. Please try again.";
        }
    }
}

// Get contact settings
$settings = [];
$result = mysqli_query($conn, "SELECT setting_key, setting_value FROM contact_settings");
while($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Urban Wheels</title>
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
        
        .hero-banner {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 60px 5%;
            text-align: center;
        }
        .hero-banner h1 { font-size: 42px; margin-bottom: 15px; }
        
        .contact-container {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        .contact-info {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .contact-info h2 { margin-bottom: 25px; color: #333; }
        .info-item {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 15px;
        }
        .info-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .info-icon i { font-size: 24px; color: white; }
        .info-item h3 { font-size: 16px; margin-bottom: 5px; color: #333; }
        .info-item p { color: #666; }
        
        .contact-form {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .contact-form h2 { margin-bottom: 25px; color: #333; }
        .form-group { margin-bottom: 20px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #eee;
            border-radius: 10px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-submit:hover { transform: translateY(-2px); }
        .alert { padding: 12px; border-radius: 10px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        
        .footer {
            background: #1a1a2e;
            color: white;
            text-align: center;
            padding: 25px;
            margin-top: 60px;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .contact-container { grid-template-columns: 1fr; }
            .hero-banner h1 { font-size: 32px; }
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
        <a href="contact.php" style="color: #FFD700;">Contact</a>
        <a href="my_bookings.php">My Bookings</a>
        <a href="cart.php" class="cart-link">
            <i class="fas fa-shopping-cart"></i> Cart
            <span class="cart-count" id="cartCount">0</span>
        </a>
        <span style="color: white;">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<div class="hero-banner">
    <h1>Get in Touch</h1>
    <p>We're here to help! Contact us anytime for inquiries, support, or feedback</p>
</div>

<div class="contact-container">
    <div class="contact-info">
        <h2>Contact Information</h2>
        <div class="info-item">
            <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
            <div><h3>Phone</h3><p><?php echo $settings['phone_1'] ?? '+254 700 000 000'; ?></p></div>
        </div>
        <div class="info-item">
            <div class="info-icon"><i class="fas fa-envelope"></i></div>
            <div><h3>Email</h3><p><?php echo $settings['email_1'] ?? 'info@urbanwheels.com'; ?></p></div>
        </div>
        <div class="info-item">
            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
            <div><h3>Location</h3><p><?php echo $settings['address'] ?? 'Nairobi, Kenya'; ?></p></div>
        </div>
    </div>

    <div class="contact-form">
        <h2>Send Us a Message</h2>
        <?php if($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-group"><input type="text" name="name" placeholder="Your Name" required></div>
            <div class="form-group"><input type="email" name="email" placeholder="Your Email" required></div>
            <div class="form-group"><input type="tel" name="phone" placeholder="Phone Number"></div>
            <div class="form-group">
                <select name="subject" required>
                    <option value="">Select Subject</option>
                    <option value="Booking Inquiry">Booking Inquiry</option>
                    <option value="Support">Customer Support</option>
                    <option value="Feedback">Feedback</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group"><textarea name="message" rows="5" placeholder="Your Message" required></textarea></div>
            <button type="submit" name="send_message" class="btn-submit">Send Message</button>
        </form>
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