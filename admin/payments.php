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

// Create/extend payment_history table to store method and admin who updated
try {
    // Check if table exists
    $check_table = "SHOW TABLES LIKE 'payment_history'";
    $table_exists = $db->query($check_table)->rowCount() > 0;
    
    if (!$table_exists) {
        // Try with foreign keys first
        try {
            $create_history_table = "CREATE TABLE payment_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                fullname VARCHAR(255) NOT NULL,
                year YEAR NOT NULL,
                month VARCHAR(20) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_method VARCHAR(20) DEFAULT NULL,
                admin_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_year_month (year, month)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $db->exec($create_history_table);
        } catch (PDOException $e) {
            // If foreign keys fail, create without them
            $create_history_table = "CREATE TABLE payment_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                fullname VARCHAR(255) NOT NULL,
                year YEAR NOT NULL,
                month VARCHAR(20) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                payment_method VARCHAR(20) DEFAULT NULL,
                admin_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_year_month (year, month)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $db->exec($create_history_table);
        }
    } else {
        // Add column payment_method if missing
        try { $db->exec("ALTER TABLE payment_history ADD COLUMN payment_method VARCHAR(20) DEFAULT NULL"); } catch (Throwable $e) {}
    }
} catch (PDOException $e) {
    // Table creation failed, but continue - will try again on next access
    error_log("Payment history table creation error: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update_payment') {
        $user_id = $_POST['user_id'];
        $year = $_POST['year'];
        $month = $_POST['month'];
        $amount = $_POST['amount'];
        $payment_method = isset($_POST['payment_method']) ? sanitize($_POST['payment_method']) : null;
        
        // Get user info
        $user_query = "SELECT fullname FROM users WHERE id = :user_id";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->bindParam(':user_id', $user_id);
        $user_stmt->execute();
        $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if payment record exists for this user and year
        $query = "SELECT id FROM payments WHERE user_id = :user_id AND year = :year";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Update existing record
            $query = "UPDATE payments SET {$month} = :amount WHERE user_id = :user_id AND year = :year";
        } else {
            // Create new record
            $query = "INSERT INTO payments (user_id, year, {$month}) VALUES (:user_id, :year, :amount)";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':year', $year);
        $stmt->bindParam(':amount', $amount);
        
        if ($stmt->execute()) {
            // Add to payment history (including zero amounts)
            try {
                // Ensure table exists first
                $check_table = "SHOW TABLES LIKE 'payment_history'";
                $table_exists = $db->query($check_table)->rowCount() > 0;
                
                if (!$table_exists) {
                    // Create table without foreign keys
                    $create_history_table = "CREATE TABLE payment_history (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        fullname VARCHAR(255) NOT NULL,
                        year YEAR NOT NULL,
                        month VARCHAR(20) NOT NULL,
                        amount DECIMAL(10,2) NOT NULL,
                        admin_id INT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_user_id (user_id),
                        INDEX idx_year_month (year, month)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                    $db->exec($create_history_table);
                }
                
                // Insert history record (even if amount is zero)
                $history_query = "INSERT INTO payment_history (user_id, fullname, year, month, amount, payment_method, admin_id) 
                                  VALUES (:user_id, :fullname, :year, :month, :amount, :payment_method, :admin_id)";
                $history_stmt = $db->prepare($history_query);
                $history_stmt->bindParam(':user_id', $user_id);
                $history_stmt->bindParam(':fullname', $user_data['fullname']);
                $history_stmt->bindParam(':year', $year);
                $history_stmt->bindParam(':month', $month);
                $history_stmt->bindParam(':amount', $amount);
                $history_stmt->bindParam(':payment_method', $payment_method);
                $history_stmt->bindParam(':admin_id', $_SESSION['admin_id']);
                
                if ($history_stmt->execute()) {
                    $message = "Payment updated successfully and added to history";
                    // Log
                    logAdminAction($db, $_SESSION['admin_id'], 'update_payment', json_encode(['user_id'=>$user_id,'year'=>$year,'month'=>$month,'amount'=>$amount,'method'=>$payment_method]));
                } else {
                    $message = "Payment updated successfully (history not saved)";
                }
            } catch (PDOException $e) {
                // Log error but continue
                error_log("Payment history insert error: " . $e->getMessage());
                $message = "Payment updated successfully (history could not be saved)";
            }
        } else {
            $error = "Error updating payment";
        }
    }
}

