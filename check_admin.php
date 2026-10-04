<?php
require_once 'config/database.php';

echo "<h1>Admin Account Check</h1>";

// Check if admin exists
$result = mysqli_query($conn, "SELECT * FROM users WHERE role = 'admin'");

if(mysqli_num_rows($result) > 0) {
    echo "<p style='color:green'>✓ Admin account exists!</p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
    while($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['role'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red'>✗ No admin account found!</p>";
}

// Show all users
echo "<h2>All Users in Database:</h2>";
$all_users = mysqli_query($conn, "SELECT id, name, email, role, status FROM users");
if(mysqli_num_rows($all_users) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr>";
    while($user = mysqli_fetch_assoc($all_users)) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['name'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "<td>" . $user['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No users found in database.</p>";
}
?>