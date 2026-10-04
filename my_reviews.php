
<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$reviews = mysqli_query($conn, "SELECT r.*, v.brand, v.model 
    FROM reviews r JOIN vehicles v ON r.vehicle_id = v.id 
    WHERE r.user_id = {$_SESSION['user_id']} ORDER BY r.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reviews - Urban Wheels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
        }

        .navbar {
            background: #1a1a2e;
            padding: 15px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
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
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #FFD700;
        }

        .logout-btn {
            background: #dc3545;
            padding: 8px 20px;
            border-radius: 25px;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
        }

        .review-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .review-card:hover {
            transform: translateY(-3px);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .vehicle-info h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 5px;
        }

        .stars {
            color: #FFD700;
            font-size: 14px;
        }

        .status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #ffc107;
            color: #000;
        }

        .status-approved {
            background: #28a745;
            color: #fff;
        }

        .status-rejected {
            background: #dc3545;
            color: #fff;
        }

        .review-comment {
            color: #555;
            line-height: 1.6;
            margin: 15px 0;
        }

        .review-date {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 20px;
        }

        .empty-state i {
            font-size: 80px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 20px;
        }

        .btn-browse {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .btn-browse:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }

        @media (max-width: 768px) {
            .review-header {
                flex-direction: column;
                align-items: flex-start;
            }
            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">URBAN WHEELS</div>
    <div class="nav-links">
        <a href="vehicles.php">Vehicles</a>
        <a href="my_bookings.php">My Bookings</a>
        <a href="my_reviews.php" style="color: #FFD700;">My Reviews</a>
        <a href="contact.php">Contact</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>My Reviews</h1>
        <p>View all your submitted vehicle reviews</p>
    </div>

    <?php if(mysqli_num_rows($reviews) > 0): ?>
        <?php while($review = mysqli_fetch_assoc($reviews)): ?>
        <div class="review-card">
            <div class="review-header">
                <div class="vehicle-info">
                    <h3><?php echo htmlspecialchars($review['brand'] . ' ' . $review['model']); ?></h3>
                    <div class="stars">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <?php if($i <= $review['rating']): ?>
                                <i class="fas fa-star"></i>
                            <?php else: ?>
                                <i class="far fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                </div>
                <span class="status status-<?php echo $review['status']; ?>">
                    <?php echo ucfirst($review['status']); ?>
                </span>
            </div>
            <div class="review-comment">
                <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
            </div>
            <div class="review-date">
                <i class="fas fa-calendar-alt"></i> Submitted on <?php echo date('F d, Y', strtotime($review['created_at'])); ?>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-star"></i>
            <h3>No Reviews Yet</h3>
            <p>You haven't written any reviews yet. Share your experience after renting a vehicle!</p>
            <a href="vehicles.php" class="btn-browse">Browse Vehicles →</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>