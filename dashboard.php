<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// ---- STATS ----
$stats = [];

// Total vehicles
$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM vehicles");
$stats['total_vehicles'] = mysqli_fetch_assoc($r)['c'];

// Available vehicles
$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM vehicles WHERE status = 'available'");
$stats['available_vehicles'] = mysqli_fetch_assoc($r)['c'];

// Total bookings
$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM bookings");
$stats['total_bookings'] = mysqli_fetch_assoc($r)['c'];

// Customers
$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users WHERE role = 'customer'");
$stats['customers'] = mysqli_fetch_assoc($r)['c'];

// Total revenue (from paid/completed)
$r = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) AS s FROM bookings WHERE payment_status IN ('Paid','paid','Refunded','refunded') OR status = 'completed'");
$stats['total_revenue'] = mysqli_fetch_assoc($r)['s'];

// This month revenue
$r = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) AS s FROM bookings WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())");
$stats['month_revenue'] = mysqli_fetch_assoc($r)['s'];

// Average rating
$r = mysqli_query($conn, "SELECT COALESCE(AVG(rating), 0) AS a FROM reviews WHERE status = 'approved'");
$stats['avg_rating'] = mysqli_fetch_assoc($r)['a'];

// Unread messages
$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM contact_messages WHERE status = 'unread'");
$stats['unread_messages'] = mysqli_fetch_assoc($r)['c'];

// ---- CHART DATA ----
// Monthly revenue (2026)
$monthly_revenue = array_fill(0, 12, 0);
$r = mysqli_query($conn, "SELECT MONTH(created_at) AS m, COALESCE(SUM(total_amount),0) AS s 
                          FROM bookings 
                          WHERE YEAR(created_at) = YEAR(NOW())
                          GROUP BY MONTH(created_at)");
while ($row = mysqli_fetch_assoc($r)) {
    $monthly_revenue[(int)$row['m'] - 1] = (float)$row['s'];
}

// Daily revenue (last 30 days)
$daily_revenue_labels = [];
$daily_revenue_data   = [];
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $daily_revenue_labels[] = date('d M', strtotime($date));
    $r = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount),0) AS s 
                              FROM bookings 
                              WHERE DATE(created_at) = '$date'");
    $daily_revenue_data[] = (float)mysqli_fetch_assoc($r)['s'];
}

