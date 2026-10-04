<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// Handle status update
if(isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    mysqli_query($conn, "UPDATE vehicles SET status = '$status' WHERE id = $id");
    header("Location: vehicles.php");
    exit();
}

// Handle featured toggle
if(isset($_GET['featured']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $featured = (int)$_GET['featured'];
    mysqli_query($conn, "UPDATE vehicles SET featured = $featured WHERE id = $id");
    header("Location: vehicles.php");
    exit();
}

// Handle delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $img = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM vehicles WHERE id = $id"));
    if($img['image'] && file_exists('../' . $img['image'])) {
        unlink('../' . $img['image']);
    }
    mysqli_query($conn, "DELETE FROM vehicles WHERE id = $id");
    header("Location: vehicles.php");
    exit();
}

// Get filter
$status_filter = isset($_GET['status_filter']) ? mysqli_real_escape_string($conn, $_GET['status_filter']) : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$query = "SELECT * FROM vehicles WHERE 1=1";
if($status_filter) $query .= " AND status = '$status_filter'";
if($search) $query .= " AND (brand LIKE '%$search%' OR model LIKE '%$search%' OR registration_number LIKE '%$search%')";
$query .= " ORDER BY featured DESC, brand, model";

$vehicles = mysqli_query($conn, $query);

// Get counts
$total = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM vehicles"));
$available = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM vehicles WHERE status = 'available'"));
$rented = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM vehicles WHERE status = 'rented'"));
$maintenance = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM vehicles WHERE status = 'maintenance'"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vehicles - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn-add { background: #28a745; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-add:hover { background: #218838; }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-box:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .stat-box.active { border: 2px solid #667eea; background: rgba(102,126,234,0.05); }
        .stat-number { font-size: 28px; font-weight: 700; }
        .stat-label { font-size: 12px; color: #666; margin-top: 5px; }
        .stat-available .stat-number { color: #28a745; }
        .stat-rented .stat-number { color: #ffc107; }
        .stat-maintenance .stat-number { color: #dc3545; }
        
        .filters {
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .filter-group input, .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .btn-filter { background: #667eea; color: white; padding: 8px 20px; border: none; border-radius: 5px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-reset { background: #6c757d; color: white; padding: 8px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        
        .vehicles-table {
            background: white;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #667eea; color: white; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        
        .vehicle-image {
            width: 60px;
            height: 60px;
            background: #f0f0f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .vehicle-image img { width: 100%; height: 100%; object-fit: cover; }
        
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .status-available { background: #d4edda; color: #155724; }
        .status-rented { background: #fff3cd; color: #856404; }
        .status-maintenance { background: #f8d7da; color: #721c24; }
        
        .featured-star { cursor: pointer; text-decoration: none; font-size: 18px; }
        .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-edit { background: #ffc107; color: #000; padding: 5px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        .btn-delete { background: #dc3545; color: white; padding: 5px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        .btn-view { background: #17a2b8; color: white; padding: 5px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; display: inline-flex; align-items: center; gap: 5px; }
        
        select.status-select { padding: 5px 10px; border-radius: 4px; border: 1px solid #ddd; cursor: pointer; }
        
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
            .action-buttons { flex-direction: column; }
            th, td { padding: 8px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <?php $current_page = "vehicles"; include __DIR__ . "/../includes/admin_sidebar.php"; ?>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-car"></i> Manage Vehicles</h1>
            <a href="add_vehicle.php" class="btn-add"><i class="fas fa-plus"></i> Add New Vehicle</a>
        </div>

        <div class="stats">
            <div class="stat-box stat-available <?php echo $status_filter == 'available' ? 'active' : ''; ?>" onclick="filterByStatus('available')">
                <div class="stat-number"><?php echo $available; ?></div>
                <div class="stat-label">Available</div>
            </div>
            <div class="stat-box stat-rented <?php echo $status_filter == 'rented' ? 'active' : ''; ?>" onclick="filterByStatus('rented')">
                <div class="stat-number"><?php echo $rented; ?></div>
                <div class="stat-label">Rented</div>
            </div>
            <div class="stat-box stat-maintenance <?php echo $status_filter == 'maintenance' ? 'active' : ''; ?>" onclick="filterByStatus('maintenance')">
                <div class="stat-number"><?php echo $maintenance; ?></div>
                <div class="stat-label">Maintenance</div>
            </div>
            <div class="stat-box <?php echo !$status_filter ? 'active' : ''; ?>" onclick="filterByStatus('')">
                <div class="stat-number"><?php echo $total; ?></div>
                <div class="stat-label">All Vehicles</div>
            </div>
        </div>

        <div class="filters">
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Search by brand, model, reg..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <input type="hidden" name="status_filter" value="<?php echo $status_filter; ?>">
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Search</button>
                <a href="vehicles.php" class="btn-reset"><i class="fas fa-sync-alt"></i> Reset</a>
            </form>
        </div>

        <div class="vehicles-table">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Registration</th>
                        <th>Brand/Model</th>
                        <th>Year</th>
                        <th>Daily Rate</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($vehicles) > 0): ?>
                        <?php while($vehicle = mysqli_fetch_assoc($vehicles)): ?>
                        <tr>
                            <td>
                                <div class="vehicle-image">
                                    <?php if($vehicle['image'] && file_exists('../' . $vehicle['image'])): ?>
                                        <img src="../<?php echo $vehicle['image']; ?>" alt="<?php echo $vehicle['brand']; ?>">
                                    <?php else: ?>
                                        <i class="fas fa-car" style="font-size: 24px; color: #ccc;"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($vehicle['registration_number']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($vehicle['brand']); ?></strong><br>
                                <small><?php echo htmlspecialchars($vehicle['model']); ?></small>
                            </td>
                            <td><?php echo $vehicle['year']; ?></td>
                            <td>KES <?php echo number_format($vehicle['daily_rate'], 2); ?></td>
                            <td>
                                <select class="status-select" onchange="updateStatus(<?php echo $vehicle['id']; ?>, this.value)">
                                    <option value="available" <?php echo $vehicle['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="rented" <?php echo $vehicle['status'] == 'rented' ? 'selected' : ''; ?>>Rented</option>
                                    <option value="maintenance" <?php echo $vehicle['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                </select>
                            </td>
                            <td>
                                <a href="?featured=<?php echo $vehicle['featured'] ? 0 : 1; ?>&id=<?php echo $vehicle['id']; ?>" class="featured-star">
                                    <?php if($vehicle['featured']): ?>
                                        <i class="fas fa-star" style="color: #FFD700;"></i>
                                    <?php else: ?>
                                        <i class="far fa-star" style="color: #999;"></i>
                                    <?php endif; ?>
                                </a>
                            </td>
                            <td class="action-buttons">
                                <a href="edit_vehicle.php?id=<?php echo $vehicle['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                <a href="../vehicle_details.php?id=<?php echo $vehicle['id']; ?>" class="btn-view" target="_blank"><i class="fas fa-eye"></i> View</a>
                                <a href="?delete=<?php echo $vehicle['id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this vehicle permanently?')"><i class="fas fa-trash"></i> Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 60px;">
                                <i class="fas fa-car-side" style="font-size: 48px; color: #ccc;"></i>
                                <p style="margin-top: 10px;">No vehicles found</p>
                                <a href="add_vehicle.php" class="btn-add" style="display: inline-block; margin-top: 15px;">Add Your First Vehicle</a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function updateStatus(id, status) {
            if(confirm('Change vehicle status to ' + status.toUpperCase() + '?')) {
                window.location.href = `?status=${status}&id=${id}`;
            }
        }
        
        function filterByStatus(status) {
            window.location.href = `?status_filter=${status}`;
        }
    </script>
</body>
</html>