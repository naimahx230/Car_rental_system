<?php
require_once 'auth_check.php';
require_once '../config/database.php';

// Handle mark as read
if(isset($_GET['mark_read']) && isset($_GET['id'])) {
    $id = (int)$_GET['mark_read'];
    mysqli_query($conn, "UPDATE contact_messages SET status = 'read' WHERE id = $id");
    header("Location: contacts.php");
    exit();
}

// Handle reply to message
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_message'])) {
    $message_id = (int)$_POST['message_id'];
    $reply = mysqli_real_escape_string($conn, $_POST['admin_reply']);
    
    $update = "UPDATE contact_messages SET admin_reply = '$reply', status = 'replied', replied_at = NOW() WHERE id = $message_id";
    mysqli_query($conn, $update);
    
    // Get user email to send reply
    $msg_query = mysqli_query($conn, "SELECT name, email, subject FROM contact_messages WHERE id = $message_id");
    $msg = mysqli_fetch_assoc($msg_query);
    
    // Send email reply
    $to = $msg['email'];
    $subject = "Re: " . $msg['subject'];
    $message = "Dear {$msg['name']},\n\nThank you for contacting Urban Wheels.\n\n" . $reply . "\n\nBest regards,\nUrban Wheels Team";
    $headers = "From: Urban Wheels <noreply@urbanwheels.com>\r\n";
    @mail($to, $subject, $message, $headers);
    
    header("Location: contacts.php?success=1");
    exit();
}

// Handle delete message
if(isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM contact_messages WHERE id = $id");
    header("Location: contacts.php");
    exit();
}

