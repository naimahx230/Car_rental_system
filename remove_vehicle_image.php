<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($vehicle_id > 0) {
    // Get current image path
    $result = mysqli_query($conn, "SELECT image FROM vehicles WHERE id = $vehicle_id");
    $vehicle = mysqli_fetch_assoc($result);

    if($vehicle && $vehicle['image']) {
        // Delete image file
        $image_path = '../' . $vehicle['image'];
        if(file_exists($image_path)) {
            unlink($image_path);
        }
        
        // Delete thumbnail if exists
        $thumb_path = str_replace('uploads/vehicles/', 'uploads/vehicles/thumbs/', $image_path);
        if(file_exists($thumb_path)) {
            unlink($thumb_path);
        }
        
        // Update database
        mysqli_query($conn, "UPDATE vehicles SET image = NULL WHERE id = $vehicle_id");
    }
}

// Redirect back to vehicles page
header("Location: vehicles.php");
exit();
?>