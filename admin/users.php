<?php
require_once '../config/config.php';

// AJAX handler for fetching payment data
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_payments') {
    $user_id = $_GET['user_id'];
    $year = $_GET['year'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM payments WHERE user_id = :user_id AND year = :year";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':year', $year);
    $stmt->execute();
    $payments = $stmt->fetch(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($payments ?: new stdClass());
    exit;
}

if (!isAdmin()) {
    redirect('auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

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

// Get pending receipts count for notification (persistent badge logic)
$query = "SELECT COUNT(*) as count FROM payment_receipts WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$pending_receipts_count = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
$last_seen = isset($_SESSION['pending_receipts_seen_count']) ? (int)$_SESSION['pending_receipts_seen_count'] : 0;
$show_pending_badge = $pending_receipts_count > $last_seen;
// Mark as seen for future pages in this session
$_SESSION['pending_receipts_seen_count'] = max($pending_receipts_count, $last_seen);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'add') {
        $fullname = sanitize($_POST['fullname']);
        $gender = sanitize($_POST['gender']);
        $password = $_POST['password'];
        $room_number = sanitize($_POST['room_number']);
        $email = sanitize($_POST['email']);
        
        if (empty($fullname) || empty($gender) || empty($password) || empty($room_number)) {
            $_SESSION['flash_error'] = "Please fill in all fields";
            redirect('admin/users.php');
        } else {
            // Check room capacity (max 4 boarders per room)
            $query = "SELECT COUNT(*) as current_count FROM users WHERE room_number = :room_number";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':room_number', $room_number);
            $stmt->execute();
            $room_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($room_data['current_count'] >= 4) {
                $_SESSION['flash_error'] = "Room {$room_number} is full (maximum 4 boarders per room)";
                redirect('admin/users.php');
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO users (fullname, gender, password, room_number, email) VALUES (:fullname, :gender, :password, :room_number, :email)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':fullname', $fullname);
                $stmt->bindParam(':gender', $gender);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':room_number', $room_number);
                $stmt->bindParam(':email', $email);
                
                if ($stmt->execute()) {
                    // Update room occupancy
                    updateRoomOccupancy($room_number, $db);
                    // Log action
                    try {
                        $details = [
                            'target' => 'user',
                            'operation' => 'add',
                            'user' => [
                                'fullname' => $fullname,
                                'gender' => $gender,
                                'room_number' => $room_number,
                                'email' => $email
                            ]
                        ];
                        logAdminAction($db, $_SESSION['admin_id'], 'user_add', json_encode($details));
                    } catch (Throwable $e) {}
                    $_SESSION['flash_success'] = "User added successfully to Room {$room_number}";
                    redirect('admin/users.php');
                } else {
                    $_SESSION['flash_error'] = "Error adding user";
                    redirect('admin/users.php');
                }
            }
        }
    } elseif ($action == 'edit') {
        $user_id = $_POST['user_id'];
        $fullname = sanitize($_POST['fullname']);
        $gender = sanitize($_POST['gender']);
        $room_number = sanitize($_POST['room_number']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        
        if (empty($fullname) || empty($gender) || empty($room_number)) {
            $_SESSION['flash_error'] = "Please fill in all required fields";
            redirect('admin/users.php');
        } else {
            // Check room capacity (max 4 boarders per room) - only if room is changing
            $query = "SELECT fullname, gender, room_number FROM users WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current_user['room_number'] != $room_number) {
                // Room is changing, check capacity
                $query = "SELECT COUNT(*) as current_count FROM users WHERE room_number = :room_number";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':room_number', $room_number);
                $stmt->execute();
                $room_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($room_data['current_count'] >= 4) {
                    $_SESSION['flash_error'] = "Room {$room_number} is full (maximum 4 boarders per room)";
                    redirect('admin/users.php');
                } else {
                    // Proceed with update
                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $query = "UPDATE users SET fullname = :fullname, gender = :gender, room_number = :room_number, email = :email, password = :password WHERE id = :user_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':password', $hashed_password);
                    } else {
                        $query = "UPDATE users SET fullname = :fullname, gender = :gender, room_number = :room_number, email = :email WHERE id = :user_id";
                        $stmt = $db->prepare($query);
                    }
                    
                    $stmt->bindParam(':fullname', $fullname);
                    $stmt->bindParam(':gender', $gender);
                    $stmt->bindParam(':room_number', $room_number);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':user_id', $user_id);
                    
                    if ($stmt->execute()) {
                        // Update room occupancy for both old and new rooms
                        updateRoomOccupancy($room_number, $db);
                        if ($current_user['room_number'] != $room_number) {
                            updateRoomOccupancy($current_user['room_number'], $db);
                        }
                        // Log action
                        try {
                            $details = [
                                'target' => 'user',
                                'operation' => 'edit',
                                'user_id' => $user_id,
                                'before' => [
                                    'fullname' => $current_user['fullname'],
                                    'gender' => $current_user['gender'],
                                    'room_number' => $current_user['room_number']
                                ],
                                'after' => [
                                    'fullname' => $fullname,
                                    'gender' => $gender,
                                    'room_number' => $room_number,
                                    'email' => $email,
                                    'password_changed' => !empty($password)
                                ]
                            ];
                            logAdminAction($db, $_SESSION['admin_id'], 'user_edit', json_encode($details));
                        } catch (Throwable $e) {}
                        $_SESSION['flash_success'] = "User updated successfully";
                        redirect('admin/users.php');
                    } else {
                        $_SESSION['flash_error'] = "Error updating user";
                        redirect('admin/users.php');
                    }
                }
            } else {
                // Room not changing, proceed with update
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $query = "UPDATE users SET fullname = :fullname, gender = :gender, room_number = :room_number, email = :email, password = :password WHERE id = :user_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':password', $hashed_password);
                } else {
                    $query = "UPDATE users SET fullname = :fullname, gender = :gender, room_number = :room_number, email = :email WHERE id = :user_id";
                    $stmt = $db->prepare($query);
                }
                
                $stmt->bindParam(':fullname', $fullname);
                $stmt->bindParam(':gender', $gender);
                $stmt->bindParam(':room_number', $room_number);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    // Update room occupancy
                    updateRoomOccupancy($room_number, $db);
                    // Log action
                    try {
                        $details = [
                            'target' => 'user',
                            'operation' => 'edit',
                            'user_id' => $user_id,
                            'before' => [
                                'fullname' => $current_user['fullname'],
                                'gender' => $current_user['gender'],
                                'room_number' => $current_user['room_number']
                            ],
                            'after' => [
                                'fullname' => $fullname,
                                'gender' => $gender,
                                'room_number' => $room_number,
                                'email' => $email,
                                'password_changed' => !empty($password)
                            ]
                        ];
                        logAdminAction($db, $_SESSION['admin_id'], 'user_edit', json_encode($details));
                    } catch (Throwable $e) {}
                    $_SESSION['flash_success'] = "User updated successfully";
                    redirect('admin/users.php');
                } else {
                    $_SESSION['flash_error'] = "Error updating user";
                    redirect('admin/users.php');
                }
            }
        }
    } elseif ($action == 'toggle_status') {
        $user_id = $_POST['user_id'];
        // Fetch user details before status toggle for logging
        try {
            $uStmt = $db->prepare("SELECT fullname, status FROM users WHERE id = :uid");
            $uStmt->bindParam(':uid', $user_id);
            $uStmt->execute();
            $user_before = $uStmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) { $user_before = null; }
        
        if (toggleUserStatus($user_id, $db)) {
            $new_status = ($user_before['status'] == 'active') ? 'deactivated' : 'active';
            $_SESSION['flash_success'] = "User " . ($new_status == 'active' ? 'activated' : 'deactivated') . " successfully";
            // Log action
            try {
                $details = [
                    'target' => 'user',
                    'operation' => 'toggle_status',
                    'user_id' => $user_id,
                    'before_status' => $user_before['status'],
                    'after_status' => $new_status
                ];
                logAdminAction($db, $_SESSION['admin_id'], 'user_status_toggle', json_encode($details));
            } catch (Throwable $e) {}
        } else {
            $_SESSION['flash_error'] = "Error updating user status";
        }
        redirect('admin/users.php');
    }
}

