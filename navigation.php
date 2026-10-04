<?php
// Navigation bar - To be included in all pages
if (session_status() === PHP_SESSION_NONE) {
    // Check if this is admin session or customer session
    if (isset($_COOKIE['ADMIN_SESSION']) || (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin')) {
        session_name('ADMIN_SESSION');
        session_start();
    } else {
        session_name('CUSTOMER_SESSION');
        session_start();
    }
}
?>

<nav class="navbar">
    <div class="logo">URBAN WHEELS</div>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="vehicles.php">Vehicles</a>
        <a href="services.php">Services</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        
        <?php if(isset($_SESSION['user_id']) && isset($_SESSION['user_role'])): ?>
            <?php if($_SESSION['user_role'] == 'admin'): ?>
                <!-- Admin Navigation -->
                <a href="admin/dashboard.php">Admin Dashboard</a>
                <a href="admin/vehicles.php">Manage Vehicles</a>
                <a href="admin/bookings.php">Manage Bookings</a>
                <a href="admin/users.php">Manage Users</a>
                <span style="color: #FFD700;">Admin: <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="admin/admin_logout.php" style="background: #dc3545; padding: 8px 20px; border-radius: 25px;">Logout</a>
            <?php else: ?>
                <!-- Customer Navigation -->
                <a href="my_bookings.php">My Bookings</a>
                <a href="cart.php">Cart</a>
                <span style="color: white;">Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="logout.php" style="background: #dc3545; padding: 8px 20px; border-radius: 25px;">Logout</a>
            <?php endif; ?>
        <?php else: ?>
            <!-- Guest Navigation -->
            <a href="index.php" style="background: linear-gradient(135deg, #FFD700, #FFA500); padding: 8px 25px; border-radius: 25px; color: #1a1a2e;">Login / Register</a>
        <?php endif; ?>
    </div>
</nav>