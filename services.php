<?php
require_once 'auth_check.php';
require_once '../config/database.php';

$current_page = 'services';
$message = '';
$error = '';

// Handle Add Service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $name  = mysqli_real_escape_string($conn, $_POST['name']);
    $desc  = mysqli_real_escape_string($conn, $_POST['description']);
    $price = (float)$_POST['price'];
    $type  = mysqli_real_escape_string($conn, $_POST['price_type']);
    $icon  = mysqli_real_escape_string($conn, $_POST['icon']);
    $order = (int)$_POST['display_order'];

    $sql = "INSERT INTO services (service_name, name, description, price, price_type, icon, display_order, is_active)
            VALUES ('$name', '$name', '$desc', $price, '$type', '$icon', $order, 1)";
    if (mysqli_query($conn, $sql)) {
        $message = "Service added successfully!";
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM services WHERE id = $id");
    header("Location: services.php?deleted=1");
    exit();
}

// Handle Toggle Active
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    mysqli_query($conn, "UPDATE services SET is_active = NOT is_active WHERE id = $id");
    header("Location: services.php");
    exit();
}

$services = mysqli_query($conn, "SELECT * FROM services ORDER BY display_order, id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Services — Urban Wheels Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; display: flex; }

        .sidebar { width: 240px; background: #1a1a2e; color: white; padding: 25px 15px; min-height: 100vh; position: fixed; }
        .sidebar h2 { color: #FFD700; font-size: 18px; margin-bottom: 25px; letter-spacing: 1px; padding-left: 10px; }
        .sidebar a { display: flex; align-items: center; gap: 12px; color: rgba(255,255,255,0.75); text-decoration: none; padding: 11px 14px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; }
        .sidebar a:hover, .sidebar a.active { background: #FFD700; color: #1a1a2e; }

        .main { margin-left: 240px; padding: 30px 40px; flex: 1; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .topbar h1 { font-size: 26px; color: #1a1a2e; }
        .topbar .user { color: #666; font-size: 14px; }

        .card { background: white; border-radius: 14px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card h3 { font-size: 16px; color: #1a1a2e; margin-bottom: 18px; }

        .alert { padding: 12px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }

        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; color: #555; margin-bottom: 6px; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 14px;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; }

        .btn { padding: 10px 22px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-danger  { background: #dc3545; color: white; }
        .btn-warn    { background: #ffc107; color: #000; }

        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #667eea; color: white; font-weight: 600; }
        tr:hover { background: #f8f9fa; }

        .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-active   { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/admin_sidebar.php'; ?>

<main class="main">
    <div class="topbar">
        <h1>Manage Services</h1>
        <div class="user">Welcome, <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></strong></div>
    </div>

    <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Service deleted.</div><?php endif; ?>

    <div class="card">
        <h3><i class="fas fa-plus-circle"></i> Add New Service</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group"><label>Service Name *</label><input type="text" name="name" required></div>
                <div class="form-group"><label>Icon (FontAwesome class)</label><input type="text" name="icon" value="fas fa-concierge-bell"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Price (KES) *</label><input type="number" step="0.01" name="price" required></div>
                <div class="form-group">
                    <label>Price Type</label>
                    <select name="price_type">
                        <option value="one_time">One-time</option>
                        <option value="per_day">Per day</option>
                    </select>
                </div>
                <div class="form-group"><label>Display Order</label><input type="number" name="display_order" value="0"></div>
            </div>
            <div class="form-group"><label>Description</label><textarea name="description" rows="3"></textarea></div>
            <br>
            <button type="submit" name="add_service" class="btn btn-primary"><i class="fas fa-save"></i> Add Service</button>
        </form>
    </div>

    <div class="card">
        <h3><i class="fas fa-list"></i> Existing Services</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Icon</th><th>Name</th><th>Description</th>
                    <th>Price</th><th>Type</th><th>Status</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($services) > 0): ?>
                    <?php while ($s = mysqli_fetch_assoc($services)): ?>
                    <tr>
                        <td><?= $s['id'] ?></td>
                        <td><i class="<?= htmlspecialchars($s['icon'] ?? 'fas fa-concierge-bell') ?>"></i></td>
                        <td><?= htmlspecialchars($s['name'] ?? $s['service_name']) ?></td>
                        <td><?= htmlspecialchars(substr($s['description'] ?? '', 0, 60)) ?></td>
                        <td>KES <?= number_format($s['price'], 2) ?></td>
                        <td><?= htmlspecialchars($s['price_type'] ?? 'one_time') ?></td>
                        <td>
                            <span class="badge <?= $s['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                <?= $s['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <a href="?toggle=<?= $s['id'] ?>" class="btn btn-warn" style="padding:5px 12px;font-size:12px;"><i class="fas fa-power-off"></i></a>
                            <a href="?delete=<?= $s['id'] ?>" class="btn btn-danger" style="padding:5px 12px;font-size:12px;" onclick="return confirm('Delete this service?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center; padding:30px;">No services yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
