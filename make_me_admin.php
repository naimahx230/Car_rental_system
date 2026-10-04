<?php
require_once 'config/database.php';

echo "<h1>Create Admin Account</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px;'>";

// Check if admin exists
$check = mysqli_query($conn, "SELECT * FROM users WHERE role = 'admin'");
if(mysqli_num_rows($check) > 0) {
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<p style='color: #856404;'><strong>⚠ Admin account already exists!</strong></p>";
    $admin = mysqli_fetch_assoc($check);
    echo "<p>Email: <strong>" . $admin['email'] . "</strong></p>";
    echo "<p><a href='admin/simple_login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Go to Login →</a></p>";
    echo "</div>";
} else {
    // Create admin
    $name = "Administrator";
    $email = "admin@urbanwheels.com";
    $password = password_hash("admin123", PASSWORD_DEFAULT);
    $phone = "0712345678";
    
    $insert = "INSERT INTO users (name, email, password, phone, role, status) 
               VALUES ('$name', '$email', '$password', '$phone', 'admin', 'active')";
    
    if(mysqli_query($conn, $insert)) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p style='color: #155724; font-weight: bold;'>✓ Admin account created successfully!</p>";
        echo "<p><strong>Email:</strong> admin@urbanwheels.com</p>";
        echo "<p><strong>Password:</strong> admin123</p>";
        echo "<p><a href='admin/simple_login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Click here to login →</a></p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p style='color: #721c24;'>Error: " . mysqli_error($conn) . "</p>";
        echo "</div>";
    }
}

// Show all users
echo "<h2>All Users in System:</h2>";
$users = mysqli_query($conn, "SELECT id, name, email, role, status FROM users ORDER BY id");
echo "<table border='1' cellpadding='10' style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #007bff; color: white;'><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
while($user = mysqli_fetch_assoc($users)) {
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td>" . htmlspecialchars($user['name']) . "</td>";
    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
    echo "<td>" . htmlspecialchars($user['role']) . "</td>";
    echo "<td>" . htmlspecialchars($user['status']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<div style='margin-top: 20px;'>";
echo "<a href='admin/simple_login.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Back to Login</a>";
echo "</div>";
echo "</div>";
?>