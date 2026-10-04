<?php
// Run this once to fix all admin files
$files = glob("*.php");
$skip_files = ['auth_check.php', 'login.php', 'simple_login.php', 'admin_forgot_password.php', 
                'create_admin.php', 'fix_admin.php', 'make_me_admin.php', 'check_responses.php',
                'fix_all_admin.php'];

foreach($files as $file) {
    if(in_array($file, $skip_files)) {
        echo "⏭️ Skipping: $file<br>";
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Check if file already has auth_check
    if(strpos($content, "require_once 'auth_check.php';") !== false) {
        echo "✅ Already has auth_check: $file<br>";
        continue;
    }
    
    // Add auth_check at the top after <?php
    $new_content = preg_replace('/<\?php/', '<?php' . "\nrequire_once 'auth_check.php';", $content, 1);
    
    file_put_contents($file, $new_content);
    echo "✅ Fixed: $file<br>";
}

echo "<br>All done!";
?>