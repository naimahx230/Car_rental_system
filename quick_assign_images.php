<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Map vehicle brands/models to image files
$image_mappings = [
    ['brand' => 'BMW', 'model' => 'X5', 'image' => 'BMW X5.png'],
    ['brand' => 'Honda', 'model' => 'Civic', 'image' => 'Honda Civic.png'],
    ['brand' => 'Mercedes', 'model' => 'C-Class', 'image' => 'Mercedes C-Class.png'],
    ['brand' => 'Toyota', 'model' => 'Fortuner', 'image' => 'Toyota Fortuner.png'],
    ['brand' => 'Toyota', 'model' => 'RAV4', 'image' => 'Toyota RAV4.png'],
];

$updated = 0;
$errors = [];

foreach($image_mappings as $mapping) {
    $brand = mysqli_real_escape_string($conn, $mapping['brand']);
    $model = mysqli_real_escape_string($conn, $mapping['model']);
    $image_path = 'uploads/vehicles/' . mysqli_real_escape_string($conn, $mapping['image']);
    
    $check = mysqli_query($conn, "SELECT id FROM vehicles WHERE brand = '$brand' AND model = '$model'");
    if(mysqli_num_rows($check) > 0) {
        $update = mysqli_query($conn, "UPDATE vehicles SET image = '$image_path' WHERE brand = '$brand' AND model = '$model'");
        if($update) {
            $updated++;
        } else {
            $errors[] = "$brand $model - " . mysqli_error($conn);
        }
    } else {
        $errors[] = "$brand $model - Vehicle not found in database";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Images - Urban Wheels</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 50px; text-align: center; }
        .success { color: green; margin: 10px 0; }
        .error { color: red; margin: 5px 0; }
        .back { margin-top: 20px; }
        .back a { color: #667eea; text-decoration: none; }
        .btn { 
            display: inline-block; 
            padding: 10px 20px; 
            background: #667eea; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <h1>Image Assignment Results</h1>
    
    <div class="success">
        ✅ Successfully updated: <?php echo $updated; ?> vehicles
    </div>
    
    <?php if(!empty($errors)): ?>
        <div class="error">
            <strong>Errors:</strong><br>
            <?php foreach($errors as $error): ?>
                <?php echo htmlspecialchars($error); ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="back">
        <a href="vehicles.php" class="btn">← Back to Manage Vehicles</a>
    </div>
</body>
</html>