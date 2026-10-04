<?php
require_once dirname(__DIR__) . '/includes/session_helper.php';
require_once dirname(__DIR__) . '/config/database.php';

// Start admin session
SessionManager::startAdminSession();

$error = '';

// If already logged in as admin, go to dashboard
if(isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') {
    header("Location: dashboard.php");
    exit();
}

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    $result = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email' AND role = 'admin'");
    
    if(mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        if(password_verify($password, $user['password']) && $user['status'] == 'active') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = 'admin';
            $_SESSION['last_activity'] = time();
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid password or inactive account";
        }
    } else {
        $error = "Admin account not found";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - Urban Wheels</title>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 20px;
            width: 400px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,215,0,0.3);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: #FFD700;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .login-header p {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
        }
        .admin-badge {
            display: inline-block;
            background: rgba(255,215,0,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            color: #FFD700;
            margin-top: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: rgba(255,255,255,0.8);
            margin-bottom: 8px;
            font-size: 14px;
        }
        .input-group {
            position: relative;
        }
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-style: normal;
        }
        .form-group input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,215,0,0.3);
            border-radius: 12px;
            color: white;
            font-size: 14px;
            transition: all 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #FFD700;
            background: rgba(255,255,255,0.15);
        }
        .form-group input::placeholder {
            color: rgba(255,255,255,0.5);
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #1a1a2e;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255,215,0,0.4);
        }
        .error-message {
            background: rgba(220,53,69,0.2);
            border: 1px solid rgba(220,53,69,0.3);
            color: #ff6b6b;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }
        .back-link {
            text-align: center;
            margin-top: 25px;
        }
        .back-link a {
            color: #FFD700;
            text-decoration: none;
            font-size: 13px;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        .footer-note {
            text-align: center;
            margin-top: 20px;
            font-size: 11px;
            color: rgba(255,255,255,0.4);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>🔐 Admin Login</h2>
            <p>Urban Wheels Management Portal</p>
            <div class="admin-badge">
                ⚡ Authorized Access Only
            </div>
        </div>
        
        <?php if($error): ?>
            <div class="error-message">
                ⚠️ <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Admin Email</label>
                <div class="input-group">
                    <i>📧</i>
                    <input type="email" name="email" placeholder="admin@urbanwheels.com" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-group">
                    <i>🔒</i>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>
            <button type="submit">Login to Admin Panel →</button>
        </form>
        
        <div class="back-link">
            <a href="../index.php">← Back to Customer Website</a>
        </div>
        <div class="footer-note">
            🔒 Secure encrypted connection
        </div>
    </div>
</body>
</html>