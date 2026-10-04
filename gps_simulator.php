<?php
require_once 'auth_check.php';  // ← ADD THIS AT THE VERY TOP
require_once '../config/database.php';

// Rest of your gps_simulator code continues...
?>
<?php
session_start();
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $vehicle_id = (int)$_POST['vehicle_id'];
    $latitude = (float)$_POST['latitude'];
    $longitude = (float)$_POST['longitude'];
    $speed = (float)$_POST['speed'];
    
    // Update last location
    mysqli_query($conn, "UPDATE gps_devices SET 
        last_location_lat = $latitude, 
        last_location_lng = $longitude, 
        last_update = NOW(),
        speed = $speed
        WHERE vehicle_id = $vehicle_id");
    
    // Insert tracking history
    mysqli_query($conn, "INSERT INTO gps_tracking_history (vehicle_id, latitude, longitude, speed, recorded_at) 
                         VALUES ($vehicle_id, $latitude, $longitude, $speed, NOW())");
    
    $message = "Location updated successfully!";
}

$vehicles = mysqli_query($conn, "SELECT v.*, g.device_id FROM vehicles v LEFT JOIN gps_devices g ON v.id = g.vehicle_id WHERE g.device_id IS NOT NULL");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPS Simulator - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #667eea; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; }
        input, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #28a745; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; }
        .alert { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-satellite-dish"></i> GPS Simulator</h1>
        <p>Simulate GPS location updates for testing</p>
        
        <?php if($message): ?>
            <div class="alert"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Select Vehicle</label>
                <select name="vehicle_id" required>
                    <option value="">-- Select Vehicle --</option>
                    <?php while($v = mysqli_fetch_assoc($vehicles)): ?>
                        <option value="<?php echo $v['id']; ?>"><?php echo $v['brand'] . ' ' . $v['model']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Latitude</label>
                <input type="text" name="latitude" value="-1.286389" required>
            </div>
            <div class="form-group">
                <label>Longitude</label>
                <input type="text" name="longitude" value="36.817223" required>
            </div>
            <div class="form-group">
                <label>Speed (km/h)</label>
                <input type="number" name="speed" value="0" step="5">
            </div>
            <button type="submit">Update Location</button>
        </form>
        
        <div style="margin-top: 20px;">
            <h3>Popular Nairobi Locations:</h3>
            <ul>
                <li>Nairobi CBD: -1.286389, 36.817223</li>
                <li>JKIA Airport: -1.319241, 36.927795</li>
                <li>Westlands: -1.267000, 36.803000</li>
                <li>Karen: -1.319000, 36.712000</li>
            </ul>
        </div>
    </div>
</body>
</html>