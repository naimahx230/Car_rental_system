<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$error = '';
$success = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $registration_number = mysqli_real_escape_string($conn, $_POST['registration_number']);
    $brand = mysqli_real_escape_string($conn, $_POST['brand']);
    $model = mysqli_real_escape_string($conn, $_POST['model']);
    $year = (int)$_POST['year'];
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    $daily_rate = (float)$_POST['daily_rate'];
    $transmission = mysqli_real_escape_string($conn, $_POST['transmission']);
    $seats = (int)$_POST['seats'];
    $fuel_type = mysqli_real_escape_string($conn, $_POST['fuel_type']);
    $fuel_consumption = mysqli_real_escape_string($conn, $_POST['fuel_consumption']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $features = mysqli_real_escape_string($conn, $_POST['features']);
    $status = 'available';
    
    $image_path = '';
    if(isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['vehicle_image']['name'], PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $upload_dir = '../uploads/vehicles/';
            if(!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $new_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $brand . '_' . $model) . '.' . $ext;
            if(move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $upload_dir . $new_filename)) {
                $image_path = 'uploads/vehicles/' . $new_filename;
            }
        }
    }
    
    $insert = "INSERT INTO vehicles (registration_number, brand, model, year, color, daily_rate, 
               transmission, seats, fuel_type, fuel_consumption, description, features, status, image) 
               VALUES ('$registration_number', '$brand', '$model', $year, '$color', $daily_rate, 
               '$transmission', $seats, '$fuel_type', '$fuel_consumption', '$description', '$features', 
               '$status', '$image_path')";
    
    if(mysqli_query($conn, $insert)) {
        $success = "Vehicle added successfully!";
        echo "<script>setTimeout(function(){ window.location.href = 'vehicles.php'; }, 1500);</script>";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Vehicle - Admin</title>
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
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
        }
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .form-section h3 {
            margin-bottom: 20px;
            color: #667eea;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
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
        .image-preview { margin-top: 10px; max-width: 200px; }
        .image-preview img { width: 100%; border-radius: 5px; }
        .btn-submit { background: #28a745; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn-cancel { background: #6c757d; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; margin-left: 10px; }
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
    <?php $current_page = "add_vehicle"; include __DIR__ . "/../includes/admin_sidebar.php"; ?>

    <div class="main-content">
        <div class="header">
            <h1>Add New Vehicle</h1>
            <a href="vehicles.php" style="color: #667eea;">← Back to Vehicles</a>
        </div>

        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-section">
                    <h3><i class="fas fa-car"></i> Basic Information</h3>
                    <div class="form-grid">
                        <div class="form-group"><label>Registration Number *</label><input type="text" name="registration_number" placeholder="KCA 123A" required></div>
                        <div class="form-group"><label>Brand *</label><select name="brand" required><option value="">Select Brand</option><option value="BMW">BMW</option><option value="Mercedes">Mercedes</option><option value="Toyota">Toyota</option><option value="Honda">Honda</option><option value="Audi">Audi</option><option value="Nissan">Nissan</option><option value="Ford">Ford</option><option value="Volkswagen">Volkswagen</option><option value="Hyundai">Hyundai</option><option value="Kia">Kia</option><option value="Subaru">Subaru</option><option value="Lexus">Lexus</option><option value="Range Rover">Range Rover</option><option value="Jeep">Jeep</option></select></div>
                        <div class="form-group"><label>Model *</label><input type="text" name="model" placeholder="X5, C-Class, RAV4" required></div>
                        <div class="form-group"><label>Year *</label><input type="number" name="year" min="2000" max="2025" placeholder="2023" required></div>
                        <div class="form-group"><label>Color</label><input type="text" name="color" placeholder="Black, White, Silver"></div>
                        <div class="form-group"><label>Daily Rate (KES) *</label><input type="number" name="daily_rate" step="0.01" placeholder="5000" required></div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-cogs"></i> Specifications</h3>
                    <div class="form-grid">
                        <div class="form-group"><label>Transmission *</label><select name="transmission" required><option value="Manual">Manual</option><option value="Automatic">Automatic</option></select></div>
                        <div class="form-group"><label>Seats *</label><select name="seats"><option value="2">2 Seats</option><option value="4">4 Seats</option><option value="5">5 Seats</option><option value="6">6 Seats</option><option value="7">7 Seats</option><option value="8">8+ Seats</option></select></div>
                        <div class="form-group"><label>Fuel Type *</label><select name="fuel_type"><option value="Petrol">Petrol</option><option value="Diesel">Diesel</option><option value="Electric">Electric</option><option value="Hybrid">Hybrid</option></select></div>
                        <div class="form-group"><label>Fuel Consumption (km/l)</label><input type="text" name="fuel_consumption" placeholder="15 km/l"></div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-image"></i> Vehicle Image</h3>
                    <div class="form-group"><label>Upload Vehicle Photo</label><input type="file" name="vehicle_image" accept="image/*" onchange="previewImage(this)"><div class="image-preview" id="imagePreview"></div></div>
                </div>

                <div class="form-section">
                    <h3><i class="fas fa-align-left"></i> Description & Features</h3>
                    <div class="form-group"><label>Description</label><textarea name="description" rows="4" placeholder="Detailed vehicle description..."></textarea></div>
                    <div class="form-group"><label>Features (comma separated)</label><textarea name="features" rows="3" placeholder="GPS, Bluetooth, Leather Seats, Sunroof, Backup Camera"></textarea></div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Add Vehicle</button>
                    <a href="vehicles.php" class="btn-cancel"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if(input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) { preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">'; }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>