<?php
session_start();
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/auth_functions.php';

// Simple authentication functions
function authenticateUser($email, $password) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Update last_login timestamp
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $updateStmt->execute([$user['id']]);
            
            return $user;
        }
        return false;
    } catch (Exception $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}

// Register new user
function registerUser($full_name, $username, $email, $password) {
    try {
        $pdo = getDB();
        
        // Check if email or username already exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $checkStmt->execute([$email, $username]);
        if ($checkStmt->fetch()) {
            return ['success' => false, 'message' => 'Email or username already exists.'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, is_active, created_at) VALUES (?, ?, ?, ?, TRUE, NOW())");
        $stmt->execute([$full_name, $username, $email, $hashedPassword]);
        
        return ['success' => true, 'message' => 'Account created successfully! You can now log in.'];
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

// Reset password
function resetPassword($email, $newPassword) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND is_active = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Email not found.'];
        }
        
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ?");
        $updateStmt->execute([$hashedPassword, $email]);
            
            return ['success' => true, 'message' => 'Password reset successfully. You can now log in with your new password.'];
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Password reset failed. Please try again.'];
    }
}

// Handle form submissions
$error_message = '';
$success_message = '';
$show_login_form = true;
$show_register_form = false;
$show_forgot_password_form = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error_message = 'Please fill in all fields.';
        } else {
                $user = authenticateUser($email, $password);
                if ($user) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['full_name'] = $user['full_name'];
                        
                        // Log successful login
                        logUserActivity($user['id'], 'login', 'User logged in successfully', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['action' => 'login']));
                        
                        header('Location: ./');
                        exit;
                } else {
                    $error_message = 'Invalid email or password.';
            }
        }
    } elseif ($action === 'register') {
        $full_name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error_message = 'Please fill in all fields.';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error_message = 'Password must be at least 6 characters long.';
        } else {
            $result = registerUser($full_name, $username, $email, $password);
                if ($result['success']) {
                    $success_message = $result['message'];
                    $show_login_form = true;
                $show_register_form = false;
                } else {
                    $error_message = $result['message'];
            }
        }
    } elseif ($action === 'forgot_password') {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error_message = 'Please enter your email address.';
        } else {
            // For now, just show a message. In a real app, you'd send a reset email
            $success_message = 'Password reset functionality is not implemented yet. Please contact support.';
                $show_login_form = true;
            $show_forgot_password_form = false;
        }
    }
}

// Handle form switching
if (isset($_GET['form'])) {
    $form = $_GET['form'];
    $show_login_form = ($form === 'login');
    $show_register_form = ($form === 'register');
    $show_forgot_password_form = ($form === 'forgot_password');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TraderEscape</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-tabs {
            display: flex;
            margin-bottom: 2rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 0.5rem;
        }

        .tab {
            flex: 1;
            padding: 1rem;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .tab.active {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
        }

        .tab:not(.active) {
            color: #9ca3af;
        }

        .tab:not(.active):hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #e5e7eb;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #3b82f6;
             background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        input::placeholder {
            color: #9ca3af;
        }

        button {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        button:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #059669, #047857);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .form-switch {
            text-align: center;
            margin-top: 1.5rem;
        }

        .form-switch a {
            color: #60a5fa;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .form-switch a:hover {
            color: #93c5fd;
        }

        .hidden {
            display: none;
        }

        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }

        .forgot-password a {
            color: #9ca3af;
            text-decoration: none;
                 font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #60a5fa;
         }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>TraderEscape</h1>
        </div>

        <div class="form-tabs">
            <div class="tab <?php echo $show_login_form ? 'active' : ''; ?>" onclick="showForm('login')">
                Login
            </div>
            <div class="tab <?php echo $show_register_form ? 'active' : ''; ?>" onclick="showForm('register')">
                Register
            </div>
                </div>

                <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <!-- Login Form -->
        <form method="POST" class="<?php echo $show_login_form ? '' : 'hidden'; ?>" id="loginForm">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password">
                    </div>

            <button type="submit">Login</button>
            
            <div class="forgot-password">
                <a href="#" onclick="showForm('forgot_password')">Forgot your password?</a>
                    </div>
                </form>

        <!-- Register Form -->
        <form method="POST" class="<?php echo $show_register_form ? '' : 'hidden'; ?>" id="registerForm">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required placeholder="Enter your full name">
                    </div>

                    <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Choose a username">
                    </div>

                    <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Create a password">
                    </div>

                    <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                    </div>

            <button type="submit">Create Account</button>
                </form>

                <!-- Forgot Password Form -->
        <form method="POST" class="<?php echo $show_forgot_password_form ? '' : 'hidden'; ?>" id="forgotPasswordForm">
                    <input type="hidden" name="action" value="forgot_password">
                    
                    <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
                    </div>

            <button type="submit">Reset Password</button>
            
            <div class="form-switch">
                <a href="#" onclick="showForm('login')">Back to Login</a>
                    </div>
                </form>
                        </div>
                        
     <script>
        function showForm(formType) {
            // Hide all forms
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('registerForm').classList.add('hidden');
            document.getElementById('forgotPasswordForm').classList.add('hidden');
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            
            // Show selected form
                if (formType === 'login') {
                document.getElementById('loginForm').classList.remove('hidden');
                document.querySelectorAll('.tab')[0].classList.add('active');
            } else if (formType === 'register') {
                document.getElementById('registerForm').classList.remove('hidden');
                document.querySelectorAll('.tab')[1].classList.add('active');
            } else if (formType === 'forgot_password') {
                document.getElementById('forgotPasswordForm').classList.remove('hidden');
            }
        }
    </script>
</body>
</html>