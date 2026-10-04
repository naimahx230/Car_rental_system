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

// Get cart from session
$cart_items = isset($_SESSION['service_cart']) ? $_SESSION['service_cart'] : [];

// Handle remove from cart
if(isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    foreach($cart_items as $key => $item) {
        if($item['id'] == $remove_id) {
            unset($cart_items[$key]);
            break;
        }
    }
    $cart_items = array_values($cart_items);
    $_SESSION['service_cart'] = $cart_items;
    header("Location: cart.php");
    exit();
}

// Handle clear cart
if(isset($_GET['clear'])) {
    $_SESSION['service_cart'] = [];
    header("Location: cart.php");
    exit();
}

// Calculate total
$total = 0;
foreach($cart_items as $item) {
    $total += $item['price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - Urban Wheels</title>
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
        
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .page-header { margin-bottom: 30px; }
        .page-header h1 { font-size: 32px; color: #333; }
        .page-header p { color: #666; }
        
        .cart-wrapper {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 30px;
        }
        
        .cart-items {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .cart-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 18px 20px;
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 0.8fr;
            gap: 10px;
            font-weight: 600;
        }
        .cart-item {
            padding: 18px 20px;
            display: grid;
            grid-template-columns: 3fr 1fr 1fr 0.8fr;
            gap: 10px;
            border-bottom: 1px solid #eee;
            align-items: center;
        }
        .cart-item:last-child { border-bottom: none; }
        .item-name { font-weight: 600; color: #333; }
        .item-name i { color: #28a745; margin-right: 8px; }
        .item-price { font-weight: 700; color: #28a745; }
        .price-type { font-size: 11px; color: #666; display: block; }
        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-remove:hover { background: #c82333; transform: translateY(-2px); }
        
        .order-summary {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .order-summary h3 {
            font-size: 20px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .summary-row.total {
            border-top: 2px solid #dee2e6;
            border-bottom: none;
            padding-top: 15px;
            margin-top: 5px;
            font-size: 18px;
            font-weight: 700;
            color: #28a745;
        }
        .btn-proceed {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-proceed:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(40,167,69,0.4); }
        .btn-clear {
            width: 100%;
            padding: 12px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-clear:hover { background: #5a6268; transform: translateY(-2px); }
        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .empty-cart {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 20px;
        }
        .empty-cart i { font-size: 80px; color: #ccc; margin-bottom: 20px; }
        .empty-cart h3 { font-size: 24px; color: #333; margin-bottom: 10px; }
        .empty-cart p { color: #666; margin-bottom: 20px; }
        .btn-browse {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
        }
        
        .footer {
            background: #1a1a2e;
            color: white;
            text-align: center;
            padding: 30px;
            margin-top: 60px;
        }
        
        @media (max-width: 768px) {
            .cart-wrapper { grid-template-columns: 1fr; }
            .cart-header, .cart-item { grid-template-columns: 2fr 1fr 0.8fr; }
            .cart-header .price-type-col, .cart-item .price-type { display: none; }
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
        <a href="my_bookings.php">My Bookings</a>
        <a href="cart.php" class="cart-link" style="color: #FFD700;">
            <i class="fas fa-shopping-cart"></i> Cart
        </a>
        <span style="color: white;">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<div class="container">
    <a href="services.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Services</a>
    
    <div class="page-header">
        <h1><i class="fas fa-shopping-cart"></i> Your Cart</h1>
        <p>Review and confirm the services you want to add to your booking</p>
    </div>

    <?php if(!empty($cart_items)): ?>
    <div class="cart-wrapper">
        <div class="cart-items">
            <div class="cart-header">
                <span>Service</span>
                <span>Price</span>
                <span>Type</span>
                <span>Action</span>
            </div>
            <?php foreach($cart_items as $item): ?>
            <div class="cart-item">
                <div class="item-name"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($item['name']); ?></div>
                <div class="item-price">KES <?php echo number_format($item['price'], 2); ?></div>
                <div class="price-type"><?php echo $item['priceType'] == 'per_day' ? 'Per day' : 'One time'; ?></div>
                <div><a href="?remove=<?php echo $item['id']; ?>" class="btn-remove" onclick="return confirm('Remove this service?')"><i class="fas fa-trash-alt"></i> Remove</a></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="order-summary">
            <h3><i class="fas fa-receipt"></i> Order Summary</h3>
            <div class="summary-row"><span>Subtotal:</span><span>KES <?php echo number_format($total, 2); ?></span></div>
            <div class="summary-row total"><span>Total:</span><span>KES <?php echo number_format($total, 2); ?></span></div>
            <a href="vehicles.php" class="btn-proceed"><i class="fas fa-arrow-right"></i> Proceed to Vehicle Booking</a>
            <a href="?clear=1" class="btn-clear" onclick="return confirm('Clear all services from cart?')"><i class="fas fa-trash"></i> Clear Cart</a>
        </div>
    </div>
    <?php else: ?>
    <div class="empty-cart">
        <i class="fas fa-shopping-cart"></i>
        <h3>Your cart is empty</h3>
        <p>You haven't added any services to your booking yet.</p>
        <a href="services.php" class="btn-browse">Browse Services →</a>
    </div>
    <?php endif; ?>
</div>

<footer class="footer">
    <p>© 2026 Urban Wheels Car Rental. All rights reserved. | Nairobi, Kenya</p>
</footer>

</body>
</html>