<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: index.php");
    exit();
}

$upload_dir = 'uploads/vehicles/';
$thumb_dir = $upload_dir . 'thumbs/';

if(!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
if(!file_exists($thumb_dir)) {
    mkdir($thumb_dir, 0777, true);
}

echo "<h1>Bulk Image Upload Results</h1>";

$uploaded = 0;
$failed = 0;
$matched = 0;

if(isset($_FILES['bulk_images'])) {
    $file_count = count($_FILES['bulk_images']['name']);
    
    for($i = 0; $i < $file_count; $i++) {
        if($_FILES['bulk_images']['error'][$i] == 0) {
            $filename = $_FILES['bulk_images']['name'][$i];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'jfif'];
            
            if(in_array($ext, $allowed)) {
                // Try to match filename with vehicle
                $name_without_ext = pathinfo($filename, PATHINFO_FILENAME);
                // Replace underscores and dashes with spaces for matching
                $search_name = str_replace(['_', '-'], ' ', $name_without_ext);
                $search_name = mysqli_real_escape_string($conn, $search_name);
                
                // Search for matching vehicle
                $vehicle_query = "SELECT id, brand, model FROM vehicles WHERE 
                                  CONCAT(brand, ' ', model) LIKE '%$search_name%' 
                                  OR brand LIKE '%$search_name%' 
                                  OR model LIKE '%$search_name%' 
                                  LIMIT 1";
                $vehicle_result = mysqli_query($conn, $vehicle_query);
                
                if(mysqli_num_rows($vehicle_result) > 0) {
                    $vehicle = mysqli_fetch_assoc($vehicle_result);
                    
                    // Create new filename
                    $new_filename = strtolower($vehicle['brand'] . '_' . $vehicle['model'] . '_' . time() . '_' . $i . '.' . $ext);
                    $new_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $new_filename);
                    $destination = $upload_dir . $new_filename;
                    
                    if(move_uploaded_file($_FILES['bulk_images']['tmp_name'][$i], $destination)) {
                        // Create thumbnail
                        createThumbnail($destination, $thumb_dir . $new_filename, 300, 200);
                        
                        // Update database
                        $image_path = 'uploads/vehicles/' . $new_filename;
                        mysqli_query($conn, "UPDATE vehicles SET image = '$image_path' WHERE id = {$vehicle['id']}");
                        
                        echo "<p style='color: green;'>✅ {$filename} → Matched with: {$vehicle['brand']} {$vehicle['model']}</p>";
                        $uploaded++;
                        $matched++;
                    } else {
                        echo "<p style='color: red;'>❌ Failed to upload: {$filename}</p>";
                        $failed++;
                    }
                } else {
                    echo "<p style='color: orange;'>⚠️ No match found for: {$filename}</p>";
                    $failed++;
                }
            } else {
                echo "<p style='color: red;'>❌ Invalid file type: {$filename}</p>";
                $failed++;
            }
        }
    }
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<ul>";
echo "<li>Total files processed: " . ($uploaded + $failed) . "</li>";
echo "<li>Successfully uploaded: $uploaded</li>";
echo "<li>Failed: $failed</li>";
echo "<li>Matched to vehicles: $matched</li>";
echo "</ul>";

echo "<p><a href='check_images.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Back to Image Manager</a></p>";
echo "<p><a href='vehicles.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Vehicles</a></p>";

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
?>