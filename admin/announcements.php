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

// Read flash messages (Post/Redirect/Get)
if (isset($_SESSION['flash_success'])) {
    $message = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// Pagination params
$per_page = 10;
$page = isset($_GET['page']) && ctype_digit((string)$_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'create_announcement') {
        $title = sanitize($_POST['title']);
        $content = sanitize($_POST['content']);
        $admin_id = $_SESSION['admin_id'];
        
        if (empty($title) || empty($content)) {
            $_SESSION['flash_error'] = "Please fill in all fields";
            redirect('admin/announcements.php');
        } else {
            $query = "INSERT INTO announcements (admin_id, title, content) VALUES (:admin_id, :title, :content)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':admin_id', $admin_id);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = "Announcement created successfully";
                redirect('admin/announcements.php');
                // Log action
                try {
                    $details = [
                        'target' => 'announcement',
                        'operation' => 'create',
                        'title' => $title,
                        'content' => $content
                    ];
                    logAdminAction($db, $_SESSION['admin_id'], 'announcement_create', json_encode($details));
                } catch (Throwable $e) {}
            } else {
                $_SESSION['flash_error'] = "Error creating announcement";
                redirect('admin/announcements.php');
            }
        }
    } elseif ($action == 'edit_announcement') {
        $announcement_id = $_POST['announcement_id'];
        $title = sanitize($_POST['title']);
        $content = sanitize($_POST['content']);
        
        if (empty($title) || empty($content)) {
            $_SESSION['flash_error'] = "Please fill in all fields";
            redirect('admin/announcements.php?edit=' . $announcement_id);
        } else {
            // Fetch previous values for logging
            $prev_stmt = $db->prepare("SELECT title, content FROM announcements WHERE id = :announcement_id");
            $prev_stmt->bindParam(':announcement_id', $announcement_id);
            $prev_stmt->execute();
            $before = $prev_stmt->fetch(PDO::FETCH_ASSOC);

            $query = "UPDATE announcements SET title = :title, content = :content WHERE id = :announcement_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':announcement_id', $announcement_id);
            
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = "Announcement updated successfully";
                redirect('admin/announcements.php');
                // Log action
                try {
                    $details = [
                        'target' => 'announcement',
                        'operation' => 'edit',
                        'announcement_id' => $announcement_id,
                        'before' => $before,
                        'after' => [ 'title' => $title, 'content' => $content ]
                    ];
                    logAdminAction($db, $_SESSION['admin_id'], 'announcement_edit', json_encode($details));
                } catch (Throwable $e) {}
            } else {
                $_SESSION['flash_error'] = "Error updating announcement";
                redirect('admin/announcements.php?edit=' . $announcement_id);
            }
        }
    } elseif ($action == 'delete_announcement') {
        $announcement_id = $_POST['announcement_id'];
        // Fetch details for logging
        try {
            $prev_stmt = $db->prepare("SELECT title, content FROM announcements WHERE id = :announcement_id");
            $prev_stmt->bindParam(':announcement_id', $announcement_id);
            $prev_stmt->execute();
            $before = $prev_stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) { $before = null; }
        $query = "DELETE FROM announcements WHERE id = :announcement_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':announcement_id', $announcement_id);
        
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = "Announcement deleted successfully";
            redirect('admin/announcements.php');
            // Log action
            try {
                $details = [
                    'target' => 'announcement',
                    'operation' => 'delete',
                    'announcement_id' => $announcement_id,
                    'before' => $before
                ];
                logAdminAction($db, $_SESSION['admin_id'], 'announcement_delete', json_encode($details));
            } catch (Throwable $e) {}
        } else {
            $_SESSION['flash_error'] = "Error deleting announcement";
            redirect('admin/announcements.php');
        }
    }
}

// Count total announcements for pagination
$count_sql = "SELECT COUNT(*) as total FROM announcements";
$count_stmt = $db->prepare($count_sql);
$count_stmt->execute();
$total_announcements = (int)$count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = max(1, (int)ceil($total_announcements / $per_page));
if ($page > $total_pages) { $page = $total_pages; $offset = ($page - 1) * $per_page; }

