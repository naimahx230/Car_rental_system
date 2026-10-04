<?php
// includes/admin_sidebar.php
// Shared sidebar with self-contained CSS
// Usage: set $current_page = 'dashboard'; then include this file.
$current_page = $current_page ?? '';

function sidebar_link($href, $icon, $label, $current, $key) {
    $active = ($current === $key) ? ' active' : '';
    echo "<a href=\"{$href}\" class=\"sidebar-link{$active}\"><i class=\"{$icon}\"></i><span>{$label}</span></a>";
}
?>
<style>
    .sidebar {
        width: 240px;
        background: #1a1a2e;
        color: #fff;
        padding: 25px 15px;
        min-height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        overflow-y: auto;
        font-family: 'Inter', 'Poppins', system-ui, -apple-system, sans-serif;
        z-index: 100;
    }
    .sidebar h2 {
        color: #FFD700;
        font-size: 18px;
        font-weight: 800;
        letter-spacing: 1px;
        margin: 0 0 25px 0;
        padding-left: 12px;
    }
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 12px;
        color: rgba(255,255,255,0.75);
        text-decoration: none;
        padding: 11px 14px;
        border-radius: 8px;
        margin-bottom: 4px;
        font-size: 14px;
        font-weight: 500;
        line-height: 1.2;
        transition: background 0.2s, color 0.2s;
        white-space: nowrap;
    }
    .sidebar-link:hover {
        background: rgba(255,215,0,0.15);
        color: #FFD700;
    }
    .sidebar-link.active {
        background: #FFD700;
        color: #1a1a2e;
        font-weight: 600;
    }
    .sidebar-link i {
        width: 18px;
        text-align: center;
        font-size: 14px;
        flex-shrink: 0;
    }
    .sidebar-link span {
        font-size: 14px;
    }
    .main, .main-content {
        margin-left: 240px;
    }
    @media (max-width: 768px) {
        .sidebar { width: 100%; position: relative; min-height: auto; }
        .main, .main-content { margin-left: 0; }
    }
</style>
<aside class="sidebar">
    <h2>URBAN WHEELS</h2>
    <?php
    sidebar_link('dashboard.php',      'fas fa-tachometer-alt', 'Dashboard',        $current_page, 'dashboard');
    sidebar_link('vehicles.php',       'fas fa-car',            'Manage Vehicles',  $current_page, 'vehicles');
    sidebar_link('add_vehicle.php',    'fas fa-plus-circle',    'Add Vehicle',      $current_page, 'add_vehicle');
    sidebar_link('bookings.php',       'fas fa-calendar-check', 'Manage Bookings',  $current_page, 'bookings');
    sidebar_link('services.php',       'fas fa-concierge-bell', 'Manage Services',  $current_page, 'services');
    sidebar_link('users.php',          'fas fa-users',          'Manage Users',     $current_page, 'users');
    sidebar_link('reviews.php',        'fas fa-star',           'Manage Reviews',   $current_page, 'reviews');
    sidebar_link('contacts.php',       'fas fa-envelope',       'Contact Messages', $current_page, 'contacts');
    sidebar_link('about_content.php',  'fas fa-info-circle',    'About Content',    $current_page, 'about_content');
    sidebar_link('gps_tracking.php',   'fas fa-map-marker-alt', 'GPS Tracking',     $current_page, 'gps_tracking');
    sidebar_link('manage_images.php',  'fas fa-images',         'Manage Images',    $current_page, 'manage_images');
    sidebar_link('admin_logout.php',   'fas fa-sign-out-alt',   'Logout',           $current_page, 'logout');
    ?>
</aside>
