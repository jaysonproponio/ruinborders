    <?php
    require_once '../config/config.php';

    if (!isLoggedIn() || isAdmin()) {
        redirect('auth/login.php');
    }

    $database = new Database();
    $db = $database->getConnection();
    ensurePaymentReceiptSchema($db);

    $user_id = $_SESSION['user_id'];
    $message = '';
    $error = '';

// Get user info for avatar and sidebar
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Mark receipts as seen
try {
    $stmt = $db->prepare("UPDATE users SET seen_receipts_at = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    // Refresh user data
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Initial badge markers for sidebar
$stmt = $db->prepare("SELECT MAX(updated_at) FROM announcements");
$stmt->execute();
$last_ann = $stmt->fetchColumn();
$has_new_announcements = ($last_ann && (!$user['seen_announcements_at'] || strtotime($last_ann) > strtotime($user['seen_announcements_at'])));

$stmt = $db->prepare("SELECT MAX(updated_at) FROM payment_receipts WHERE user_id = :id AND status != 'pending'");
$stmt->execute([':id' => $user_id]);
$last_rec = $stmt->fetchColumn();
$has_new_receipts = false; // Visiting this page clears it, so force false for current view

$stmt = $db->prepare("SELECT MAX(created_at) FROM payment_history WHERE user_id = :id");
$stmt->execute([':id' => $user_id]);
$last_pay = $stmt->fetchColumn();
$has_new_payments = ($last_pay && (!$user['seen_payments_at'] || strtotime($last_pay) > strtotime($user['seen_payments_at'])));

    // Handle receipt upload
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action == 'upload_receipt') {
            $month = sanitize($_POST['month']);
            $year = sanitize($_POST['year']);
            $amount = $_POST['amount'];
            $user_comment = trim(strip_tags($_POST['user_comment'] ?? ''));
            
            if (empty($month) || empty($year) || empty($amount)) {
                $error = "Please fill in all fields";
            } elseif (!isset($_FILES['receipt_image']) || $_FILES['receipt_image']['error'] != 0) {
                $error = "Please select a receipt image";
            } else {
                $file = $_FILES['receipt_image'];
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $file_size = $file['size'];
                    if ($file_size <= 10 * 1024 * 1024) { // 10MB limit
                        $new_filename = 'receipt_' . $user_id . '_' . $month . '_' . $year . '_' . time() . '.' . $file_extension;
                        $upload_path = RECEIPT_PATH . $new_filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            $query = "INSERT INTO payment_receipts (user_id, month, year, receipt_image, amount, status, user_comment) 
                                    VALUES (:user_id, :month, :year, :receipt_image, :amount, 'pending', :user_comment)";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':user_id', $user_id);
                            $stmt->bindParam(':month', $month);
                            $stmt->bindParam(':year', $year);
                            $stmt->bindParam(':receipt_image', $new_filename);
                            $stmt->bindParam(':amount', $amount);
                            $stmt->bindParam(':user_comment', $user_comment);
                            
                            if ($stmt->execute()) {
                                notifyAdminsOfReceiptUpload($db, $user, [
                                    'month' => $month,
                                    'year' => $year,
                                    'amount' => $amount,
                                    'receipt_image' => $new_filename,
                                    'user_comment' => $user_comment,
                                    'created_at' => date('Y-m-d H:i:s'),
                                ]);

                                $message = "Receipt uploaded successfully. Waiting for admin approval.";
                            } else {
                                $error = "Error saving receipt information";
                            }
                        } else {
                            $error = "Error uploading file";
                        }
                    } else {
                        $error = "File size must be less than 10MB";
                    }
                } else {
                    $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed";
                }
            }
        } elseif ($action == 'cancel_receipt') {
            $receipt_id = isset($_POST['receipt_id']) ? (int) $_POST['receipt_id'] : 0;

            $stmt = $db->prepare("SELECT id, receipt_image FROM payment_receipts WHERE id = :id AND user_id = :user_id AND status = 'pending' LIMIT 1");
            $stmt->execute([
                ':id' => $receipt_id,
                ':user_id' => $user_id,
            ]);
            $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($receipt) {
                $stmt = $db->prepare("DELETE FROM payment_receipts WHERE id = :id AND user_id = :user_id AND status = 'pending'");
                $stmt->execute([
                    ':id' => $receipt_id,
                    ':user_id' => $user_id,
                ]);

                if ($stmt->rowCount() > 0) {
                    $receipt_path = RECEIPT_PATH . $receipt['receipt_image'];
                    if (is_file($receipt_path)) {
                        @unlink($receipt_path);
                    }
                    $message = "Pending receipt cancelled successfully.";
                } else {
                    $error = "Unable to cancel that receipt.";
                }
            } else {
                $error = "Pending receipt not found.";
            }
        } elseif ($action == 'delete_history') {
            $receipt_id = isset($_POST['receipt_id']) ? (int) $_POST['receipt_id'] : 0;

            $stmt = $db->prepare("UPDATE payment_receipts SET user_deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND user_id = :user_id AND status IN ('approved', 'rejected') AND user_deleted_at IS NULL");
            $stmt->execute([
                ':id' => $receipt_id,
                ':user_id' => $user_id,
            ]);

            if ($stmt->rowCount() > 0) {
                $message = "Receipt history entry deleted from your view.";
            } else {
                $error = "Unable to delete that receipt history entry.";
            }
        }
    }



    // Get active (pending) receipts
    $query = "SELECT * FROM payment_receipts WHERE user_id = :user_id AND status = 'pending' AND user_deleted_at IS NULL ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $active_receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get receipt history (approved/rejected)
    $query = "SELECT * FROM payment_receipts WHERE user_id = :user_id AND status IN ('approved', 'rejected') AND user_deleted_at IS NULL ORDER BY updated_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $receipt_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <title>Payment Receipts - Ruin Boarders</title>
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

            .user-info {
                text-align: center;
                margin-bottom: 20px;
            }

            .user-avatar {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: linear-gradient(135deg, #667eea, #764ba2);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: 600;
                font-size: 24px;
                margin: 0 auto 10px;
            }

            .user-name {
                font-weight: 600;
                color: #333;
                margin-bottom: 5px;
            }

            .user-room {
                color: #666;
                font-size: 0.9rem;
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

        .sidebar-menu li {
            position: relative;
        }

        .notification-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
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
            }

            .header h1 {
                color: #333;
                font-size: 2rem;
                font-weight: 700;
                margin-bottom: 10px;
            }

            .content-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 30px;
            }

            .content-grid > .card {
                min-width: 0;
            }

            .card-span-2 {
                grid-column: 1 / -1;
            }

            .card {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 15px;
                padding: 25px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            }

            .card-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 1px solid #e1e5e9;
            }

            .card-title {
                font-size: 1.3rem;
                font-weight: 600;
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

            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }

            .file-upload-area {
                border: 2px dashed #e1e5e9;
                border-radius: 8px;
                padding: 30px;
                text-align: center;
                transition: all 0.3s ease;
                cursor: pointer;
            }

            .file-upload-area:hover {
                border-color: #667eea;
                background: #f8f9ff;
            }

            .file-upload-area.dragover {
                border-color: #667eea;
                background: #f0f4ff;
            }

            .file-upload-icon {
                font-size: 48px;
                color: #667eea;
                margin-bottom: 15px;
            }

            .file-upload-text {
                color: #666;
                margin-bottom: 10px;
            }

            .file-upload-hint {
                font-size: 0.8rem;
                color: #999;
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

            .btn-secondary {
                background: #6c757d;
                color: white;
            }

            .btn-secondary:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
            }

            .btn-warning {
                background: #f0ad4e;
                color: white;
            }

            .btn-warning:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(240, 173, 78, 0.3);
            }

            .btn-danger {
                background: #dc3545;
                color: white;
            }

            .btn-danger:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
            }

            .table-container {
                overflow-x: auto;
                border-radius: 12px;
            }

            .receipts-table {
                width: 100%;
                border-collapse: collapse;
                min-width: 780px;
            }

            .receipts-table.compact-table {
                min-width: 620px;
            }

            .receipts-table th,
            .receipts-table td {
                padding: 14px 12px;
                text-align: left;
                border-bottom: 1px solid #e9ecef;
                vertical-align: top;
            }

            .receipts-table th {
                background: #f8f9fa;
                color: #555;
                font-size: 0.85rem;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }

            .receipts-table tbody tr:hover {
                background: #f7f9ff;
            }

            .status-chip {
                display: inline-flex;
                align-items: center;
                padding: 4px 10px;
                border-radius: 999px;
                font-size: 0.8rem;
                font-weight: 600;
                text-transform: capitalize;
            }

            .status-chip.pending {
                background: #fff3cd;
                color: #856404;
            }

            .status-chip.approved {
                background: #d4edda;
                color: #155724;
            }

            .status-chip.rejected {
                background: #f8d7da;
                color: #721c24;
            }

            .comment-cell {
                max-width: 220px;
                color: #666;
                line-height: 1.5;
                white-space: normal;
                word-break: break-word;
            }

            .action-cell {
                white-space: nowrap;
            }

            .action-stack {
                display: inline-flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .receipts-table .btn-secondary {
                background: #6c757d !important;
                color: white !important;
            }

            .receipts-table .btn-warning {
                background: #f0ad4e !important;
                color: white !important;
            }

            .receipts-table .btn-danger {
                background: #dc3545 !important;
                color: white !important;
            }

            .empty-state {
                color: #666;
                text-align: center;
                padding: 20px 0;
            }

            .comment-modal-card {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: min(480px, 92vw);
                background: #fff;
                border-radius: 16px;
                padding: 24px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            }

            .comment-modal-card h3 {
                margin-bottom: 12px;
                color: #333;
            }

            .comment-modal-card p {
                color: #666;
                line-height: 1.6;
                white-space: pre-wrap;
                word-break: break-word;
            }

            .receipt-item {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 20px;
                margin-bottom: 15px;
                border-left: 4px solid #667eea;
                transition: all 0.3s ease;
            }

            .receipt-item:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            }

            .receipt-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
            }

            .receipt-month {
                font-weight: 600;
                color: #333;
                font-size: 1.1rem;
            }

            .receipt-status {
                padding: 4px 12px;
                border-radius: 12px;
                font-size: 0.8rem;
                font-weight: 500;
            }

            .receipt-status.pending {
                background: #fff3cd;
                color: #856404;
            }

            .receipt-status.approved {
                background: #d4edda;
                color: #155724;
            }

            .receipt-status.rejected {
                background: #f8d7da;
                color: #721c24;
            }

            .receipt-details {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 15px;
                margin-bottom: 15px;
            }

            .receipt-amount {
                font-size: 1.2rem;
                font-weight: 700;
                color: #333;
            }

            .receipt-date {
                color: #666;
                font-size: 0.9rem;
            }

            .receipt-image {
                width: 100%;
                max-width: 200px;
                height: 150px;
                object-fit: cover;
                border-radius: 8px;
                cursor: pointer;
                transition: transform 0.3s ease;
            }

            .receipt-image:hover {
                transform: scale(1.05);
            }

            .receipt-comment {
                background: #e9ecef;
                padding: 10px;
                border-radius: 6px;
                margin-top: 10px;
                font-size: 0.9rem;
                color: #666;
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

            .modal {
                display: none;
                position: fixed;
                z-index: 2000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                backdrop-filter: blur(5px);
            }

            .modal-content {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                max-width: 90%;
                max-height: 90%;
            }

            .modal-image {
                max-width: 100%;
                max-height: 80vh;
                border-radius: 8px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            }

            .close {
                position: absolute;
                top: 15px;
                right: 25px;
                color: white;
                font-size: 35px;
                font-weight: bold;
                cursor: pointer;
            }

            .close:hover {
                color: #ccc;
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

                .content-grid {
                    grid-template-columns: 1fr;
                }

                .card-span-2 {
                    grid-column: auto;
                }

                .form-row {
                    grid-template-columns: 1fr;
                }

                .receipt-details {
                    grid-template-columns: 1fr;
                }
            }
        
        /* Profile & Dark Mode Styles */
        .sidebar { padding-bottom: 90px; }
        .sidebar-profile { position: absolute; bottom: 0; left: 0; width: 100%; padding: 20px; background: rgba(255, 255, 255, 0.95); border-top: 1px solid #e1e5e9; transition: all 0.3s ease; }
        .profile-info { display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 10px; border-radius: 10px; transition: background 0.2s; }
        .profile-info:hover { background: rgba(0,0,0,0.05); }
        .profile-icon { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 1.2rem; overflow: hidden; }
        .profile-icon img { width: 100%; height: 100%; object-fit: cover; }
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
        .dark-mode .user-info .user-name { color: #fff; }
        .dark-mode .user-info .user-room { color: #aaa; }
        .dark-mode .card, .dark-mode .header, .dark-mode .stats-grid > div, .dark-mode .stat-card, .dark-mode .filters, .dark-mode .table-container, .dark-mode .announcement-item { background: rgba(30, 30, 45, 0.95); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3); color: #fff; border: 1px solid #333; }
        .dark-mode .card-header { border-bottom-color: #333; }
        .dark-mode .card-title, .dark-mode .stat-number, .dark-mode th, .dark-mode td { color: #fff; }
        .dark-mode .stat-label, .dark-mode .announcement-date, .dark-mode .announcement-meta { color: #aaa; }
        .dark-mode .announcement-title { color: #fff; }
        .dark-mode .announcement-content { color: #ccc; }
        .dark-mode .form-group label { color: #ddd; }
        .dark-mode .form-control { background: #2a2a35; border-color: #444; color: #fff; }
        .dark-mode table th, .dark-mode table td { border-bottom-color: #444; }
        .dark-mode table { border-color: #444; }
        .dark-mode table th { background: #2a2a35; }
        .dark-mode .profile-name { color: #fff; }
        .dark-mode .profile-role { color: #aaa; }
        .dark-mode .profile-dropdown { background: #2a2a35; border: 1px solid #444; }
        .dark-mode .profile-dropdown a { color: #ddd; }
        .dark-mode .profile-dropdown a.logout-text { color: #ff6b6b; border-top-color: #444; }
        .dark-mode .profile-dropdown a:hover { background: #3a3a45; color: #a8c0ff; }
        .dark-mode .profile-dropdown a.logout-text:hover { background: rgba(220, 53, 69, 0.2); }
        .dark-mode .modal-content { background: #2a2a35; color: #fff; border-color: #444; }
        .dark-mode .comment-modal-card { background: #2a2a35; color: #fff; border: 1px solid #444; }
        .dark-mode .comment-modal-card h3 { color: #fff; }
        .dark-mode .comment-modal-card p { color: #ddd; }
        .dark-mode .modal-header { border-bottom-color: #444; }
        .dark-mode .modal-header h3 { color: #fff; }
        .dark-mode .close { color: #aaa; }
        .dark-mode .close:hover { color: #fff; }
        .dark-mode .payment-month { background: rgba(40, 40, 55, 0.95); border-color: #444; }
        .dark-mode .payment-month.paid { background: rgba(46, 125, 50, 0.2); border-color: #2e7d32; }
        .dark-mode .payment-month.pending { background: rgba(255, 193, 7, 0.15); border-color: #ffc107; }
        .dark-mode .month-name { color: #fff; }
        .dark-mode .month-amount { color: #ccc; }
        .dark-mode .alert-success { background: rgba(40, 167, 69, 0.2); border-color: #2e7d32; color: #81c784; }
        .dark-mode .alert-danger { background: rgba(220, 53, 69, 0.2); border-color: #e53e3e; color: #fc8181; }
    </style>
        <link rel="stylesheet" href="../assets/css/neuromorphic-theme.css">
        <link rel="stylesheet" href="../assets/css/user-sidebar.css">
        <script src="../assets/js/neuromorphic-theme.js" defer></script>
    </head>
    <body>
        <?php $current_user_page = 'receipts'; ?>
        <?php require __DIR__ . '/includes/user-sidebar.php'; ?>

        <div class="main-content">
            <div class="header">
                <h1>Payment Receipts</h1>
                <p>Upload payment receipts for admin verification</p>
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

            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Upload New Receipt</h3>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_receipt">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="month">Month</label>
                                <select id="month" name="month" class="form-control" required>
                                    <option value="">Select Month</option>
                                    <?php foreach ($months as $key => $name): ?>
                                        <option value="<?php echo $key; ?>"><?php echo $name; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="year">Year</label>
                                <select id="year" name="year" class="form-control" required>
                                    <option value="">Select Year</option>
                                    <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $y == date('Y') ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="amount">Amount Paid</label>
                            <input type="number" id="amount" name="amount" class="form-control" 
                                placeholder="Enter amount" step="0.01" min="0" required>
                        </div>

                        <div class="form-group">
                            <label for="user_comment">Comment (Optional)</label>
                            <textarea id="user_comment" name="user_comment" class="form-control" rows="4"
                                placeholder="Add any note for the admin about this receipt"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Receipt Image</label>
                            <div class="file-upload-area" onclick="document.getElementById('receipt_image').click()">
                                <div class="file-upload-icon">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                </div>
                                <div class="file-upload-text">Click to upload or drag and drop</div>
                                <div class="file-upload-hint">JPG, PNG, GIF up to 10MB</div>
                                <input type="file" id="receipt_image" name="receipt_image" accept="image/*" class="hidden" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-upload"></i> Upload Receipt
                        </button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Pending Receipts</h3>
                    </div>
                    
                    <?php if (empty($active_receipts)): ?>
                        <p class="empty-state">No pending receipts.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="receipts-table compact-table">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th>Amount</th>
                                        <th>Uploaded</th>
                                        <th>Your Comment</th>
                                        <th>Receipt</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_receipts as $receipt): ?>
                                        <tr>
                                            <td><?php echo $months[$receipt['month']]; ?> <?php echo $receipt['year']; ?></td>
                                            <td>₱<?php echo number_format($receipt['amount'], 2); ?></td>
                                            <td><?php echo date('M d, Y g:i A', strtotime($receipt['created_at'])); ?></td>
                                            <td class="comment-cell">
                                                <?php echo !empty($receipt['user_comment']) ? nl2br(htmlspecialchars($receipt['user_comment'])) : '<span style="color:#999;">No comment</span>'; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-secondary" style="padding: 8px 12px;"
                                                    onclick="openModal('../uploads/receipts/<?php echo htmlspecialchars($receipt['receipt_image']); ?>')">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                            <td><span class="status-chip pending">Pending</span></td>
                                            <td class="action-cell">
                                                <form method="POST" onsubmit="return confirm('Cancel this pending receipt?');">
                                                    <input type="hidden" name="action" value="cancel_receipt">
                                                    <input type="hidden" name="receipt_id" value="<?php echo $receipt['id']; ?>">
                                                    <button type="submit" class="btn btn-warning" style="padding: 8px 12px;">
                                                        <i class="fas fa-ban"></i> Cancel
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card card-span-2">
                    <div class="card-header">
                        <h3 class="card-title">Receipt History</h3>
                    </div>
                    
                    <?php if (empty($receipt_history)): ?>
                        <p class="empty-state">No receipt history found.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="receipts-table">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th>Amount</th>
                                        <th>Uploaded</th>
                                        <th>Processed</th>
                                        <th>Your Comment</th>
                                        <th>Receipt</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($receipt_history as $receipt): ?>
                                        <tr>
                                            <td><?php echo $months[$receipt['month']]; ?> <?php echo $receipt['year']; ?></td>
                                            <td>₱<?php echo number_format($receipt['amount'], 2); ?></td>
                                            <td><?php echo date('M d, Y g:i A', strtotime($receipt['created_at'])); ?></td>
                                            <td><?php echo date('M d, Y g:i A', strtotime($receipt['updated_at'])); ?></td>
                                            <td class="comment-cell">
                                                <?php echo !empty($receipt['user_comment']) ? nl2br(htmlspecialchars($receipt['user_comment'])) : '<span style="color:#999;">No comment</span>'; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-secondary" style="padding: 8px 12px;"
                                                    onclick="openModal('../uploads/receipts/<?php echo htmlspecialchars($receipt['receipt_image']); ?>')">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            </td>
                                            <td>
                                                <span class="status-chip <?php echo htmlspecialchars($receipt['status']); ?>">
                                                    <?php echo ucfirst($receipt['status']); ?>
                                                </span>
                                            </td>
                                            <td class="action-cell">
                                                <div class="action-stack">
                                                    <button type="button" class="btn btn-primary"
                                                        onclick='openCommentModal(
                                                            <?php echo json_encode("Admin Comment"); ?>,
                                                            <?php echo json_encode($receipt["admin_comment"] ?? "No admin comment provided."); ?>
                                                        )'
                                                        <?php echo empty($receipt['admin_comment']) ? 'disabled style="padding: 8px 12px; opacity: 0.6; cursor: not-allowed;"' : 'style="padding: 8px 12px;"'; ?>>
                                                        <i class="fas fa-comment"></i> Show Comment
                                                    </button>
                                                    <form method="POST" onsubmit="return confirm('Delete this receipt history entry from your view?');">
                                                        <input type="hidden" name="action" value="delete_history">
                                                        <input type="hidden" name="receipt_id" value="<?php echo $receipt['id']; ?>">
                                                        <button type="submit" class="btn btn-danger" style="padding: 8px 12px;">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Image Modal -->
        <div id="imageModal" class="modal">
            <span class="close" onclick="closeModal()">&times;</span>
            <div class="modal-content">
                <img id="modalImage" class="modal-image" src="" alt="Receipt">
            </div>
        </div>

        <div id="commentModal" class="modal">
            <div class="comment-modal-card">
                <span class="close" onclick="closeCommentModal()">&times;</span>
                <h3 id="commentModalTitle">Admin Comment</h3>
                <p id="commentModalBody">No comment provided.</p>
            </div>
        </div>

        <script>
            function toggleSidebar() {
                const sidebar = document.getElementById('sidebar');
                sidebar.classList.toggle('open');
            }

            function openModal(imageSrc) {
                document.getElementById('modalImage').src = imageSrc;
                document.getElementById('imageModal').style.display = 'block';
            }

            function closeModal() {
                document.getElementById('imageModal').style.display = 'none';
            }

            function openCommentModal(title, comment) {
                document.getElementById('commentModalTitle').textContent = title;
                document.getElementById('commentModalBody').textContent = comment && comment.trim() !== '' ? comment : 'No admin comment provided.';
                document.getElementById('commentModal').style.display = 'block';
            }

            function closeCommentModal() {
                document.getElementById('commentModal').style.display = 'none';
            }

            // File upload drag and drop
            const fileUploadArea = document.querySelector('.file-upload-area');
            const fileInput = document.getElementById('receipt_image');

            fileUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                fileUploadArea.classList.add('dragover');
            });

            fileUploadArea.addEventListener('dragleave', () => {
                fileUploadArea.classList.remove('dragover');
            });

            fileUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                fileUploadArea.classList.remove('dragover');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    updateFileDisplay(files[0]);
                }
            });

            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    updateFileDisplay(e.target.files[0]);
                }
            });

            function updateFileDisplay(file) {
                const text = fileUploadArea.querySelector('.file-upload-text');
                text.textContent = file.name;
            }

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('imageModal');
                if (event.target == modal) {
                    closeModal();
                }

                const commentModal = document.getElementById('commentModal');
                if (event.target == commentModal) {
                    closeCommentModal();
                }
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
                            const bPayments = document.getElementById('badge-payments');
                            const bReceipts = document.getElementById('badge-receipts');
                            const bAnnouncements = document.getElementById('badge-announcements');
                            
                            if (bPayments) bPayments.style.display = data.badges.payments ? 'flex' : 'none';
                            if (bReceipts) bReceipts.style.display = data.badges.receipts ? 'flex' : 'none';
                            if (bAnnouncements) bAnnouncements.style.display = data.badges.announcements ? 'flex' : 'none';
                        }
                    })
                    .catch(e => console.error('Error polling badges:', e));
            }, 5000);
        }
    </script>
    </body>
    </html>
