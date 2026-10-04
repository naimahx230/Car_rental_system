
<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

// Get the booking to find the vehicle
$booking_query = "SELECT b.*, v.id as vehicle_id FROM bookings b 
                  JOIN vehicles v ON b.vehicle_id = v.id 
                  WHERE b.id = ? AND b.user_id = ?";
$stmt = mysqli_prepare($conn, $booking_query);
mysqli_stmt_bind_param($stmt, "ii", $booking_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$booking_result = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($booking_result);

if(!$booking) {
    header("Location: my_bookings.php");
    exit();
}

$error = '';
$success = '';

// Check if user already reviewed this vehicle
$check_review = mysqli_query($conn, "SELECT id FROM reviews WHERE user_id = {$_SESSION['user_id']} AND vehicle_id = {$booking['vehicle_id']}");
$already_reviewed = mysqli_num_rows($check_review) > 0;

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $rating = (int)$_POST['rating'];
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    
    if($rating < 1 || $rating > 5) {
        $error = "Please select a rating from 1 to 5 stars.";
    } elseif(empty($comment)) {
        $error = "Please write your review.";
    } elseif($already_reviewed) {
        $error = "You have already reviewed this vehicle.";
    } else {
        $insert = "INSERT INTO reviews (user_id, vehicle_id, rating, comment, status) 
                   VALUES ({$_SESSION['user_id']}, {$booking['vehicle_id']}, $rating, '$comment', 'pending')";
        
        if(mysqli_query($conn, $insert)) {
            $success = "Thank you for your review! It will appear on our website after admin approval.";
            header("refresh:3;url=my_bookings.php");
        } else {
            $error = "Failed to submit review. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write a Review - Urban Wheels</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

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

        .back-link {
            color: white;
            text-decoration: none;
            padding: 8px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 25px;
        }

        .container {
            max-width: 600px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .review-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .review-card h2 {
            color: #333;
            margin-bottom: 10px;
        }

        .star-rating {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            justify-content: center;
        }

        .star {
            font-size: 45px;
            cursor: pointer;
            color: #ddd;
            transition: color 0.3s;
        }

        .star:hover,
        .star.active {
            color: #FFD700;
        }

        textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #eee;
            border-radius: 10px;
            margin: 20px 0;
            font-family: inherit;
            resize: vertical;
            transition: border-color 0.3s;
        }

        textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40,167,69,0.4);
        }

        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .rating-value {
            text-align: center;
            margin-top: 10px;
            font-weight: 500;
            color: #666;
        }

        .already-reviewed {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">URBAN WHEELS</div>
        <a href="my_bookings.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Bookings</a>
    </nav>

    <div class="container">
        <div class="review-card">
            <h2>Share Your Experience</h2>
            <p>How was your rental experience with the <?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?>?</p>

            <?php if($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if($already_reviewed): ?>
                <div class="already-reviewed">
                    <i class="fas fa-info-circle"></i> You have already reviewed this vehicle. Thank you for your feedback!
                </div>
            <?php endif; ?>

            <?php if(!$already_reviewed && !$success): ?>
            <form method="POST">
                <div class="star-rating">
                    <i class="fas fa-star star" data-rating="1"></i>
                    <i class="fas fa-star star" data-rating="2"></i>
                    <i class="fas fa-star star" data-rating="3"></i>
                    <i class="fas fa-star star" data-rating="4"></i>
                    <i class="fas fa-star star" data-rating="5"></i>
                </div>
                <input type="hidden" name="rating" id="rating" required>
                <div class="rating-value" id="ratingValue"></div>

                <textarea name="comment" rows="5" placeholder="Write your review here... Tell us about your experience with the vehicle, customer service, etc." required></textarea>

                <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Submit Review</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('rating');
        const ratingValue = document.getElementById('ratingValue');

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.dataset.rating;
                ratingInput.value = rating;
                
                // Update rating display
                ratingValue.innerHTML = 'You selected ' + rating + ' star' + (rating > 1 ? 's' : '');
                
                stars.forEach(s => {
                    if(s.dataset.rating <= rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = this.dataset.rating;
                stars.forEach(s => {
                    if(s.dataset.rating <= rating) {
                        s.style.color = '#FFD700';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
            
            star.addEventListener('mouseleave', function() {
                const currentRating = ratingInput.value;
                stars.forEach(s => {
                    if(currentRating && s.dataset.rating <= currentRating) {
                        s.style.color = '#FFD700';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
        });
    </script>
</body>
</html>