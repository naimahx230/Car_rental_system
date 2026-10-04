<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// Handle approve review
if(isset($_GET['approve']) && isset($_GET['id'])) {
    $id = (int)$_GET['approve'];
    mysqli_query($conn, "UPDATE reviews SET status = 'approved' WHERE id = $id");
    header("Location: reviews.php?success=approved");
    exit();
}

// Handle reject review
if(isset($_GET['reject']) && isset($_GET['id'])) {
    $id = (int)$_GET['reject'];
    mysqli_query($conn, "UPDATE reviews SET status = 'rejected' WHERE id = $id");
    header("Location: reviews.php?success=rejected");
    exit();
}

// Handle delete review
if(isset($_GET['delete']) && isset($_GET['id'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM reviews WHERE id = $id");
    header("Location: reviews.php?success=deleted");
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$query = "SELECT r.*, u.name as customer_name, u.email, v.brand, v.model, v.registration_number 
          FROM reviews r 
          JOIN users u ON r.user_id = u.id 
          JOIN vehicles v ON r.vehicle_id = v.id 
          WHERE 1=1";

if($status_filter) $query .= " AND r.status = '$status_filter'";
if($search) $query .= " AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR r.comment LIKE '%$search%')";
$query .= " ORDER BY r.created_at DESC";

$reviews = mysqli_query($conn, $query);

// Get counts
$total_reviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM reviews"))['count'];
$pending_reviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM reviews WHERE status = 'pending'"))['count'];
$approved_reviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM reviews WHERE status = 'approved'"))['count'];
$rejected_reviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM reviews WHERE status = 'rejected'"))['count'];
$avg_rating = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(rating) as avg FROM reviews WHERE status = 'approved'"))['avg'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; }
        
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
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .stat-number { font-size: 28px; font-weight: 700; }
        .stat-label { font-size: 12px; color: #666; margin-top: 5px; }
        .stat-total .stat-number { color: #667eea; }
        .stat-pending .stat-number { color: #ffc107; }
        .stat-approved .stat-number { color: #28a745; }
        .stat-rejected .stat-number { color: #dc3545; }
        
        .filters {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .filter-group input, .filter-group select {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        .btn-filter { background: #667eea; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .btn-reset { background: #6c757d; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        
        .reviews-table {
            background: white;
            border-radius: 10px;
            overflow-x: auto;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #667eea; color: white; font-weight: 600; }
        tr:hover { background: #f8f9fa; }
        
        .rating-stars { color: #FFD700; font-size: 13px; }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-approved { background: #28a745; color: #fff; }
        .status-rejected { background: #dc3545; color: #fff; }
        
        .review-comment {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-view { background: #17a2b8; color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; }
        .btn-approve { background: #28a745; color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-reject { background: #ffc107; color: #000; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-delete { background: #dc3545; color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; text-decoration: none; display: inline-block; }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            max-width: 550px;
            width: 90%;
            border-radius: 15px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body { padding: 25px; }
        .full-comment {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            line-height: 1.6;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
            .stats { grid-template-columns: repeat(2, 1fr); }
            th, td { padding: 8px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <?php $current_page = "reviews"; include __DIR__ . "/../includes/admin_sidebar.php"; ?>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-star"></i> Manage Reviews</h1>
            <p>Approve, reject, or manage customer reviews</p>
        </div>

        <?php if(isset($_GET['success'])): ?>
            <div class="alert"><i class="fas fa-check-circle"></i> <?php if($_GET['success'] == 'approved') echo "Review approved successfully and is now visible to customers!"; elseif($_GET['success'] == 'rejected') echo "Review rejected successfully!"; elseif($_GET['success'] == 'deleted') echo "Review deleted successfully!"; ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card stat-total" onclick="window.location.href='?status='"><div class="stat-number"><?php echo $total_reviews; ?></div><div class="stat-label">Total Reviews</div></div>
            <div class="stat-card stat-pending" onclick="window.location.href='?status=pending'"><div class="stat-number"><?php echo $pending_reviews; ?></div><div class="stat-label">Pending</div></div>
            <div class="stat-card stat-approved" onclick="window.location.href='?status=approved'"><div class="stat-number"><?php echo $approved_reviews; ?></div><div class="stat-label">Approved</div></div>
            <div class="stat-card stat-rejected" onclick="window.location.href='?status=rejected'"><div class="stat-number"><?php echo $rejected_reviews; ?></div><div class="stat-label">Rejected</div></div>
            <?php if($avg_rating): ?><div class="stat-card"><div class="stat-number"><?php echo number_format($avg_rating, 1); ?></div><div class="stat-label">Avg Rating (approved)</div></div><?php endif; ?>
        </div>

        <div class="filters">
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center;">
                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                <div class="filter-group"><input type="text" name="search" placeholder="Search by name, email, review..." value="<?php echo htmlspecialchars($search); ?>"></div>
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Search</button>
                <a href="reviews.php" class="btn-reset"><i class="fas fa-sync-alt"></i> Reset</a>
            </form>
        </div>

        <div class="reviews-table">
            <table>
                <thead><tr><th>ID</th><th>Customer</th><th>Vehicle</th><th>Rating</th><th>Review</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if(mysqli_num_rows($reviews) > 0): ?>
                        <?php while($review = mysqli_fetch_assoc($reviews)): ?>
                        <tr>
                            <td><?php echo $review['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($review['customer_name']); ?></strong><br><small><?php echo htmlspecialchars($review['email']); ?></small></td>
                            <td><?php echo htmlspecialchars($review['brand'] . ' ' . $review['model']); ?></td>
                            <td><div class="rating-stars"><?php for($i = 1; $i <= 5; $i++): ?><?php if($i <= $review['rating']): ?><i class="fas fa-star"></i><?php else: ?><i class="far fa-star"></i><?php endif; ?><?php endfor; ?></div></td>
                            <td class="review-comment"><?php echo htmlspecialchars(substr($review['comment'], 0, 80)); ?>...</td>
                            <td><?php echo date('M d, Y', strtotime($review['created_at'])); ?></td>
                            <td><span class="status-badge status-<?php echo $review['status']; ?>"><?php echo ucfirst($review['status']); ?></span></td>
                            <td><div class="action-buttons"><button class="btn-view" onclick="viewReview(<?php echo $review['id']; ?>)"><i class="fas fa-eye"></i> View</button><?php if($review['status'] == 'pending'): ?><a href="?approve=<?php echo $review['id']; ?>" class="btn-approve" onclick="return confirm('Approve this review? It will be visible to customers.')"><i class="fas fa-check"></i> Approve</a><a href="?reject=<?php echo $review['id']; ?>" class="btn-reject" onclick="return confirm('Reject this review?')"><i class="fas fa-times"></i> Reject</a><?php endif; ?><a href="?delete=<?php echo $review['id']; ?>" class="btn-delete" onclick="return confirm('Delete this review permanently?')"><i class="fas fa-trash"></i> Delete</a></div></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align: center; padding: 60px;"><i class="fas fa-star" style="font-size: 60px; color: #ccc; margin-bottom: 20px;"></i><h3>No Reviews Found</h3><p>No customer reviews match your filters.</p></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3>Review Details</h3><button onclick="closeModal()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">&times;</button></div>
            <div class="modal-body" id="viewReviewContent"></div>
        </div>
    </div>

    <script>
        const reviewsData = {};
        <?php
        mysqli_data_seek($reviews, 0);
        while($r = mysqli_fetch_assoc($reviews)) {
            echo "reviewsData[{$r['id']}] = " . json_encode(['id'=>$r['id'],'customer_name'=>$r['customer_name'],'email'=>$r['email'],'brand'=>$r['brand'],'model'=>$r['model'],'rating'=>$r['rating'],'comment'=>$r['comment'],'status'=>$r['status'],'created_at'=>$r['created_at']]) . ";\n";
        }
        ?>
        
        function viewReview(id) {
            const review = reviewsData[id]; if(!review) return;
            let stars = ''; for(let i = 1; i <= 5; i++) { if(i <= review.rating) stars += '<i class="fas fa-star" style="color: #FFD700;"></i> '; else stars += '<i class="far fa-star" style="color: #ccc;"></i> '; }
            document.getElementById('viewReviewContent').innerHTML = `<div><p><strong>Customer:</strong> ${escapeHtml(review.customer_name)}</p><p><strong>Email:</strong> ${escapeHtml(review.email)}</p><p><strong>Vehicle:</strong> ${escapeHtml(review.brand)} ${escapeHtml(review.model)}</p><p><strong>Rating:</strong> ${stars}</p><p><strong>Date:</strong> ${new Date(review.created_at).toLocaleDateString()}</p><p><strong>Review:</strong></p><div class="full-comment">${escapeHtml(review.comment)}</div><p><strong>Status:</strong> <span class="status-badge status-${review.status}">${review.status.toUpperCase()}</span></p></div><div style="margin-top: 20px; text-align: right;">${review.status === 'pending' ? `<a href="?approve=${review.id}" class="btn-approve" style="padding: 8px 20px; text-decoration: none; display: inline-block; margin: 0 5px;">Approve</a><a href="?reject=${review.id}" class="btn-reject" style="padding: 8px 20px; text-decoration: none; display: inline-block; margin: 0 5px;">Reject</a>` : ''}<button class="btn-view" onclick="closeModal()">Close</button></div>`;
            document.getElementById('viewModal').style.display = 'flex';
        }
        
        function closeModal() { document.getElementById('viewModal').style.display = 'none'; }
        function escapeHtml(text) { if(!text) return ''; const div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
        window.onclick = function(event) { const modal = document.getElementById('viewModal'); if (event.target == modal) closeModal(); }
    </script>
</body>
</html>