<?php
session_start();
require_once '../config/database.php';
require_once '../includes/security.php';

$security = new Security($conn);
$error = '';
$success = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $security->sanitize($_POST['email']);
    
    if(empty($email)) {
        $error = "Please enter your email address.";
    } else {
        // Check if admin exists
        $query = "SELECT id, name, email FROM users WHERE email = ? AND role = 'admin'";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1) {
            $admin = mysqli_fetch_assoc($result);
            
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Save token
            mysqli_query($conn, "UPDATE users SET reset_token = '$token', reset_expires = '$expires' WHERE email = '$email'");
            
            // Create reset link
            $reset_link = "http://localhost/car_rental_system/admin/admin_reset_password.php?token=" . $token;
            
            // For now, display the link (since mail is not configured)
            $success = "Password reset link generated. Click the link below to reset your password:<br><br>
                       <a href='$reset_link' style='color: #28a745; word-break: break-all;'>$reset_link</a><br><br>
                       <strong>Note:</strong> This link will expire in 1 hour.";
        } else {
            $success = "If an admin account exists with this email, you will receive a reset link.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Forgot Password - Urban Wheels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #1a1a2e, #16213e); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 20px; padding: 40px; max-width: 500px; width: 100%; border: 1px solid rgba(255,215,0,0.3); }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h2 { font-size: 28px; color: #FFD700; }
        h3 { color: white; margin-bottom: 10px; }
        .subtitle { color: rgba(255,255,255,0.7); font-size: 14px; margin-bottom: 30px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group input { width: 100%; padding: 14px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,215,0,0.3); border-radius: 10px; color: white; font-size: 14px; }
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #FFD700, #FFA500); border: none; border-radius: 10px; font-weight: 600; cursor: pointer; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #FFD700; text-decoration: none; font-size: 14px; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .alert-error { background: rgba(220,53,69,0.2); color: #ff6b6b; }
        .alert-success { background: rgba(40,167,69,0.2); color: #28a745; }
        .reset-link-box { background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px; margin-top: 15px; word-break: break-all; }
        .reset-link-box a { color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo"><h2>URBAN WHEELS</h2></div>
        <h3>Admin Forgot Password?</h3>
        <p class="subtitle">Enter your email to reset your admin password</p>

        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="email" name="email" placeholder="Enter your admin email" required>
            </div>
            <button type="submit" class="btn-submit">Send Reset Link</button>
        </form>

        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>