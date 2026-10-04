<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// Get all vehicles
$vehicles = mysqli_query($conn, "SELECT * FROM vehicles ORDER BY brand, model");

// Get all images from uploads folder
$images_dir = '../uploads/vehicles/';
$available_images = [];
if(is_dir($images_dir)) {
    $files = scandir($images_dir);
    foreach($files as $file) {
        if($file != '.' && $file != '..' && !is_dir($images_dir . $file)) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $available_images[] = $file;
            }
        }
    }
}

// Handle image assignment
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_image'])) {
    $vehicle_id = (int)$_POST['vehicle_id'];
    $image_name = mysqli_real_escape_string($conn, $_POST['image_name']);
    $image_path = 'uploads/vehicles/' . $image_name;
    
    mysqli_query($conn, "UPDATE vehicles SET image = '$image_path' WHERE id = $vehicle_id");
    header("Location: manage_images.php?success=1");
    exit();
}

// Handle image upload
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_image'])) {
    if(isset($_FILES['new_image']) && $_FILES['new_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['new_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $new_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $filename);
            $destination = $images_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['new_image']['tmp_name'], $destination)) {
                header("Location: manage_images.php?uploaded=1");
                exit();
            }
        }
    }
}

// Handle image delete
if(isset($_GET['delete_image'])) {
    $image_name = $_GET['delete_image'];
    $file_path = $images_dir . $image_name;
    if(file_exists($file_path)) {
        unlink($file_path);
    }
    header("Location: manage_images.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vehicle Images - Admin</title>
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
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .sidebar nav a:hover, .sidebar nav a.active { background: #FFD700; color: #1a1a2e; }
        
        .main-content { margin-left: 260px; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .image-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .image-card:hover { transform: translateY(-5px); }
        .image-card img { width: 100%; height: 180px; object-fit: cover; }
        .image-card .image-name { padding: 10px; font-size: 12px; text-align: center; background: #f8f9fa; word-break: break-all; }
        .image-actions { display: flex; padding: 10px; gap: 10px; }
        .btn-assign, .btn-delete { flex: 1; padding: 8px; border: none; border-radius: 5px; cursor: pointer; font-size: 12px; transition: opacity 0.3s; }
        .btn-assign { background: #28a745; color: white; }
        .btn-delete { background: #dc3545; color: white; text-decoration: none; text-align: center; display: inline-block; }
        .btn-assign:hover, .btn-delete:hover { opacity: 0.8; }
        
        .vehicle-list {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .vehicle-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .vehicle-item:last-child { border-bottom: none; }
        .vehicle-image-preview {
            width: 60px;
            height: 60px;
            background: #f0f0f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .vehicle-image-preview img { width: 100%; height: 100%; object-fit: cover; }
        .vehicle-info { flex: 1; }
        .vehicle-info strong { font-size: 16px; }
        .vehicle-info small { color: #666; }
        .vehicle-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        
        .upload-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .alert { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        select, button[type="submit"] { padding: 8px 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        button[type="submit"] { background: #007bff; color: white; border: none; cursor: pointer; }
        h3 { margin: 20px 0 15px 0; color: #333; }
        
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
            .vehicle-item { flex-direction: column; text-align: center; }
            .vehicle-actions { justify-content: center; }
        }
    </style>
</head>
<body>
    <?php $current_page = "manage_images"; include __DIR__ . "/../includes/admin_sidebar.php"; ?>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-images"></i> Manage Vehicle Images</h1>
            <p>Upload, assign, and manage vehicle photos</p>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success">✅ Image assigned successfully!</div>
        <?php endif; ?>
        <?php if(isset($_GET['uploaded'])): ?>
            <div class="alert alert-success">✅ Image uploaded successfully!</div>
        <?php endif; ?>

        <div class="upload-form">
            <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <input type="file" name="new_image" accept="image/*" required style="padding: 8px;">
                <button type="submit" name="upload_image" class="btn-assign" style="padding: 8px 20px;">Upload Image</button>
            </form>
        </div>

        <h3>📷 Available Images (<?php echo count($available_images); ?>)</h3>
        <div class="image-grid">
            <?php if(empty($available_images)): ?>
                <p style="color: #999; grid-column: 1/-1; text-align: center;">No images uploaded yet. Use the upload form above to add images.</p>
            <?php endif; ?>
            <?php foreach($available_images as $image): ?>
            <div class="image-card">
                <img src="../uploads/vehicles/<?php echo urlencode($image); ?>" alt="<?php echo htmlspecialchars($image); ?>">
                <div class="image-name"><?php echo htmlspecialchars($image); ?></div>
                <div class="image-actions">
                    <button class="btn-assign" onclick="showAssignModal('<?php echo htmlspecialchars($image); ?>')">Assign to Vehicle</button>
                    <a href="?delete_image=<?php echo urlencode($image); ?>" class="btn-delete" onclick="return confirm('Delete this image? This action cannot be undone.')">Delete</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="vehicle-list">
            <h3>🚗 Current Vehicles (<?php echo mysqli_num_rows($vehicles); ?>)</h3>
            <?php if(mysqli_num_rows($vehicles) == 0): ?>
                <p style="color: #999; text-align: center;">No vehicles found. <a href="add_vehicle.php">Add a vehicle</a> first.</p>
            <?php endif; ?>
            <?php while($vehicle = mysqli_fetch_assoc($vehicles)): ?>
            <div class="vehicle-item">
                <div class="vehicle-image-preview">
                    <?php if($vehicle['image'] && file_exists('../' . $vehicle['image'])): ?>
                        <img src="../<?php echo $vehicle['image']; ?>" alt="<?php echo $vehicle['brand']; ?>">
                    <?php else: ?>
                        <i class="fas fa-car" style="font-size: 30px; color: #ccc;"></i>
                    <?php endif; ?>
                </div>
                <div class="vehicle-info">
                    <strong><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></strong><br>
                    <small>Reg: <?php echo htmlspecialchars($vehicle['registration_number']); ?> | ID: <?php echo $vehicle['id']; ?></small>
                </div>
                <div class="vehicle-actions">
                    <form method="POST" style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                        <select name="image_name" required>
                            <option value="">Select Image</option>
                            <?php foreach($available_images as $img): ?>
                                <option value="<?php echo htmlspecialchars($img); ?>" <?php echo ($vehicle['image'] == 'uploads/vehicles/' . $img) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($img); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="assign_image">Assign</button>
                    </form>
                    <?php if($vehicle['image']): ?>
                        <a href="remove_vehicle_image.php?id=<?php echo $vehicle['id']; ?>" class="btn-delete" style="padding: 8px 12px; text-decoration: none;" onclick="return confirm('Remove image from this vehicle?')">Remove Image</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <a href="vehicles.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Vehicles</a>
    </div>

    <script>
        function showAssignModal(imageName) {
            const vehicleId = prompt("Enter Vehicle ID to assign this image to:\n\nYou can find Vehicle IDs in the list below.");
            if(vehicleId && !isNaN(vehicleId)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="vehicle_id" value="${vehicleId}"><input type="hidden" name="image_name" value="${imageName}"><input type="hidden" name="assign_image" value="1">`;
                document.body.appendChild(form);
                form.submit();
            } else if(vehicleId) {
                alert("Please enter a valid numeric Vehicle ID.");
            }
        }
    </script>
</body>
</html>