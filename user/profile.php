<?php
require_once '../config/config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get user info
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Initial badge markers for sidebar
$stmt = $db->prepare("SELECT MAX(updated_at) FROM announcements");
$stmt->execute();
$last_ann = $stmt->fetchColumn();
$has_new_announcements = ($last_ann && (!$user['seen_announcements_at'] || strtotime($last_ann) > strtotime($user['seen_announcements_at'])));

$stmt = $db->prepare("SELECT MAX(updated_at) FROM payment_receipts WHERE user_id = :id AND status != 'pending'");
$stmt->execute([':id' => $user_id]);
$last_rec = $stmt->fetchColumn();
$has_new_receipts = ($last_rec && (!$user['seen_receipts_at'] || strtotime($last_rec) > strtotime($user['seen_receipts_at'])));

$stmt = $db->prepare("SELECT MAX(created_at) FROM payment_history WHERE user_id = :id");
$stmt->execute([':id' => $user_id]);
$last_pay = $stmt->fetchColumn();
$has_new_payments = ($last_pay && (!$user['seen_payments_at'] || strtotime($last_pay) > strtotime($user['seen_payments_at'])));

// Handle AJAX password verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'verify_password') {
    header('Content-Type: application/json');
    $password = $_POST['password'] ?? '';
    $is_valid = password_verify($password, $user['password']);
    echo json_encode(['valid' => $is_valid]);
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update_profile') {
        $fullname = sanitize($_POST['fullname']);
        $gender = sanitize($_POST['gender']);
        $email = sanitize($_POST['email'] ?? '');
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($fullname) || empty($gender)) {
            $error = "Please fill in all required fields";
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address";
        } else {
            // Verify current password if changing password
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $error = "Please enter your current password";
                } elseif (!password_verify($current_password, $user['password'])) {
                    $error = "Current password is incorrect";
                } elseif ($new_password !== $confirm_password) {
                    $error = "New passwords do not match";
                } else {
                    // Strong password validation: 8 characters, one capital, one lowercase, one number, one special character
                    if (strlen($new_password) < 8) {
                        $error = "New password must be at least 8 characters long";
                    } elseif (!preg_match('/[A-Z]/', $new_password)) {
                        $error = "New password must contain at least one capital letter";
                    } elseif (!preg_match('/[a-z]/', $new_password)) {
                        $error = "New password must contain at least one lowercase letter";
                    } elseif (!preg_match('/[0-9]/', $new_password)) {
                        $error = "New password must contain at least one number";
                    } elseif (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
                        $error = "New password must contain at least one special character";
                    }
                }
            }
            
            if (empty($error)) {
                // Check if email already exists (for other users)
                if (!empty($email)) {
                    $check_query = "SELECT id FROM users WHERE email = :email AND id != :user_id";
                    $check_stmt = $db->prepare($check_query);
                    $check_stmt->bindParam(':email', $email);
                    $check_stmt->bindParam(':user_id', $user_id);
                    $check_stmt->execute();
                    
                    if ($check_stmt->rowCount() > 0) {
                        $error = "Email already registered by another user";
                    }
                }
                
                if (empty($error)) {
                    try {
                        if (!empty($new_password)) {
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            // Try to update with email
                            try {
                                $query = "UPDATE users SET fullname = :fullname, gender = :gender, email = :email, password = :password WHERE id = :user_id";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':email', $email);
                                $stmt->bindParam(':password', $hashed_password);
                            } catch (PDOException $e) {
                                // Email column doesn't exist, update without email
                                $query = "UPDATE users SET fullname = :fullname, gender = :gender, password = :password WHERE id = :user_id";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':password', $hashed_password);
                            }
                        } else {
                            // Try to update with email
                            try {
                                $query = "UPDATE users SET fullname = :fullname, gender = :gender, email = :email WHERE id = :user_id";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':email', $email);
                            } catch (PDOException $e) {
                                // Email column doesn't exist, update without email
                                $query = "UPDATE users SET fullname = :fullname, gender = :gender WHERE id = :user_id";
                                $stmt = $db->prepare($query);
                            }
                        }
                        
                        $stmt->bindParam(':fullname', $fullname);
                        $stmt->bindParam(':gender', $gender);
                        $stmt->bindParam(':user_id', $user_id);
                        
                        if ($stmt->execute()) {
                            $_SESSION['user_fullname'] = $fullname;
                            $message = "Profile updated successfully";
                            // Refresh user data
                            $query = "SELECT * FROM users WHERE id = :user_id";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':user_id', $user_id);
                            $stmt->execute();
                            $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        } else {
                            $error = "Error updating profile";
                        }
                    } catch (PDOException $e) {
                        // If email column doesn't exist, just update without it
                if (!empty($new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $query = "UPDATE users SET fullname = :fullname, gender = :gender, password = :password WHERE id = :user_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':password', $hashed_password);
                } else {
                    $query = "UPDATE users SET fullname = :fullname, gender = :gender WHERE id = :user_id";
                    $stmt = $db->prepare($query);
                }
                
                $stmt->bindParam(':fullname', $fullname);
                $stmt->bindParam(':gender', $gender);
                $stmt->bindParam(':user_id', $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['user_fullname'] = $fullname;
                    $message = "Profile updated successfully";
                    // Refresh user data
                    $query = "SELECT * FROM users WHERE id = :user_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error = "Error updating profile";
                        }
                    }
                }
            }
        }
    } elseif ($action == 'upload_photo') {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $file = $_FILES['profile_picture'];
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $file_size = $file['size'];
                if ($file_size <= 5 * 1024 * 1024) { // 5MB limit
                    $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                    $upload_path = PROFILE_PIC_PATH . $new_filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        // Delete old profile picture if exists
                        if ($user['profile_picture'] && file_exists(PROFILE_PIC_PATH . $user['profile_picture'])) {
                            unlink(PROFILE_PIC_PATH . $user['profile_picture']);
                        }
                        
                        // Update database
                        $query = "UPDATE users SET profile_picture = :profile_picture WHERE id = :user_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':profile_picture', $new_filename);
                        $stmt->bindParam(':user_id', $user_id);
                        
                        if ($stmt->execute()) {
                            $user['profile_picture'] = $new_filename;
                            $message = "Profile picture updated successfully";
                        } else {
                            $error = "Error updating profile picture";
                        }
                    } else {
                        $error = "Error uploading file";
                    }
                } else {
                    $error = "File size must be less than 5MB";
                }
            } else {
                $error = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed";
            }
        } else {
            $error = "Please select a file to upload";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Ruin Boarders</title>
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
            position: relative;
            overflow: hidden;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
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

        .profile-photo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            position: relative;
            overflow: hidden;
            border: 4px solid #e1e5e9;
            transition: all 0.3s ease;
        }

        .profile-photo:hover {
            border-color: #667eea;
            transform: scale(1.05);
        }

        .profile-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-photo-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            font-weight: 600;
        }

        .photo-upload-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .photo-upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
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

        .form-control.password-valid {
            border-color: #28a745 !important;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        .form-control.password-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
            background: #5a6268;
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

        .hidden {
            display: none;
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

            .form-row {
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
        .password-section { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e1e5e9; }
        .password-section-title { margin: 0 0 15px; color: #333; }
        .password-section-help { color: #666; font-size: 0.9rem; margin: 0 0 15px; }
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
        .dark-mode .password-section { border-top-color: #444; }
        .dark-mode .password-section-title { color: #fff; }
        .dark-mode .password-section-help { color: #ddd; }
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
    <?php $current_user_page = 'profile'; ?>
    <?php require __DIR__ . '/includes/user-sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Profile Management</h1>
            <p>Update your personal information and profile picture</p>
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
                    <h3 class="card-title">Profile Picture</h3>
                </div>
                
                <div class="profile-photo-section">
                    <div class="profile-photo">
                        <?php if ($user['profile_picture']): ?>
                            <img src="../uploads/profiles/<?php echo $user['profile_picture']; ?>" alt="Profile Picture">
                        <?php else: ?>
                            <div class="profile-photo-placeholder">
                                <?php echo strtoupper(substr($user['fullname'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_photo">
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*" class="hidden" onchange="this.form.submit()">
                        <button type="button" class="photo-upload-btn" onclick="document.getElementById('profile_picture').click()">
                            <i class="fas fa-camera"></i> Change Photo
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Personal Information</h3>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fullname">Full Name</label>
                            <input type="text" id="fullname" name="fullname" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo $user['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $user['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo $user['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" 
                               <?php echo isset($user['email']) ? '' : 'readonly placeholder="Email not set"'; ?>>
                    </div>
                    
                    <div class="form-group">
                        <label for="room_number">Room Number</label>
                        <input type="text" id="room_number" class="form-control" 
                               value="<?php echo htmlspecialchars($user['room_number']); ?>" disabled>
                        <small style="color: #666; font-size: 0.8rem;">Room number cannot be changed. Contact admin if needed.</small>
                    </div>
                    
                    <div class="password-section">
                        <h4 class="password-section-title">Change Password</h4>
                        <p class="password-section-help">Leave password fields blank to keep current password</p>
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">Reset</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const currentPasswordInput = document.getElementById('current_password');
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const userPasswordHash = '<?php echo addslashes($user['password']); ?>';

        function validatePassword(password) {
            const errors = [];
            
            if (password.length < 8) {
                errors.push('At least 8 characters long');
            }
            if (!/[A-Z]/.test(password)) {
                errors.push('At least one capital letter (A-Z)');
            }
            if (!/[a-z]/.test(password)) {
                errors.push('At least one lowercase letter (a-z)');
            }
            if (!/[0-9]/.test(password)) {
                errors.push('At least one number (0-9)');
            }
            if (!/[^A-Za-z0-9]/.test(password)) {
                errors.push('At least one special character (!@#$%^&*...)');
            }
            
            return errors;
        }

        // Verify current password via AJAX
        async function verifyCurrentPassword(password) {
            try {
                const formData = new FormData();
                formData.append('action', 'verify_password');
                formData.append('password', password);

                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                return data.valid;
            } catch (error) {
                return false;
            }
        }

        // Check current password on blur (if new password is being entered)
        currentPasswordInput.addEventListener('blur', async function() {
            const password = this.value;
            if (password.length > 0 && newPasswordInput.value.length > 0) {
                const isValid = await verifyCurrentPassword(password);
                if (isValid) {
                    this.classList.remove('password-invalid');
                    this.classList.add('password-valid');
                } else {
                    this.classList.remove('password-valid');
                    this.classList.add('password-invalid');
                }
            } else {
                this.classList.remove('password-valid', 'password-invalid');
            }
        });

        // Update current password field based on input
        currentPasswordInput.addEventListener('input', function() {
            // Reset validation state when typing
            if (this.value.length > 0) {
                this.classList.remove('password-valid', 'password-invalid');
            } else {
                this.classList.remove('password-valid', 'password-invalid');
            }
        });

        // Validate new password
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;
            if (password.length === 0) {
                this.classList.remove('password-valid', 'password-invalid');
                return;
            }

            const errors = validatePassword(password);
            if (errors.length === 0) {
                this.classList.remove('password-invalid');
                this.classList.add('password-valid');
            } else {
                this.classList.remove('password-valid');
                this.classList.add('password-invalid');
            }

            // Update confirm password if it has value
            if (confirmPasswordInput.value.length > 0) {
                updateConfirmPasswordField(password, confirmPasswordInput.value);
            }
        });

        function updateConfirmPasswordField(newPassword, confirmPassword) {
            if (confirmPassword.length === 0) {
                confirmPasswordInput.classList.remove('password-valid', 'password-invalid');
                return;
            }

            const passwordErrors = validatePassword(newPassword);
            if (newPassword === confirmPassword && passwordErrors.length === 0) {
                confirmPasswordInput.classList.remove('password-invalid');
                confirmPasswordInput.classList.add('password-valid');
            } else {
                confirmPasswordInput.classList.remove('password-valid');
                confirmPasswordInput.classList.add('password-invalid');
            }
        }

        confirmPasswordInput.addEventListener('input', function() {
            updateConfirmPasswordField(newPasswordInput.value, this.value);
        });

        // Validate on form submit
        document.querySelector('form').addEventListener('submit', async function(e) {
            const currentPassword = currentPasswordInput.value;
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            // If new password is provided, validate everything
            if (newPassword.length > 0) {
                // Validate current password
                if (currentPassword.length === 0) {
                    e.preventDefault();
                    currentPasswordInput.classList.add('password-invalid');
                    alert('Please enter your current password');
                    return;
                }

                // Validate new password
                const passwordErrors = validatePassword(newPassword);
                if (passwordErrors.length > 0) {
                    e.preventDefault();
                    newPasswordInput.classList.add('password-invalid');
                    alert('New password does not meet requirements:\n' + passwordErrors.join('\n'));
                    return;
                }

                // Validate confirm password
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    confirmPasswordInput.classList.add('password-invalid');
                    alert('New passwords do not match!');
                    return;
                }
            }
        });

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('open');
        }

        function resetForm() {
            document.getElementById('fullname').value = '<?php echo htmlspecialchars($user['fullname']); ?>';
            document.getElementById('gender').value = '<?php echo $user['gender']; ?>';
            document.getElementById('email').value = '<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>';
            document.getElementById('current_password').value = '';
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
            currentPasswordInput.classList.remove('password-valid', 'password-invalid');
            newPasswordInput.classList.remove('password-valid', 'password-invalid');
            confirmPasswordInput.classList.remove('password-valid', 'password-invalid');
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
