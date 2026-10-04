<?php
session_start();
require_once 'config/database.php';
require_once 'includes/security.php';

$security = new Security($conn);
$error = '';
$success = '';
$token = isset($_GET['token']) ? $_GET['token'] : '';
$valid_token = false;
$user_email = '';

// Verify token
if(empty($token)) {
    header("Location: forgot_password.php");
    exit();
}

// Check token in password_resets table
$query = "SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = FALSE";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 1) {
    $reset = mysqli_fetch_assoc($result);
    $user_email = $reset['email'];
    $valid_token = true;
    
    // Also check users table
    $user_check = mysqli_query($conn, "SELECT id, name, email FROM users WHERE email = '$user_email'");
    $user = mysqli_fetch_assoc($user_check);
} else {
    // Check in users table as fallback
    $user_check = mysqli_query($conn, "SELECT id, name, email FROM users WHERE reset_token = '$token' AND reset_expires > NOW()");
    if(mysqli_num_rows($user_check) == 1) {
        $user = mysqli_fetch_assoc($user_check);
        $user_email = $user['email'];
        $valid_token = true;
    } else {
        $error = "Invalid or expired reset link. Please request a new password reset.";
    }
}

// Process password reset
if($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password strength
    $password_errors = $security->validatePasswordStrength($password);
    
    if(empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif(!empty($password_errors)) {
        $error = implode("<br>", $password_errors);
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password
        $update_query = "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ss", $hashed_password, $user_email);
        
        if(mysqli_stmt_execute($update_stmt)) {
            // Mark token as used
            mysqli_query($conn, "UPDATE password_resets SET used = TRUE WHERE token = '$token'");
            
            // Log security event
            $security->logSecurityEvent($user['id'], 'password_reset_success', "Password reset successful for user: $user_email");
            
            $success = "Password has been reset successfully! Redirecting to login...";
            header("refresh:3;url=index.php");
        } else {
            $error = "Failed to reset password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Urban Wheels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        
        .container { background: white; border-radius: 20px; padding: 40px; max-width: 450px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h2 { font-size: 28px; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        h3 { color: #333; margin-bottom: 10px; }
        .subtitle { color: #666; font-size: 14px; margin-bottom: 30px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group input { width: 100%; padding: 14px; border: 1px solid #ddd; border-radius: 10px; font-size: 14px; }
        .form-group input:focus { outline: none; border-color: #667eea; }
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.3s; }
        .btn-submit:hover { transform: translateY(-2px); }
        .alert { padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; text-align: center; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .user-info { background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .password-hint { font-size: 11px; color: #888; margin-top: 5px; display: block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo"><h2>URBAN WHEELS</h2></div>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if($valid_token && !$success): ?>
            <h3>Reset Your Password</h3>
            <div class="user-info">
                <i class="fas fa-user"></i> Resetting password for: <strong><?php echo htmlspecialchars($user_email); ?></strong>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <input type="password" name="password" placeholder="New Password" required>
                </div>
                <div class="password-hint">Password must be 8+ chars with uppercase, lowercase, number & special character</div>
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                </div>
                <button type="submit" class="btn-submit">Reset Password</button>
            </form>
        <?php elseif(!$valid_token && !$success): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="forgot_password.php" style="color: #667eea; text-decoration: none;">Request New Reset Link →</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>