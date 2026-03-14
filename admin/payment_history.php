<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

$pending_receipts_count = 0; $show_pending_badge = false;
try {
    $q = $db->prepare("SELECT COUNT(*) as count FROM payment_receipts WHERE status = 'pending'");
    $q->execute();
    $pending_receipts_count = (int)$q->fetch(PDO::FETCH_ASSOC)['count'];
} catch (Throwable $e) {}
$last_seen = isset($_SESSION['pending_receipts_seen_count']) ? (int)$_SESSION['pending_receipts_seen_count'] : 0;
$show_pending_badge = $pending_receipts_count > $last_seen;
$_SESSION['pending_receipts_seen_count'] = max($pending_receipts_count, $last_seen);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action == 'delete_history') {
        $history_id = $_POST['history_id'];
        
        $query = "DELETE FROM payment_history WHERE id = :history_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':history_id', $history_id);
        
        if ($stmt->execute()) {
            $message = "Payment history deleted successfully";
            logAdminAction($db, $_SESSION['admin_id'], 'delete_payment_history', json_encode(['history_id'=>$history_id]));
        } else {
            $error = "Error deleting payment history";
        }
    }
}

// Get all active users for filter
$query = "SELECT * FROM users WHERE status = 'active' ORDER BY fullname";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_user_id = $_GET['user_id'] ?? 'all';
$selected_year = $_GET['year'] ?? date('Y');
$selected_month = $_GET['month'] ?? 'all';

