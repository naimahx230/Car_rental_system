<?php
session_start();
require_once '../config/database.php';
require_once '../includes/security.php';

$security = new Security($conn);
$error = '';

// If already logged in as admin, go to dashboard
if(isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') {
    header("Location: dashboard.php");
    exit();
}

// If customer is logged in, destroy session and show admin login
if(isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'customer') {
    session_destroy();
    session_start();
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $security->sanitize($_POST['email']);
    $password = $_POST['password'];
    
    // Use prepared statement for security
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? AND role = 'admin'");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) == 1) {
        $admin = mysqli_fetch_assoc($result);
        if(password_verify($password, $admin['password']) && $admin['status'] == 'active') {
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_name'] = $admin['name'];
            $_SESSION['user_email'] = $admin['email'];
            $_SESSION['user_role'] = 'admin';
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            $security->createSession($admin['id']);
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid credentials or inactive account";
        }
    } else {
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Urban Wheels</title>
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
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            border: 1px solid rgba(255,215,0,0.3);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h2 {
            font-size: 28px;
            color: #FFD700;
        }
        
        .logo p {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,215,0,0.3);
            border-radius: 10px;
            color: white;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #FFD700;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,215,0,0.3);
        }
        
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            background: rgba(220,53,69,0.2);
            color: #ff6b6b;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #FFD700;
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h2>URBAN WHEELS</h2>
            <p>Administrator Login</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <input type="email" name="email" placeholder="Admin Email" required autofocus>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login to Admin Panel
            </button>
        </form>
        
        <div class="back-link">
            <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Website</a>
        </div>
    </div>
</body>
</html>