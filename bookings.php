<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// Handle admin force cancellation
if(isset($_GET['force_cancel']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $reason = isset($_GET['reason']) ? mysqli_real_escape_string($conn, $_GET['reason']) : 'Cancelled by administrator';
    
    mysqli_begin_transaction($conn);
    
    $booking = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM bookings WHERE id = $id"));
    
    if($booking) {
        mysqli_query($conn, "UPDATE bookings SET status = 'cancelled', cancellation_reason = '$reason', cancelled_at = NOW() WHERE id = $id");
        mysqli_query($conn, "UPDATE vehicles SET status = 'available' WHERE id = {$booking['vehicle_id']}");
        
        if($booking['payment_status'] == 'paid') {
            mysqli_query($conn, "UPDATE payments SET status = 'refunded' WHERE booking_id = $id");
            mysqli_query($conn, "UPDATE bookings SET payment_status = 'refunded' WHERE id = $id");
            
            $refund_query = "INSERT INTO refunds (booking_id, original_amount, refund_amount, cancellation_fee, reason, status) 
                             VALUES ($id, {$booking['total_amount']}, {$booking['total_amount']}, 0, '$reason', 'processed')";
            mysqli_query($conn, $refund_query);
        }
        
        mysqli_commit($conn);
        $_SESSION['cancel_success'] = "Booking #{$booking['booking_number']} has been force cancelled by admin!";
    }
    header("Location: bookings.php");
    exit();
}

// Handle status update
if(isset($_GET['update_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['update_status'];
    mysqli_query($conn, "UPDATE bookings SET status = '$status' WHERE id = $id");
    header("Location: bookings.php");
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT b.*, u.name as customer_name, u.email, u.phone, v.brand, v.model, v.registration_number,
          p.payment_method, p.receipt_number, p.payment_date,
          (SELECT COUNT(*) FROM booking_services WHERE booking_id = b.id) as services_count,
          (SELECT SUM(total_price) FROM booking_services WHERE booking_id = b.id) as services_total
          FROM bookings b 
          JOIN users u ON b.user_id = u.id 
          JOIN vehicles v ON b.vehicle_id = v.id 
          LEFT JOIN payments p ON b.id = p.booking_id 
          WHERE 1=1";

if($status_filter) $query .= " AND b.status = '$status_filter'";
if($search) $query .= " AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR b.booking_number LIKE '%$search%')";
$query .= " ORDER BY b.created_at DESC";

$bookings_result = mysqli_query($conn, $query);

// Get counts
$total_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings"))['count'];
$pending_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'"))['count'];
$confirmed_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE status = 'confirmed'"))['count'];
$active_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE status = 'active'"))['count'];
$completed_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE status = 'completed'"))['count'];
$cancelled_bookings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM bookings WHERE status = 'cancelled'"))['count'];
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM bookings WHERE payment_status = 'paid'"))['total'];

$cancel_success = isset($_SESSION['cancel_success']) ? $_SESSION['cancel_success'] : '';
unset($_SESSION['cancel_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100%;
            background: #1a1a2e;
            padding: 20px;
            overflow-y: auto;
        }
        .sidebar h2 { color: #FFD700; margin-bottom: 30px; }
        .sidebar nav a {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
            padding: 12px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .sidebar nav a:hover, .sidebar nav a.active { background: #FFD700; color: #1a1a2e; }
        
        .main-content { margin-left: 260px; padding: 20px; }
        .header {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header h1 { font-size: 24px; color: #333; }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-box:hover { transform: translateY(-2px); }
        .stat-box .number { font-size: 28px; font-weight: 700; }
        .stat-box .label { font-size: 12px; color: #666; margin-top: 5px; }
        .stat-box.total .number { color: #667eea; }
        .stat-box.pending .number { color: #ffc107; }
        .stat-box.confirmed .number { color: #17a2b8; }
        .stat-box.active .number { color: #28a745; }
        .stat-box.completed .number { color: #6c757d; }
        .stat-box.cancelled .number { color: #dc3545; }
        .stat-box.revenue .number { color: #28a745; }
        
        .filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .filter-group { display: flex; gap: 10px; align-items: center; }
        .filter-group select, .filter-group input { padding: 8px 15px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; }
        .btn-filter { background: #667eea; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; }
        .btn-reset { background: #6c757d; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-export { background: #28a745; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; }
        
        .bookings-table {
            background: white;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #667eea; color: white; font-weight: 600; position: sticky; top: 0; }
        tr:hover { background: #f8f9fa; }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-confirmed { background: #17a2b8; color: #fff; }
        .status-active { background: #28a745; color: #fff; }
        .status-completed { background: #6c757d; color: #fff; }
        .status-cancelled { background: #dc3545; color: #fff; }
        
        .payment-paid { color: #28a745; font-weight: 600; }
        .payment-pending { color: #ffc107; font-weight: 600; }
        
        .services-badge {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            display: inline-block;
        }
        
        .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-view {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }
        .btn-cancel-admin {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            display: inline-block;
        }
        .btn-cancel-admin:hover { background: #c82333; }
        select.status-select { padding: 5px 10px; border-radius: 5px; border: 1px solid #ddd; font-family: inherit; font-size: 12px; }
        
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            table { display: block; overflow-x: auto; }
            .action-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php $current_page = "bookings"; include __DIR__ . "/../includes/admin_sidebar.php"; ?>

    <div class="main-content">
        <div class="header">
            <div><h1><i class="fas fa-calendar-check"></i> Manage Bookings</h1><p>View and manage all customer bookings</p></div>
            <div><a href="export_bookings.php" class="btn-export"><i class="fas fa-download"></i> Export Report</a></div>
        </div>

        <?php if($cancel_success): ?>
        <div class="alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $cancel_success; ?>
        </div>
        <?php endif; ?>

        <div class="stats-row">
            <div class="stat-box total" onclick="window.location.href='?status='"><div class="number"><?php echo $total_bookings; ?></div><div class="label">Total Bookings</div></div>
            <div class="stat-box pending" onclick="window.location.href='?status=pending'"><div class="number"><?php echo $pending_bookings; ?></div><div class="label">Pending</div></div>
            <div class="stat-box confirmed" onclick="window.location.href='?status=confirmed'"><div class="number"><?php echo $confirmed_bookings; ?></div><div class="label">Confirmed</div></div>
            <div class="stat-box active" onclick="window.location.href='?status=active'"><div class="number"><?php echo $active_bookings; ?></div><div class="label">Active</div></div>
            <div class="stat-box completed" onclick="window.location.href='?status=completed'"><div class="number"><?php echo $completed_bookings; ?></div><div class="label">Completed</div></div>
            <div class="stat-box cancelled" onclick="window.location.href='?status=cancelled'"><div class="number"><?php echo $cancelled_bookings; ?></div><div class="label">Cancelled</div></div>
            <div class="stat-box revenue"><div class="number">KES <?php echo number_format($total_revenue ?? 0, 0); ?></div><div class="label">Total Revenue</div></div>
        </div>

        <div class="filters">
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <div class="filter-group">
                    <label>Status:</label>
                    <select name="status">
                        <option value="">All</option>
                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Search:</label>
                    <input type="text" name="search" placeholder="Name, Email, Booking #" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filter</button>
                <a href="bookings.php" class="btn-reset"><i class="fas fa-sync-alt"></i> Reset</a>
            </form>
        </div>

        <div class="bookings-table">
            <table>
                <thead>
                    <tr><th>ID</th><th>Booking #</th><th>Customer</th><th>Vehicle</th><th>Pickup Date</th><th>Return Date</th><th>Amount</th><th>Services</th><th>Status</th><th>Payment</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($bookings_result) > 0): ?>
                        <?php while($booking = mysqli_fetch_assoc($bookings_result)): ?>
                        <tr>
                            <td><?php echo $booking['id']; ?></td>
                            <td><strong><?php echo $booking['booking_number']; ?></strong></td>
                            <td><?php echo htmlspecialchars($booking['customer_name']); ?><br><small><?php echo $booking['email']; ?></small></td>
                            <td><?php echo $booking['brand'] . ' ' . $booking['model']; ?><br><small><?php echo $booking['registration_number']; ?></small></td>
                            <td><?php echo date('M d, Y', strtotime($booking['pickup_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($booking['return_date'])); ?></td>
                            <td>KES <?php echo number_format($booking['total_amount'], 2); ?></td>
                            <td><?php if($booking['services_count'] > 0): ?><span class="services-badge"><i class="fas fa-concierge-bell"></i> <?php echo $booking['services_count']; ?> services</span><?php else: ?><span style="color: #999;">-</span><?php endif; ?></td>
                            <td><select class="status-select" onchange="updateStatus(this, <?php echo $booking['id']; ?>)"><option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option><option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option><option value="active" <?php echo $booking['status'] == 'active' ? 'selected' : ''; ?>>Active</option><option value="completed" <?php echo $booking['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option><option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option></select></td>
                            <td class="<?php echo $booking['payment_status'] == 'paid' ? 'payment-paid' : 'payment-pending'; ?>"><?php echo $booking['payment_status'] == 'paid' ? '✓ Paid' : '⏳ Pending'; ?></td>
                            <td><div class="action-buttons"><a href="booking_details.php?id=<?php echo $booking['id']; ?>" class="btn-view" target="_blank"><i class="fas fa-eye"></i> View</a><a href="../cancel_booking.php?id=<?php echo $booking['id']; ?>" class="btn-cancel-admin" onclick="return confirm('Cancel this booking? Admin override enabled.')"><i class="fas fa-times-circle"></i> Cancel</a></div></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="11" style="text-align: center; padding: 60px;"><i class="fas fa-calendar-alt" style="font-size: 48px; color: #ccc;"></i><p style="margin-top: 10px;">No bookings found</p></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function updateStatus(select, bookingId) {
            const status = select.value;
            if(confirm('Change booking status to ' + status.toUpperCase() + '?')) {
                window.location.href = `?update_status=${status}&id=${bookingId}`;
            } else {
                location.reload();
            }
        }
    </script>
</body>
</html>