// Handle bulk delete
if(isset($_POST['bulk_delete']) && isset($_POST['selected_ids'])) {
    $ids = implode(',', array_map('intval', $_POST['selected_ids']));
    mysqli_query($conn, "DELETE FROM contact_messages WHERE id IN ($ids)");
    header("Location: contacts.php");
    exit();
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT * FROM contact_messages WHERE 1=1";
if($status_filter) $query .= " AND status = '$status_filter'";
if($search) $query .= " AND (name LIKE '%$search%' OR email LIKE '%$search%' OR subject LIKE '%$search%' OR message LIKE '%$search%')";
$query .= " ORDER BY created_at DESC";

$messages = mysqli_query($conn, $query);

// Get counts
$total = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM contact_messages"));
$unread = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM contact_messages WHERE status = 'unread'"));
$read = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM contact_messages WHERE status = 'read'"));
$replied = mysqli_num_rows(mysqli_query($conn, "SELECT * FROM contact_messages WHERE status = 'replied'"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header h1 { font-size: 24px; color: #333; }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .stat-card.active { border: 2px solid #667eea; background: rgba(102,126,234,0.05); }
        .stat-number { font-size: 32px; font-weight: 700; color: #667eea; }
        .stat-label { color: #666; font-size: 14px; margin-top: 5px; }
        
        .filters {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        .filter-group { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .filter-group input, .filter-group select { padding: 8px 15px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; }
        .btn-filter { background: #667eea; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; }
        .btn-reset { background: #6c757d; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; text-decoration: none; }
        .btn-bulk-delete { background: #dc3545; color: white; border: none; padding: 8px 20px; border-radius: 8px; cursor: pointer; }
        
        .messages-table { background: white; border-radius: 12px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #667eea; color: white; font-weight: 600; position: sticky; top: 0; }
        
        .checkbox-col { width: 30px; text-align: center; }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .status-unread { background: #dc3545; color: white; }
        .status-read { background: #17a2b8; color: white; }
        .status-replied { background: #28a745; color: white; }
        
        .message-preview { max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: #666; }
        
        .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-view { background: #17a2b8; color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; }
        .btn-reply { background: #28a745; color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; }
        .btn-delete { background: #dc3545; color: white; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; }
        .btn-mark-read { background: #ffc107; color: #000; border: none; padding: 5px 12px; border-radius: 5px; cursor: pointer; font-size: 12px; }
        
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
            max-width: 650px;
            width: 90%;
            border-radius: 15px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
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
        .message-detail { margin-bottom: 20px; }
        .message-detail p { margin: 8px 0; line-height: 1.6; }
        .reply-section { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
        .reply-section textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; resize: vertical; margin: 10px 0; }
        .btn-send { background: #28a745; color: white; border: none; padding: 10px 25px; border-radius: 8px; cursor: pointer; }
        .btn-close { background: #6c757d; color: white; border: none; padding: 10px 25px; border-radius: 8px; cursor: pointer; }
        
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
            .stats { grid-template-columns: repeat(2, 1fr); }
            .filter-group { width: 100%; }
            .filters { flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php $current_page = "contacts"; include __DIR__ . "/../includes/admin_sidebar.php"; ?>

    <div class="main-content">
        <div class="header">
            <div><h1>Contact Messages</h1><p>Manage customer inquiries and support requests</p></div>
            <div><button class="btn-bulk-delete" onclick="bulkDelete()" style="display: none;" id="bulkDeleteBtn"><i class="fas fa-trash"></i> Delete Selected</button></div>
        </div>

        <div class="stats">
            <div class="stat-card <?php echo !$status_filter ? 'active' : ''; ?>" onclick="filterByStatus('')"><div class="stat-number"><?php echo $total; ?></div><div class="stat-label">All Messages</div></div>
            <div class="stat-card <?php echo $status_filter == 'unread' ? 'active' : ''; ?>" onclick="filterByStatus('unread')"><div class="stat-number"><?php echo $unread; ?></div><div class="stat-label">Unread</div></div>
            <div class="stat-card <?php echo $status_filter == 'read' ? 'active' : ''; ?>" onclick="filterByStatus('read')"><div class="stat-number"><?php echo $read; ?></div><div class="stat-label">Read</div></div>
            <div class="stat-card <?php echo $status_filter == 'replied' ? 'active' : ''; ?>" onclick="filterByStatus('replied')"><div class="stat-number"><?php echo $replied; ?></div><div class="stat-label">Replied</div></div>
        </div>

        <div class="filters">
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: center; flex: 1;">
                <div class="filter-group"><input type="text" name="search" placeholder="Search by name, email, subject..." value="<?php echo htmlspecialchars($search); ?>"></div>
                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Search</button>
                <a href="contacts.php" class="btn-reset"><i class="fas fa-sync-alt"></i> Reset</a>
            </form>
        </div>

        <div class="messages-table">
            <form method="POST" id="bulkForm">
                <input type="hidden" name="bulk_delete" value="1">
                <div id="selectedIdsInput"></div>
                <table>
                    <thead>
                        <tr>
                            <th class="checkbox-col"><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($messages) > 0): ?>
                            <?php while($msg = mysqli_fetch_assoc($messages)): ?>
                            <tr style="<?php echo $msg['status'] == 'unread' ? 'background: #fff3cd;' : ''; ?>">
                                <td class="checkbox-col"><input type="checkbox" class="message-checkbox" value="<?php echo $msg['id']; ?>" onchange="updateBulkDeleteButton()"></td>
                                <td><?php echo $msg['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($msg['name']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($msg['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                                <td class="message-preview"><?php echo htmlspecialchars(substr($msg['message'], 0, 80)); ?>...</td>
                                <td><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></td>
                                <td><span class="status-badge status-<?php echo $msg['status']; ?>"><?php echo ucfirst($msg['status']); ?></span></td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn-view" onclick="viewMessage(<?php echo $msg['id']; ?>)"><i class="fas fa-eye"></i> View</button>
                                        <button type="button" class="btn-reply" onclick="replyMessage(<?php echo $msg['id']; ?>)"><i class="fas fa-reply"></i> Reply</button>
                                        <?php if($msg['status'] == 'unread'): ?>
                                            <a href="?mark_read=<?php echo $msg['id']; ?>" class="btn-mark-read"><i class="fas fa-check"></i> Mark Read</a>
                                        <?php endif; ?>
                                        <a href="?delete=<?php echo $msg['id']; ?>" class="btn-delete" onclick="return confirm('Delete this message?')"><i class="fas fa-trash"></i> Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 60px;">
                                    <i class="fas fa-envelope-open-text" style="font-size: 60px; color: #ccc;"></i>
                                    <h3>No Messages Found</h3>
                                    <p>No contact messages have been submitted yet.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>
        </div>
    </div>

    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-envelope"></i> Message Details</h3>
                <button onclick="closeModal()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body" id="viewMessageContent"></div>
        </div>
    </div>

    <div id="replyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-reply"></i> Reply to Message</h3>
                <button onclick="closeReplyModal()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <div id="replyMessageContent"></div>
                <form method="POST" id="replyForm">
                    <input type="hidden" name="message_id" id="reply_message_id">
                    <div class="reply-section">
                        <label><strong>Your Reply:</strong></label>
                        <textarea name="admin_reply" rows="6" placeholder="Type your reply here..." required></textarea>
                        <button type="submit" name="reply_message" class="btn-send"><i class="fas fa-paper-plane"></i> Send Reply</button>
                        <button type="button" onclick="closeReplyModal()" class="btn-close">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const messagesData = {};
        <?php
        mysqli_data_seek($messages, 0);
        while($m = mysqli_fetch_assoc($messages)) {
            echo "messagesData[{$m['id']}] = " . json_encode([
                'id' => $m['id'],
                'name' => $m['name'],
                'email' => $m['email'],
                'phone' => $m['phone'],
                'subject' => $m['subject'],
                'message' => $m['message'],
                'created_at' => $m['created_at'],
                'admin_reply' => $m['admin_reply'],
                'status' => $m['status']
            ]) . ";\n";
        }
        ?>
        
        function viewMessage(id) {
            const msg = messagesData[id];
            if(!msg) return;
            
            let replyHtml = '';
            if(msg.admin_reply) {
                replyHtml = `<p><strong>Admin Reply:</strong></p><div style="background:#e7f3ff;padding:15px;border-radius:8px;margin:10px 0;">${escapeHtml(msg.admin_reply)}</div>`;
            }
            
            document.getElementById('viewMessageContent').innerHTML = `
                <div class="message-detail">
                    <p><strong>From:</strong> ${escapeHtml(msg.name)}</p>
                    <p><strong>Email:</strong> ${escapeHtml(msg.email)}</p>
                    <p><strong>Phone:</strong> ${msg.phone || 'Not provided'}</p>
                    <p><strong>Subject:</strong> ${escapeHtml(msg.subject)}</p>
                    <p><strong>Date:</strong> ${new Date(msg.created_at).toLocaleString()}</p>
                    <p><strong>Message:</strong></p>
                    <div style="background:#f8f9fa;padding:15px;border-radius:8px;margin:10px 0;">${escapeHtml(msg.message)}</div>
                    ${replyHtml}
                </div>
                <div style="margin-top:20px;text-align:right;">
                    <button class="btn-reply" onclick="closeModal(); replyMessage(${id});">Reply</button>
                    <button class="btn-close" onclick="closeModal()">Close</button>
                </div>
            `;
            document.getElementById('viewModal').style.display = 'flex';
            
            // Mark as read via AJAX
            fetch(`?mark_read=${id}`);
        }
        
        function replyMessage(id) {
            const msg = messagesData[id];
            if(!msg) return;
            
            document.getElementById('reply_message_id').value = id;
            document.getElementById('replyMessageContent').innerHTML = `
                <div style="background:#f8f9fa;padding:15px;border-radius:8px;margin-bottom:15px;">
                    <p><strong>From:</strong> ${escapeHtml(msg.name)}</p>
                    <p><strong>Subject:</strong> ${escapeHtml(msg.subject)}</p>
                    <p><strong>Message:</strong></p>
                    <p style="margin-top:5px;">${escapeHtml(msg.message)}</p>
                </div>
            `;
            document.getElementById('replyModal').style.display = 'flex';
        }
        
        function filterByStatus(status) { 
            window.location.href = `?status=${status}`; 
        }
        
        function toggleSelectAll() { 
            const selectAll = document.getElementById('selectAll'); 
            const checkboxes = document.querySelectorAll('.message-checkbox'); 
            checkboxes.forEach(cb => cb.checked = selectAll.checked); 
            updateBulkDeleteButton(); 
        }
        
        function updateBulkDeleteButton() { 
            const checkboxes = document.querySelectorAll('.message-checkbox:checked'); 
            const btn = document.getElementById('bulkDeleteBtn'); 
            btn.style.display = checkboxes.length > 0 ? 'inline-block' : 'none'; 
        }
        
        function bulkDelete() { 
            const checkboxes = document.querySelectorAll('.message-checkbox:checked'); 
            if(checkboxes.length === 0) return; 
            if(confirm(`Delete ${checkboxes.length} selected message(s)?`)) { 
                const ids = Array.from(checkboxes).map(cb => cb.value); 
                const idsInput = document.getElementById('selectedIdsInput'); 
                ids.forEach(id => { 
                    idsInput.innerHTML += `<input type="hidden" name="selected_ids[]" value="${id}">`; 
                }); 
                document.getElementById('bulkForm').submit(); 
            } 
        }
        
        function closeModal() { 
            document.getElementById('viewModal').style.display = 'none'; 
        }
        
        function closeReplyModal() { 
            document.getElementById('replyModal').style.display = 'none'; 
        }
        
        function escapeHtml(text) { 
            if(!text) return ''; 
            const div = document.createElement('div'); 
            div.textContent = text; 
            return div.innerHTML; 
        }
        
        window.onclick = function(event) { 
            const viewModal = document.getElementById('viewModal'); 
            const replyModal = document.getElementById('replyModal'); 
            if (event.target == viewModal) closeModal(); 
            if (event.target == replyModal) closeReplyModal(); 
        }
    </script>
</body>
</html>