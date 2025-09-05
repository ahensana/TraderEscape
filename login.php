<?php
/**
 * Login/Signup Page for TraderEscape
 * Handles user authentication and registration
 */

// Start session
session_start();

// Include database functions
require_once 'includes/db_functions.php';

// Check if user is already logged in
if (isset($_SESSION['user_id']) && $_SESSION['user_id']) {
    header('Location: ./index.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                
                if (empty($email) || empty($password)) {
                    $error_message = 'Please fill in all fields.';
                } else {
                    try {
                        $pdo = getDB();
                        $stmt = $pdo->prepare("SELECT id, username, email, password_hash, full_name FROM users WHERE email = ? AND is_active = TRUE");
                        $stmt->execute([$email]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($user && password_verify($password, $user['password_hash'])) {
                            // Login successful
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            $_SESSION['full_name'] = $user['full_name'];
                            
                            // Update last login
                            $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                            $updateStmt->execute([$user['id']]);
                            
                            // Log activity
                            logUserActivity($user['id'], 'login', 'User logged in successfully', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                            
                            // Redirect to index page
                            header('Location: ./index.php');
                            exit();
                        } else {
                            $error_message = 'Invalid email or password.';
                        }
                    } catch (Exception $e) {
                        $error_message = 'Login failed. Please try again.';
                        error_log("Login error: " . $e->getMessage());
                    }
                }
                break;
                
            case 'register':
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                $full_name = trim($_POST['full_name'] ?? '');
                
                // Validation
                if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name)) {
                    $error_message = 'Please fill in all fields.';
                } elseif ($password !== $confirm_password) {
                    $error_message = 'Passwords do not match.';
                } elseif (strlen($password) < 6) {
                    $error_message = 'Password must be at least 6 characters long.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error_message = 'Please enter a valid email address.';
                } elseif (strlen($username) < 3) {
                    $error_message = 'Username must be at least 3 characters long.';
                } else {
                    try {
                        $pdo = getDB();
                        
                        // Check if username or email already exists
                        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                        $checkStmt->execute([$username, $email]);
                        
                        if ($checkStmt->fetch()) {
                            $error_message = 'Username or email already exists.';
                        } else {
                            // Hash password
                            $password_hash = password_hash($password, PASSWORD_DEFAULT);
                            
                            // Insert new user
                            $insertStmt = $pdo->prepare("
                                INSERT INTO users (username, email, password_hash, full_name, is_active, is_verified, created_at) 
                                VALUES (?, ?, ?, ?, TRUE, FALSE, NOW())
                            ");
                            
                            if ($insertStmt->execute([$username, $email, $password_hash, $full_name])) {
                                $user_id = $pdo->lastInsertId();
                                
                                // Log activity
                                logUserActivity($user_id, 'other', 'User registered successfully', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']);
                                
                                $success_message = 'Registration successful! Please log in.';
                                
                                // Clear form data
                                $_POST = array();
                            } else {
                                $error_message = 'Registration failed. Please try again.';
                            }
                        }
                    } catch (Exception $e) {
                        $error_message = 'Registration failed. Please try again.';
                        error_log("Registration error: " . $e->getMessage());
                    }
                }
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Login/Sign Up - The Trader's Escape</title>
    <meta name="description" content="Access your trading education account. Login or sign up to access premium educational content and advanced trading tools.">
    <meta name="keywords" content="trading login, trading signup, trading education, account access">
    <meta name="author" content="The Trader's Escape">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#3b82f6">
    <meta name="msapplication-TileColor" content="#3b82f6">
    
    <!-- PWA Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TraderEscape">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="The Trader's Escape">
    <meta name="msapplication-config" content="./browserconfig.xml">
    
    <!-- iPhone PWA Meta Tags -->
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-touch-fullscreen" content="yes">
    <meta name="apple-mobile-web-app-orientations" content="portrait">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TraderEscape">
    <meta name="msapplication-TileImage" content="./assets/logo.png">
    <meta name="msapplication-TileColor" content="#3b82f6">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://thetradersescape.com/login.php">
    <meta property="og:title" content="Login/Sign Up - The Trader's Escape">
    <meta property="og:description" content="Access your trading education account. Login or sign up to access premium educational content and advanced trading tools.">
    <meta property="og:image" content="./assets/logo.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="The Trader's Escape">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://thetradersescape.com/login.php">
    <meta property="twitter:title" content="Login/Sign Up - The Trader's Escape">
    <meta property="twitter:description" content="Access your trading education account. Login or sign up to access premium educational content and advanced trading tools.">
    <meta property="twitter:image" content="./assets/logo.png">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="./assets/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./assets/logo.png">
    
    <!-- iPhone Icons -->
    <link rel="apple-touch-icon" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="57x57" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="60x60" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="72x72" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="76x76" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="114x114" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="120x120" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="144x144" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="152x152" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="192x192" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="512x512" href="./assets/logo.png">
    
    <!-- Web App Manifest -->
    <link rel="manifest" href="./manifest.json">
    
    <!-- Preconnect to CDNs for faster loading -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://unpkg.com">
    
    <!-- DNS Prefetch for external resources -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="//unpkg.com">
    
    <!-- Resource hints for better performance -->
    <link rel="preload" href="./assets/styles.css" as="style">
    <link rel="preload" href="./assets/app.js" as="script">
    <link rel="preload" href="./assets/logo.png" as="image">
    
    <!-- Google Fonts with display=swap for better performance -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
     
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="./assets/styles.css" id="main-stylesheet">
</head>
<body>
    <!-- Trading Background -->
    <div class="trading-background">
        <div class="bg-grid"></div>
        <div class="bg-particles"></div>
        <div class="bg-trading-elements"></div>
        <div class="bg-glow"></div>
    </div>

    <!-- Top Navigation Bar -->
    <nav class="auth-top-nav">
        <div class="auth-nav-container">
            <div class="auth-nav-left">
                <a href="./" class="back-home-btn">
                    <i class="bi bi-arrow-left"></i>
                    <span>Back to Home</span>
                </a>
            </div>
            <div class="auth-nav-center">
                <div class="auth-nav-logo">
                    <img src="./assets/logo.png" alt="The Trader's Escape" class="nav-logo-img">
                    <span class="nav-logo-text">TraderEscape</span>
                </div>
            </div>
            <div class="auth-nav-right">
                <a href="./" class="home-link">
                    <i class="bi bi-house"></i>
                    <span>Home</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="auth-main" role="main">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">
                        <img src="./assets/logo.png" alt="The Trader's Escape" class="logo-img">
                    </div>
                    <h1 class="auth-title">Welcome Back</h1>
                    <p class="auth-subtitle">Access your trading education account</p>
                </div>

                <!-- Error/Success Messages -->
                <?php if ($error_message): ?>
                    <div class="alert alert-error" role="alert">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle"></i>
                        <span><?php echo htmlspecialchars($success_message); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Form Toggle Buttons -->
                <div class="form-toggle">
                    <button class="toggle-btn active" data-form="login">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Sign In</span>
                    </button>
                    <button class="toggle-btn" data-form="register">
                        <i class="bi bi-person-plus"></i>
                        <span>Create Account</span>
                    </button>
                </div>

                <!-- Login Form -->
                <form id="loginForm" class="auth-form active" method="POST" action="">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="loginEmail" class="form-label">Email Address</label>
                        <div class="input-wrapper">
                            <i class="bi bi-envelope input-icon"></i>
                            <input type="email" id="loginEmail" name="email" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   placeholder="Enter your email address"
                                   required autocomplete="email">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="loginPassword" class="form-label">Password</label>
                        <div class="input-wrapper">
                            <i class="bi bi-lock input-icon"></i>
                            <input type="password" id="loginPassword" name="password" class="form-input" 
                                   placeholder="Enter your password"
                                   required autocomplete="current-password">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Sign In</span>
                    </button>
                </form>

                <!-- Registration Form -->
                <form id="registerForm" class="auth-form" method="POST" action="">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-group">
                        <label for="signupFullName" class="form-label">Full Name</label>
                        <div class="input-wrapper">
                            <i class="bi bi-person input-icon"></i>
                            <input type="text" id="signupFullName" name="full_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                                   placeholder="Enter your full name"
                                   required autocomplete="name">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="signupUsername" class="form-label">Username</label>
                        <div class="input-wrapper">
                            <i class="bi bi-person-badge input-icon"></i>
                            <input type="text" id="signupUsername" name="username" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                   placeholder="Choose a username"
                                   required autocomplete="username" minlength="3">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="signupEmail" class="form-label">Email Address</label>
                        <div class="input-wrapper">
                            <i class="bi bi-envelope input-icon"></i>
                            <input type="email" id="signupEmail" name="email" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   placeholder="Enter your email address"
                                   required autocomplete="email">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="signupPassword" class="form-label">Password</label>
                        <div class="input-wrapper">
                            <i class="bi bi-lock input-icon"></i>
                            <input type="password" id="signupPassword" name="password" class="form-input" 
                                   placeholder="Create a strong password"
                                   required autocomplete="new-password" minlength="6">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="signupConfirmPassword" class="form-label">Confirm Password</label>
                        <div class="input-wrapper">
                            <i class="bi bi-lock input-icon"></i>
                            <input type="password" id="signupConfirmPassword" name="confirm_password" class="form-input" 
                                   placeholder="Confirm your password"
                                   required autocomplete="new-password" minlength="6">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="bi bi-person-plus"></i>
                        <span>Create Account</span>
                    </button>
                </form>

                <!-- Social Login -->
                <div class="social-login">
                    <div class="divider">
                        <span>or continue with</span>
                    </div>
                    
                    <div class="social-buttons">
                        <button type="button" class="btn btn-social btn-google" onclick="socialLogin('google')">
                            <i class="bi bi-google"></i>
                            <span>Google</span>
                        </button>
                        <button type="button" class="btn btn-social btn-facebook" onclick="socialLogin('facebook')">
                            <i class="bi bi-facebook"></i>
                            <span>Facebook</span>
                        </button>
                    </div>
                </div>

                <!-- Terms and Privacy -->
                <div class="auth-legal">
                    <p class="legal-text">
                        By continuing, you agree to our 
                        <a href="./terms.php" class="legal-link">Terms of Service</a> and 
                        <a href="./privacy.php" class="legal-link">Privacy Policy</a>
                    </p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="./assets/trading-background.js" defer></script>
    <script src="./assets/app.js" defer></script>
    
    <script>
        // Form toggle functionality
        document.querySelectorAll('.toggle-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const formType = this.getAttribute('data-form');
                const loginForm = document.getElementById('loginForm');
                const registerForm = document.getElementById('registerForm');
                const loginTitle = document.querySelector('.auth-title');
                const loginSubtitle = document.querySelector('.auth-subtitle');
                
                // Update active button
                document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Update forms
                if (formType === 'login') {
                    loginForm.classList.add('active');
                    registerForm.classList.remove('active');
                    loginTitle.textContent = 'Welcome Back';
                    loginSubtitle.textContent = 'Access your trading education account';
                } else {
                    registerForm.classList.add('active');
                    loginForm.classList.remove('active');
                    loginTitle.textContent = 'Create Account';
                    loginSubtitle.textContent = 'Join our trading education community';
                }
            });
        });

        // Social login functionality (placeholder for future implementation)
        function socialLogin(provider) {
            alert('Social login will be implemented soon. Please use email registration for now.');
        }

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('signupPassword').value;
            const confirmPassword = document.getElementById('signupConfirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // Add input focus effects
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    </script>
</body>
</html>
