<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('auth/login.php');
}

$database = new Database();
$db = $database->getConnection();
ensurePaymentReceiptSchema($db);

$message = '';
$error = '';

// Handle Receipt Actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $receipt_id = isset($_POST['receipt_id']) ? (int) $_POST['receipt_id'] : 0;
    
    // Fetch receipt details first
    $q = $db->prepare("SELECT * FROM payment_receipts WHERE id = :id");
    $q->bindParam(':id', $receipt_id);
    $q->execute();
    $receipt = $q->fetch(PDO::FETCH_ASSOC);
    
    if ($receipt) {
        if ($action == 'approve') {
            $comment = trim(strip_tags($_POST['admin_comment'] ?? ''));
            $comment_value = $comment !== '' ? $comment : null;
            $db->beginTransaction();
            try {
                // 1. Update receipt status
                $stmt = $db->prepare("UPDATE payment_receipts SET status = 'approved', admin_comment = :comment WHERE id = :id");
                $stmt->bindValue(':comment', $comment_value, $comment_value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
                $stmt->bindValue(':id', $receipt_id, PDO::PARAM_INT);
                $stmt->execute();
                
                // 2. Update payments table
                $user_id = $receipt['user_id'];
                $month = $receipt['month'];
                $year = $receipt['year'];
                $amount = $receipt['amount'];
                
                // Check if payment record exists for this year
                $check_q = $db->prepare("SELECT id FROM payments WHERE user_id = :user_id AND year = :year");
                $check_q->bindParam(':user_id', $user_id);
                $check_q->bindParam(':year', $year);
                $check_q->execute();
                
                if ($check_q->rowCount() == 0) {
                    // Create new record
                    $cols = "user_id, year, $month";
                    $vals = ":user_id, :year, :amount";
                    $insert_q = $db->prepare("INSERT INTO payments ($cols) VALUES ($vals)");
                    $insert_q->bindParam(':user_id', $user_id);
                    $insert_q->bindParam(':year', $year);
                    $insert_q->bindParam(':amount', $amount);
                    $insert_q->execute();
                } else {
                    // Update existing record
                    $update_q = $db->prepare("UPDATE payments SET $month = $month + :amount WHERE user_id = :user_id AND year = :year");
                    $update_q->bindParam(':amount', $amount);
                    $update_q->bindParam(':user_id', $user_id);
                    $update_q->bindParam(':year', $year);
                    $update_q->execute();
                }
                
                $db->commit();
                logAdminAction($db, $_SESSION['admin_id'], 'Approve Receipt', "Approved receipt #$receipt_id for user #$user_id ($month $year). Comment: " . ($comment !== '' ? $comment : 'None'));
                $_SESSION['flash_success'] = "Receipt approved and payment recorded.";
            } catch (Exception $e) {
                $db->rollBack();
                $_SESSION['flash_error'] = "Error approving receipt: " . $e->getMessage();
            }
        } elseif ($action == 'reject') {
            $comment = trim(strip_tags($_POST['admin_comment'] ?? ''));
            $comment_value = $comment !== '' ? $comment : null;
            $stmt = $db->prepare("UPDATE payment_receipts SET status = 'rejected', admin_comment = :comment WHERE id = :id");
            $stmt->bindValue(':comment', $comment_value, $comment_value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':id', $receipt_id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                logAdminAction($db, $_SESSION['admin_id'], 'Reject Receipt', "Rejected receipt #$receipt_id. Comment: " . ($comment !== '' ? $comment : 'None'));
                $_SESSION['flash_success'] = "Receipt rejected.";
            } else {
                $_SESSION['flash_error'] = "Error rejecting receipt.";
            }
        } elseif ($action == 'revert') {
            $db->beginTransaction();
            try {
                // If it was approved, we need to deduct the amount from payments
                if ($receipt['status'] == 'approved') {
                    $month = $receipt['month'];
                    $amount = $receipt['amount'];
                    $user_id = $receipt['user_id'];
                    $year = $receipt['year'];
                    
                    $deduct_q = $db->prepare("UPDATE payments SET $month = GREATEST(0, $month - :amount) WHERE user_id = :user_id AND year = :year");
                    $deduct_q->bindParam(':amount', $amount);
                    $deduct_q->bindParam(':user_id', $user_id);
                    $deduct_q->bindParam(':year', $year);
                    $deduct_q->execute();
                }
                
                $stmt = $db->prepare("UPDATE payment_receipts SET status = 'pending', admin_comment = NULL WHERE id = :id");
                $stmt->bindValue(':id', $receipt_id, PDO::PARAM_INT);
                $stmt->execute();
                
                $db->commit();
                logAdminAction($db, $_SESSION['admin_id'], 'Revert Receipt', "Reverted receipt #$receipt_id to pending.");
                $_SESSION['flash_success'] = "Receipt reverted to pending.";
            } catch (Exception $e) {
                $db->rollBack();
                $_SESSION['flash_error'] = "Error reverting receipt: " . $e->getMessage();
            }
        } elseif ($action == 'delete_history' && $receipt['status'] != 'pending') {
            $stmt = $db->prepare("UPDATE payment_receipts SET admin_deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND admin_deleted_at IS NULL");
            $stmt->bindValue(':id', $receipt_id, PDO::PARAM_INT);
            if ($stmt->execute() && $stmt->rowCount() > 0) {
                logAdminAction($db, $_SESSION['admin_id'], 'Delete Receipt History', "Removed receipt #$receipt_id from admin history view.");
                $_SESSION['flash_success'] = "Receipt history entry deleted.";
            } else {
                $_SESSION['flash_error'] = "Error deleting receipt history entry.";
            }
        }
    }
    redirect('admin/receipts.php');
}

