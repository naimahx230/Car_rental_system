<?php
require_once '../config/database.php';

echo "<h1>Create Admin Account</h1>";

// Check if admin exists
$check = mysqli_query($conn, "SELECT * FROM users WHERE role = 'admin'");

if(mysqli_num_rows($check) > 0) {
    echo "<p style='color:orange'>Admin account already exists!</p>";
    $admin = mysqli_fetch_assoc($check);
    echo "<p>Email: " . $admin['email'] . "</p>";
    echo "<p><a href='simple_login.php'>Go to Admin Login →</a></p>";
} else {
    // Create new admin
    $name = "Administrator";
    $email = "admin@urbanwheels.com";
    $password = password_hash("admin123", PASSWORD_DEFAULT);
    $phone = "+254700000000";
    
    $insert = "INSERT INTO users (name, email, password, phone, role, status) 
               VALUES ('$name', '$email', '$password', '$phone', 'admin', 'active')";
    
    if(mysqli_query($conn, $insert)) {
        echo "<p style='color:green'>✓ Admin account created successfully!</p>";
        echo "<p><strong>Email:</strong> admin@urbanwheels.com</p>";
        echo "<p><strong>Password:</strong> admin123</p>";
        echo "<p><a href='simple_login.php'>Click here to login →</a></p>";
    } else {
        echo "<p style='color:red'>Error: " . mysqli_error($conn) . "</p>";
    }
}
?>