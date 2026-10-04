<?php
session_start();
require_once 'config/database.php';
require_once 'includes/security.php';

$security = new Security($conn);
$error = '';
$success = '';
$reset_link = '';

// Rate limiting
if (!$security->checkRateLimit('forgot_password', 5, 300)) {
    $error = "Too many requests. Please try again later.";
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $security->sanitize($_POST['email']);
    
    if(empty($email)) {
        $error = "Please enter your email address.";
    } elseif(!$security->validateEmail($email)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists
        $query = "SELECT id, name, email FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Generate unique token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store in password_resets table
            $insert_query = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "sss", $email, $token, $expires);
            
            if(mysqli_stmt_execute($insert_stmt)) {
                // Also update users table
                mysqli_query($conn, "UPDATE users SET reset_token = '$token', reset_expires = '$expires' WHERE email = '$email'");
                
                // Create reset link
                $reset_link = "http://localhost/car_rental_system/reset_password.php?token=" . $token;
                
                // Try to send email
                $subject = "Password Reset - Urban Wheels";
                $message = "Dear {$user['name']},\n\n";
                $message .= "You requested to reset your password. Click the link below to reset it:\n\n";
                $message .= $reset_link . "\n\n";
                $message .= "This link will expire in 1 hour.\n\n";
                $message .= "If you did not request this, please ignore this email.\n\n";
                $message .= "Best regards,\nUrban Wheels Team";
                $headers = "From: Urban Wheels <noreply@urbanwheels.com>\r\n";
                
                $email_sent = @mail($email, $subject, $message, $headers);
                
                if($email_sent) {
                    $success = "Password reset link has been sent to your email address. Please check your inbox.";
                } else {
                    // Show link directly on page since email failed
                    $success = "Password reset link generated. Click the link below to reset your password:";
                }
                $security->logSecurityEvent($user['id'], 'password_reset_request', "Password reset requested for email: $email");
            } else {
                $error = "Failed to process request. Please try again.";
            }
        } else {
            // Don't reveal that email doesn't exist for security
            $success = "If an account exists with this email, you will receive a password reset link.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Urban Wheels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 20px; 
        }
        
        .container { 
            background: white; 
            border-radius: 20px; 
            padding: 40px; 
            max-width: 500px; 
            width: 100%; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
        }
        
        .logo { 
            text-align: center; 
            margin-bottom: 30px; 
        }
        
        .logo h2 { 
            font-size: 28px; 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
        }
        
        h3 { 
            color: #333; 
            margin-bottom: 10px; 
            text-align: center;
        }
        
        .subtitle { 
            color: #666; 
            font-size: 14px; 
            margin-bottom: 30px; 
            text-align: center; 
        }
        
        .form-group { 
            margin-bottom: 20px; 
        }
        
        .form-group input { 
            width: 100%; 
            padding: 14px; 
            border: 1px solid #ddd; 
            border-radius: 10px; 
            font-size: 14px; 
        }
        
        .form-group input:focus { 
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
            transition: transform 0.3s; 
        }
        
        .btn-submit:hover { 
            transform: translateY(-2px); 
        }
        
        .back-link { 
            text-align: center; 
            margin-top: 20px; 
        }
        
        .back-link a { 
            color: #667eea; 
            text-decoration: none; 
        }
        
        .alert { 
            padding: 12px; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            font-size: 14px; 
            text-align: center; 
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
        
        .reset-link-box { 
            background: #f0f7ff; 
            padding: 20px; 
            border-radius: 10px; 
            margin-top: 20px; 
            word-break: break-all; 
            text-align: center;
        }
        
        .reset-link-box a { 
            color: #28a745; 
            font-weight: 600;
            word-break: break-all;
            display: inline-block;
            margin-top: 10px;
        }
        
        .reset-link-box p {
            margin-bottom: 10px;
            color: #666;
        }
        
        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h2>URBAN WHEELS</h2>
        </div>
        
        <h3>Forgot Password?</h3>
        <p class="subtitle">Enter your email to reset your password</p>

        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success && strpos($success, 'sent to your email') !== false): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php elseif($success && $reset_link): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <div class="reset-link-box">
                <p><i class="fas fa-link"></i> <strong>Your Reset Link:</strong></p>
                <a href="<?php echo $reset_link; ?>" target="_blank"><?php echo $reset_link; ?></a>
                <p style="margin-top: 15px; font-size: 12px;">
                    <i class="fas fa-clock"></i> This link will expire in 1 hour.
                </p>
            </div>
        <?php elseif($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if(!$reset_link): ?>
        <form method="POST">
            <div class="form-group">
                <input type="email" name="email" placeholder="Enter your registered email" required>
            </div>
            <button type="submit" class="btn-submit">Send Reset Link</button>
        </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="index.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>