<?php
require_once '../config/config.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize(trim($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Enter your email or username and password.";
    } else {
        $database = new Database();
        $db = $database->getConnection();
        $admin = null;

        try {
            $stmt = $db->prepare("SELECT id, username, password, fullname FROM admins WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            $admin = null;
        }

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_fullname'] = $admin['fullname'];
            $_SESSION['login_success'] = "Welcome back, " . $admin['fullname'] . "!";
            $_SESSION['login_time'] = date('Y-m-d H:i:s');

            try {
                $details = [
                    'event' => 'login',
                    'admin' => [
                        'id' => $admin['id'],
                        'username' => $admin['username'],
                        'fullname' => $admin['fullname']
                    ],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'time' => $_SESSION['login_time']
                ];
                logAdminAction($db, $admin['id'], 'admin_login', json_encode($details));
            } catch (Throwable $e) {
            }

            redirect('admin/dashboard.php');
        }

        try {
            $stmt = $db->prepare("SELECT id, fullname, password, room_number, email, status FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] === 'deactivated') {
                    $error = "Your account is currently inactive. Please contact the administrator.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_fullname'] = $user['fullname'];
                    $_SESSION['user_room'] = $user['room_number'];
                    $_SESSION['login_success'] = "Welcome back, " . $user['fullname'] . "!";
                    $_SESSION['login_time'] = date('Y-m-d H:i:s');
                    redirect('user/dashboard.php');
                }
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $stmt = $db->prepare("SELECT id, fullname, password, room_number, status FROM users WHERE room_number = :room_number");
            $stmt->execute([':room_number' => $username]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $matchedUser = null;

            foreach ($users as $candidate) {
                if (password_verify($password, $candidate['password'])) {
                    $matchedUser = $candidate;
                    break;
                }
            }

            if ($matchedUser) {
                if ($matchedUser['status'] === 'deactivated') {
                    $error = "Your account is currently inactive. Please contact the administrator.";
                } else {
                    $_SESSION['user_id'] = $matchedUser['id'];
                    $_SESSION['user_fullname'] = $matchedUser['fullname'];
                    $_SESSION['user_room'] = $matchedUser['room_number'];
                    $_SESSION['login_success'] = "Welcome back, " . $matchedUser['fullname'] . "!";
                    $_SESSION['login_time'] = date('Y-m-d H:i:s');
                    redirect('user/dashboard.php');
                }
            } else {
                $error = "Invalid email or password.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruin Borders | Sign In</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/neuromorphic-theme.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body.auth-page {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            color: #f7f2ff;
            background:
                radial-gradient(circle at 18% 18%, rgba(183, 116, 255, 0.3), transparent 26%),
                radial-gradient(circle at 82% 75%, rgba(118, 92, 255, 0.24), transparent 24%),
                linear-gradient(145deg, #12011f 0%, #240148 48%, #3c0678 100%);
            overflow: hidden;
        }

        .auth-stage {
            position: relative;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px 20px;
            isolation: isolate;
        }

        .auth-ribbon {
            position: absolute;
            border-radius: 999px;
            background: linear-gradient(180deg, rgba(213, 166, 255, 0.92), rgba(109, 56, 231, 0.18));
            box-shadow:
                inset 0 0 24px rgba(255, 255, 255, 0.22),
                0 18px 44px rgba(83, 27, 170, 0.35);
            opacity: 0.92;
            pointer-events: none;
            filter: saturate(1.15);
        }

        .auth-ribbon.one {
            width: 240px;
            height: 820px;
            left: -42px;
            bottom: -160px;
            transform: rotate(28deg);
        }

        .auth-ribbon.two {
            width: 220px;
            height: 560px;
            right: -8px;
            top: -26px;
            transform: rotate(58deg);
        }

        .auth-ribbon.three {
            width: 168px;
            height: 420px;
            right: 58px;
            bottom: -138px;
            transform: rotate(-32deg);
        }

        .auth-card {
            position: relative;
            width: min(100%, 470px);
            padding: 34px 34px 28px;
            border-radius: 38px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background:
                linear-gradient(180deg, rgba(57, 12, 106, 0.78), rgba(31, 5, 67, 0.84)),
                linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.03));
            box-shadow:
                0 30px 80px rgba(8, 0, 24, 0.5),
                inset 0 1px 0 rgba(255, 255, 255, 0.24),
                inset 0 -1px 0 rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(18px);
            overflow: hidden;
            z-index: 1;
        }

        .auth-card::before {
            content: "";
            position: absolute;
            inset: -10% auto auto -16%;
            width: 72%;
            height: 140%;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.14), transparent 45%);
            transform: rotate(18deg);
            pointer-events: none;
        }

        .brand-lockup {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            margin-bottom: 22px;
            text-align: center;
        }

        .brand-mark {
            position: relative;
            width: 72px;
            height: 72px;
        }

        .brand-mark span {
            position: absolute;
            left: 50%;
            width: 26px;
            height: 44px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.55);
            background: linear-gradient(180deg, rgba(180, 147, 255, 0.95), rgba(78, 38, 190, 0.55));
            box-shadow:
                inset 0 1px 8px rgba(255, 255, 255, 0.4),
                0 14px 26px rgba(40, 6, 88, 0.35);
            transform-origin: center;
        }

        .brand-mark span:first-child {
            top: 6px;
            transform: translateX(-50%) rotate(42deg);
        }

        .brand-mark span:last-child {
            top: 30px;
            transform: translateX(-50%) rotate(42deg);
        }

        .brand-name {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 600;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            color: #ffffff;
            text-shadow: 0 6px 18px rgba(10, 3, 26, 0.45);
        }

        .brand-caption {
            margin: 0;
            font-size: 0.88rem;
            color: rgba(37, 49, 66, 0.78);
            letter-spacing: 0.06em;
        }

        .auth-copy {
            position: relative;
            z-index: 1;
            text-align: center;
            margin-bottom: 24px;
        }

        .auth-eyebrow {
            display: inline-block;
            margin-bottom: 10px;
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(37, 49, 66, 0.72);
        }

        .auth-copy h1 {
            margin: 0 0 10px;
            font-size: clamp(2rem, 5vw, 2.6rem);
            line-height: 1.05;
            color: #253142;
            text-shadow: none;
        }

        .auth-copy p {
            margin: 0;
            color: rgba(37, 49, 66, 0.8);
            font-size: 0.98rem;
            line-height: 1.65;
        }

        .message {
            position: relative;
            z-index: 1;
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 18px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            font-size: 0.93rem;
            line-height: 1.5;
        }

        .message.error {
            background: rgba(163, 30, 72, 0.32);
            color: #ffe4ef;
        }

        .auth-form {
            position: relative;
            z-index: 1;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-size: 0.98rem;
            color: #253142;
        }

        .input-shell {
            position: relative;
        }

        .auth-form .form-control {
            width: 100%;
            padding: 16px 18px;
            border-radius: 18px !important;
            border: 1.5px solid rgba(255, 255, 255, 0.72) !important;
            background: rgba(18, 5, 42, 0.42) !important;
            color: #ffffff !important;
            box-shadow:
                inset 0 1px 14px rgba(255, 255, 255, 0.08),
                inset 0 -8px 18px rgba(5, 0, 16, 0.14) !important;
        }

        .auth-form .form-control::placeholder {
            color: rgba(235, 228, 250, 0.55);
        }

        .auth-form .form-control:focus {
            border-color: rgba(240, 231, 255, 0.95) !important;
            box-shadow:
                inset 0 1px 14px rgba(255, 255, 255, 0.1),
                0 0 0 4px rgba(196, 165, 255, 0.14) !important;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 14px;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            border: 0;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.08);
            color: rgba(250, 247, 255, 0.86);
            cursor: pointer;
        }

        .password-toggle:hover {
            background: rgba(255, 255, 255, 0.14);
        }

        .support-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin: 4px 0 18px;
            font-size: 0.92rem;
        }

        .support-row span,
        .support-row a {
            color: rgba(37, 49, 66, 0.82);
            text-decoration: none;
        }

        .support-row a:hover {
            color: #253142;
        }

        .btn-login {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 17px 20px;
            border: 0;
            border-radius: 20px;
            background: linear-gradient(180deg, #d5acff 0%, #b183ff 52%, #9d74f0 100%);
            color: #2b0b56;
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            cursor: pointer;
            box-shadow:
                inset 0 1px 0 rgba(255, 255, 255, 0.75),
                0 18px 35px rgba(107, 53, 221, 0.34);
        }

        .btn-login:hover {
            transform: translateY(-2px);
        }

        .auth-footer {
            position: relative;
            z-index: 1;
            margin-top: 24px;
            text-align: center;
        }

        .auth-footer p {
            margin: 0 0 8px;
            color: rgba(37, 49, 66, 0.82);
        }

        .auth-footer a {
            color: #253142;
            font-weight: 700;
            text-decoration: none;
        }

        .auth-footer a:hover {
            color: #4f7cff;
        }

        .auth-footer .helper {
            font-size: 0.88rem;
            color: rgba(98, 114, 134, 0.9);
        }

        body.auth-page:not(.dark-mode) .brand-name {
            color: #365ecf;
            text-shadow: none;
        }

        body.auth-page:not(.dark-mode) .message {
            border-color: rgba(37, 49, 66, 0.12);
        }

        body.auth-page:not(.dark-mode) .message.error {
            background: rgba(216, 93, 103, 0.12);
            color: #8b1e2d;
        }

        body.auth-page:not(.dark-mode) .auth-form .form-control {
            background: rgba(115, 126, 147, 0.22) !important;
            border-color: rgba(98, 114, 134, 0.28) !important;
            color: #253142 !important;
            box-shadow:
                inset 0 1px 14px rgba(255, 255, 255, 0.18),
                inset 0 -8px 18px rgba(98, 114, 134, 0.08) !important;
        }

        body.auth-page:not(.dark-mode) .auth-form .form-control::placeholder {
            color: rgba(98, 114, 134, 0.72);
        }

        body.auth-page:not(.dark-mode) .auth-form .form-control:focus {
            border-color: rgba(79, 124, 255, 0.62) !important;
            box-shadow:
                inset 0 1px 14px rgba(255, 255, 255, 0.2),
                0 0 0 4px rgba(79, 124, 255, 0.14) !important;
        }

        body.auth-page:not(.dark-mode) .password-toggle {
            background: rgba(98, 114, 134, 0.14);
            color: rgba(37, 49, 66, 0.78);
        }

        body.auth-page:not(.dark-mode) .password-toggle:hover {
            background: rgba(79, 124, 255, 0.14);
            color: #253142;
        }

        body.auth-page.dark-mode .brand-caption {
            color: rgba(243, 235, 255, 0.76);
        }

        body.auth-page.dark-mode .auth-eyebrow {
            color: rgba(231, 221, 255, 0.82);
        }

        body.auth-page.dark-mode .auth-copy h1 {
            color: #ffffff;
            text-shadow: 0 10px 26px rgba(8, 0, 28, 0.35);
        }

        body.auth-page.dark-mode .auth-copy p {
            color: rgba(241, 234, 255, 0.8);
        }

        body.auth-page.dark-mode .form-group label {
            color: #f7f1ff;
        }

        body.auth-page.dark-mode .support-row span,
        body.auth-page.dark-mode .support-row a {
            color: rgba(242, 236, 255, 0.82);
        }

        body.auth-page.dark-mode .support-row a:hover {
            color: #ffffff;
        }

        body.auth-page.dark-mode .auth-footer p {
            color: rgba(246, 239, 255, 0.82);
        }

        body.auth-page.dark-mode .auth-footer a {
            color: #ffffff;
        }

        body.auth-page.dark-mode .auth-footer a:hover {
            color: #ead8ff;
        }

        body.auth-page.dark-mode .auth-footer .helper {
            color: rgba(231, 220, 255, 0.64);
        }

        @media (max-width: 640px) {
            .auth-stage {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px 14px;
            }

            .auth-card {
                width: min(100%, 420px);
                margin: 0 auto;
                padding: 28px 22px 24px;
                border-radius: 30px;
            }

            .auth-ribbon.one {
                left: -86px;
            }

            .auth-ribbon.two {
                right: -70px;
            }

            .auth-copy,
            .auth-form,
            .auth-footer {
                width: 100%;
                margin-left: auto;
                margin-right: auto;
            }

            .support-row {
                flex-direction: column;
                align-items: center;
                justify-content: center;
                text-align: center;
            }
        }
    </style>
    <script src="../assets/js/neuromorphic-theme.js" defer></script>
</head>
<body class="auth-page">
    <main class="auth-stage">
        <span class="auth-ribbon one" aria-hidden="true"></span>
        <span class="auth-ribbon two" aria-hidden="true"></span>
        <span class="auth-ribbon three" aria-hidden="true"></span>

        <section class="auth-card login-container" aria-label="Login form">
            <div class="brand-lockup">
                <div class="brand-mark" aria-hidden="true">
                    <span></span>
                    <span></span>
                </div>
                <p class="brand-name">Ruin Borders</p>
                <p class="brand-caption">Boarder Payment Portal</p>
            </div>

            <div class="auth-copy">
                <span class="auth-eyebrow">Welcome back</span>
                <h1>Sign in to your portal</h1>
                <p>Review your payment status, upload receipts, and stay updated with announcements in one place.</p>
            </div>

            <?php if ($error): ?>
                <div class="message error">
                    <i class="fas fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="username">Email address or username</label>
                    <div class="input-shell">
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-control"
                            placeholder="Enter your email or username"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            autocomplete="username"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-shell">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="password-toggle" id="passwordToggle" aria-label="Show password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="support-row">
                    <a href="#" onclick="toggleDarkMode(event)" id="darkModeBtn" style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-moon"></i> <span>Switch to Dark Mode</span>
                    </a>
                    <a href="signup.php">Create an account</a>
                </div>

                <button type="submit" class="btn-login">
                    <span>Login</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="auth-footer">
                <p>New to Ruin Borders? <a href="signup.php">Sign Up</a></p>
                <p class="helper">Room assignment and account activation are handled by the administrator.</p>
            </div>
        </section>
    </main>

    <script>
        (function () {
            var passwordInput = document.getElementById('password');
            var passwordToggle = document.getElementById('passwordToggle');

            if (passwordInput && passwordToggle) {
                passwordToggle.addEventListener('click', function () {
                    var isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';
                    passwordToggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                    passwordToggle.innerHTML = isPassword
                        ? '<i class="fas fa-eye-slash"></i>'
                        : '<i class="fas fa-eye"></i>';
                });
            }

            // Dark Mode Logic
            function updateDarkModeText() {
                const isDark = document.body.classList.contains('dark-mode');
                const btnText = document.querySelector('#darkModeBtn span');
                const btnIcon = document.querySelector('#darkModeBtn i');
                if (btnText) {
                    btnText.textContent = isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode';
                    btnIcon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
                }
            }

            window.toggleDarkMode = function(e) {
                if(e) e.preventDefault();
                document.body.classList.toggle('dark-mode');
                const isDark = document.body.classList.contains('dark-mode');
                localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
                updateDarkModeText();
            };

            if (localStorage.getItem('darkMode') === 'enabled') {
                document.body.classList.add('dark-mode');
            }
            updateDarkModeText();
        }());
    </script>
</body>
</html>
