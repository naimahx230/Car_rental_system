<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// Handle updates
if($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['update_content'])) {
        $id = (int)$_POST['id'];
        $title = mysqli_real_escape_string($conn, $_POST['title']);
        $content = mysqli_real_escape_string($conn, $_POST['content']);
        
        mysqli_query($conn, "UPDATE about_us SET title='$title', content='$content' WHERE id=$id");
        header("Location: about_content.php?success=1");
        exit();
    }
    
    if(isset($_POST['add_team'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $position = mysqli_real_escape_string($conn, $_POST['position']);
        $bio = mysqli_real_escape_string($conn, $_POST['bio']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        mysqli_query($conn, "INSERT INTO team_members (name, position, bio, email, is_active) VALUES ('$name', '$position', '$bio', '$email', 1)");
        header("Location: about_content.php");
        exit();
    }
    
    if(isset($_POST['add_testimonial'])) {
        $customer_name = mysqli_real_escape_string($conn, $_POST['customer_name']);
        $testimonial = mysqli_real_escape_string($conn, $_POST['testimonial']);
        $rating = (int)$_POST['rating'];
        
        mysqli_query($conn, "INSERT INTO testimonials (customer_name, testimonial, rating, is_active) VALUES ('$customer_name', '$testimonial', $rating, 1)");
        header("Location: about_content.php");
        exit();
    }
    
    if(isset($_GET['delete_team'])) {
        $id = (int)$_GET['delete_team'];
        mysqli_query($conn, "DELETE FROM team_members WHERE id=$id");
        header("Location: about_content.php");
        exit();
    }
    
    if(isset($_GET['delete_testimonial'])) {
        $id = (int)$_GET['delete_testimonial'];
        mysqli_query($conn, "DELETE FROM testimonials WHERE id=$id");
        header("Location: about_content.php");
        exit();
    }
}

// Get data
$about_pages = mysqli_query($conn, "SELECT * FROM about_us ORDER BY display_order");
$team_members = mysqli_query($conn, "SELECT * FROM team_members ORDER BY display_order");
$testimonials_list = mysqli_query($conn, "SELECT * FROM testimonials ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage About Content - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f5f5; }
        
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
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px;
            margin: 5px 0;
            border-radius: 8px;
        }
        .sidebar nav a:hover, .sidebar nav a.active { background: #FFD700; color: #1a1a2e; }
        
        .main-content { margin-left: 260px; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .section-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 30px; }
        .section-card h3 { margin-bottom: 20px; color: #667eea; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn-submit { background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
        .btn-delete { background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
        table { width: 100%; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #eee; }
        
        @media (max-width: 768px) { .sidebar { width: 100%; height: auto; position: relative; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <?php $current_page = "about_content"; include __DIR__ . "/../includes/admin_sidebar.php"; ?>

    <div class="main-content">
        <div class="header"><h1>Manage About Us Content</h1></div>

        <div class="section-card">
            <h3>About Page Sections</h3>
            <?php while($page = mysqli_fetch_assoc($about_pages)): ?>
            <form method="POST" style="margin-bottom: 20px; padding: 15px; border: 1px solid #eee; border-radius: 5px;">
                <input type="hidden" name="id" value="<?php echo $page['id']; ?>">
                <div class="form-group"><label><?php echo ucfirst($page['section_name']); ?> Title</label><input type="text" name="title" value="<?php echo htmlspecialchars($page['title']); ?>"></div>
                <div class="form-group"><label>Content</label><textarea name="content" rows="4"><?php echo htmlspecialchars($page['content']); ?></textarea></div>
                <button type="submit" name="update_content" class="btn-submit">Update</button>
            </form>
            <?php endwhile; ?>
        </div>

        <div class="section-card">
            <h3>Team Members</h3>
            <form method="POST" style="margin-bottom: 20px;">
                <div class="form-group"><input type="text" name="name" placeholder="Full Name" required></div>
                <div class="form-group"><input type="text" name="position" placeholder="Position" required></div>
                <div class="form-group"><textarea name="bio" placeholder="Bio" rows="3"></textarea></div>
                <div class="form-group"><input type="email" name="email" placeholder="Email"></div>
                <button type="submit" name="add_team" class="btn-submit">Add Team Member</button>
            </form>
            <table>
                <thead><tr><th>Name</th><th>Position</th><th>Action</th></tr></thead>
                <tbody>
                    <?php while($member = mysqli_fetch_assoc($team_members)): ?>
                    <tr><td><?php echo htmlspecialchars($member['name']); ?></td><td><?php echo htmlspecialchars($member['position']); ?></td><td><a href="?delete_team=<?php echo $member['id']; ?>" class="btn-delete" onclick="return confirm('Delete?')">Delete</a></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="section-card">
            <h3>Testimonials</h3>
            <form method="POST" style="margin-bottom: 20px;">
                <div class="form-group"><input type="text" name="customer_name" placeholder="Customer Name" required></div>
                <div class="form-group"><textarea name="testimonial" placeholder="Testimonial" rows="3" required></textarea></div>
                <div class="form-group"><select name="rating"><option value="5">★★★★★ (5)</option><option value="4">★★★★☆ (4)</option><option value="3">★★★☆☆ (3)</option><option value="2">★★☆☆☆ (2)</option><option value="1">★☆☆☆☆ (1)</option></select></div>
                <button type="submit" name="add_testimonial" class="btn-submit">Add Testimonial</button>
            </form>
            <table>
                <thead><tr><th>Customer</th><th>Testimonial</th><th>Rating</th><th>Action</th></tr></thead>
                <tbody>
                    <?php while($test = mysqli_fetch_assoc($testimonials_list)): ?>
                    <tr><td><?php echo htmlspecialchars($test['customer_name']); ?></td><td><?php echo htmlspecialchars(substr($test['testimonial'], 0, 50)); ?>...</td><td><?php echo $test['rating']; ?> ★</td><td><a href="?delete_testimonial=<?php echo $test['id']; ?>" class="btn-delete" onclick="return confirm('Delete?')">Delete</a></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
