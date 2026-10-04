<?php
require_once 'auth_check.php';  // ← ADD THIS AT THE VERY TOP
require_once '../config/database.php';

// Rest of your assign_asset_images code continues...
?>
<?php
session_start();
require_once '../config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Get all vehicles
$vehicles = mysqli_query($conn, "SELECT * FROM vehicles ORDER BY brand");

// Get all images from assets folder
$assets_images = [];
$assets_dir = '../assets/images/';

if(is_dir($assets_dir)) {
    $files = scandir($assets_dir);
    foreach($files as $file) {
        if($file != '.' && $file != '..') {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if(in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'])) {
                $assets_images[] = $file;
            }
        }
    }
}

// Auto-assign images based on filename matching
if(isset($_POST['auto_assign'])) {
    $assigned = 0;
    
    foreach($vehicles as $vehicle) {
        $vehicle_name = strtolower($vehicle['brand'] . '_' . $vehicle['model']);
        $vehicle_name2 = strtolower($vehicle['brand'] . ' ' . $vehicle['model']);
        $vehicle_brand = strtolower($vehicle['brand']);
        $vehicle_model = strtolower($vehicle['model']);
        
        foreach($assets_images as $image) {
            $image_lower = strtolower($image);
            $image_name = strtolower(pathinfo($image, PATHINFO_FILENAME));
            
            // Match by vehicle name
            if(strpos($image_name, $vehicle_name) !== false || 
               strpos($image_name, $vehicle_name2) !== false ||
               (strpos($image_name, $vehicle_brand) !== false && strpos($image_name, $vehicle_model) !== false)) {
                
                $image_path = 'assets/images/' . $image;
                $update = "UPDATE vehicles SET image = '$image_path' WHERE id = {$vehicle['id']}";
                if(mysqli_query($conn, $update)) {
                    $assigned++;
                    break;
                }
            }
        }
    }
    
    $message = "Auto-assigned $assigned images to vehicles!";
}

// Manual assign
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_manual'])) {
    $vehicle_id = (int)$_POST['vehicle_id'];
    $image_name = $_POST['image_name'];
    $image_path = 'assets/images/' . $image_name;
    
    $update = "UPDATE vehicles SET image = '$image_path' WHERE id = $vehicle_id";
    if(mysqli_query($conn, $update)) {
        $message = "Image assigned successfully!";
    } else {
        $error = "Failed to assign image.";
    }
}

