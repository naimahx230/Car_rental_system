<?php
require_once 'config/database.php';

// First, check if there are any users
$result = mysqli_query($conn, "SELECT * FROM users");
echo "<h1>Existing Users:</h1>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
while($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['name'] . "</td>";
    echo "<td>" . $row['email'] . "</td>";
    echo "<td>" . $row['role'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Make a user an Admin</h2>";
echo "<form method='POST'>";
echo "<input type='email' name='email' placeholder='Enter user email' required>";
echo "<button type='submit' name='make_admin'>Make Admin</button>";
echo "</form>";

if(isset($_POST['make_admin'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $update = "UPDATE users SET role = 'admin' WHERE email = '$email'";
    if(mysqli_query($conn, $update)) {
        echo "<p style='color:green'>User with email $email is now an ADMIN!</p>";
    } else {
        echo "<p style='color:red'>Error: " . mysqli_error($conn) . "</p>";
    }
}

echo "<h2>Or Create a New Admin:</h2>";
echo "<form method='POST'>";
echo "<input type='text' name='name' placeholder='Full Name' required><br><br>";
echo "<input type='email' name='email' placeholder='Email' required><br><br>";
echo "<input type='password' name='password' placeholder='Password' required><br><br>";
echo "<button type='submit' name='create_admin'>Create Admin</button>";
echo "</form>";

if(isset($_POST['create_admin'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $insert = "INSERT INTO users (name, email, password, role, status) 
               VALUES ('$name', '$email', '$password', 'admin', 'active')";
    if(mysqli_query($conn, $insert)) {
        echo "<p style='color:green'>Admin user created! You can now login.</p>";
    } else {
        echo "<p style='color:red'>Error: " . mysqli_error($conn) . "</p>";
    }
}
?>