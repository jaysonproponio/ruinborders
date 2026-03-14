<?php
require_once '../config/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $fullname = sanitize($_POST['fullname']);
    
    // Validation
    if (empty($username) || empty($password) || empty($confirm_password) || empty($fullname)) {
        $error = "Please fill in all fields";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters long";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        // Check if username already exists
        $check_query = "SELECT id FROM admins WHERE username = :username";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':username', $username);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $error = "Username already exists";
        } else {
            // Create new admin
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO admins (username, password, fullname) VALUES (:username, :password, :fullname)";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(':username', $username);
            $insert_stmt->bindParam(':password', $hashed_password);
            $insert_stmt->bindParam(':fullname', $fullname);
            
            if ($insert_stmt->execute()) {
                $success = "Admin account created successfully! You can now login.";
            } else {
                $error = "Failed to create admin account. Please try again.";
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
    <title>Ruin Borders - Admin Registration</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .background-animation {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }

        .floating-shapes {
            position: absolute;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .floating-shapes:nth-child(1) {
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-shapes:nth-child(2) {
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }

        .floating-shapes:nth-child(3) {
            bottom: 20%;
            left: 20%;
            animation-delay: 4s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 2;
            animation: slideUp 0.8s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            color: #333;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo p {
            color: #666;
            font-size: 0.9rem;
        }

        .admin-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 10px;
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

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            z-index: 3;
        }

        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fff;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .password-strength {
            margin-top: 5px;
            font-size: 0.8rem;
        }

        .strength-weak { color: #e74c3c; }
        .strength-medium { color: #f39c12; }
        .strength-strong { color: #27ae60; }

        .btn-register {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #c33;
        }

        .success-message {
            background: #efe;
            color: #27ae60;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #27ae60;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #764ba2;
        }

        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 1.2rem;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #764ba2;
        }
    </style>
    <link rel="stylesheet" href="../assets/css/neuromorphic-theme.css">
    <script src="../assets/js/neuromorphic-theme.js" defer></script>
</head>
<body>
    <div class="background-animation">
        <div class="floating-shapes"></div>
        <div class="floating-shapes"></div>
        <div class="floating-shapes"></div>
    </div>

    <div class="register-container">
        <a href="login.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
        </a>

        <div class="logo">
            <h1><i class="fas fa-user-shield"></i> Ruin Borders</h1>
            <p>Admin Registration</p>
            <div class="admin-badge">
                <i class="fas fa-crown"></i> Administrator Access
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="fullname">Full Name</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="fullname" name="fullname" class="form-control" 
                           placeholder="Enter your full name" required value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <i class="fas fa-at"></i>
                    <input type="text" id="username" name="username" class="form-control" 
                           placeholder="Enter username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter password" required>
                </div>
                <div id="password-strength" class="password-strength"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                           placeholder="Confirm password" required>
                </div>
            </div>

            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Create Admin Account
            </button>
        </form>

        <div class="login-link">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const strengthIndicator = document.getElementById('password-strength');

            // Password strength checker
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strength = checkPasswordStrength(password);
                
                strengthIndicator.textContent = strength.text;
                strengthIndicator.className = 'password-strength ' + strength.class;
            });

            // Password confirmation checker
            confirmPasswordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                const confirmPassword = this.value;
                
                if (confirmPassword && password !== confirmPassword) {
                    this.style.borderColor = '#e74c3c';
                    strengthIndicator.textContent = 'Passwords do not match';
                    strengthIndicator.className = 'password-strength strength-weak';
                } else if (confirmPassword && password === confirmPassword) {
                    this.style.borderColor = '#27ae60';
                    strengthIndicator.textContent = 'Passwords match';
                    strengthIndicator.className = 'password-strength strength-strong';
                } else {
                    this.style.borderColor = '#e1e5e9';
                }
            });

            function checkPasswordStrength(password) {
                let score = 0;
                let feedback = [];

                if (password.length >= 6) score++;
                else feedback.push('at least 6 characters');

                if (/[a-z]/.test(password)) score++;
                else feedback.push('lowercase letters');

                if (/[A-Z]/.test(password)) score++;
                else feedback.push('uppercase letters');

                if (/[0-9]/.test(password)) score++;
                else feedback.push('numbers');

                if (/[^A-Za-z0-9]/.test(password)) score++;
                else feedback.push('special characters');

                if (score < 2) {
                    return { text: 'Weak password. Add: ' + feedback.join(', '), class: 'strength-weak' };
                } else if (score < 4) {
                    return { text: 'Medium strength password', class: 'strength-medium' };
                } else {
                    return { text: 'Strong password', class: 'strength-strong' };
                }
            }
        });
    </script>
</body>
</html>
