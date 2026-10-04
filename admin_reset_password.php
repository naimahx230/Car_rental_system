<?php
require_once 'config/database.php';

echo "<h1>Reset Admin Password</h1>";

// New password
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update admin password
$update = "UPDATE users SET password = '$hashed_password' WHERE role = 'admin'";

if(mysqli_query($conn, $update)) {
    echo "<p style='color:green'>✓ Admin password reset successfully!</p>";
    echo "<p><strong>Email:</strong> admin@urbanwheels.com</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
    
    // Verify the update
    $check = mysqli_query($conn, "SELECT id, email, role, password FROM users WHERE role = 'admin'");
    if($admin = mysqli_fetch_assoc($check)) {
        echo "<p>Password hash in database: " . substr($admin['password'], 0, 50) . "...</p>";
        
        // Test the password
        if(password_verify('admin123', $admin['password'])) {
            echo "<p style='color:green'>✓ Password verification successful!</p>";
        } else {
            echo "<p style='color:red'>✗ Password verification failed!</p>";
        }
    }
} else {
    echo "<p style='color:red'>Error: " . mysqli_error($conn) . "</p>";
}

// Show all admin users
echo "<h2>Admin Users:</h2>";
$admins = mysqli_query($conn, "SELECT id, name, email, role, status FROM users WHERE role = 'admin'");
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
while($admin = mysqli_fetch_assoc($admins)) {
    echo "<tr>";
    echo "<td>" . $admin['id'] . "</td>";
    echo "<td>" . $admin['name'] . "</td>";
    echo "<td>" . $admin['email'] . "</td>";
    echo "<td>" . $admin['role'] . "</td>";
    echo "<td>" . $admin['status'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><a href='admin/simple_login.php'>Go to Admin Login →</a></p>";
?>