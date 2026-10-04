<?php
require_once 'auth_check.php';  // ← ADD THIS AT THE VERY TOP
require_once '../config/database.php';

// Rest of your code continues...
?>
<?php
require_once '../config/database.php';

// Set a password to protect responses (change this to your own password)
$access_password = 'urban123';

$show_responses = false;

// Check if password is correct
if(isset($_GET['view'])) {
    if($_GET['view'] === $access_password) {
        $show_responses = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questionnaire Responses - Urban Wheels</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header h1 {
            color: #667eea;
            font-size: 24px;
        }
        
        .header .stats {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .stat-box {
            background: #f8f9fa;
            padding: 10px 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #28a745;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
        
        .login-box {
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            max-width: 450px;
            margin: 50px auto;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .login-box input {
            width: 100%;
            padding: 12px;
            margin: 15px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .login-box button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
        }
        
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #667eea;
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .response-detail {
            display: none;
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .response-detail.active {
            display: block;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .detail-section {
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 15px;
        }
        
        .detail-section h4 {
            color: #667eea;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row {
            padding: 8px 0;
            display: flex;
            flex-wrap: wrap;
        }
        
        .detail-label {
            font-weight: 600;
            width: 180px;
            color: #666;
        }
        
        .detail-value {
            flex: 1;
            color: #333;
        }
        
        .export-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }
            th, td {
                padding: 8px;
                font-size: 12px;
            }
            .detail-label {
                width: 100%;
                margin-bottom: 5px;
            }
            .stats {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <?php if(!$show_responses): ?>
        <!-- Password Protected Login -->
        <div class="login-box">
            <i class="fas fa-lock" style="font-size: 48px; color: #667eea; margin-bottom: 20px;"></i>
            <h2>Response Viewer</h2>
            <p>Enter password to view questionnaire responses</p>
            <form method="GET">
                <input type="password" name="view" placeholder="Enter access password" required>
                <button type="submit">Access Responses <i class="fas fa-arrow-right"></i></button>
            </form>
            <div style="margin-top: 20px; font-size: 12px; color: #999;">
                <i class="fas fa-info-circle"></i> Contact admin for access password
            </div>
        </div>
    <?php else: ?>
        <?php
        // Get all responses
        $result = mysqli_query($conn, "SELECT * FROM questionnaire_responses ORDER BY submission_date DESC");
        $total = mysqli_num_rows($result);
        
        // Get counts for statistics
        $total_managers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM questionnaire_responses WHERE role = 'Manager'"))['count'];
        $total_staff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM questionnaire_responses WHERE role = 'Staff'"))['count'];
        $total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM questionnaire_responses WHERE role = 'Customer'"))['count'];
        $double_booking_yes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM questionnaire_responses WHERE double_booking = 'Yes'"))['count'];
        $online_booking_yes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM questionnaire_responses WHERE booking_efficiency = 'Yes'"))['count'];
        ?>
        
        <!-- Header with Stats -->
        <div class="header">
            <div>
                <h1><i class="fas fa-clipboard-list"></i> Questionnaire Responses</h1>
                <p style="color: #666; margin-top: 5px;">Total responses received: <strong><?php echo $total; ?></strong></p>
            </div>
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $total_managers; ?></div>
                    <div class="stat-label">Managers</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $total_staff; ?></div>
                    <div class="stat-label">Staff</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $total_customers; ?></div>
                    <div class="stat-label">Customers</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $double_booking_yes; ?></div>
                    <div class="stat-label">Experienced Double Booking</div>
                </div>
            </div>
            <div class="btn-group">
                <button class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                <a href="export_questionnaire.php" class="btn btn-success"><i class="fas fa-file-excel"></i> Export CSV</a>
                <a href="../admin/dashboard.php" class="btn btn-danger"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>
        
        <!-- Export Section -->
        <div class="export-section">
            <div>
                <i class="fas fa-download"></i> <strong>Export Data:</strong>
            </div>
            <div class="btn-group">
                <a href="export_questionnaire.php?format=csv" class="btn btn-success"><i class="fas fa-file-csv"></i> CSV Format</a>
                <a href="export_questionnaire.php?format=excel" class="btn btn-success"><i class="fas fa-file-excel"></i> Excel Format</a>
            </div>
        </div>
        
        <!-- Responses Table -->
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Experience</th>
                        <th>Booking Method</th>
                        <th>Double Booking</th>
                        <th>Efficiency</th>
                        <th>Tracking Method</th>
                        <th>Difficulty</th>
                        <th>Record Storage</th>
                        <th>Records Lost</th>
                        <th>Payment Errors</th>
                        <th>Date</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $count = 1;
                    mysqli_data_seek($result, 0);
                    while($row = mysqli_fetch_assoc($result)): 
                    ?>
                    <tr>
                        <td><?php echo $count++; ?></td>
                        <td><?php echo htmlspecialchars($row['respondent_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['respondent_email'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['respondent_phone'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['role'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($row['experience'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars(substr($row['booking_method'] ?? '', 0, 30)); ?></td>
                        <td><?php echo $row['double_booking'] ?? 'N/A'; ?></td>
                        <td><?php echo $row['booking_efficiency'] ?? 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($row['tracking_method'] ?? 'N/A'); ?></td>
                        <td><?php echo $row['tracking_difficulty'] ?? 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars(substr($row['record_storage'] ?? '', 0, 20)); ?></td>
                        <td><?php echo $row['records_lost'] ?? 'N/A'; ?></td>
                        <td><?php echo $row['payment_errors'] ?? 'N/A'; ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['submission_date'])); ?></td>
                        <td><button class="btn btn-info" style="padding: 5px 10px; font-size: 12px;" onclick="showDetails(<?php echo $row['id']; ?>)">View</button></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Detailed Response View -->
        <div id="detailModal" class="response-detail">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <h2><i class="fas fa-file-alt"></i> Full Response Details</h2>
                <button class="btn" style="background: #dc3545; color: white;" onclick="closeDetails()">Close</button>
            </div>
            <div id="detailContent"></div>
        </div>
    <?php endif; ?>
</div>

<script>
// Store all response data in JavaScript
const responsesData = {};
<?php
if($show_responses) {
    mysqli_data_seek($result, 0);
    while($row = mysqli_fetch_assoc($result)) {
        echo "responsesData[{$row['id']}] = " . json_encode($row) . ";\n";
    }
}
?>

function showDetails(id) {
    const data = responsesData[id];
    if(!data) return;
    
    const detailContent = document.getElementById('detailContent');
    detailContent.innerHTML = `
        <div class="detail-grid">
            <div class="detail-section">
                <h4><i class="fas fa-user"></i> Respondent Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">${escapeHtml(data.respondent_name) || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">${escapeHtml(data.respondent_email) || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value">${escapeHtml(data.respondent_phone) || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Role:</span>
                    <span class="detail-value">${escapeHtml(data.role) || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Experience:</span>
                    <span class="detail-value">${escapeHtml(data.experience) || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Submission Date:</span>
                    <span class="detail-value">${new Date(data.submission_date).toLocaleString()}</span>
                </div>
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-calendar-check"></i> Booking System</h4>
                <div class="detail-row">
                    <span class="detail-label">Booking Method:</span>
                    <span class="detail-value">${escapeHtml(data.booking_method) || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Double Booking:</span>
                    <span class="detail-value">${data.double_booking || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Efficiency:</span>
                    <span class="detail-value">${data.booking_efficiency || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Challenges:</span>
                    <span class="detail-value">${escapeHtml(data.booking_challenges) || 'None provided'}</span>
                </div>
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-chart-line"></i> Vehicle Tracking</h4>
                <div class="detail-row">
                    <span class="detail-label">Tracking Method:</span>
                    <span class="detail-value">${escapeHtml(data.tracking_method) || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Difficulty:</span>
                    <span class="detail-value">${data.tracking_difficulty || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Challenges:</span>
                    <span class="detail-value">${escapeHtml(data.tracking_challenges) || 'None provided'}</span>
                </div>
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-folder-open"></i> Record Management</h4>
                <div class="detail-row">
                    <span class="detail-label">Record Storage:</span>
                    <span class="detail-value">${escapeHtml(data.record_storage) || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Records Lost:</span>
                    <span class="detail-value">${data.records_lost || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Problems:</span>
                    <span class="detail-value">${escapeHtml(data.record_problems) || 'None provided'}</span>
                </div>
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-credit-card"></i> Payment System</h4>
                <div class="detail-row">
                    <span class="detail-label">Payment Recording:</span>
                    <span class="detail-value">${escapeHtml(data.payment_recording) || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Errors:</span>
                    <span class="detail-value">${data.payment_errors || 'N/A'}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Suggestions:</span>
                    <span class="detail-value">${escapeHtml(data.payment_suggestions) || 'None provided'}</span>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('detailModal').classList.add('active');
    document.getElementById('detailModal').scrollIntoView({ behavior: 'smooth' });
}

function closeDetails() {
    document.getElementById('detailModal').classList.remove('active');
}

function escapeHtml(text) {
    if(!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

</body>
</html>