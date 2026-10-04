<?php
require_once 'config/database.php';

echo "<h1>Fix Admin Account</h1>";

$email = 'talktoace25@gmail.com';
$hashed_password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

// Check if user exists
$check = mysqli_query($conn, "SELECT id, email, role FROM users WHERE email = '$email'");

if(mysqli_num_rows($check) > 0) {
    // User exists, update to admin
    $user = mysqli_fetch_assoc($check);
    echo "<p>User found with email: {$user['email']} (Current Role: {$user['role']})</p>";
    
    $update = "UPDATE users SET role = 'admin', status = 'active', password = '$hashed_password' WHERE email = '$email'";
    if(mysqli_query($conn, $update)) {
        echo "<p style='color:green'>✓ User updated to ADMIN successfully!</p>";
    } else {
        echo "<p style='color:red'>Error updating: " . mysqli_error($conn) . "</p>";
    }
} else {
    // User doesn't exist, create new
    $insert = "INSERT INTO users (name, email, password, phone, role, status) 
               VALUES ('Administrator', '$email', '$hashed_password', '+254700000000', 'admin', 'active')";
    if(mysqli_query($conn, $insert)) {
        echo "<p style='color:green'>✓ New admin created successfully!</p>";
    } else {
        echo "<p style='color:red'>Error: " . mysqli_error($conn) . "</p>";
    }
}

// Show all admin users
echo "<h2>Current Admin Users:</h2>";
$admins = mysqli_query($conn, "SELECT id, name, email, role, status FROM users WHERE role = 'admin'");
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></table>";
while($row = mysqli_fetch_assoc($admins)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['name'] . "</td>";
    echo "<td>" . $row['email'] . "</td>";
    echo "<td>" . $row['role'] . "</td>";
    echo "<td>" . $row['status'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Login Credentials:</h2>";
echo "<p><strong>Email:</strong> talktoace25@gmail.com</p>";
echo "<p><strong>Password:</strong> admin123</p>";
echo "<p><a href='admin/simple_login.php'>Go to Admin Login →</a></p>";
?>