<?php
session_start();
require_once 'config/database.php';

if(isset($_SESSION['user_id'])) {
    header("Location: vehicles.php");
    exit();
}

$error = '';
$success = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Check if terms are accepted
    if(!isset($_POST['accept_terms'])) {
        $error = "You must agree to the Terms and Conditions and Privacy Policy to register.";
    } else {
        // Validation
        if(empty($name) || empty($email) || empty($phone) || empty($password)) {
            $error = "Please fill in all fields.";
        } elseif($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } elseif(strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } else {
            // Check if email exists
            $check_query = "SELECT id FROM users WHERE email = ?";
            $check_stmt = mysqli_prepare($conn, $check_query);
            mysqli_stmt_bind_param($check_stmt, "s", $email);
            mysqli_stmt_execute($check_stmt);
            mysqli_stmt_store_result($check_stmt);
            
            if(mysqli_stmt_num_rows($check_stmt) > 0) {
                $error = "Email already registered.";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // Insert without terms_accepted columns
                $insert_query = "INSERT INTO users (name, email, phone, password, role, status) 
                                 VALUES (?, ?, ?, ?, 'customer', 'active')";
                $insert_stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($insert_stmt, "ssss", $name, $email, $phone, $hashed_password);
                
                if(mysqli_stmt_execute($insert_stmt)) {
                    $success = "Registration successful! You can now login.";
                    // Auto login after registration
                    $user_id = mysqli_insert_id($conn);
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = 'customer';
                    $_SESSION['last_activity'] = time();
                    header("refresh:2;url=vehicles.php");
                } else {
                    $error = "Registration failed. Please try again.";
                }
                mysqli_stmt_close($insert_stmt);
            }
            mysqli_stmt_close($check_stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Urban Wheels Car Rental</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-container {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            width: 100%;
            max-width: 500px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 215, 0, 0.3);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h2 {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #fff;
            font-weight: 500;
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
            color: #FFD700;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 10px;
            font-size: 14px;
            color: #fff;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #FFD700;
            background: rgba(255, 255, 255, 0.15);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #FFD700, #FFA500);
            color: #000;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.3);
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            color: #ccc;
            font-size: 14px;
        }

        .login-link a {
            color: #FFD700;
            text-decoration: none;
            font-weight: 500;
        }

        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.5);
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.5);
        }

        .password-hint {
            font-size: 11px;
            color: #888;
            margin-top: 5px;
            display: block;
        }

        /* Terms Checkbox Styles */
        .checkbox-group {
            margin: 20px 0;
        }
        .checkbox-label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
            color: #ccc;
            font-size: 13px;
        }
        .checkbox-label input {
            width: auto;
            margin-top: 2px;
        }
        .checkbox-label a {
            color: #FFD700;
            text-decoration: none;
        }
        .checkbox-label a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <h2>Create Account</h2>
        </div>

        <?php if($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Full Name</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="name" required placeholder="Enter your full name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <div class="input-group">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" required placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <div class="input-group">
                    <i class="fas fa-phone"></i>
                    <input type="tel" name="phone" required placeholder="Enter your phone number" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" required placeholder="Create a password (min 6 characters)">
                </div>
                <div class="password-hint">Password must be at least 6 characters</div>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <div class="input-group">
                    <i class="fas fa-check-circle"></i>
                    <input type="password" name="confirm_password" required placeholder="Confirm your password">
                </div>
            </div>

            <!-- Terms and Conditions Checkbox -->
            <div class="checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="accept_terms" required>
                    <span>I have read and agree to the <a href="terms_conditions.php" target="_blank">Terms and Conditions</a> and <a href="privacy_policy.php" target="_blank">Privacy Policy</a></span>
                </label>
            </div>

            <button type="submit" class="btn-register">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>