// Reset all images
if(isset($_POST['reset_images'])) {
    mysqli_query($conn, "UPDATE vehicles SET image = NULL");
    $message = "All vehicle images have been reset!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Asset Images - Admin</title>
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
        
        .section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section h3 {
            margin-bottom: 15px;
            color: #667eea;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn-primary { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: #000; }
        .btn-danger { background: #dc3545; color: white; }
        .vehicle-list, .image-list {
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #667eea; color: white; position: sticky; top: 0; }
        .preview-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .image-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }
        .image-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            width: 120px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .image-card:hover {
            border-color: #667eea;
            transform: scale(1.05);
        }
        .image-card img {
            width: 100px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }
        .image-card.selected {
            border-color: #28a745;
            background: #d4edda;
        }
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
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="vehicles.php"><i class="fas fa-car"></i> Manage Vehicles</a>
            <a href="assign_asset_images.php" class="active"><i class="fas fa-image"></i> Assign Images</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Assign Vehicle Images from Assets</h1>
            <p>Assign images from your assets folder to vehicles</p>
        </div>

        <?php if($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Auto Assign Section -->
        <div class="section">
            <h3><i class="fas fa-magic"></i> Auto-Assign Images</h3>
            <p>Automatically match images to vehicles based on filenames.</p>
            <form method="POST" style="margin-top: 15px;">
                <button type="submit" name="auto_assign" class="btn btn-primary" onclick="return confirm('Auto-assign images to vehicles?')">
                    <i class="fas fa-play"></i> Auto-Assign Images
                </button>
                <button type="submit" name="reset_images" class="btn btn-danger" onclick="return confirm('Reset all vehicle images?')">
                    <i class="fas fa-undo"></i> Reset All Images
                </button>
            </form>
        </div>

        <!-- Manual Assign Section -->
        <div class="section">
            <h3><i class="fas fa-hand-pointer"></i> Manual Assign</h3>
            <p>Select a vehicle and an image to assign.</p>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Vehicle List -->
                <div>
                    <h4>Vehicles</h4>
                    <div class="vehicle-list">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Vehicle</th>
                                    <th>Current Image</th>
                                    <th>Select</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                mysqli_data_seek($vehicles, 0);
                                while($v = mysqli_fetch_assoc($vehicles)): 
                                ?>
                                <tr>
                                    <td><?php echo $v['id']; ?></td>
                                    <td><?php echo $v['brand'] . ' ' . $v['model']; ?></td>
                                    <td>
                                        <?php if($v['image'] && file_exists('../' . $v['image'])): ?>
                                            <img src="../<?php echo $v['image']; ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                                        <?php else: ?>
                                            <i class="fas fa-car" style="color: #ccc;"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn" style="background: #667eea; color: white; padding: 5px 10px;" onclick="selectVehicle(<?php echo $v['id']; ?>, '<?php echo $v['brand'] . ' ' . $v['model']; ?>')">
                                            Select
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Image List -->
                <div>
                    <h4>Available Images in assets/images/</h4>
                    <div class="image-grid" id="imageGrid">
                        <?php foreach($assets_images as $image): ?>
                        <div class="image-card" onclick="selectImage('<?php echo $image; ?>')" data-image="<?php echo $image; ?>">
                            <img src="../assets/images/<?php echo $image; ?>" alt="<?php echo $image; ?>">
                            <small><?php echo substr($image, 0, 20); ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Assign Form -->
            <div class="section" style="margin-top: 20px; background: #f8f9fa;">
                <h4>Assign Selected</h4>
                <form method="POST" id="assignForm">
                    <input type="hidden" name="assign_manual" value="1">
                    <input type="hidden" name="vehicle_id" id="selectedVehicleId">
                    <input type="hidden" name="image_name" id="selectedImageName">
                    <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                        <div>
                            <strong>Selected Vehicle:</strong> 
                            <span id="selectedVehicleDisplay">None</span>
                        </div>
                        <div>
                            <strong>Selected Image:</strong> 
                            <span id="selectedImageDisplay">None</span>
                        </div>
                        <button type="submit" class="btn btn-primary" id="assignBtn" disabled>
                            <i class="fas fa-link"></i> Assign Image to Vehicle
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Current Assignments -->
        <div class="section">
            <h3><i class="fas fa-list"></i> Current Assignments</h3>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Registration</th>
                            <th>Vehicle</th>
                            <th>Image</th>
                            <th>Preview</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        mysqli_data_seek($vehicles, 0);
                        while($v = mysqli_fetch_assoc($vehicles)): 
                        ?>
                        <tr>
                            <td><?php echo $v['id']; ?></td>
                            <td><?php echo $v['registration_number']; ?></td>
                            <td><?php echo $v['brand'] . ' ' . $v['model']; ?></td>
                            <td>
                                <?php if($v['image']): ?>
                                    <?php echo basename($v['image']); ?>
                                <?php else: ?>
                                    <span style="color: #999;">No image</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($v['image'] && file_exists('../' . $v['image'])): ?>
                                    <img src="../<?php echo $v['image']; ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                <?php else: ?>
                                    <i class="fas fa-car" style="font-size: 30px; color: #ccc;"></i>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let selectedVehicleId = null;
        let selectedImageName = null;
        
        function selectVehicle(id, name) {
            selectedVehicleId = id;
            document.getElementById('selectedVehicleId').value = id;
            document.getElementById('selectedVehicleDisplay').innerHTML = name;
            updateAssignButton();
        }
        
        function selectImage(imageName) {
            selectedImageName = imageName;
            document.getElementById('selectedImageName').value = imageName;
            document.getElementById('selectedImageDisplay').innerHTML = imageName;
            
            // Highlight selected image
            document.querySelectorAll('.image-card').forEach(card => {
                card.classList.remove('selected');
                if(card.dataset.image === imageName) {
                    card.classList.add('selected');
                }
            });
            updateAssignButton();
        }
        
        function updateAssignButton() {
            const btn = document.getElementById('assignBtn');
            if(selectedVehicleId && selectedImageName) {
                btn.disabled = false;
            } else {
                btn.disabled = true;
            }
        }
    </script>
</body>
</html>