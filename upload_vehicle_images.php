<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$message = '';
$error = '';

// Handle image upload
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload'])) {
    $vehicle_id = (int)$_POST['vehicle_id'];
    
    if(isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'];
        $filename = $_FILES['vehicle_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $vehicle = mysqli_fetch_assoc(mysqli_query($conn, "SELECT brand, model FROM vehicles WHERE id = $vehicle_id"));
            
            if($vehicle) {
                $new_filename = strtolower($vehicle['brand'] . '_' . $vehicle['model'] . '_' . time() . '.' . $ext);
                $new_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $new_filename);
                
                $upload_dir = '../uploads/vehicles/';
                if(!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if(move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $upload_dir . $new_filename)) {
                    $image_path = 'uploads/vehicles/' . $new_filename;
                    mysqli_query($conn, "UPDATE vehicles SET image = '$image_path' WHERE id = $vehicle_id");
                    $message = "Image uploaded successfully!";
                } else {
                    $error = "Failed to upload image.";
                }
            } else {
                $error = "Vehicle not found.";
            }
        } else {
            $error = "Invalid file type. Allowed: jpg, jpeg, png, gif, webp, jfif";
        }
    } else {
        $error = "Please select an image file.";
    }
}

$vehicles = mysqli_query($conn, "SELECT * FROM vehicles ORDER BY brand");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Vehicle Images - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; padding: 20px; }
        
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: #667eea; }
        
        .vehicle-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        .vehicle-card { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .vehicle-card:hover { transform: translateY(-5px); }
        .vehicle-image { height: 180px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .vehicle-image img { width: 100%; height: 100%; object-fit: cover; }
        .vehicle-info { padding: 15px; }
        .vehicle-title { font-size: 18px; font-weight: 600; margin-bottom: 5px; }
        .upload-form { margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; }
        .form-group { margin-bottom: 10px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        input[type="file"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        .btn-upload { background: #28a745; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-upload:hover { background: #218838; }
        .alert { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .sidebar-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            background: white;
            padding: 10px 20px;
            border-radius: 8px;
        }
        .sidebar-link:hover { background: #667eea; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="sidebar-link">← Back to Dashboard</a>
        
        <div class="header">
            <h1><i class="fas fa-upload"></i> Upload Vehicle Images</h1>
            <p>Upload images for your vehicles. Supported formats: JPG, PNG, JFIF, GIF, WEBP</p>
        </div>

        <?php if($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="vehicle-grid">
            <?php if(mysqli_num_rows($vehicles) > 0): ?>
                <?php while($vehicle = mysqli_fetch_assoc($vehicles)): ?>
                <div class="vehicle-card">
                    <div class="vehicle-image">
                        <?php if($vehicle['image'] && file_exists('../' . $vehicle['image'])): ?>
                            <img src="../<?php echo $vehicle['image']; ?>" alt="<?php echo $vehicle['brand']; ?>">
                        <?php else: ?>
                            <i class="fas fa-car" style="font-size: 50px; color: #ccc;"></i>
                        <?php endif; ?>
                    </div>
                    <div class="vehicle-info">
                        <div class="vehicle-title"><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></div>
                        <div class="vehicle-reg" style="color: #666; font-size: 12px;"><?php echo htmlspecialchars($vehicle['registration_number']); ?></div>
                        <div style="color: #667eea; font-weight: 600;">KES <?php echo number_format($vehicle['daily_rate'], 2); ?>/day</div>
                        
                        <form method="POST" enctype="multipart/form-data" class="upload-form">
                            <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                            <div class="form-group">
                                <label>Select Image</label>
                                <input type="file" name="vehicle_image" accept="image/*" required>
                            </div>
                            <button type="submit" name="upload" class="btn-upload"><i class="fas fa-cloud-upload-alt"></i> Upload Image</button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 60px; background: white; border-radius: 10px;">
                    <i class="fas fa-car" style="font-size: 60px; color: #ccc;"></i>
                    <h3>No Vehicles Found</h3>
                    <p>Please add vehicles first before uploading images.</p>
                    <a href="add_vehicle.php" class="btn-upload" style="background: #667eea; margin-top: 15px; display: inline-block;">Add Vehicle</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>