<?php
require_once '../config/config.php';

if (!isAdmin()) {
    redirect('auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

try {
    $stmt = $db->prepare("SELECT id, username, fullname, created_at FROM admins WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $_SESSION['admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $admin = false;
}

if (!$admin) {
    redirect('auth/login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullname = sanitize(trim($_POST['fullname'] ?? ''));
        $username = sanitize(trim($_POST['username'] ?? ''));

        if ($fullname === '' || $username === '') {
            $error = 'Full name and username are required.';
        } else {
            try {
                $check = $db->prepare("SELECT id FROM admins WHERE username = :username AND id != :id LIMIT 1");
                $check->execute([':username' => $username, ':id' => $_SESSION['admin_id']]);

                if ($check->fetch()) {
                    $error = 'That username is already in use.';
                } else {
                    $update = $db->prepare("UPDATE admins SET fullname = :fullname, username = :username WHERE id = :id");
                    $update->execute([
                        ':fullname' => $fullname,
                        ':username' => $username,
                        ':id' => $_SESSION['admin_id']
                    ]);

                    $_SESSION['admin_fullname'] = $fullname;
                    $_SESSION['admin_username'] = $username;
                    $message = 'Profile updated successfully.';
                    logAdminAction($db, $_SESSION['admin_id'], 'update_admin_profile', json_encode(['fullname' => $fullname, 'username' => $username]));
                }
            } catch (Throwable $e) {
                $error = 'Unable to update your profile right now.';
            }
        }
    }

    if ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($current_password === '' || $new_password === '' || $confirm_password === '') {
            $error = 'Complete all password fields to change your password.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_password) < 8) {
            $error = 'New password must be at least 8 characters long.';
        } else {
            try {
                $pw = $db->prepare("SELECT password FROM admins WHERE id = :id LIMIT 1");
                $pw->execute([':id' => $_SESSION['admin_id']]);
                $admin_password = $pw->fetchColumn();

                if (!$admin_password || !password_verify($current_password, $admin_password)) {
                    $error = 'Current password is incorrect.';
                } else {
                    $update = $db->prepare("UPDATE admins SET password = :password WHERE id = :id");
                    $update->execute([
                        ':password' => password_hash($new_password, PASSWORD_DEFAULT),
                        ':id' => $_SESSION['admin_id']
                    ]);

                    $message = 'Password updated successfully.';
                    logAdminAction($db, $_SESSION['admin_id'], 'change_admin_password');
                }
            } catch (Throwable $e) {
                $error = 'Unable to change your password right now.';
            }
        }
    }

    try {
        $stmt = $db->prepare("SELECT id, username, fullname, created_at FROM admins WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['admin_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Ruin Boarders</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
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
        .sidebar-menu {
            list-style: none;
        }
        .sidebar-menu li {
            margin-bottom: 5px;
            position: relative;
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
        .main-content { margin-left: 250px; padding: 30px; min-height: 100vh; }
        .header, .card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); border-radius: 18px; padding: 25px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); margin-bottom: 24px; }
        .header h1, .card-title { color: #333; }
        .header p { color: #666; margin-top: 8px; }
        .content-grid { display: grid; grid-template-columns: minmax(280px, 320px) 1fr; gap: 24px; }
        .card-header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #e1e5e9; }
        .profile-summary { display: flex; flex-direction: column; align-items: center; text-align: center; margin-bottom: 20px; }
        .user-avatar { width: 86px; height: 86px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 32px; margin-bottom: 16px; }
        .summary-title { color: #333; margin-bottom: 6px; }
        .summary-subtitle { color: #666; margin-bottom: 18px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 500; }
        .form-control { width: 100%; padding: 12px 14px; border: 1px solid #ddd; border-radius: 12px; background: #fff; color: #333; }
        .form-control[readonly] { background: #f8f9fa; color: #666; }
        .section-title { margin: 6px 0 14px; color: #333; }
        .section-help { margin-bottom: 16px; color: #666; font-size: 0.92rem; }
        .password-block { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e1e5e9; }
        .btn-row { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 8px; }
        .btn { padding: 12px 18px; border: 0; border-radius: 12px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
        .alert { padding: 14px 16px; border-radius: 12px; margin-bottom: 18px; }
        .alert-success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .alert-danger { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .sidebar {
            padding-bottom: 90px;
        }
        .sidebar-profile {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-top: 1px solid #e1e5e9;
            transition: all 0.3s ease;
        }
        .profile-info {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 10px;
            border-radius: 10px;
            transition: background 0.2s;
        }
        .profile-info:hover {
            background: rgba(0, 0, 0, 0.05);
        }
        .profile-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }
        .profile-details {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .profile-name {
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }
        .profile-role {
            font-size: 0.8rem;
            color: #666;
        }
        .profile-dropdown {
            position: absolute;
            bottom: 85px;
            left: 20px;
            right: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            z-index: 1001;
        }
        .profile-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .profile-dropdown a {
            padding: 12px 20px;
            color: #444;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .profile-dropdown a:hover {
            background: #f8f9fa;
            color: #667eea;
        }
        .profile-dropdown a.logout-text {
            color: #dc3545;
            border-top: 1px solid #eee;
        }
        .profile-dropdown a.logout-text:hover {
            background: #fff5f5;
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
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 20px; }
            .content-grid, .form-row { grid-template-columns: 1fr; }
            .mobile-menu-toggle { display: block; }
        }
        .dark-mode { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); }
        .dark-mode .sidebar { background: rgba(25, 25, 35, 0.95); border-right: 1px solid #333; }
        .dark-mode .sidebar-header { border-bottom-color: #333; }
        .dark-mode .sidebar-menu a { color: #aaa; }
        .dark-mode .sidebar-menu a:hover,
        .dark-mode .sidebar-menu a.active { background: linear-gradient(135deg, #3f2b96, #a8c0ff); color: white; }
        .dark-mode .sidebar-profile { background: rgba(25, 25, 35, 0.95); border-top-color: #333; }
        .dark-mode .header, .dark-mode .card { background: rgba(30, 30, 45, 0.95); color: #fff; border: 1px solid #333; }
        .dark-mode .header h1, .dark-mode .card-title, .dark-mode .summary-title, .dark-mode .form-group label, .dark-mode .section-title { color: #fff; }
        .dark-mode .header p, .dark-mode .summary-subtitle, .dark-mode .section-help { color: #bbb; }
        .dark-mode .card-header { border-bottom-color: #333; }
        .dark-mode .password-block { border-top-color: #333; }
        .dark-mode .form-control { background: #2a2a35; border-color: #444; color: #fff; }
        .dark-mode .form-control[readonly] { background: rgba(255, 255, 255, 0.06); color: #ddd; }
        .dark-mode .profile-info:hover { background: rgba(255, 255, 255, 0.05); }
        .dark-mode .profile-name { color: #fff; }
        .dark-mode .profile-role { color: #aaa; }
        .dark-mode .profile-dropdown { background: #2a2a35; border: 1px solid #444; }
        .dark-mode .profile-dropdown a { color: #ddd; }
        .dark-mode .profile-dropdown a.logout-text { color: #ff6b6b; border-top-color: #444; }
        .dark-mode .profile-dropdown a:hover { background: #3a3a45; color: #a8c0ff; }
        .dark-mode .profile-dropdown a.logout-text:hover { background: rgba(220, 53, 69, 0.2); }
        .dark-mode .mobile-menu-toggle { background: rgba(42, 42, 53, 0.96); color: #fff; }
    </style>
    <link rel="stylesheet" href="../assets/css/neuromorphic-theme.css">
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <script src="../assets/js/neuromorphic-theme.js" defer></script>
</head>
<body>
    <?php $current_admin_page = 'profile'; ?>
    <?php require __DIR__ . '/includes/admin-sidebar.php'; ?>

    <div class="main-content">
        <div class="header">
            <h1>Admin Profile</h1>
            <p>Manage your account details and password.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Account Summary</h3>
                </div>
                <div class="profile-summary">
                    <div class="user-avatar"><?php echo htmlspecialchars(strtoupper(substr($admin['fullname'], 0, 1))); ?></div>
                    <h3 class="summary-title"><?php echo htmlspecialchars($admin['fullname']); ?></h3>
                    <p class="summary-subtitle">@<?php echo htmlspecialchars($admin['username']); ?></p>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <input type="text" class="form-control" value="Administrator" readonly>
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($admin['username']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label>Joined</label>
                    <input type="text" class="form-control" value="<?php echo $admin['created_at'] ? date('M d, Y', strtotime($admin['created_at'])) : 'N/A'; ?>" readonly>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Profile Details</h3>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fullname">Full Name</label>
                            <input type="text" id="fullname" name="fullname" class="form-control" value="<?php echo htmlspecialchars($admin['fullname']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                        </div>
                    </div>
                    <div class="btn-row">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                </form>

                <div class="password-block">
                    <h4 class="section-title">Change Password</h4>
                    <p class="section-help">Use your current password to set a new one.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
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
                        <div class="btn-row">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); }
        function toggleProfileDropdown(e) {
            if (e) e.stopPropagation();
            document.getElementById('profileDropdown').classList.toggle('show');
        }
        function toggleDarkMode(e) {
            if (e) e.preventDefault();
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
            updateDarkModeButton();
        }
        function updateDarkModeButton() {
            const isDark = document.body.classList.contains('dark-mode');
            const btnText = document.querySelector('#darkModeBtn span');
            const btnIcon = document.querySelector('#darkModeBtn i');
            if (btnText) {
                btnText.textContent = isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode';
            }
            if (btnIcon) {
                btnIcon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            }
        }
        document.addEventListener('DOMContentLoaded', function () {
            if (localStorage.getItem('darkMode') === 'enabled') {
                document.body.classList.add('dark-mode');
            }
            updateDarkModeButton();
        });
        document.addEventListener('click', function (e) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            if (window.innerWidth <= 768 && sidebar && toggle && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
        window.addEventListener('click', function () {
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            }
        });
    </script>
</body>
</html>