// Monthly bookings (2026)
$monthly_bookings = array_fill(0, 12, 0);
$r = mysqli_query($conn, "SELECT MONTH(created_at) AS m, COUNT(*) AS c 
                          FROM bookings 
                          WHERE YEAR(created_at) = YEAR(NOW())
                          GROUP BY MONTH(created_at)");
while ($row = mysqli_fetch_assoc($r)) {
    $monthly_bookings[(int)$row['m'] - 1] = (int)$row['c'];
}

// Vehicle status distribution
$status_counts = ['available' => 0, 'rented' => 0, 'maintenance' => 0];
$r = mysqli_query($conn, "SELECT status, COUNT(*) AS c FROM vehicles GROUP BY status");
while ($row = mysqli_fetch_assoc($r)) {
    if (isset($status_counts[$row['status']])) {
        $status_counts[$row['status']] = (int)$row['c'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — Urban Wheels Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar { width: 240px; background: #1a1a2e; color: white; padding: 25px 15px; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar h2 { color: #FFD700; font-size: 18px; margin-bottom: 25px; letter-spacing: 1px; padding-left: 10px; }
        .sidebar a {
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
    font-family: inherit;
    transition: background 0.2s, color 0.2s;
}
.sidebar a:hover,
.sidebar a.active {
    background: #FFD700;
    color: #1a1a2e;
    font-weight: 500;
    font-size: 14px;
}
.sidebar a i {
    width: 20px;
    font-size: 14px;
}
        .sidebar a i { width: 20px; }

        /* Main */
        .main { margin-left: 240px; padding: 25px 35px; flex: 1; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .topbar h1 { font-size: 26px; color: #1a1a2e; }
        .topbar .user-info { display: flex; align-items: center; gap: 15px; font-size: 14px; color: #555; }
        .btn-logout { background: #dc3545; color: white; padding: 8px 18px; border-radius: 25px; text-decoration: none; font-weight: 500; font-size: 13px; }
        .btn-logout:hover { background: #c82333; }

        /* Stats Grid */
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 18px; margin-bottom: 30px; }
        .stat { background: white; border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
        .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; flex-shrink: 0; }
        .icon-vehicles  { background: linear-gradient(135deg, #667eea, #764ba2); }
        .icon-available { background: linear-gradient(135deg, #28a745, #20c997); }
        .icon-bookings  { background: linear-gradient(135deg, #17a2b8, #138496); }
        .icon-customers { background: linear-gradient(135deg, #fd7e14, #e8590c); }
        .icon-revenue   { background: linear-gradient(135deg, #FFD700, #FFA500); color: #1a1a2e; }
        .icon-month     { background: linear-gradient(135deg, #e83e8c, #c2185b); }
        .icon-rating    { background: linear-gradient(135deg, #6f42c1, #5a32a3); }
        .icon-messages  { background: linear-gradient(135deg, #dc3545, #c82333); }
        .stat-info .value { font-size: 22px; font-weight: 700; color: #1a1a2e; }
        .stat-info .label { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 3px; }

        /* Charts */
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .chart-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.04); }
        .chart-card h3 { font-size: 14px; color: #1a1a2e; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .chart-card h3 i { color: #FFD700; }
        .chart-wrap { height: 250px; position: relative; }

        @media (max-width: 1000px) { .charts-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) {
            .sidebar { width: 100%; position: relative; height: auto; }
            .main { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>

<?php $current_page = "dashboard"; include __DIR__ . "/../includes/admin_sidebar.php"; ?>

<main class="main">
    <div class="topbar">
        <h1>Dashboard</h1>
        <div class="user-info">
            <span>Welcome back, <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></strong></span>
            <a href="admin_logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <!-- Stats Row 1 -->
    <div class="stats">
        <div class="stat">
            <div class="stat-icon icon-vehicles"><i class="fas fa-car"></i></div>
            <div class="stat-info"><div class="value"><?= $stats['total_vehicles'] ?></div><div class="label">Total Vehicles</div></div>
        </div>
        <div class="stat">
            <div class="stat-icon icon-available"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info"><div class="value"><?= $stats['available_vehicles'] ?></div><div class="label">Available Now</div></div>
        </div>
        <div class="stat">
            <div class="stat-icon icon-bookings"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-info"><div class="value"><?= $stats['total_bookings'] ?></div><div class="label">Total Bookings</div></div>
        </div>
        <div class="stat">
            <div class="stat-icon icon-customers"><i class="fas fa-users"></i></div>
            <div class="stat-info"><div class="value"><?= $stats['customers'] ?></div><div class="label">Customers</div></div>
        </div>
        <div class="stat">
            <div class="stat-icon icon-revenue"><i class="fas fa-money-bill-wave"></i></div>
            <div class="stat-info"><div class="value">KES <?= number_format($stats['total_revenue'], 0) ?></div><div class="label">Total Revenue</div></div>
        </div>
    </div>

    <!-- Stats Row 2 -->
    <div class="stats">
        <div class="stat">
            <div class="stat-icon icon-month"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-info"><div class="value">KES <?= number_format($stats['month_revenue'], 0) ?></div><div class="label">This Month Revenue</div></div>
        </div>
        <div class="stat">
            <div class="stat-icon icon-rating"><i class="fas fa-star"></i></div>
            <div class="stat-info"><div class="value"><?= number_format($stats['avg_rating'], 1) ?> ★</div><div class="label">Average Rating</div></div>
        </div>
        <div class="stat">
            <div class="stat-icon icon-messages"><i class="fas fa-envelope"></i></div>
            <div class="stat-info"><div class="value"><?= $stats['unread_messages'] ?></div><div class="label">Unread Messages</div></div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
        <div class="chart-card">
            <h3><i class="fas fa-chart-area"></i> Monthly Revenue <?= date('Y') ?></h3>
            <div class="chart-wrap"><canvas id="chartMonthly"></canvas></div>
        </div>
        <div class="chart-card">
            <h3><i class="fas fa-chart-bar"></i> Daily Revenue (Last 30 Days)</h3>
            <div class="chart-wrap"><canvas id="chartDaily"></canvas></div>
        </div>
        <div class="chart-card">
            <h3><i class="fas fa-chart-bar"></i> Monthly Bookings <?= date('Y') ?></h3>
            <div class="chart-wrap"><canvas id="chartBookings"></canvas></div>
        </div>
        <div class="chart-card">
            <h3><i class="fas fa-chart-pie"></i> Vehicle Status Distribution</h3>
            <div class="chart-wrap"><canvas id="chartStatus"></canvas></div>
        </div>
    </div>
</main>

<script>
const monthLabels   = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const monthlyData   = <?= json_encode($monthly_revenue) ?>;
const bookingsData  = <?= json_encode($monthly_bookings) ?>;
const dailyLabels   = <?= json_encode($daily_revenue_labels) ?>;
const dailyData     = <?= json_encode($daily_revenue_data) ?>;
const statusData    = <?= json_encode(array_values($status_counts)) ?>;

// Monthly Revenue (area)
new Chart(document.getElementById('chartMonthly'), {
    type: 'line',
    data: {
        labels: monthLabels,
        datasets: [{
            label: 'Revenue (KES)',
            data: monthlyData,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102,126,234,0.15)',
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#667eea',
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});

// Daily Revenue (bar)
new Chart(document.getElementById('chartDaily'), {
    type: 'bar',
    data: {
        labels: dailyLabels,
        datasets: [{
            label: 'Revenue (KES)',
            data: dailyData,
            backgroundColor: '#28a745',
            borderRadius: 4
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { maxRotation: 90, minRotation: 45, font: { size: 9 } } } } }
});

// Monthly Bookings (bar)
new Chart(document.getElementById('chartBookings'), {
    type: 'bar',
    data: {
        labels: monthLabels,
        datasets: [{
            label: 'Bookings',
            data: bookingsData,
            backgroundColor: '#17a2b8',
            borderRadius: 6
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});

// Vehicle Status (donut)
new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: ['Available', 'Rented', 'Maintenance'],
        datasets: [{
            data: statusData,
            backgroundColor: ['#28a745', '#FFD700', '#dc3545'],
            borderWidth: 0
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'bottom' } } }
});
</script>

</body>
</html>
