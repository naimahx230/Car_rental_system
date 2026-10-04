<?php
require_once 'config/database.php';

// Get available vehicles
$vehicles = mysqli_query($conn, "SELECT * FROM vehicles WHERE status = 'available' LIMIT 10");
$vehicles_array = [];
while($v = mysqli_fetch_assoc($vehicles)) {
    $vehicles_array[] = $v;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental - Live Fleet</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1c2e 0%, #292b3e 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Road Section */
        .road-section {
            position: relative;
            background: #2c3e50;
            padding: 60px 0;
            overflow: hidden;
        }

        .road {
            position: relative;
            background: #34495e;
            padding: 30px 0;
            border-top: 3px solid #f1c40f;
            border-bottom: 3px solid #f1c40f;
        }

        .road-lines {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: repeating-linear-gradient(
                90deg,
                #fff,
                #fff 40px,
                transparent 40px,
                transparent 80px
            );
            transform: translateY(-50%);
        }

        /* Car Container - Animated */
        .car-container {
            position: relative;
            display: flex;
            gap: 40px;
            animation: moveCars 20s linear infinite;
            white-space: nowrap;
        }

        @keyframes moveCars {
            0% {
                transform: translateX(100%);
            }
            100% {
                transform: translateX(-100%);
            }
        }

        /* Individual Car Card */
        .moving-car {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            background: white;
            border-radius: 15px;
            padding: 15px;
            width: 280px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: transform 0.3s;
            cursor: pointer;
        }

        .moving-car:hover {
            transform: scale(1.05);
            animation-play-state: paused;
        }

        .car-image {
            width: 100%;
            height: 150px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .car-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .car-image i {
            font-size: 60px;
            color: white;
        }

        .car-info {
            text-align: center;
            margin-top: 12px;
        }

        .car-name {
            font-size: 18px;
            font-weight: 700;
            color: #333;
        }

        .car-price {
            font-size: 20px;
            font-weight: 700;
            color: #28a745;
            margin: 5px 0;
        }

        .car-price span {
            font-size: 12px;
            color: #666;
        }

        .book-btn {
            margin-top: 10px;
            padding: 8px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .book-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }

        /* Pause on hover */
        .road:hover .car-container {
            animation-play-state: paused;
        }

        /* Other sections */
        .header {
            background: rgba(0,0,0,0.9);
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            margin-left: 25px;
        }

        .btn-login {
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #1a1a2e !important;
            padding: 8px 25px;
            border-radius: 25px;
        }

        .featured-section {
            padding: 60px 5%;
            text-align: center;
        }

        .featured-section h2 {
            color: white;
            font-size: 36px;
            margin-bottom: 40px;
        }

        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .featured-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s;
        }

        .featured-card:hover {
            transform: translateY(-5px);
        }

        .featured-card .car-image {
            height: 200px;
        }

        .featured-card .car-info {
            padding: 20px;
        }

        .footer {
            background: #1a1a2e;
            color: white;
            text-align: center;
            padding: 30px;
            margin-top: 60px;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @media (max-width: 768px) {
            .moving-car {
                width: 220px;
            }
            .car-name {
                font-size: 14px;
            }
            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>

<header class="header">
    <div class="logo">URBAN WHEELS</div>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="vehicles.php">Vehicles</a>
        <a href="services.php">Services</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="my_bookings.php">My Bookings</a>
            <a href="logout.php" style="background: #dc3545; padding: 8px 20px; border-radius: 25px;">Logout</a>
        <?php else: ?>
            <a href="index.php" class="btn-login">Login / Register</a>
        <?php endif; ?>
    </div>
</header>

<!-- Animated Cars on Road Section -->
<section class="road-section">
    <div class="road">
        <div class="road-lines"></div>
        <div class="car-container">
            <?php 
            // Duplicate cars for seamless loop
            $all_cars = array_merge($vehicles_array, $vehicles_array);
            foreach($all_cars as $car): 
            ?>
            <div class="moving-car" onclick="location.href='book.php?id=<?php echo $car['id']; ?>'">
                <div class="car-image">
                    <?php if($car['image'] && file_exists($car['image'])): ?>
                        <img src="<?php echo $car['image']; ?>" alt="<?php echo $car['brand']; ?>">
                    <?php else: ?>
                        <i class="fas fa-car"></i>
                    <?php endif; ?>
                </div>
                <div class="car-info">
                    <div class="car-name"><?php echo $car['brand'] . ' ' . $car['model']; ?></div>
                    <div class="car-price">
                        KES <?php echo number_format($car['daily_rate'], 2); ?> <span>/ day</span>
                    </div>
                    <button class="book-btn">Book Now →</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Vehicles Section -->
<section class="featured-section">
    <h2>🔥 Featured Vehicles</h2>
    <div class="featured-grid">
        <?php 
        $featured = mysqli_query($conn, "SELECT * FROM vehicles WHERE featured = 1 AND status = 'available' LIMIT 3");
        while($car = mysqli_fetch_assoc($featured)): 
        ?>
        <div class="featured-card">
            <div class="car-image">
                <?php if($car['image'] && file_exists($car['image'])): ?>
                    <img src="<?php echo $car['image']; ?>" alt="<?php echo $car['brand']; ?>">
                <?php else: ?>
                    <i class="fas fa-car"></i>
                <?php endif; ?>
            </div>
            <div class="car-info">
                <h3><?php echo $car['brand'] . ' ' . $car['model']; ?></h3>
                <div class="car-price">KES <?php echo number_format($car['daily_rate'], 2); ?> <span>/ day</span></div>
                <button class="book-btn" onclick="location.href='book.php?id=<?php echo $car['id']; ?>'">Book Now →</button>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</section>

<footer class="footer">
    <p>&copy; 2026 Urban Wheels Car Rental. All rights reserved. | Nairobi, Kenya</p>
</footer>

<script>
    // Add smooth scrolling
    document.querySelectorAll('.book-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
</script>
</body>
</html>