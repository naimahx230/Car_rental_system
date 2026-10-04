<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// Handle delete
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM users WHERE id = $id AND role != 'admin'");
    header("Location: users.php");
    exit();
}

// Handle status update
if(isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    mysqli_query($conn, "UPDATE users SET status = '$status' WHERE id = $id");
    header("Location: users.php");
    exit();
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
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
            overflow-y: auto;
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
            transition: all 0.3s;
        }
        .sidebar nav a:hover, .sidebar nav a.active { background: #FFD700; color: #1a1a2e; }
        
        .main-content { margin-left: 260px; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .header h1 { color: #333; }
        
        .table-container {
            background: white;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #667eea; color: white; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        
        .status-active { color: #28a745; font-weight: 600; }
        .status-inactive { color: #dc3545; font-weight: 600; }
        
        .btn-delete { 
            background: #dc3545; 
            color: white; 
            border: none; 
            padding: 5px 10px; 
            border-radius: 5px; 
            cursor: pointer; 
            text-decoration: none; 
            font-size: 12px;
            display: inline-block;
        }
        .btn-delete:hover { opacity: 0.8; }
        
        .role-badge { 
            background: #667eea; 
            color: white; 
            padding: 4px 8px; 
            border-radius: 5px; 
            font-size: 11px;
            display: inline-block;
        }
        .role-admin { background: #FFD700; color: #1a1a2e; }
        
        select.status-select {
            padding: 5px 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            cursor: pointer;
        }
        
        .protected-text { color: #999; font-size: 12px; }
        
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
            th, td { padding: 8px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <?php $current_page = "users"; include __DIR__ . "/../includes/admin_sidebar.php"; ?>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-users"></i> Manage Users</h1>
            <p>View and manage all registered customers</p>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Registered</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($users) > 0): ?>
                        <?php while($user = mysqli_fetch_assoc($users)): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'N/A'; ?></td>
                            <td><span class="role-badge <?php echo $user['role'] == 'admin' ? 'role-admin' : ''; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                            <td><?php if($user['role'] != 'admin'): ?><select class="status-select" onchange="updateStatus(<?php echo $user['id']; ?>, this.value)"><option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo $user['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option></select><?php else: ?><span class="status-active">Active</span><?php endif; ?></td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td><?php if($user['role'] != 'admin'): ?><a href="?delete=<?php echo $user['id']; ?>" class="btn-delete" onclick="return confirm('Delete this user permanently? This action cannot be undone.')"><i class="fas fa-trash"></i> Delete</a><?php else: ?><span class="protected-text"><i class="fas fa-shield-alt"></i> Protected</span><?php endif; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align: center; padding: 40px;"><i class="fas fa-users" style="font-size: 48px; color: #ccc;"></i><p style="margin-top: 10px;">No users found</p></td></tr>
                    <?php endif; ?>
                </tbody>
            <table>
        </div>
    </div>

    <script>
        function updateStatus(id, status) {
            if(confirm('Change user status to ' + status.toUpperCase() + '?')) {
                window.location.href = `?status=${status}&id=${id}`;
            } else {
                location.reload();
            }
        }
    </script>
</body>
</html>