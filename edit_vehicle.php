<?php
require_once 'auth_check.php';
require_once '../config/database.php';

function uploadVehicleImage($file, $vehicle_id, $brand, $model) {
    if($file['error'] != UPLOAD_ERR_OK) {
        return null;
    }
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if(!in_array($ext, $allowed)) {
        return null;
    }
    
    $upload_dir = '../uploads/vehicles/';
    if(!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $new_filename = strtolower($brand . '_' . $model . '_' . time() . '.' . $ext);
    $new_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $new_filename);
    $destination = $upload_dir . $new_filename;
    
    if(move_uploaded_file($file['tmp_name'], $destination)) {
        return 'uploads/vehicles/' . $new_filename;
    }
    
    return null;
}

$vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$vehicle = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM vehicles WHERE id = $vehicle_id"));

if(!$vehicle) {
    header("Location: vehicles.php");
    exit();
}

$message = '';
$error = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $brand = mysqli_real_escape_string($conn, $_POST['brand']);
    $model = mysqli_real_escape_string($conn, $_POST['model']);
    $year = (int)$_POST['year'];
    $registration_number = mysqli_real_escape_string($conn, $_POST['registration_number']);
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    $transmission = mysqli_real_escape_string($conn, $_POST['transmission']);
    $seats = (int)$_POST['seats'];
    $fuel_type = mysqli_real_escape_string($conn, $_POST['fuel_type']);
    $daily_rate = (float)$_POST['daily_rate'];
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $features = mysqli_real_escape_string($conn, $_POST['features']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $image_path = $vehicle['image'];
    if(isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] == UPLOAD_ERR_OK) {
        $uploaded_image = uploadVehicleImage($_FILES['vehicle_image'], $vehicle_id, $brand, $model);
        if($uploaded_image) {
            if($vehicle['image'] && file_exists('../' . $vehicle['image'])) {
                unlink('../' . $vehicle['image']);
                $thumb_path = str_replace('uploads/vehicles/', 'uploads/vehicles/thumbs/', '../' . $vehicle['image']);
                if(file_exists($thumb_path)) {
                    unlink($thumb_path);
                }
            }
            $image_path = $uploaded_image;
        }
    }
    
    $update = "UPDATE vehicles SET 
               brand = '$brand',
               model = '$model',
               year = $year,
               registration_number = '$registration_number',
               color = '$color',
               transmission = '$transmission',
               seats = $seats,
               fuel_type = '$fuel_type',
               daily_rate = $daily_rate,
               description = '$description',
               features = '$features',
               status = '$status',
               image = '$image_path'
               WHERE id = $vehicle_id";
    
    if(mysqli_query($conn, $update)) {
        $message = "Vehicle updated successfully!";
        $vehicle = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM vehicles WHERE id = $vehicle_id"));
    } else {
        $error = "Update failed: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Vehicle - Admin</title>
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
        }
        .sidebar nav a:hover, .sidebar nav a.active { background: #FFD700; color: #1a1a2e; }
        
        .main-content { margin-left: 260px; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            max-width: 800px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .current-image {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .current-image img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .btn-save {
            background: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
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
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="vehicles.php" class="active"><i class="fas fa-car"></i> Manage Vehicles</a>
            <a href="add_vehicle.php"><i class="fas fa-plus"></i> Add Vehicle</a>
            <a href="manage_images.php"><i class="fas fa-images"></i> Manage Images</a>
            <a href="admin_logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Edit Vehicle</h1>
            <p>Update vehicle information</p>
        </div>

        <div class="form-container">
            <?php if($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group"><label>Brand *</label><input type="text" name="brand" value="<?php echo htmlspecialchars($vehicle['brand']); ?>" required></div>
                    <div class="form-group"><label>Model *</label><input type="text" name="model" value="<?php echo htmlspecialchars($vehicle['model']); ?>" required></div>
                </div>

                <div class="form-row">
                    <div class="form-group"><label>Year *</label><input type="number" name="year" value="<?php echo $vehicle['year']; ?>" required></div>
                    <div class="form-group"><label>Registration Number *</label><input type="text" name="registration_number" value="<?php echo htmlspecialchars($vehicle['registration_number']); ?>" required></div>
                </div>

                <div class="form-row">
                    <div class="form-group"><label>Color</label><input type="text" name="color" value="<?php echo htmlspecialchars($vehicle['color']); ?>"></div>
                    <div class="form-group"><label>Transmission *</label><select name="transmission" required><option value="Manual" <?php echo $vehicle['transmission'] == 'Manual' ? 'selected' : ''; ?>>Manual</option><option value="Automatic" <?php echo $vehicle['transmission'] == 'Automatic' ? 'selected' : ''; ?>>Automatic</option></select></div>
                </div>

                <div class="form-row">
                    <div class="form-group"><label>Seats *</label><input type="number" name="seats" value="<?php echo $vehicle['seats']; ?>" required></div>
                    <div class="form-group"><label>Fuel Type</label><select name="fuel_type"><option value="Petrol" <?php echo $vehicle['fuel_type'] == 'Petrol' ? 'selected' : ''; ?>>Petrol</option><option value="Diesel" <?php echo $vehicle['fuel_type'] == 'Diesel' ? 'selected' : ''; ?>>Diesel</option><option value="Electric" <?php echo $vehicle['fuel_type'] == 'Electric' ? 'selected' : ''; ?>>Electric</option><option value="Hybrid" <?php echo $vehicle['fuel_type'] == 'Hybrid' ? 'selected' : ''; ?>>Hybrid</option></select></div>
                </div>

                <div class="form-row">
                    <div class="form-group"><label>Daily Rate (KES) *</label><input type="number" step="0.01" name="daily_rate" value="<?php echo $vehicle['daily_rate']; ?>" required></div>
                    <div class="form-group"><label>Status *</label><select name="status" required><option value="available" <?php echo $vehicle['status'] == 'available' ? 'selected' : ''; ?>>Available</option><option value="rented" <?php echo $vehicle['status'] == 'rented' ? 'selected' : ''; ?>>Rented</option><option value="maintenance" <?php echo $vehicle['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option></select></div>
                </div>

                <div class="form-group">
                    <label>Vehicle Image</label>
                    <?php if($vehicle['image'] && file_exists('../' . $vehicle['image'])): ?>
                    <div class="current-image">
                        <img src="../<?php echo $vehicle['image']; ?>" alt="Current image">
                        <div><p>Current image</p><small><?php echo $vehicle['image']; ?></small></div>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="vehicle_image" accept="image/*">
                    <small>Leave empty to keep current image. Supported: JPG, PNG, GIF, WEBP</small>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"><?php echo htmlspecialchars($vehicle['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Features (comma separated)</label>
                    <textarea name="features" rows="2" placeholder="e.g., Air Conditioning, GPS, Bluetooth, Backup Camera"><?php echo htmlspecialchars($vehicle['features']); ?></textarea>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn-save"><i class="fas fa-save"></i> Save Changes</button>
                    <a href="vehicles.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>