$payment_history = [];
try {
    $check_table = "SHOW TABLES LIKE 'payment_history'";
    $table_exists = $db->query($check_table)->rowCount() > 0;
    
    if ($table_exists) {
        $history_query = "SELECT ph.*, u.room_number, a.fullname AS admin_name FROM payment_history ph 
                          LEFT JOIN users u ON ph.user_id = u.id 
                          LEFT JOIN admins a ON ph.admin_id = a.id 
                          WHERE 1=1 ";
        if ($selected_user_id != 'all' && is_numeric($selected_user_id)) {
            $history_query .= " AND ph.user_id = :user_id ";
        }
        if ($selected_year != 'all') {
            $history_query .= " AND ph.year = :year ";
        }
        if ($selected_month != 'all') {
            $history_query .= " AND ph.month = :month ";
        }
        $history_query .= " ORDER BY ph.created_at DESC";

        $history_stmt = $db->prepare($history_query);
        if ($selected_user_id != 'all' && is_numeric($selected_user_id)) {
            $history_stmt->bindParam(':user_id', $selected_user_id);
        }
        if ($selected_year != 'all') {
            $history_stmt->bindParam(':year', $selected_year);
        }
        if ($selected_month != 'all') {
            $history_stmt->bindParam(':month', $selected_month);
        }
        $history_stmt->execute();
        $payment_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Payment history query error: " . $e->getMessage());
}

$months = [
    'january' => 'January',
    'february' => 'February', 
    'march' => 'March',
    'april' => 'April',
    'may' => 'May',
    'june' => 'June',
    'july' => 'July',
    'august' => 'August',
    'september' => 'September',
    'october' => 'October',
    'november' => 'November',
    'december' => 'December'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - Ruin Boarders</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        
        .sidebar { position: fixed; left: 0; top: 0; width: 250px; height: 100vh; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); padding: 20px 0; z-index: 1000; transition: transform 0.3s ease; }
        .sidebar-header { text-align: center; padding: 0 20px 30px; border-bottom: 1px solid #e1e5e9; margin-bottom: 20px; }
        .sidebar-header h2 { color: #333; font-size: 1.5rem; font-weight: 700; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin-bottom: 5px; position: relative; }
        .sidebar-menu a { display: flex; align-items: center; padding: 15px 20px; color: #666; text-decoration: none; transition: all 0.3s ease; border-radius: 0 25px 25px 0; margin-right: 20px; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: linear-gradient(135deg, #667eea, #764ba2); color: white; transform: translateX(5px); }
        .sidebar-menu i { margin-right: 10px; width: 20px; }
        .notification-badge { position: absolute; top: 8px; right: 8px; background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; }
        
        .main-content { margin-left: 250px; padding: 30px; min-height: 100vh; }
        
        .header { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 15px; padding: 20px 30px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .header h1 { color: #333; font-size: 2rem; font-weight: 700; margin-bottom: 10px; }
        
        .filters { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 15px; padding: 25px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .filter-row { display: grid; grid-template-columns: 1fr 1fr auto; gap: 20px; align-items: end; }
        .form-group { margin-bottom: 0; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        .form-control { width: 100%; padding: 12px 15px; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; }
        .form-control:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        
        .btn { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; gap: 8px; font-size: 14px; text-decoration: none;}
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3); }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; transform: translateY(-1px); box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3); }
        .btn-delete-sm { padding: 8px 12px; background: #dc3545; color: white; border: none; border-radius: 6px; font-size: 12px; cursor: pointer; transition: all 0.3s ease; }
        .btn-delete-sm:hover { background: #c82333; transform: translateY(-1px); }

        .card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 15px; padding: 25px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        
        .table-container { overflow-x: auto; border-radius: 10px; }
        .history-table { width: 100%; border-collapse: collapse; min-width: 800px; }
        .history-table th, .history-table td { padding: 15px; text-align: left; border-bottom: 1px solid #e1e5e9; }
        .history-table th { background: #f8f9fa; font-weight: 600; color: #333; }
        .history-table tbody tr { transition: background-color 0.2s ease; }
        .history-table tbody tr:hover { background-color: #f0f4ff; }
        .history-table td.amount { font-weight: 700; color: #28a745; }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid; }
        .alert-success { background: #d4edda; color: #155724; border-color: #28a745; }
        .alert-danger { background: #f8d7da; color: #721c24; border-color: #dc3545; }
        
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(5px); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 25px; border-radius: 15px; width: 90%; max-width: 420px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); animation: slideDown 0.3s ease; }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e1e5e9; padding-bottom: 15px; }
        .modal-header h3 { color: #333; font-size: 1.25rem; }
        .close { color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; transition: color 0.3s; }
        .close:hover { color: #333; }

        .mobile-menu-toggle { display: none; position: fixed; top: 20px; left: 20px; z-index: 1001; background: rgba(255, 255, 255, 0.9); border: none; border-radius: 8px; padding: 10px; cursor: pointer; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); }
        
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-menu-toggle { display: block; }
            .filter-row { grid-template-columns: 1fr; }
        }
    
        /* Profile & Dark Mode Styles */
        .sidebar { padding-bottom: 90px; }
        .sidebar-profile { position: absolute; bottom: 0; left: 0; width: 100%; padding: 20px; background: rgba(255, 255, 255, 0.95); border-top: 1px solid #e1e5e9; transition: all 0.3s ease; }
        .sidebar.dark-mode-element .sidebar-profile { background: rgba(30, 30, 40, 0.95); border-top-color: #333; }
        .profile-info { display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px; border-radius: 10px; transition: background 0.2s; }
        .profile-info:hover { background: rgba(0,0,0,0.05); }
        .sidebar.dark-mode-element .profile-info:hover { background: rgba(255,255,255,0.05); }
        .profile-icon { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1.2rem; }
        .profile-details { flex-grow: 1; display: flex; flex-direction: column; }
        .profile-name { font-weight: 600; color: #333; font-size: 0.95rem; }
        .profile-role { font-size: 0.8rem; color: #666; }
        .profile-dropdown { position: absolute; bottom: 85px; left: 20px; right: 20px; background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.15); opacity: 0; visibility: hidden; transform: translateY(10px); transition: all 0.3s ease; display: flex; flex-direction: column; overflow: hidden; z-index: 1001; }
        .profile-dropdown.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .profile-dropdown a { padding: 12px 20px; color: #444; text-decoration: none; display: flex; align-items: center; gap: 10px; font-weight: 500; transition: background 0.2s; }
        .profile-dropdown a:hover { background: #f8f9fa; color: #667eea; }
        .profile-dropdown a.logout-text { color: #dc3545; border-top: 1px solid #eee; }
        .profile-dropdown a.logout-text:hover { background: #fff5f5; }
        
        body.dark-mode { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); }
        .dark-mode .sidebar { background: rgba(25, 25, 35, 0.95); border-right: 1px solid #333; }
        .dark-mode .sidebar-profile { background: rgba(25, 25, 35, 0.95); border-top-color: #333; }
        .dark-mode .sidebar-header { border-bottom-color: #333; }
        .dark-mode .sidebar-header h2 { background: linear-gradient(135deg, #a8c0ff, #3f2b96); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .dark-mode .sidebar-header p { color: #aaa; }
        .dark-mode .sidebar-menu a { color: #aaa; }
        .dark-mode .sidebar-menu a:hover, .dark-mode .sidebar-menu a.active { background: linear-gradient(135deg, #3f2b96, #a8c0ff); color: white; }
        .dark-mode .main-content h1 { color: #fff; }
        .dark-mode .main-content p { color: #aaa; }
        .dark-mode .card, .dark-mode .header, .dark-mode .stats-grid > div, .dark-mode .filters, .dark-mode .table-container, .dark-mode .announcement-item { background: rgba(30, 30, 45, 0.95); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); color: #fff; border: 1px solid #333; }
        .dark-mode .card-header { border-bottom-color: #333; }
        .dark-mode .card-title, .dark-mode .stat-number, .dark-mode th, .dark-mode td { color: #fff; }
        .dark-mode .stat-label, .dark-mode .announcement-date { color: #aaa; }
        .dark-mode .form-group label { color: #ddd; }
        .dark-mode .form-control { background: #2a2a35; border-color: #444; color: #fff; }
        .dark-mode table th, .dark-mode table td { border-bottom-color: #444; }
        .dark-mode table { border-color: #444; }
        .dark-mode table th { background: #2a2a35; }
        .dark-mode .clickable-row:hover { background: #3a3a45 !important; }
        .dark-mode .profile-name { color: #fff; }
        .dark-mode .profile-role { color: #aaa; }
        .dark-mode .profile-dropdown { background: #2a2a35; border: 1px solid #444; }
        .dark-mode .profile-dropdown a { color: #ddd; }
        .dark-mode .profile-dropdown a.logout-text { color: #ff6b6b; border-top-color: #444; }
        .dark-mode .profile-dropdown a:hover { background: #3a3a45; color: #a8c0ff; }
        .dark-mode .profile-dropdown a.logout-text:hover { background: rgba(220, 53, 69, 0.2); }
        .dark-mode .modal-content { background: #2a2a35; color: #fff; border-color: #444; }
        .dark-mode .modal-header { border-bottom-color: #444; }
        .dark-mode .modal-header h3 { color: #fff; }
        .dark-mode .close { color: #aaa; }
        .dark-mode .close:hover { color: #fff; }
        .dark-mode .nav-btn { background: #333; border-color: #555; color: #fff; }
        .dark-mode .history-nav { background: #2a2a35; }
        .dark-mode .history-year { color: #fff; }
        .dark-mode .history-item.paid { background: rgba(46, 125, 50, 0.2); border-color: #2e7d32; color: #81c784; }
        .dark-mode .history-item.unpaid { background: rgba(229, 62, 62, 0.2); border-color: #e53e3e; color: #fc8181; }
        .dark-mode .history-table tbody tr:hover { background: rgba(255,255,255,0.05) !important; }
        .dark-mode .alert-success { background: rgba(40, 167, 69, 0.2); border-color: #2e7d32; color: #81c784; }
        .dark-mode .alert-danger { background: rgba(220, 53, 69, 0.2); border-color: #e53e3e; color: #fc8181; }

    </style>
    <link rel="stylesheet" href="../assets/css/neuromorphic-theme.css">
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <script src="../assets/js/neuromorphic-theme.js" defer></script>
</head>
<body>
    <?php $current_admin_page = 'payment_history'; ?>
    <?php require __DIR__ . '/includes/admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Payment History</h1>
            <p>Review recorded payment updates for every boarder.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="filters">
            <form method="GET">
                <div class="filter-row">
                    <div class="form-group">
                        <label for="user_id">Select Boarder</label>
                        <select id="user_id" name="user_id" class="form-control" onchange="this.form.submit()">
                            <option value="all" <?php echo $selected_user_id == 'all' ? 'selected' : ''; ?>>
                                All Boarders
                            </option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" 
                                        <?php echo $selected_user_id == $user['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['fullname']); ?> (Room <?php echo htmlspecialchars($user['room_number']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="year">Year</label>
                        <select id="year" name="year" class="form-control" onchange="this.form.submit()">
                            <option value="all" <?php echo $selected_year == 'all' ? 'selected' : ''; ?>>All Years</option>
                            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="month">Month</label>
                        <select id="month" name="month" class="form-control" onchange="this.form.submit()">
                            <option value="all" <?php echo $selected_month == 'all' ? 'selected' : ''; ?>>All Months</option>
                            <?php foreach ($months as $k => $v): ?>
                                <option value="<?php echo $k; ?>" <?php echo $selected_month == $k ? 'selected' : ''; ?>>
                                    <?php echo $v; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <?php if (empty($payment_history)): ?>
                <p style="text-align: center; color: #666; padding: 40px;">No payment history available.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Boarder Name</th>
                                <th>Room</th>
                                <th>Month/Year</th>
                                <th>Amount</th>
                                <th>Payment Method</th>
                                <th>Admin</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payment_history as $history): ?>
                                <tr>
                                    <td><?php echo date('M d, Y g:i A', strtotime($history['created_at'])); ?></td>
                                    <td style="font-weight: 500;"><?php echo htmlspecialchars($history['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($history['room_number']); ?></td>
                                    <td><?php echo ucfirst($history['month']) . ' ' . $history['year']; ?></td>
                                    <td class="amount">₱<?php echo number_format($history['amount'], 2); ?></td>
                                    <td>
                                        <?php if ($history['payment_method']): ?>
                                            <?php echo htmlspecialchars($history['payment_method']); ?>
                                        <?php else: ?>
                                            <span style="color: #999;">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($history['admin_name'] ?? 'Admin'); ?></td>
                                    <td>
                                        <button type="button" class="btn-delete-sm" onclick="openDeleteHistoryModal('<?php echo $history['id']; ?>','<?php echo htmlspecialchars($history['fullname']); ?>','<?php echo ucfirst($history['month']); ?>','<?php echo $history['year']; ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete History Modal -->
    <div id="deleteHistoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete History Record</h3>
                <span class="close" onclick="closeDeleteHistoryModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete_history">
                <input type="hidden" name="history_id" id="dh_history_id">
                <p id="dh_text" style="color:#666; margin-bottom:20px; line-height:1.5;"></p>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn" onclick="closeDeleteHistoryModal()" style="background:#6c757d; color:white;">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Record</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function openDeleteHistoryModal(id, fullname, month, year) {
            document.getElementById('dh_history_id').value = id;
            document.getElementById('dh_text').innerHTML = `Are you sure you want to delete the payment history for <strong>${fullname}</strong> for <strong>${month} ${year}</strong>?<br><br><span style="color:red; font-size: 13px;">Warning: This action cannot be undone.</span>`;
            document.getElementById('deleteHistoryModal').style.display = 'block';
        }

        function closeDeleteHistoryModal() {
            document.getElementById('deleteHistoryModal').style.display = 'none';
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });

        // Close modals on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('deleteHistoryModal');
            if (event.target == modal) {
                closeDeleteHistoryModal();
            }
        }
    
        function toggleProfileDropdown(e) {
            e.stopPropagation();
            document.getElementById('profileDropdown').classList.toggle('show');
        }

        function updateDarkModeText() {
            const isDark = document.body.classList.contains('dark-mode');
            const btnText = document.querySelector('#darkModeBtn span');
            const btnIcon = document.querySelector('#darkModeBtn i');
            if (btnText) {
                btnText.textContent = isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode';
                btnIcon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            }
        }

        function toggleDarkMode(e) {
            if(e) e.preventDefault();
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
            updateDarkModeText();
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('darkMode') === 'enabled') {
                document.body.classList.add('dark-mode');
            }
            updateDarkModeText();
            startNotificationPolling();
        });
        
        window.addEventListener('click', function(e) {
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        });

        function startNotificationPolling() {
            setInterval(() => {
                fetch('../config/config.php?ajax_check_badges=1')
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const badge = document.getElementById('badge-receipts');
                            if (badge) {
                                if (data.badges.receipts) {
                                    badge.style.display = 'flex';
                                    badge.textContent = '!';
                                } else {
                                    badge.style.display = 'none';
                                }
                            }
                        }
                    })
                    .catch(e => console.error('Error polling badges:', e));
            }, 5000);
        }

    </script>
</body>
</html>
