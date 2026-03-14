<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

// Handle Log Clearing (optional feature for admins)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'clear_logs') {
    $stmt = $db->prepare("DELETE FROM admin_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    if ($stmt->execute()) {
        $_SESSION['flash_success'] = "Logs older than 30 days have been cleared.";
    } else {
        $_SESSION['flash_error'] = "Error clearing logs.";
    }
    redirect('admin/logs.php');
}

// Fetch logs with admin details
// Note: admin_id might refer to admins table. Let's try to join with admins first.
$query = "SELECT l.*, a.fullname as admin_name 
          FROM admin_logs l 
          LEFT JOIN admins a ON l.admin_id = a.id 
          ORDER BY l.created_at DESC LIMIT 100";
$stmt = $db->prepare($query);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending count for sidebar badge
$q = $db->prepare("SELECT COUNT(*) as count FROM payment_receipts WHERE status = 'pending'");
$q->execute();
$pending_receipts_count = (int)$q->fetch(PDO::FETCH_ASSOC)['count'];
$last_seen = isset($_SESSION['pending_receipts_seen_count']) ? (int)$_SESSION['pending_receipts_seen_count'] : 0;
$show_pending_badge = $pending_receipts_count > $last_seen;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Logs | Ruin Boarders</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        
        .sidebar { position: fixed; left: 0; top: 0; width: 250px; height: 100vh; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); padding: 20px 0; z-index: 1000; transition: transform 0.3s ease; }
        .sidebar-header { text-align: center; padding: 0 20px 30px; border-bottom: 1px solid #e1e5e9; margin-bottom: 20px; }
        .sidebar-header h2 { color: #333; font-size: 1.5rem; font-weight: 700; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .sidebar-header p { color: #666; font-size: 0.9rem; margin-top: 5px; }
        .sidebar-menu { list-style: none; }
        .sidebar-menu li { margin-bottom: 5px; position: relative; }
        .sidebar-menu a { display: flex; align-items: center; padding: 15px 20px; color: #666; text-decoration: none; transition: all 0.3s ease; border-radius: 0 25px 25px 0; margin-right: 20px; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: linear-gradient(135deg, #667eea, #764ba2); color: white; transform: translateX(5px); }
        .sidebar-menu i { margin-right: 10px; width: 20px; }

        .main-content { margin-left: 250px; padding: 30px; min-height: 100vh; }
        .header { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 15px; padding: 20px 30px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #333; font-size: 2rem; font-weight: 700; }

        .card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 15px; padding: 25px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); margin-bottom: 30px; }
        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e1e5e9; }
        .card-title { font-size: 1.3rem; font-weight: 600; color: #333; }

        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3); }

        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 15px; background: #f8f9fa; color: #666; font-weight: 600; font-size: 0.9rem; border-bottom: 2px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #eee; color: #444; font-size: 0.95rem; }
        tr:hover { background: #f0f4ff; }

        .log-details { font-family: monospace; font-size: 0.85rem; color: #666; max-width: 400px; white-space: pre-wrap; word-break: break-all; }
        .log-details-preview { font-size: 0.9rem; color: #666; }
        .log-date { font-size: 0.85rem; color: #888; white-space: nowrap; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(5px); }
        .modal-content { background-color: #fff; margin: 5% auto; padding: 30px; border-radius: 15px; width: 90%; max-width: 600px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .modal-header h3 { font-size: 1.5rem; color: #333; }
        .close { font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa; transition: color 0.3s; }
        .close:hover { color: #333; }

        .notification-badge { position: absolute; top: 8px; right: 8px; background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; }
        .mobile-menu-toggle { display: none; position: fixed; top: 20px; left: 20px; z-index: 1001; background: rgba(255, 255, 255, 0.9); border: none; border-radius: 8px; padding: 10px; cursor: pointer; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-menu-toggle { display: block; }
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
        .dark-mode table tbody tr:hover { background: rgba(255,255,255,0.05) !important; }
        .dark-mode .log-details-preview { color: #bbb; }
        .dark-mode .log-date { color: #aaa; }
        .dark-mode .alert-success { background: rgba(40, 167, 69, 0.2); border-color: #2e7d32; color: #81c784; }
        .dark-mode .alert-danger { background: rgba(220, 53, 69, 0.2); border-color: #e53e3e; color: #fc8181; }

    </style>
    <link rel="stylesheet" href="../assets/css/neuromorphic-theme.css">
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <script src="../assets/js/neuromorphic-theme.js" defer></script>
</head>
<body>
    <?php $current_admin_page = 'logs'; ?>
    <?php require __DIR__ . '/includes/admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <div>
                <h1>Admin Logs</h1>
                <p style="color: #666; margin-top: 8px;">Review recent admin actions and audit entries.</p>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="clear_logs">
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Clear Old Logs</button>
            </form>
        </div>

        <?php if (isset($_SESSION['flash_success'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 25px;">
                <?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Activities</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="4" style="text-align: center; padding: 40px;">No logs found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="log-date"><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></td>
                                    <td><strong><?php echo htmlspecialchars($log['admin_name'] ?: 'Unknown (ID: ' . $log['admin_id'] . ')'); ?></strong></td>
                                    <td><span style="font-weight: 600; color: #667eea;"><?php echo htmlspecialchars($log['action']); ?></span></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span class="log-details-preview"><?php 
                                                $details = htmlspecialchars($log['details']);
                                                echo strlen($details) > 50 ? substr($details, 0, 50) . '...' : $details;
                                            ?></span>
                                            <button onclick="viewLogDetails(<?php echo htmlspecialchars(json_encode($log)); ?>)" class="btn btn-primary" style="padding: 5px 12px; font-size: 0.8rem;">
                                                View full details
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Log Details Modal -->
    <div id="logDetailsModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3>Log Details</h3>
                <span class="close" onclick="closeLogModal()">&times;</span>
            </div>
            <div id="logModalContent" style="padding: 20px 0;">
                <!-- Content will be injected here -->
            </div>
            <div style="margin-top: 25px; text-align: right;">
                <button class="btn btn-primary" onclick="closeLogModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function viewLogDetails(log) {
            const modal = document.getElementById('logDetailsModal');
            const content = document.getElementById('logModalContent');
            
            const timestamp = new Date(log.created_at).toLocaleString('en-US', {
                month: 'long',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            content.innerHTML = `
                <div style="display: grid; gap: 15px;">
                    <div>
                        <strong style="color: #667eea;">Timestamp:</strong>
                        <div style="margin-top: 5px; color: #666;">${timestamp}</div>
                    </div>
                    <div>
                        <strong style="color: #667eea;">Admin:</strong>
                        <div style="margin-top: 5px; color: #666;">${log.admin_name || 'Unknown (ID: ' + log.admin_id + ')'}</div>
                    </div>
                    <div>
                        <strong style="color: #667eea;">Action:</strong>
                        <div style="margin-top: 5px; color: #666;">${log.action}</div>
                    </div>
                    <div>
                        <strong style="color: #667eea;">Details:</strong>
                        <div style="margin-top: 5px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-family: monospace; font-size: 0.9rem; color: #333; white-space: pre-wrap; word-break: break-word;">${log.details}</div>
                    </div>
                </div>
            `;
            
            modal.style.display = 'block';
        }

        function closeLogModal() {
            document.getElementById('logDetailsModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('logDetailsModal');
            if (event.target == modal) {
                closeLogModal();
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
