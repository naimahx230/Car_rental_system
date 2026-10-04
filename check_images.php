<?php
session_start();
require_once 'config/database.php';

// Check if admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: index.php");
    exit();
}

echo "<h1>Image Upload Helper</h1>";

// Create directories if not exist
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
$vehicles = mysqli_query($conn, "SELECT id, brand, model, registration_number, image FROM vehicles ORDER BY brand, model");

echo "<h2>Current Vehicles & Image Status</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #667eea; color: white;'>
        <th>ID</th>
        <th>Brand</th>
        <th>Model</th>
        <th>Registration</th>
        <th>Current Image</th>
        <th>Status</th>
        <th>Action</th>
      </tr>";

while($vehicle = mysqli_fetch_assoc($vehicles)) {
    $has_image = ($vehicle['image'] && file_exists($vehicle['image'])) ? true : false;
    $status = $has_image ? "<span style='color: green;'>✓ Has Image</span>" : "<span style='color: red;'>✗ No Image</span>";
    
    echo "<tr>";
    echo "<td>{$vehicle['id']}</td>";
    echo "<td>{$vehicle['brand']}</td>";
    echo "<td>{$vehicle['model']}</td>";
    echo "<td>{$vehicle['registration_number']}</td>";
    echo "<td>" . ($vehicle['image'] ? basename($vehicle['image']) : 'None') . "</td>";
    echo "<td>$status</td>";
    echo "<td>
            <form method='POST' enctype='multipart/form-data' style='display: inline-block;'>
                <input type='hidden' name='vehicle_id' value='{$vehicle['id']}'>
                <input type='file' name='vehicle_image' accept='image/*' required style='display: none;' id='file_{$vehicle['id']}'>
                <button type='button' onclick=\"document.getElementById('file_{$vehicle['id']}').click()\" style='background: #28a745; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer;'>Upload</button>
                <button type='submit' name='upload_single' style='display: none;'></button>
            </form>
          </td>";
    echo "</tr>";
}
echo "</table>";

// Handle single image upload
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['upload_single'])) {
    $vehicle_id = (int)$_POST['vehicle_id'];
    
    // Get vehicle info
    $vehicle = mysqli_fetch_assoc(mysqli_query($conn, "SELECT brand, model FROM vehicles WHERE id = $vehicle_id"));
    
    if(isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'];
        $filename = $_FILES['vehicle_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            $new_filename = strtolower($vehicle['brand'] . '_' . $vehicle['model'] . '_' . time() . '.' . $ext);
            $new_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $new_filename);
            $destination = $upload_dir . $new_filename;
            
            if(move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $destination)) {
                // Create thumbnail
                createThumbnail($destination, $thumb_dir . $new_filename, 300, 200);
                
                // Update database
                $image_path = 'uploads/vehicles/' . $new_filename;
                mysqli_query($conn, "UPDATE vehicles SET image = '$image_path' WHERE id = $vehicle_id");
                echo "<p style='color: green;'>✅ Image uploaded for {$vehicle['brand']} {$vehicle['model']}!</p>";
                echo "<meta http-equiv='refresh' content='2'>";
            } else {
                echo "<p style='color: red;'>❌ Failed to upload image.</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Invalid file type. Allowed: jpg, jpeg, png, gif, webp, jfif</p>";
        }
    }
}

// Thumbnail function
function createThumbnail($source, $destination, $width, $height) {
    if(!file_exists($source)) return false;
    
    list($orig_width, $orig_height, $type) = getimagesize($source);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($source);
            break;
        case IMAGETYPE_WEBP:
            $image = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }
    
    $thumb = imagecreatetruecolor($width, $height);
    
    if($type == IMAGETYPE_PNG) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
    }
    
    imagecopyresampled($thumb, $image, 0, 0, 0, 0, $width, $height, $orig_width, $orig_height);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumb, $destination, 80);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumb, $destination, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumb, $destination);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($thumb, $destination, 80);
            break;
    }
    
    imagedestroy($image);
    imagedestroy($thumb);
    return true;
}

echo "<hr>";
echo "<h2>Bulk Upload Instructions</h2>";
echo "<p>You can also upload multiple images at once. Name your images to match vehicle names:</p>";
echo "<ul>";
echo "<li><strong>BMW_X5.jpg</strong> → BMW X5</li>";
echo "<li><strong>Mercedes_C-Class.jpg</strong> → Mercedes C-Class</li>";
echo "<li><strong>Toyota_RAV4.jpg</strong> → Toyota RAV4</li>";
echo "<li><strong>Toyota_Fortuner.jpg</strong> → Toyota Fortuner</li>";
echo "<li><strong>Honda_Civic.jpg</strong> → Honda Civic</li>";
echo "</ul>";

echo "<h2>Bulk Upload Form</h2>";
echo "<form method='POST' enctype='multipart/form-data' action='bulk_upload_images.php'>";
echo "<input type='file' name='bulk_images[]' accept='image/*' multiple required>";
echo "<button type='submit' name='bulk_upload' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;'>Upload All</button>";
echo "</form>";
?>

<style>
    table { width: 100%; }
    th, td { padding: 10px; text-align: left; }
    tr:hover { background: #f5f5f5; }
</style>