// Get all users with room occupancy information (only counting active users for occupancy)
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM users u2 WHERE u2.room_number = u.room_number AND u2.status = 'active') as room_occupancy
          FROM users u 
          ORDER BY u.room_number, u.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user for editing
$edit_user = null;
if (isset($_GET['edit'])) {
    $user_id = $_GET['edit'];
    $query = "SELECT * FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Ruin Boarders</title>
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
            background: linear-gradient(135deg, #56ab2f, #a8e6cf);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff416c, #ff4b2b);
            color: white;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
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

        .table-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
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
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s ease;
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

        .clickable-row { cursor: pointer; transition: background 0.2s; }
        .clickable-row:hover { background: #f0f4ff !important; }
        
        .history-modal-content { max-width: 600px; }
        .history-nav { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; background: #f8f9fa; padding: 10px 15px; border-radius: 8px; }
        .history-year { font-size: 1.2rem; font-weight: 700; color: #333; }
        .history-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .history-item { padding: 15px; border-radius: 10px; border: 1px solid #eee; text-align: center; }
        .history-item.paid { background: #e8f5e8; border-color: #2e7d32; color: #2e7d32; }
        .history-item.unpaid { background: #fff5f5; border-color: #e53e3e; color: #e53e3e; }
        .history-month { font-weight: 600; margin-bottom: 5px; text-transform: capitalize; }
        .history-amount { font-size: 1.1rem; font-weight: 700; }
        .nav-btn { background: white; border: 1px solid #ddd; padding: 5px 12px; border-radius: 5px; cursor: pointer; transition: all 0.3s; }
        .nav-btn:hover:not(:disabled) { background: #667eea; color: white; border-color: #667eea; }
        .nav-btn:disabled { opacity: 0.5; cursor: not-allowed; }

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

            .form-row {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .modal-content { margin: 10% auto; padding: 20px; }
            .history-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) { .history-grid { grid-template-columns: 1fr; } }
    
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
        .dark-mode .alert-success { background: rgba(40, 167, 69, 0.2); border-color: #2e7d32; color: #81c784; }
        .dark-mode .alert-danger { background: rgba(220, 53, 69, 0.2); border-color: #e53e3e; color: #fc8181; }
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
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
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s ease;
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

        .clickable-row { cursor: pointer; transition: background 0.2s; }
        .clickable-row:hover { background: #f0f4ff !important; }
        
        .history-modal-content { max-width: 600px; }
        .history-nav { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; background: #f8f9fa; padding: 10px 15px; border-radius: 8px; }
        .history-year { font-size: 1.2rem; font-weight: 700; color: #333; }
        .history-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .history-item { padding: 15px; border-radius: 10px; border: 1px solid #eee; text-align: center; }
        .history-item.paid { background: #e8f5e8; border-color: #2e7d32; color: #2e7d32; }
        .history-item.unpaid { background: #fff5f5; border-color: #e53e3e; color: #e53e3e; }
        .history-month { font-weight: 600; margin-bottom: 5px; text-transform: capitalize; }
        .history-amount { font-size: 1.1rem; font-weight: 700; }
        .nav-btn { background: white; border: 1px solid #ddd; padding: 5px 12px; border-radius: 5px; cursor: pointer; transition: all 0.3s; }
        .nav-btn:hover:not(:disabled) { background: #667eea; color: white; border-color: #667eea; }
        .nav-btn:disabled { opacity: 0.5; cursor: not-allowed; }

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

            .form-row {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .modal-content { margin: 10% auto; padding: 20px; }
            .history-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) { .history-grid { grid-template-columns: 1fr; } }
    
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
        .dark-mode .alert-success { background: rgba(40, 167, 69, 0.2); border-color: #2e7d32; color: #81c784; }
        .dark-mode .alert-danger { background: rgba(220, 53, 69, 0.2); border-color: #e53e3e; color: #fc8181; }

    </style>
    <link rel="stylesheet" href="../assets/css/neuromorphic-theme.css">
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <script src="../assets/js/neuromorphic-theme.js" defer></script>
</head>
<body>
    <?php $current_admin_page = 'users'; ?>
    <?php require __DIR__ . '/includes/admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Manage Users</h1>
            <button class="btn btn-primary" type="button" onclick="openModal('addUserModal')">
                <i class="fas fa-user-plus"></i> Add New Boarder
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

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Avatar</th>
                        <th>Full Name</th>
                        <th>Gender</th>
                        <th>Email</th>
                        <th>Room Number</th>
                        <th>Room Status</th>
                        <th>Account Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr class="clickable-row" onclick="event.target.tagName !== 'BUTTON' && event.target.tagName !== 'I' && showPaymentHistory(<?php echo $user['id']; ?>, '<?php echo addslashes($user['fullname']); ?>')">
                            <td>
                                <div class="user-avatar" style="position: relative; overflow: hidden;">
                                    <?php if (!empty($user['profile_picture'])): ?>
                                        <img src="../uploads/profiles/<?php echo $user['profile_picture']; ?>" alt="Profile Picture" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <?php echo strtoupper(substr($user['fullname'], 0, 1)); ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($user['gender']); ?></td>
                            <td style="font-size: 0.85rem; color: #666;"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($user['room_number']); ?></td>
                            <td>
                                <span class="room-status" style="
                                    padding: 4px 8px; 
                                    border-radius: 12px; 
                                    font-size: 12px; 
                                    font-weight: 500;
                                    background: <?php echo $user['room_occupancy'] >= 4 ? '#ffebee' : '#e8f5e8'; ?>;
                                    color: <?php echo $user['room_occupancy'] >= 4 ? '#c62828' : '#2e7d32'; ?>;
                                ">
                                    <?php echo $user['room_occupancy']; ?>/4 
                                    <?php echo $user['room_occupancy'] >= 4 ? 'FULL' : 'Available'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge" style="
                                    padding: 4px 8px; 
                                    border-radius: 12px; 
                                    font-size: 12px; 
                                    font-weight: 500;
                                    background: <?php echo $user['status'] == 'active' ? '#e8f5e8' : '#ffebee'; ?>;
                                    color: <?php echo $user['status'] == 'active' ? '#2e7d32' : '#c62828'; ?>;
                                ">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-warning btn-sm" onclick="editUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn <?php echo $user['status'] == 'active' ? 'btn-danger' : 'btn-success'; ?> btn-sm" 
                                            onclick="toggleStatus(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['fullname']); ?>', '<?php echo $user['status']; ?>')">
                                        <i class="fas <?php echo $user['status'] == 'active' ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New User</h3>
                <span class="close" onclick="closeModal('addUserModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" id="fullname" name="fullname" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="room_number">Room Number</label>
                        <input type="text" id="room_number" name="room_number" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal('addUserModal')" style="background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit User</h3>
                <span class="close" onclick="closeModal('editUserModal')">&times;</span>
            </div>
            <form method="POST" id="editUserForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_fullname">Full Name</label>
                        <input type="text" id="edit_fullname" name="fullname" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" id="edit_email" name="email" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_gender">Gender</label>
                        <select id="edit_gender" name="gender" class="form-control" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_room_number">Room Number</label>
                        <input type="text" id="edit_room_number" name="room_number" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_password">New Password (leave blank to keep current)</label>
                    <input type="password" id="edit_password" name="password" class="form-control">
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal('editUserModal')" style="background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Status Toggle Confirmation Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="statusModalTitle">Change User Status</h3>
                <span class="close" onclick="closeModal('statusModal')">&times;</span>
            </div>
            <p id="statusModalText">Are you sure you want to change this user's status?</p>
            <form method="POST" id="statusForm">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="user_id" id="status_user_id">
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn" onclick="closeModal('statusModal')" style="background: #6c757d; color: white;">Cancel</button>
                    <button type="submit" class="btn" id="statusSubmitBtn">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Multi-Year Payment History Modal -->
    <div id="historyModal" class="modal">
        <div class="modal-content history-modal-content">
            <div class="modal-header">
                <h3 id="historyModalTitle">Payment History</h3>
                <span class="close" onclick="closeModal('historyModal')">&times;</span>
            </div>
            <div class="history-nav">
                <button class="nav-btn" onclick="changeYear(-1)"><i class="fas fa-chevron-left"></i> Back</button>
                <div class="history-year" id="currentYearDisplay">2026</div>
                <button class="nav-btn" onclick="changeYear(1)">Next <i class="fas fa-chevron-right"></i></button>
            </div>
            <div id="historyModalContent" class="history-grid">
                <!-- Data will be injected here -->
            </div>
            <div style="margin-top: 25px; text-align: right;">
                <button class="btn btn-primary" onclick="closeModal('historyModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function editUser(userId) {
            // This would typically fetch user data via AJAX
            // For now, we'll redirect to edit page
            window.location.href = '<?php echo BASE_URL; ?>admin/users.php?edit=' + userId;
        }

        function toggleStatus(userId, userName, currentStatus) {
            document.getElementById('status_user_id').value = userId;
            const action = currentStatus === 'active' ? 'deactivate' : 'activate';
            const btnClass = currentStatus === 'active' ? 'btn-danger' : 'btn-success';
            
            document.getElementById('statusModalTitle').textContent = `Confirm ${action.charAt(0).toUpperCase() + action.slice(1)}`;
            document.getElementById('statusModalText').innerHTML = `Are you sure you want to <strong>${action}</strong> <strong>${userName}</strong>?`;
            
            const submitBtn = document.getElementById('statusSubmitBtn');
            submitBtn.textContent = `${action.charAt(0).toUpperCase() + action.slice(1)} User`;
            submitBtn.className = `btn ${btnClass}`;
            
            openModal('statusModal');
        }

        let currentUserId = null;
        let currentYear = new Date().getFullYear();
        const monthsKey = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        async function showPaymentHistory(userId, userName) {
            currentUserId = userId;
            document.getElementById('historyModalTitle').textContent = `Payment History: ${userName}`;
            document.getElementById('currentYearDisplay').textContent = currentYear;
            await fetchAndShowPayments();
            openModal('historyModal');
        }

        async function changeYear(delta) {
            currentYear += delta;
            document.getElementById('currentYearDisplay').textContent = currentYear;
            await fetchAndShowPayments();
        }

        async function fetchAndShowPayments() {
            const container = document.getElementById('historyModalContent');
            container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            try {
                const response = await fetch(`users.php?ajax=get_payments&user_id=${currentUserId}&year=${currentYear}`);
                const data = await response.json();
                
                container.innerHTML = '';
                monthsKey.forEach((m, i) => {
                    const amount = parseFloat(data[m]) || 0;
                    const isPaid = amount > 0;
                    
                    const div = document.createElement('div');
                    div.className = `history-item ${isPaid ? 'paid' : 'unpaid'}`;
                    div.innerHTML = `
                        <div class="history-month">${monthNames[i]}</div>
                        <div class="history-amount">${isPaid ? '₱' + amount.toLocaleString() : 'Unpaid'}</div>
                    `;
                    container.appendChild(div);
                });
            } catch (e) {
                container.innerHTML = '<div style="grid-column: 1/-1; color: red;">Error loading data.</div>';
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Auto-populate edit form if editing
        <?php if ($edit_user): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('edit_user_id').value = '<?php echo $edit_user['id']; ?>';
            document.getElementById('edit_fullname').value = '<?php echo htmlspecialchars($edit_user['fullname']); ?>';
            document.getElementById('edit_email').value = '<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>';
            document.getElementById('edit_gender').value = '<?php echo htmlspecialchars($edit_user['gender']); ?>';
            document.getElementById('edit_room_number').value = '<?php echo htmlspecialchars($edit_user['room_number']); ?>';
            openModal('editUserModal');
        });
        <?php endif; ?>

        // Prevent double form submissions by disabling submit buttons
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(function(form) {
                form.addEventListener('submit', function() {
                    const buttons = form.querySelectorAll('button[type="submit"]');
                    buttons.forEach(function(btn) {
                        btn.disabled = true;
                        btn.style.opacity = '0.7';
                    });
                });
            });
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
