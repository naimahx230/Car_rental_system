<?php
require_once 'auth_check.php';  // ← ADD THIS AT THE VERY TOP
require_once '../config/database.php';

// Rest of your update_vehicle_locations code continues...
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
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    
    $update = "UPDATE vehicles SET latitude = $latitude, longitude = $longitude, location_address = '$address', last_location_update = NOW() WHERE id = $vehicle_id";
    
    if(mysqli_query($conn, $update)) {
        $message = "Location updated successfully!";
    } else {
        $message = "Error: " . mysqli_error($conn);
    }
}

$vehicles = mysqli_query($conn, "SELECT id, brand, model, registration_number, latitude, longitude, location_address FROM vehicles ORDER BY brand");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Vehicle Locations - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        h1 { color: #667eea; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; }
        select, input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #28a745; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; }
        .alert { background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #667eea; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-map-marker-alt"></i> Update Vehicle Locations</h1>
        
        <?php if($message): ?>
            <div class="alert"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Select Vehicle</label>
                <select name="vehicle_id" required>
                    <option value="">-- Select Vehicle --</option>
                    <?php while($v = mysqli_fetch_assoc($vehicles)): ?>
                        <option value="<?php echo $v['id']; ?>">
                            <?php echo $v['brand'] . ' ' . $v['model'] . ' - ' . $v['registration_number']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Latitude</label>
                <input type="text" name="latitude" placeholder="-1.286389" required>
            </div>
            <div class="form-group">
                <label>Longitude</label>
                <input type="text" name="longitude" placeholder="36.817223" required>
            </div>
            <div class="form-group">
                <label>Location Address</label>
                <input type="text" name="address" placeholder="Nairobi CBD, Kenyatta Avenue">
            </div>
            <button type="submit">Update Location</button>
        </form>
        
        <h3>Popular Nairobi Locations:</h3>
        <ul>
            <li>Nairobi CBD: -1.286389, 36.817223</li>
            <li>JKIA Airport: -1.319241, 36.927795</li>
            <li>Westlands: -1.267000, 36.803000</li>
            <li>Karen: -1.319000, 36.712000</li>
            <li>Mombasa Road: -1.320000, 36.820000</li>
        </ul>
        
        <h3>Current Vehicle Locations</h3>
        <table>
            <thead><tr><th>Vehicle</th><th>Location</th><th>Coordinates</th></tr></thead>
            <tbody>
                <?php 
                mysqli_data_seek($vehicles, 0);
                while($v = mysqli_fetch_assoc($vehicles)): 
                ?>
                <tr>
                    <td><?php echo $v['brand'] . ' ' . $v['model']; ?></td>
                    <td><?php echo $v['location_address'] ?: 'Not set'; ?></td>
                    <td><?php echo $v['latitude'] ? $v['latitude'] . ', ' . $v['longitude'] : 'Not set'; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>