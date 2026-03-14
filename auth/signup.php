<?php
require_once '../config/config.php';

if (isLoggedIn()) {
    redirect('user/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = sanitize(trim($_POST['fullname'] ?? ''));
    $email = sanitize(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($fullname === '' || $email === '' || $password === '' || $confirm_password === '') {
        $error = "Complete all required fields before continuing.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email address.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        if (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = "Password must contain at least one uppercase letter.";
        } elseif (!preg_match('/[a-z]/', $password)) {
            $error = "Password must contain at least one lowercase letter.";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = "Password must contain at least one number.";
        } elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $error = "Password must contain at least one special character.";
        } else {
            $database = new Database();
            $db = $database->getConnection();

            $check_query = "SELECT id FROM users WHERE email = :email";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                $error = "That email is already registered. Sign in instead or use a different one.";
            } else {
                $room_number = "N/A";
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                try {
                    $insert_query = "INSERT INTO users (fullname, email, password, room_number, gender) VALUES (:fullname, :email, :password, :room_number, 'Other')";
                    $insert_stmt = $db->prepare($insert_query);
                    $insert_stmt->bindParam(':fullname', $fullname);
                    $insert_stmt->bindParam(':email', $email);
                    $insert_stmt->bindParam(':password', $hashed_password);
                    $insert_stmt->bindParam(':room_number', $room_number);

                    if ($insert_stmt->execute()) {
                        $success = "Account created successfully. Wait for an administrator to assign your room, then <a href='login.php'>sign in here</a>.";
                    } else {
                        $error = "Unable to create your account right now. Please try again.";
                    }
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'email') !== false) {
                        $error = "The database still needs the user email field. Please contact the administrator.";
                    } else {
                        $error = "Unable to create your account right now. Please try again.";
                    }
                }
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
    <title>Ruin Borders | Create Account</title>
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
                radial-gradient(circle at 14% 16%, rgba(196, 135, 255, 0.28), transparent 24%),
                radial-gradient(circle at 88% 74%, rgba(120, 88, 255, 0.24), transparent 22%),
                linear-gradient(150deg, #12011f 0%, #28024d 46%, #421185 100%);
        }

        .auth-stage {
            position: relative;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 36px 20px;
            isolation: isolate;
            overflow: hidden;
        }

        .auth-ribbon {
            position: absolute;
            border-radius: 999px;
            background: linear-gradient(180deg, rgba(218, 177, 255, 0.94), rgba(108, 57, 230, 0.16));
            box-shadow:
                inset 0 0 24px rgba(255, 255, 255, 0.22),
                0 18px 44px rgba(83, 27, 170, 0.34);
            opacity: 0.92;
            pointer-events: none;
        }

        .auth-ribbon.one {
            width: 240px;
            height: 860px;
            left: -64px;
            bottom: -220px;
            transform: rotate(30deg);
        }

        .auth-ribbon.two {
            width: 220px;
            height: 560px;
            right: -18px;
            top: -38px;
            transform: rotate(56deg);
        }

        .auth-ribbon.three {
            width: 190px;
            height: 480px;
            right: 72px;
            bottom: -164px;
            transform: rotate(-34deg);
        }

        .auth-card {
            position: relative;
            width: min(100%, 540px);
            max-height: min(92vh, 920px);
            padding: 34px 34px 28px;
            overflow: auto;
            border-radius: 38px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            background:
                linear-gradient(180deg, rgba(56, 12, 104, 0.78), rgba(28, 5, 62, 0.86)),
                linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.03));
            box-shadow:
                0 30px 80px rgba(8, 0, 24, 0.52),
                inset 0 1px 0 rgba(255, 255, 255, 0.24),
                inset 0 -1px 0 rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(18px);
            z-index: 1;
        }

        .auth-card::before {
            content: "";
            position: absolute;
            inset: -12% auto auto -18%;
            width: 72%;
            height: 140%;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.13), transparent 46%);
            transform: rotate(18deg);
            pointer-events: none;
        }

        .auth-card::-webkit-scrollbar {
            width: 8px;
        }

        .auth-card::-webkit-scrollbar-thumb {
            background: rgba(216, 194, 255, 0.42);
            border-radius: 999px;
        }

        .brand-lockup {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
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
            margin-bottom: 22px;
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
            font-size: clamp(2rem, 4.6vw, 2.7rem);
            line-height: 1.08;
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

        .message.success {
            background: rgba(39, 176, 125, 0.14);
            color: #1e6e53;
        }

        .message.success a {
            color: #1a4fb8;
            font-weight: 700;
        }

        .auth-note {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 10px;
            align-items: flex-start;
            margin-bottom: 22px;
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(79, 124, 255, 0.08);
            border: 1px solid rgba(79, 124, 255, 0.14);
            color: rgba(37, 49, 66, 0.86);
            line-height: 1.55;
            font-size: 0.92rem;
        }

        .auth-note i {
            margin-top: 3px;
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
            margin: 4px 0 20px;
            font-size: 0.92rem;
            color: #253142;
        }

        .darkModeLink {
            color: rgba(37, 49, 66, 0.82) !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .darkModeLink:hover {
            color: #253142 !important;
        }

        .auth-form .form-control {
            width: 100%;
            padding: 16px 18px;
            border-radius: 18px !important;
            border: 1.5px solid rgba(98, 114, 134, 0.28) !important;
            background: rgba(115, 126, 147, 0.22) !important;
            color: #253142 !important;
            box-shadow:
                inset 0 1px 14px rgba(255, 255, 255, 0.18),
                inset 0 -8px 18px rgba(98, 114, 134, 0.08) !important;
        }

        .auth-form .form-control::placeholder {
            color: rgba(98, 114, 134, 0.72);
        }

        .auth-form .form-control:focus {
            border-color: rgba(79, 124, 255, 0.62) !important;
            box-shadow:
                inset 0 1px 14px rgba(255, 255, 255, 0.2),
                0 0 0 4px rgba(79, 124, 255, 0.14) !important;
        }

        .password-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .password-requirements {
            display: none;
            margin-top: 10px;
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(79, 124, 255, 0.08) !important;
            border: 1px solid rgba(79, 124, 255, 0.14) !important;
            color: rgba(37, 49, 66, 0.84) !important;
            box-shadow: none !important;
            font-size: 0.9rem;
        }

        .password-requirements.show {
            display: block;
        }

        .password-requirements.error {
            border-color: rgba(255, 167, 191, 0.4) !important;
            background: rgba(118, 19, 54, 0.26) !important;
        }

        .password-requirements strong {
            display: block;
            margin-bottom: 8px;
            color: #253142;
        }

        .password-requirements ul {
            margin: 0;
            padding-left: 18px;
        }

        .password-requirements li {
            margin: 4px 0;
        }

        .btn-signup {
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

        .btn-signup:hover {
            transform: translateY(-2px);
        }

        .auth-footer {
            position: relative;
            z-index: 1;
            margin-top: 22px;
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

        body.auth-page.dark-mode .message.success {
            background: rgba(31, 128, 96, 0.28);
            color: #e8fff7;
        }

        body.auth-page.dark-mode .message.success a {
            color: #ffffff;
        }

        body.auth-page.dark-mode .auth-note {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15);
            color: rgba(244, 238, 255, 0.86);
        }

        body.auth-page.dark-mode .form-group label {
            color: #f7f1ff;
        }

        body.auth-page.dark-mode .darkModeLink {
            color: rgba(242, 236, 255, 0.82) !important;
        }

        body.auth-page.dark-mode .darkModeLink:hover {
            color: #ffffff !important;
        }

        body.auth-page.dark-mode .auth-form .form-control {
            border-color: rgba(255, 255, 255, 0.72) !important;
            background: rgba(18, 5, 42, 0.42) !important;
            color: #ffffff !important;
            box-shadow:
                inset 0 1px 14px rgba(255, 255, 255, 0.08),
                inset 0 -8px 18px rgba(5, 0, 16, 0.14) !important;
        }

        body.auth-page.dark-mode .auth-form .form-control::placeholder {
            color: rgba(235, 228, 250, 0.55);
        }

        body.auth-page.dark-mode .auth-form .form-control:focus {
            border-color: rgba(240, 231, 255, 0.95) !important;
            box-shadow:
                inset 0 1px 14px rgba(255, 255, 255, 0.1),
                0 0 0 4px rgba(196, 165, 255, 0.14) !important;
        }

        body.auth-page.dark-mode .password-requirements {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
            color: rgba(244, 238, 255, 0.84) !important;
        }

        body.auth-page.dark-mode .password-requirements strong {
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

            .auth-copy,
            .auth-note,
            .auth-form,
            .auth-footer {
                width: 100%;
                margin-left: auto;
                margin-right: auto;
            }

            .password-row {
                grid-template-columns: 1fr;
                gap: 0;
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

        <section class="auth-card signup-container" aria-label="Signup form">
            <div class="brand-lockup">
                <div class="brand-mark" aria-hidden="true">
                    <span></span>
                    <span></span>
                </div>
                <p class="brand-name">Ruin Borders</p>
                <p class="brand-caption">Boarder Payment Portal</p>
            </div>

            <div class="auth-copy">
                <span class="auth-eyebrow">Create account</span>
                <h1>Join the member portal</h1>
                <p>Set up your account details. Room assignment happens after an administrator reviews your account.</p>
            </div>

            <?php if ($error): ?>
                <div class="message error">
                    <i class="fas fa-circle-exclamation"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="message success">
                    <i class="fas fa-circle-check"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="auth-note">
                <i class="fas fa-sparkles" aria-hidden="true"></i>
                <span>The sign-up form no longer asks for a room number. That part is assigned for you inside the system after approval.</span>
            </div>

            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="fullname">Full name</label>
                    <input
                        type="text"
                        id="fullname"
                        name="fullname"
                        class="form-control"
                        placeholder="Enter your full name"
                        value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>"
                        autocomplete="name"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="Enter your email"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="password-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Create your password"
                            autocomplete="new-password"
                            required
                        >
                        <div class="password-requirements" id="password-requirements">
                            <strong>Password checklist</strong>
                            <ul>
                                <li>At least 8 characters</li>
                                <li>One uppercase letter</li>
                                <li>One lowercase letter</li>
                                <li>One number</li>
                                <li>One special character</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm password</label>
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control"
                            placeholder="Confirm your password"
                            autocomplete="new-password"
                            required
                        >
                    </div>
                </div>

                <button type="submit" class="btn-signup">
                    <span>Create Account</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="auth-footer">
                <p style="margin-bottom: 12px;">
                    <a href="#" onclick="toggleDarkMode(event)" id="darkModeBtn" class="darkModeLink" style="justify-content: center;">
                        <i class="fas fa-moon"></i> <span>Switch to Dark Mode</span>
                    </a>
                </p>
                <p>Already registered? <a href="login.php">Login</a></p>
                <p class="helper">Once approved, you can immediately use your account email to access the portal.</p>
            </div>
        </section>
    </main>

    <script>
        (function () {
            var passwordInput = document.getElementById('password');
            var confirmPasswordInput = document.getElementById('confirm_password');
            var passwordRequirements = document.getElementById('password-requirements');
            var form = document.querySelector('.auth-form');

            function validatePassword(password) {
                var errors = [];
                if (password.length < 8) errors.push('At least 8 characters');
                if (!/[A-Z]/.test(password)) errors.push('One uppercase letter');
                if (!/[a-z]/.test(password)) errors.push('One lowercase letter');
                if (!/[0-9]/.test(password)) errors.push('One number');
                if (!/[^A-Za-z0-9]/.test(password)) errors.push('One special character');
                return errors;
            }

            function renderPasswordState() {
                var password = passwordInput.value;
                var errors = validatePassword(password);
                if (password.length === 0) {
                    passwordRequirements.classList.remove('show', 'error');
                    passwordInput.classList.remove('password-valid', 'password-invalid');
                    return;
                }
                passwordRequirements.classList.add('show');
                if (errors.length === 0) {
                    passwordRequirements.classList.remove('error');
                    passwordInput.classList.remove('password-invalid');
                    passwordInput.classList.add('password-valid');
                } else {
                    passwordRequirements.classList.add('error');
                    passwordInput.classList.remove('password-valid');
                    passwordInput.classList.add('password-invalid');
                    var list = passwordRequirements.querySelector('ul');
                    list.innerHTML = '';
                    errors.forEach(function (error) {
                        var item = document.createElement('li');
                        item.textContent = error;
                        list.appendChild(item);
                    });
                }
            }

            function renderConfirmState() {
                if (confirmPasswordInput.value.length === 0) {
                    confirmPasswordInput.classList.remove('password-valid', 'password-invalid');
                    return;
                }
                if (passwordInput.value === confirmPasswordInput.value && validatePassword(passwordInput.value).length === 0) {
                    confirmPasswordInput.classList.remove('password-invalid');
                    confirmPasswordInput.classList.add('password-valid');
                } else {
                    confirmPasswordInput.classList.remove('password-valid');
                    confirmPasswordInput.classList.add('password-invalid');
                }
            }

            passwordInput.addEventListener('input', function () {
                renderPasswordState();
                renderConfirmState();
            });

            confirmPasswordInput.addEventListener('input', renderConfirmState);

            form.addEventListener('submit', function (event) {
                var passwordErrors = validatePassword(passwordInput.value);
                renderPasswordState();
                renderConfirmState();
                if (passwordErrors.length > 0 || passwordInput.value !== confirmPasswordInput.value) {
                    event.preventDefault();
                }
            });

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
