<?php
session_start();
require_once 'config/database.php';

// Check if admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Create directories
$upload_dir = 'uploads/vehicles/';
$thumb_dir = $upload_dir . 'thumbs/';

if(!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
    echo "<p>✅ Created: $upload_dir</p>";
}
if(!file_exists($thumb_dir)) {
    mkdir($thumb_dir, 0777, true);
    echo "<p>✅ Created: $thumb_dir</p>";
}

// Get all vehicles
$vehicles = mysqli_query($conn, "SELECT * FROM vehicles");
$updated = 0;

while($vehicle = mysqli_fetch_assoc($vehicles)) {
    $brand = strtolower($vehicle['brand']);
    $model = strtolower($vehicle['model']);
    
    // Check if image exists in uploads folder
    $possible_images = [
        $upload_dir . $brand . '_' . $model . '.jpg',
        $upload_dir . $brand . '_' . $model . '.png',
        $upload_dir . $brand . ' ' . $model . '.jpg',
        $upload_dir . $brand . ' ' . $model . '.png',
        $upload_dir . $brand . '.jpg',
        $upload_dir . $brand . '.png',
        $upload_dir . $model . '.jpg',
        $upload_dir . $model . '.png'
    ];
    
    $image_found = false;
    foreach($possible_images as $img_path) {
        if(file_exists($img_path)) {
            $db_path = $img_path;
            mysqli_query($conn, "UPDATE vehicles SET image = '$db_path' WHERE id = {$vehicle['id']}");
            $updated++;
            echo "<p>✅ Assigned image to: {$vehicle['brand']} {$vehicle['model']}</p>";
            $image_found = true;
            break;
        }
    }
    
    if(!$image_found) {
        // Create a colored placeholder image using GD
        $img = imagecreate(400, 300);
        $bg_color = imagecolorallocate($img, 102, 126, 234); // #667eea
        $text_color = imagecolorallocate($img, 255, 255, 255);
        
        // Fill background
        imagefilledrectangle($img, 0, 0, 400, 300, $bg_color);
        
        // Add car icon text
        $text = strtoupper(substr($brand, 0, 1) . substr($model, 0, 1));
        $font_size = 5;
        $text_width = imagefontwidth($font_size) * strlen($text);
        $text_height = imagefontheight($font_size);
        $x = (400 - $text_width) / 2;
        $y = (300 - $text_height) / 2;
        imagestring($img, $font_size, $x, $y, $text, $text_color);
        
        // Save image
        $filename = $upload_dir . strtolower($brand . '_' . $model . '.png');
        imagepng($img, $filename);
        imagedestroy($img);
        
        // Update database
        mysqli_query($conn, "UPDATE vehicles SET image = '$filename' WHERE id = {$vehicle['id']}");
        $updated++;
        echo "<p>🎨 Created placeholder for: {$vehicle['brand']} {$vehicle['model']}</p>";
    }
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>✅ Total vehicles updated: $updated</p>";
echo "<p><a href='vehicles.php'>View Vehicles Page →</a></p>";
echo "<p><a href='admin/manage_images.php'>Manage Images (Admin) →</a></p>";
?>