// Get all active users for payment management
$query = "SELECT * FROM users WHERE status = 'active' ORDER BY fullname";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected user, year, and month
$selected_user_id = isset($_GET['user_id']) && is_numeric($_GET['user_id']) ? $_GET['user_id'] : null;
$selected_year = $_GET['year'] ?? date('Y');
$selected_month = $_GET['month'] ?? 'all';

// Get payment data for selected user and year
$payments = null;
if ($selected_user_id) {
    $query = "SELECT * FROM payments WHERE user_id = :user_id AND year = :year";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $selected_user_id);
    $stmt->bindParam(':year', $selected_year);
    $stmt->execute();
    $payments = $stmt->fetch(PDO::FETCH_ASSOC);
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

if (isset($_GET['ajax_check_badges'])) {
    echo json_encode(['badges' => []]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - Ruin Boarders</title>
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
        }

        .header h1 {
            color: #333;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .filters {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .filter-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 20px;
            align-items: end;
        }

        .form-group {
            margin-bottom: 0;
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

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .payments-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        .payments-table thead th,
        .payments-table tbody td {
            padding: 15px;
            text-align: left;
        }

        .payments-table thead th {
            border-bottom: 2px solid #e1e5e9;
            font-weight: 600;
            color: #333;
        }

        .payments-table tbody tr {
            border-bottom: 1px solid #e1e5e9;
            transition: background-color 0.2s;
        }

        .payments-table .attribute-cell {
            font-weight: 600;
            color: #253142 !important;
        }

        .payments-table .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
        }

        .payments-table .status-badge.paid {
            background: #e8f5e8;
            color: #28a745 !important;
        }

        .payments-table .status-badge.unpaid {
            background: #fff3cd;
            color: #856404 !important;
        }

        .payments-table .amount-value {
            font-weight: 700;
            color: #28a745;
        }

        .dark-mode .payments-table thead th {
            border-bottom-color: #444;
        }

        .dark-mode .payments-table tbody tr {
            border-bottom-color: #444;
        }

        .dark-mode .payments-table .attribute-cell {
            color: #edf3fb !important;
        }



        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            position: relative;
            animation: modalFadeIn 0.3s ease-out;
            margin: auto; /* Fallback for older browsers */
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            color: #333;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover {
            color: #333;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        /* Dark Mode Modal Adjustments */
        .dark-mode .modal-content {
            background-color: #2a2a35;
            border: 1px solid #444;
            color: #fff;
        }

        .dark-mode .modal-header {
            border-bottom-color: #444;
        }

        .dark-mode .modal-header h3 {
            /* Inherits the gradient background clip nicely */
        }

        .dark-mode .close {
            color: #888;
        }

        .dark-mode .close:hover {
            color: #fff;
        }

        .dark-mode .form-control {
            background: #1e1e2a;
            border-color: #444;
            color: #fff;
        }

        .dark-mode .form-control:read-only {
            background: #1a1a24;
            opacity: 0.7;
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
        .dark-mode .card h3 { color: #fff !important; }
        .dark-mode table th { color: #ddd !important; }
        .dark-mode table td { color: #ddd; }
        .dark-mode .amount-value { color: #fff !important; }
        .dark-mode table tbody tr:hover { background: rgba(255,255,255,0.05) !important; }
        .dark-mode .alert-success { background: rgba(40, 167, 69, 0.2); border-color: #2e7d32; color: #81c784; }
        .dark-mode .alert-danger { background: rgba(220, 53, 69, 0.2); border-color: #e53e3e; color: #fc8181; }

    </style>
    <link rel="stylesheet" href="../assets/css/neuromorphic-theme.css">
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <script src="../assets/js/neuromorphic-theme.js" defer></script>
</head>
<body>
    <?php $current_admin_page = 'payments'; ?>
    <?php require __DIR__ . '/includes/admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Payment Management</h1>
            <p>Manage monthly payments for boarders</p>
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
                            <option value="">Select a Boarder...</option>
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
                            <?php for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>>
                                    <?php echo $y; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">

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

        <?php if ($selected_user_id && is_numeric($selected_user_id)): ?>
            <?php
            $selected_user = null;
            foreach ($users as $user) {
                if ($user['id'] == $selected_user_id) {
                    $selected_user = $user;
                    break;
                }
            }
            ?>
            
            <?php if ($selected_user): ?>
                <!-- Payment Update View -->
                <div class="card">
                    <h3 style="margin-bottom: 20px; color: #333;">
                        Payment Status for <?php echo htmlspecialchars($selected_user['fullname']); ?> - <?php echo $selected_year; ?>
                    </h3>
                    
                    <div style="overflow-x: auto; border-radius: 10px;">
                        <table class="payments-table">
                            <thead>
                                <tr>
                                    <th class="attribute-heading">Month</th>
                                    <th>Status</th>
                                    <th>Amount Paid</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($months as $month_key => $month_name): ?>
                                    <?php
                                    if ($selected_month !== 'all' && $selected_month !== $month_key) continue;
                                    $amount = $payments ? $payments[$month_key] : 0;
                                    $is_paid = $amount > 0;
                                    ?>
                                    <tr>
                                        <td class="attribute-cell"><?php echo $month_name; ?></td>
                                        <td>
                                            <?php if ($is_paid): ?>
                                                <span class="status-badge paid">Paid</span>
                                            <?php else: ?>
                                                <span class="status-badge unpaid">Unpaid</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="amount-value">
                                            ₱<?php echo number_format($amount, 2); ?>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-primary" style="padding: 6px 12px; font-size: 13px;" onclick="openPaymentModal(<?php echo $selected_user_id; ?>, '<?php echo $selected_year; ?>', '<?php echo $month_key; ?>', '<?php echo $month_name; ?>', '<?php echo $amount; ?>')">
                                                <i class="fas fa-edit"></i> Update Payment
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card">
                <p style="text-align: center; color: #666; padding: 40px;">Select a boarder to view and manage their payments.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Update Payment Modal -->
    <div id="paymentModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Payment</h3>
                <span class="close" onclick="closePaymentModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_payment">
                <input type="hidden" name="user_id" id="pm_user_id">
                <input type="hidden" name="year" id="pm_year">
                <input type="hidden" name="month" id="pm_month">
                
                <div class="form-group">
                    <label for="pm_month_label">Month</label>
                    <input type="text" id="pm_month_label" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label for="pm_amount">Amount</label>
                    <input type="number" id="pm_amount" name="amount" class="form-control" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="pm_method">Payment Method</label>
                    <select id="pm_method" name="payment_method" class="form-control" required>
                        <option value="">Select Method</option>
                        <option value="Cash">Cash</option>
                        <option value="Gcash">Gcash</option>
                    </select>
                </div>
                
                <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px;">
                    <button type="button" class="btn" onclick="closePaymentModal()" style="background:#6c757d; color:white;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>



    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        function openPaymentModal(userId, year, monthKey, monthLabel, currentAmount) {
            document.getElementById('pm_user_id').value = userId;
            document.getElementById('pm_year').value = year;
            document.getElementById('pm_month').value = monthKey;
            document.getElementById('pm_month_label').value = monthLabel;
            document.getElementById('pm_amount').value = currentAmount || 0;
            document.getElementById('pm_method').value = '';
            document.getElementById('paymentModal').style.display = 'flex';
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
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
            // Close Profile Dropdown
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }

            // Close Payment Modal when clicking outside
            const paymentModal = document.getElementById('paymentModal');
            if (e.target === paymentModal) {
                closePaymentModal();
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
