<?php
session_start();
echo "<h1>Cart Debug</h1>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "User Role: " . ($_SESSION['user_role'] ?? 'Not set') . "\n\n";
echo "Service Cart Contents:\n";
print_r($_SESSION['service_cart'] ?? 'Not set');
echo "</pre>";

echo "<h2>Actions</h2>";
echo "<a href='services.php'>Go to Services</a><br>";
echo "<a href='clear_cart.php'>Clear Cart</a><br>";
echo "<a href='vehicles.php'>Go to Vehicles</a>";
?>