// Get paginated announcements (using LEFT JOIN to show all announcements even if admin is deleted)
$query = "SELECT a.*, ad.fullname as admin_name 
          FROM announcements a 
          LEFT JOIN admins ad ON a.admin_id = ad.id 
          ORDER BY a.created_at DESC 
          LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get announcement for editing
$edit_announcement = null;
if (isset($_GET['edit'])) {
    $announcement_id = $_GET['edit'];
    $query = "SELECT * FROM announcements WHERE id = :announcement_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':announcement_id', $announcement_id);
    $stmt->execute();
    $edit_announcement = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Ruin Boarders</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 0;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            text-align: center;
            padding: 0 20px 30px;
            border-bottom: 1px solid #e1e5e9;
            margin-bottom: 20px;
        }

        .sidebar-header h2 {
            color: #333;
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #666;
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 0 25px 25px 0;
            margin-right: 20px;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #333;
            font-size: 2rem;
            font-weight: 700;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #333;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .announcement-item {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }

        .announcement-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .announcement-title {
            font-weight: 600;
            color: #333;
            font-size: 1.2rem;
        }

        .announcement-meta {
            font-size: 0.8rem;
            color: #666;
        }

        .announcement-content {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .announcement-actions {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s ease;
            margin: auto;
            position: relative;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e1e5e9;
        }

        .close {
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .close:hover {
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-color: #dc3545;
        }

        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 8px;
            padding: 10px;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .mobile-menu-toggle {
                display: block;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .announcement-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .announcement-actions {
                flex-wrap: wrap;
            }
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
        .dark-mode .announcement-title { color: #fff !important; }
        .dark-mode .announcement-content { color: #ddd !important; }
        .dark-mode .announcement-meta { color: #aaa !important; }
        .dark-mode .announcement-header { color: #fff; }
        .dark-mode .close { color: #aaa; }
        .dark-mode .close:hover { color: #fff; }
        .dark-mode .modal-content p { color: #ddd; }
        .dark-mode .alert-success { background: rgba(40, 167, 69, 0.2); border-color: #2e7d32; color: #81c784; }
        .dark-mode .alert-danger { background: rgba(220, 53, 69, 0.2); border-color: #e53e3e; color: #fc8181; }

    </style>
    <link rel="stylesheet" href="../assets/css/neuromorphic-theme.css">
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <script src="../assets/js/neuromorphic-theme.js" defer></script>
</head>
<body>
    <?php $current_admin_page = 'announcements'; ?>
    <?php require __DIR__ . '/includes/admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Announcements</h1>
            <button class="btn btn-primary" onclick="openModal('createModal')">
                <i class="fas fa-plus"></i> New Announcement
            </button>
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

        <div class="card">
            <?php if (empty($announcements)): ?>
                <p style="color: #666; text-align: center; padding: 40px;">No announcements yet.</p>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-item">
                        <div class="announcement-header">
                            <div>
                                <div class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                                <div class="announcement-meta">
                                    By <?php echo htmlspecialchars($announcement['admin_name'] ?? 'Unknown Admin'); ?> • 
                                    <?php echo date('M d, Y g:i A', strtotime($announcement['created_at'])); ?>
                                </div>
                            </div>
                            <div class="announcement-actions">
                                <button class="btn btn-warning btn-sm" onclick="editAnnouncement(<?php echo $announcement['id']; ?>)">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>, '<?php echo htmlspecialchars($announcement['title']); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if ($total_pages > 1): ?>
                    <div style="display:flex; justify-content:center; gap:8px; margin-top: 10px;">
                        <?php 
                            $base = 'announcements.php';
                            $qs = $_GET; unset($qs['page']);
                            $base_qs = http_build_query($qs);
                            $link = function($p) use ($base, $base_qs) { 
                                $q = $base_qs ? ($base_qs . '&page=' . $p) : ('page=' . $p);
                                return $base . '?' . $q; 
                            };
                        ?>
                        <a href="<?php echo $page > 1 ? htmlspecialchars($link($page-1)) : 'javascript:void(0)'; ?>" 
                           style="padding:8px 12px; border-radius:6px; text-decoration:none; color:#fff; background: <?php echo $page > 1 ? '#667eea' : '#a0a4b8'; ?>; pointer-events: <?php echo $page > 1 ? 'auto' : 'none'; ?>;">Prev</a>
                        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                            <a href="<?php echo htmlspecialchars($link($p)); ?>" 
                               style="padding:8px 12px; border-radius:6px; text-decoration:none; color: <?php echo $p === $page ? '#fff' : '#333'; ?>; background: <?php echo $p === $page ? '#764ba2' : '#e9ecef'; ?>;">
                               <?php echo $p; ?>
                            </a>
                        <?php endfor; ?>
                        <a href="<?php echo $page < $total_pages ? htmlspecialchars($link($page+1)) : 'javascript:void(0)'; ?>" 
                           style="padding:8px 12px; border-radius:6px; text-decoration:none; color:#fff; background: <?php echo $page < $total_pages ? '#667eea' : '#a0a4b8'; ?>; pointer-events: <?php echo $page < $total_pages ? 'auto' : 'none'; ?>;">Next</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Announcement Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Announcement</h3>
                <span class="close" onclick="closeModal('createModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_announcement">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" class="form-control" rows="6" required></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal('createModal')" style="background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Announcement</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Announcement Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Announcement</h3>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit_announcement">
                <input type="hidden" name="announcement_id" id="edit_announcement_id">
                <div class="form-group">
                    <label for="edit_title">Title</label>
                    <input type="text" id="edit_title" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="edit_content">Content</label>
                    <textarea id="edit_content" name="content" class="form-control" rows="6" required></textarea>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal('editModal')" style="background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Announcement</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            <p>Are you sure you want to delete this announcement? This action cannot be undone.</p>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="delete_announcement">
                <input type="hidden" name="announcement_id" id="delete_announcement_id">
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal('deleteModal')" style="background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Announcement</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            // Remove 'edit' parameter from URL without full reload
            const url = new URL(window.location.href);
            if (url.searchParams.has('edit')) {
                url.searchParams.delete('edit');
                window.history.replaceState({}, document.title, url.pathname + url.search);
            }
        }

        function editAnnouncement(announcementId) {
            window.location.href = 'announcements.php?edit=' + announcementId;
        }

        function deleteAnnouncement(announcementId, title) {
            document.getElementById('delete_announcement_id').value = announcementId;
            document.querySelector('#deleteModal p').innerHTML = `Are you sure you want to delete "<strong>${title}</strong>"? This action cannot be undone.`;
            openModal('deleteModal');
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

        function toggleProfileDropdown(e) {
            e.stopPropagation();
            document.getElementById('profileDropdown').classList.toggle('show');
        }

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

        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('darkMode') === 'enabled') {
                document.body.classList.add('dark-mode');
            }
            updateDarkModeText();
            startNotificationPolling();

            // Auto-populate edit form if editing
            <?php if ($edit_announcement): ?>
            document.getElementById('edit_announcement_id').value = '<?php echo $edit_announcement['id']; ?>';
            document.getElementById('edit_title').value = '<?php echo addslashes(htmlspecialchars($edit_announcement['title'])); ?>';
            document.getElementById('edit_content').value = '<?php echo addslashes(htmlspecialchars($edit_announcement['content'])); ?>';
            openModal('editModal');
            <?php endif; ?>
        });

        // Global clicks
        window.onclick = function(event) {
            // Close modals when clicking outside
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target == modal) {
                    closeModal(modal.id);
                }
            });

            // Close Profile Dropdown
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (sidebar && toggle && window.innerWidth <= 768 && 
                !sidebar.contains(e.target) && 
                !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    </script>
</body>
</html>
