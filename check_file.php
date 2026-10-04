<?php
echo "<h1>File Checker</h1>";
echo "<h2>Root Folder Files:</h2>";
$files = scandir(__DIR__);
echo "<ul>";
foreach($files as $file) {
    if($file != '.' && $file != '..') {
        if(is_dir($file)) {
            echo "<li><strong>📁 $file/</strong></li>";
        } else {
            echo "<li>📄 $file</li>";
        }
    }
}
echo "</ul>";

echo "<h2>Admin Folder Files:</h2>";
if(is_dir('admin')) {
    $admin_files = scandir('admin');
    echo "<ul>";
    foreach($admin_files as $file) {
        if($file != '.' && $file != '..') {
            echo "<li>📄 admin/$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color:red'>Admin folder not found!</p>";
}

echo "<h2>Customer Folder Files:</h2>";
if(is_dir('customer')) {
    $customer_files = scandir('customer');
    echo "<ul>";
    foreach($customer_files as $file) {
        if($file != '.' && $file != '..') {
            echo "<li>📄 customer/$file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color:orange'>Customer folder not found (optional)</p>";
}
?>