// Fetch pending receipts
$query = "SELECT pr.*, u.fullname, u.room_number 
          FROM payment_receipts pr 
          JOIN users u ON pr.user_id = u.id 
          WHERE pr.status = 'pending' 
            AND pr.admin_deleted_at IS NULL
            AND pr.user_deleted_at IS NULL
          ORDER BY pr.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$pending_receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch history
$query = "SELECT pr.*, u.fullname, u.room_number 
          FROM payment_receipts pr 
          JOIN users u ON pr.user_id = u.id 
          WHERE pr.status != 'pending' 
            AND pr.admin_deleted_at IS NULL
          ORDER BY pr.updated_at DESC LIMIT 50";
$stmt = $db->prepare($query);
$stmt->execute();
$receipt_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending count for navigation
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
    <title>Payment Receipts | Admin</title>
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

        .receipt-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; }
        .receipt-card { background: #fff; border: 1px solid #eee; border-radius: 12px; overflow: hidden; transition: transform 0.3s; }
        .receipt-card:hover { transform: translateY(-5px); box-shadow: var(--shadow); }
        .receipt-img { width: 100%; height: 200px; object-fit: cover; cursor: pointer; }
        .receipt-info { padding: 20px; }
        .receipt-user { font-weight: 700; font-size: 1.1rem; margin-bottom: 5px; color: var(--primary-color); }
        .receipt-details { font-size: 0.9rem; color: #666; margin-bottom: 15px; }
        .receipt-amount { font-size: 1.2rem; font-weight: 700; color: var(--success-color); margin-bottom: 15px; }
        .receipt-meta { display: grid; gap: 10px; margin-bottom: 15px; }
        .receipt-comment-box { background: #f8f9fa; border-radius: 10px; padding: 12px; color: #555; font-size: 0.9rem; line-height: 1.5; min-height: 72px; }
        .receipt-comment-box strong { display: block; margin-bottom: 6px; color: #333; }
        .receipt-card-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .receipt-card-actions .btn { flex: 1; justify-content: center; }

        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-size: 0.9rem; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3); }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3); }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3); }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-secondary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3); }

        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-transform: uppercase; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }

        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 15px; background: #f8f9fa; color: #666; font-weight: 600; font-size: 0.9rem; border-bottom: 2px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #eee; color: #444; font-size: 0.95rem; }
        tr:hover { background: #f0f4ff; }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(5px); }
        .modal-content { background-color: #fff; margin: 5% auto; padding: 30px; border-radius: 15px; width: 90%; max-width: 600px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); animation: slideDown 0.3s ease-out; }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-img { width: 100%; border-radius: 10px; margin-bottom: 20px; }
        .close { float: right; font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa; transition: color 0.3s; }
        .close:hover { color: #333; }
        .review-summary { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; }
        .review-summary div { background: #f8f9fa; border-radius: 10px; padding: 12px; }
        .review-summary strong { display: block; font-size: 0.8rem; color: #666; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.04em; }
        .modal-field { margin-bottom: 18px; }
        .modal-field label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .modal-field textarea { width: 100%; min-height: 110px; padding: 12px; border-radius: 10px; border: 1px solid #ddd; resize: vertical; font-family: inherit; }
        .modal-actions { display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap; }
        .history-comment { max-width: 240px; white-space: normal; word-break: break-word; line-height: 1.5; }
        .comment-modal-copy { color: #555; line-height: 1.6; white-space: pre-wrap; word-break: break-word; }
        .receipt-card .btn-secondary,
        .table-container .btn-secondary { background: #6c757d !important; color: white !important; }
        .receipt-card .btn-primary,
        .table-container .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2) !important; color: white !important; }
        .table-container .btn-danger { background: #dc3545 !important; color: white !important; }
        .table-container .btn-success { background: #28a745 !important; color: white !important; }

        .notification-badge { position: absolute; top: 8px; right: 8px; background: #dc3545; color: white; border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; }
        .mobile-menu-toggle { display: none; position: fixed; top: 20px; left: 20px; z-index: 1001; background: rgba(255, 255, 255, 0.9); border: none; border-radius: 8px; padding: 10px; cursor: pointer; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .mobile-menu-toggle { display: block; }
            .receipt-grid { grid-template-columns: 1fr; }
            .review-summary { grid-template-columns: 1fr; }
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
        .dark-mode .receipt-card { background: rgba(30, 30, 45, 0.95); border-color: #333; }
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
        .dark-mode .receipt-comment-box,
        .dark-mode .review-summary div { background: rgba(255, 255, 255, 0.05); color: #ddd; }
        .dark-mode .receipt-comment-box strong,
        .dark-mode .review-summary strong,
        .dark-mode .modal-field label { color: #fff; }
        .dark-mode .modal-field textarea { background: #1f1f2c; border-color: #444; color: #fff; }
        .dark-mode .comment-modal-copy { color: #ddd; }
        .dark-mode table tbody tr:hover { background: rgba(255,255,255,0.05) !important; }
        .dark-mode .alert-success { background: rgba(40, 167, 69, 0.2); border-color: #2e7d32; color: #81c784; }
        .dark-mode .alert-danger { background: rgba(220, 53, 69, 0.2); border-color: #e53e3e; color: #fc8181; }

    </style>
    <link rel="stylesheet" href="../assets/css/neuromorphic-theme.css">
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <script src="../assets/js/neuromorphic-theme.js" defer></script>
</head>
<body>
    <?php $current_admin_page = 'receipts'; ?>
    <?php require __DIR__ . '/includes/admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Payment Receipts</h1>
            <p>Review pending uploads and recent receipt decisions.</p>
        </div>

        <?php if (isset($_SESSION['flash_success'])): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 25px;">
                <?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_error'])): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 25px;">
                <?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
            </div>
        <?php endif; ?>

        <!-- Pending Receipts Section -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pending Approvals</h3>
                <span class="status-badge status-pending"><?php echo count($pending_receipts); ?> Pending</span>
            </div>

            <?php if (empty($pending_receipts)): ?>
                <p style="text-align: center; color: #666; padding: 20px;">No pending receipts to review.</p>
            <?php else: ?>
                <div class="receipt-grid">
                    <?php foreach ($pending_receipts as $receipt): ?>
                        <div class="receipt-card">
                            <img src="<?php echo BASE_URL; ?>uploads/receipts/<?php echo htmlspecialchars($receipt['receipt_image']); ?>" 
                                 alt="Receipt" class="receipt-img" 
                                 onclick="viewImage('<?php echo BASE_URL; ?>uploads/receipts/<?php echo htmlspecialchars($receipt['receipt_image']); ?>')">
                            <div class="receipt-info">
                                <div class="receipt-user"><?php echo htmlspecialchars($receipt['fullname']); ?></div>
                                <div class="receipt-meta">
                                    <div class="receipt-details">Room <?php echo htmlspecialchars($receipt['room_number']); ?> • <?php echo ucfirst($receipt['month']); ?> <?php echo $receipt['year']; ?></div>
                                    <div class="receipt-amount">₱<?php echo number_format($receipt['amount'], 2); ?></div>
                                </div>
                                <div class="receipt-card-actions">
                                    <button type="button" class="btn btn-secondary"
                                        onclick='viewImage(<?php echo json_encode(BASE_URL . "uploads/receipts/" . rawurlencode($receipt["receipt_image"])); ?>)'>
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button type="button" class="btn btn-secondary"
                                        title="Boarder Comment"
                                        onclick='openCommentModal(
                                            <?php echo json_encode("Boarder Comment - " . $receipt["fullname"]); ?>,
                                            <?php echo json_encode($receipt["user_comment"] ?? "No comment provided."); ?>
                                        )'
                                        <?php echo empty($receipt['user_comment']) ? 'disabled style="opacity: 0.6; cursor: not-allowed;"' : ''; ?>>
                                        <i class="fas fa-comment"></i> Comment
                                    </button>
                                    <button type="button" class="btn btn-primary"
                                        onclick='openReviewModal(
                                            <?php echo (int) $receipt['id']; ?>,
                                            <?php echo json_encode($receipt['fullname']); ?>,
                                            <?php echo json_encode('Room ' . $receipt['room_number']); ?>,
                                            <?php echo json_encode(ucfirst($receipt['month']) . ' ' . $receipt['year']); ?>,
                                            <?php echo json_encode('₱' . number_format($receipt['amount'], 2)); ?>,
                                            <?php echo json_encode($receipt['user_comment'] ?? ''); ?>,
                                            <?php echo json_encode(BASE_URL . "uploads/receipts/" . rawurlencode($receipt["receipt_image"])); ?>
                                        )'>
                                        <i class="fas fa-comment-dots"></i> Review
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Receipt History Section -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent History</h3>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Room</th>
                            <th>Period</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($receipt_history)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #666;">No recent receipt history found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($receipt_history as $row): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($row['room_number']); ?></td>
                                    <td><?php echo ucfirst($row['month']) . ' ' . $row['year']; ?></td>
                                    <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                                    <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo $row['status']; ?></span></td>
                                    <td>
                                        <button onclick='viewImage(<?php echo json_encode(BASE_URL . "uploads/receipts/" . rawurlencode($row["receipt_image"])); ?>)' class="btn btn-secondary" style="padding: 5px 10px;"><i class="fas fa-eye"></i></button>
                                        <button type="button" class="btn btn-secondary"
                                            title="Boarder Comment"
                                            onclick='openCommentModal(
                                                <?php echo json_encode("Boarder Comment - " . $row["fullname"]); ?>,
                                                <?php echo json_encode($row["user_comment"] ?? "No comment provided."); ?>
                                            )'
                                            <?php echo empty($row['user_comment']) ? 'disabled style="padding: 5px 10px; opacity: 0.6; cursor: not-allowed;"' : 'style="padding: 5px 10px;"'; ?>>
                                            <i class="fas fa-user-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-secondary"
                                            title="Admin Comment"
                                            onclick='openCommentModal(
                                                <?php echo json_encode("Admin Comment - " . $row["fullname"]); ?>,
                                                <?php echo json_encode($row["admin_comment"] ?? "No comment provided."); ?>
                                            )'
                                            <?php echo empty($row['admin_comment']) ? 'disabled style="padding: 5px 10px; opacity: 0.6; cursor: not-allowed;"' : 'style="padding: 5px 10px;"'; ?>>
                                            <i class="fas fa-comment-dots"></i>
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Change status back to pending?')">
                                            <input type="hidden" name="action" value="revert">
                                            <input type="hidden" name="receipt_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="btn btn-primary" style="padding: 5px 10px;"><i class="fas fa-undo"></i></button>
                                        </form>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this receipt history entry from admin view?')">
                                            <input type="hidden" name="action" value="delete_history">
                                            <input type="hidden" name="receipt_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px;"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div id="imageModal" class="modal" onclick="closeImageModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <span class="close" onclick="closeImageModal()">&times;</span>
            <img id="modalImg" class="modal-img">
        </div>
    </div>

    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeReviewModal()">&times;</span>
            <h3 style="margin-bottom: 20px;">Review Receipt</h3>
            <div class="review-summary">
                <div>
                    <strong>Boarder</strong>
                    <span id="reviewUser"></span>
                </div>
                <div>
                    <strong>Room</strong>
                    <span id="reviewRoom"></span>
                </div>
                <div>
                    <strong>Period</strong>
                    <span id="reviewPeriod"></span>
                </div>
                <div>
                    <strong>Amount</strong>
                    <span id="reviewAmount"></span>
                </div>
            </div>
            <img id="reviewImage" class="modal-img" alt="Receipt preview">
            <div class="receipt-comment-box" style="margin-bottom: 20px;">
                <strong>Boarder Comment</strong>
                <div id="reviewUserComment">No comment provided.</div>
            </div>
            <form method="POST">
                <input type="hidden" name="action" id="reviewAction" value="approve">
                <input type="hidden" name="receipt_id" id="reviewReceiptId">
                <div class="modal-field">
                    <label for="reviewAdminComment">Admin Comment (Optional)</label>
                    <textarea name="admin_comment" id="reviewAdminComment" placeholder="Add a note for the boarder or your records"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeReviewModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-danger" onclick="setReviewAction('reject')">Reject</button>
                    <button type="submit" class="btn btn-success" onclick="setReviewAction('approve')">Approve</button>
                </div>
            </form>
        </div>
    </div>

    <div id="commentModal" class="modal">
        <div class="modal-content" style="max-width: 480px;">
            <span class="close" onclick="closeCommentModal()">&times;</span>
            <h3 id="commentModalTitle" style="margin-bottom: 16px;">Comment</h3>
            <div id="commentModalBody" class="comment-modal-copy">No comment provided.</div>
        </div>
    </div>

    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); }
        function viewImage(src) {
            document.getElementById('modalImg').src = src;
            document.getElementById('imageModal').style.display = 'block';
        }
        function closeImageModal() { document.getElementById('imageModal').style.display = 'none'; }
        function openReviewModal(id, user, room, period, amount, userComment, imageSrc) {
            document.getElementById('reviewReceiptId').value = id;
            document.getElementById('reviewUser').textContent = user;
            document.getElementById('reviewRoom').textContent = room;
            document.getElementById('reviewPeriod').textContent = period;
            document.getElementById('reviewAmount').textContent = amount;
            document.getElementById('reviewUserComment').textContent = userComment && userComment.trim() !== '' ? userComment : 'No comment provided.';
            document.getElementById('reviewImage').src = imageSrc;
            document.getElementById('reviewAdminComment').value = '';
            document.getElementById('reviewAction').value = 'approve';
            document.getElementById('reviewModal').style.display = 'block';
        }
        function closeReviewModal() { document.getElementById('reviewModal').style.display = 'none'; }
        function setReviewAction(action) { document.getElementById('reviewAction').value = action; }
        function openCommentModal(title, comment) {
            document.getElementById('commentModalTitle').textContent = title;
            document.getElementById('commentModalBody').textContent = comment && comment.trim() !== '' ? comment : 'No comment provided.';
            document.getElementById('commentModal').style.display = 'block';
        }
        function closeCommentModal() { document.getElementById('commentModal').style.display = 'none'; }

        // Window click handler for closing modals
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
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
