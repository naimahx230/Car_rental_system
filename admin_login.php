<?php
session_name('ADMIN_SESSION');
session_start();

if (isset($_SESSION['admin_id'])) {
    header("Location: admin/dashboard.php");
    exit();
}

$error = '';
$conn = mysqli_connect('127.0.0.1', 'urbanwheels', 'StrongPass123!', 'car_rental_system');
if (!$conn) die("Connection failed: " . mysqli_connect_error());

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ? AND role = 'admin' AND status = 'active'");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_id']    = $user['id'];
            $_SESSION['admin_name']  = $user['name'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_role']  = 'admin';
            header("Location: admin/dashboard.php");
            exit();
        }
        $error = "Invalid password";
    } else {
        $error = "Admin account not found";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login — Urban Wheels</title>
    <style>
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63); min-height: 100vh; display: flex; align-items: center; justify-content: center; margin: 0; }
        .box { background: white; padding: 40px; border-radius: 12px; width: 350px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        h2 { text-align: center; color: #333; margin-bottom: 25px; }
        input { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 14px; }
        button { width: 100%; padding: 12px; background: linear-gradient(135deg, #FFD700, #FFA500); color: #000; border: none; border-radius: 6px; font-size: 15px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .err { background: #fee; color: #c33; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-size: 13px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Admin Login</h2>
        <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Admin email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
