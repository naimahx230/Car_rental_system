<?php
session_start();

echo "<h1>Session Debug</h1>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set') . "\n";
echo "User Name: " . (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Not set') . "\n";
echo "User Role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Not set') . "\n";
echo "All Session Data:\n";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Actions:</h2>";
echo "<a href='my_bookings.php'>Go to My Bookings</a><br>";
echo "<a href='logout.php'>Logout</a>";
?>