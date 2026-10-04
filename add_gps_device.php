<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$error = '';
$success = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $vehicle_id = (int)$_POST['vehicle_id'];
    $device_id = mysqli_real_escape_string($conn, $_POST['device_id']);
    $device_name = mysqli_real_escape_string($conn, $_POST['device_name']);
    $imei_number = mysqli_real_escape_string($conn, $_POST['imei_number']);
    
    $check = mysqli_query($conn, "SELECT id FROM gps_devices WHERE device_id = '$device_id'");
    if(mysqli_num_rows($check) > 0) {
        $error = "Device ID already exists!";
    } else {
        $insert = "INSERT INTO gps_devices (vehicle_id, device_id, device_name, imei_number, status) 
                   VALUES ($vehicle_id, '$device_id', '$device_name', '$imei_number', 'active')";
        if(mysqli_query($conn, $insert)) {
            $success = "GPS device added successfully!";
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

$vehicles = mysqli_query($conn, "SELECT id, brand, model, registration_number FROM vehicles ORDER BY brand");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add GPS Device - Admin</title>
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
        }
        .sidebar nav a:hover, .sidebar nav a.active { background: #FFD700; color: #1a1a2e; }
        
        .main-content { margin-left: 260px; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .form-container { background: white; padding: 30px; border-radius: 10px; max-width: 600px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn-submit { background: #28a745; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; }
        .alert { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>URBAN WHEELS</h2>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="vehicles.php">Manage Vehicles</a>
            <a href="gps_tracking.php">GPS Tracking</a>
            <a href="add_gps_device.php" class="active">Add GPS Device</a>
            <a href="admin_logout.php">Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Add GPS Device</h1>
            <a href="gps_tracking.php">← Back to GPS Tracking</a>
        </div>

        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label>Select Vehicle</label>
                    <select name="vehicle_id" required>
                        <option value="">-- Select Vehicle --</option>
                        <?php while($v = mysqli_fetch_assoc($vehicles)): ?>
                            <option value="<?php echo $v['id']; ?>"><?php echo $v['brand'] . ' ' . $v['model'] . ' - ' . $v['registration_number']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Device ID</label>
                    <input type="text" name="device_id" placeholder="Enter unique device ID" required>
                </div>
                <div class="form-group">
                    <label>Device Name</label>
                    <input type="text" name="device_name" placeholder="Device name (e.g., GPS Tracker 001)">
                </div>
                <div class="form-group">
                    <label>IMEI Number</label>
                    <input type="text" name="imei_number" placeholder="Enter IMEI number">
                </div>
                <button type="submit" class="btn-submit">Add GPS Device</button>
            </form>
        </div>
    </div>
</body>
</html>