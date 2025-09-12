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


function registerUser($full_name, $username, $email, $password) {
    try {
        $pdo = getDB();
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists.'];
        }
        
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }
        
        // Create the user account directly
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, is_active, is_verified, created_at) VALUES (?, ?, ?, ?, TRUE, TRUE, NOW())");
        $stmt->execute([$full_name, $username, $email, $hashedPassword]);
        
        $userId = $pdo->lastInsertId();
        
        // Log successful registration
        logUserActivity($userId, 'register', 'User registered successfully', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['action' => 'register']));
        
        return ['success' => true, 'message' => 'Account created successfully! You can now log in.', 'user_id' => $userId];
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}


// Track page view
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
trackPageView('login', $userId, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, session_id());

// Log user activity if logged in
if ($userId) {
    logUserActivity($userId, 'page_view', 'Viewed login page', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['page' => 'login']));
}

// Handle login/registration
$error_message = '';
$success_message = '';

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
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

include 'includes/header.php';
?>

    <!-- Critical CSS inline for faster rendering -->
    <style>
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            transition: all 0.3s ease;
        }
        
         .hero-section {
             min-height: 100vh;
             display: flex;
             align-items: flex-start;
             padding-top: 2rem;
             padding-bottom: 4rem;
             position: relative;
             overflow: hidden;
         }
        
        /* Performance optimizations */
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0f172a;
            color: #ffffff;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Login Form Styles */
        .auth-container {
            max-width: 480px;
            margin: 0 auto;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .auth-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ffffff, #f8fafc, #e2e8f0);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 3rem;
            padding-top: 1rem;
        }

        .auth-title {
            font-size: 2.75rem;
            font-weight: 800;
            margin: 0 0 0.75rem 0;
            color: #ffffff;
            letter-spacing: -0.02em;
        }

        .auth-subtitle {
            color: #94a3b8;
            font-size: 1.125rem;
            margin: 0;
            font-weight: 500;
            line-height: 1.6;
        }

        /* Form Toggle - Modern Design */
        .form-toggle {
            position: relative;
            display: flex;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 6px;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            overflow: hidden;
        }

        .toggle-slider {
            position: absolute;
            top: 6px;
            left: 6px;
            height: calc(100% - 12px);
            width: calc(50% - 6px);
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            border-radius: 12px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.1);
            z-index: 1;
        }

        .toggle-slider.slide-right {
            transform: translateX(100%);
        }

        .toggle-btn {
            position: relative;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1.25rem 1.5rem;
            border: none;
            background: transparent;
            color: #ffffff;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            font-size: 1rem;
            z-index: 2;
        }

        .toggle-btn.active {
            color: #1e293b;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .toggle-btn:hover:not(.active) {
            color: #ffffff;
            transform: translateY(-1px);
        }

        .toggle-btn:active {
            transform: translateY(0);
        }

        .toggle-btn i {
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .toggle-btn:hover i {
            transform: scale(1.1);
        }

        /* Remove any blue background from form-toggle::before */
        .form-toggle::before {
            background: none !important;
            background-color: transparent !important;
        }

        /* Remove blue focus outlines */
        *:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        button:focus,
        input:focus,
        textarea:focus,
        select:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        .toggle-btn:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        .btn:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        /* Forms */
        .auth-form {
            display: none;
            animation: fadeIn 0.5s ease-out;
        }

        .auth-form.active {
            display: block;
        }
        

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-input {
            width: 100%;
            padding: 1.25rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            line-height: 1.5;
        }

        .form-input:focus {
            outline: none;
            border-color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

         .form-input::placeholder {
             color: #64748b;
         }

         /* Password Toggle */
         .password-input-wrapper {
             position: relative;
             display: flex;
             align-items: center;
         }

         .password-input-wrapper .form-input {
             padding-right: 3.5rem;
         }

         .password-toggle {
             position: absolute;
             right: 1rem;
             top: 50%;
             transform: translateY(-50%);
             background: none;
             border: none;
             color: #94a3b8;
             cursor: pointer;
             padding: 0.5rem;
             border-radius: 6px;
             transition: all 0.3s ease;
             display: flex;
             align-items: center;
             justify-content: center;
         }

         .password-toggle:hover {
             color: #ffffff;
             background: rgba(255, 255, 255, 0.1);
         }

         .password-toggle:focus {
             outline: none;
             color: #ffffff;
             background: rgba(255, 255, 255, 0.15);
         }

         .password-toggle i {
             font-size: 1.1rem;
         }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1.25rem 2rem;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            position: relative;
            overflow: hidden;
            text-transform: none;
            letter-spacing: 0.025em;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            color: #1e293b;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15), 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            color: #0f172a;
            transform: translateY(-3px);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.2), 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-primary:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15), 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .btn-full {
            width: 100%;
            padding: 1.5rem 2rem;
            font-size: 1.125rem;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            font-size: 0.9rem;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.05));
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.05));
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        /* Legal */
        .auth-legal {
            margin-top: 2rem;
            text-align: center;
        }

        .legal-text {
            color: #94a3b8;
            font-size: 0.85rem;
            line-height: 1.5;
            margin: 0;
        }

        .legal-link {
            color: #ffffff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .legal-link:hover {
            color: #e2e8f0;
            text-decoration: underline;
        }

         /* Responsive */
         @media (max-width: 768px) {
             .hero-section {
                 padding-top: 2rem;
                 padding-bottom: 3rem;
             }

             .auth-container {
                 margin: 0 1rem;
                 padding: 2rem;
             }

             .auth-title {
                 font-size: 2rem;
             }

             .toggle-btn {
                 padding: 1rem 1rem;
                 font-size: 0.9rem;
             }

             .form-input {
                 padding: 1rem;
             }

             .btn-full {
                 padding: 1.25rem 1.5rem;
                 font-size: 1rem;
             }
         }

         @media (max-width: 480px) {
             .hero-section {
                 padding-top: 1.5rem;
                 padding-bottom: 2.5rem;
             }

             .auth-container {
                 padding: 1.5rem;
             }

             .auth-title {
                 font-size: 1.75rem;
             }
         }
    </style>

    <!-- Main Content -->
    <main class="hero-section" role="main">
        <div class="container">
        <div class="auth-container">
                <div class="auth-header">
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

                <!-- Form Toggle -->
                <div class="form-toggle">
                    <div class="toggle-slider"></div>
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
                            <input type="email" id="loginEmail" name="email" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   placeholder="Enter your email address"
                                   required autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label for="loginPassword" class="form-label">Password</label>
                         <div class="password-input-wrapper">
                            <input type="password" id="loginPassword" name="password" class="form-input" 
                                   placeholder="Enter your password"
                                   required autocomplete="current-password">
                             <button type="button" class="password-toggle" onclick="togglePassword('loginPassword')">
                                 <i class="bi bi-eye" id="loginPasswordIcon"></i>
                             </button>
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
                            <input type="text" id="signupFullName" name="full_name" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" 
                                   placeholder="Enter your full name"
                                   required autocomplete="name">
                    </div>

                    <div class="form-group">
                        <label for="signupUsername" class="form-label">Username</label>
                            <input type="text" id="signupUsername" name="username" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                   placeholder="Choose a username"
                                   required autocomplete="username" minlength="3">
                    </div>

                    <div class="form-group">
                        <label for="signupEmail" class="form-label">Email Address</label>
                            <input type="email" id="signupEmail" name="email" class="form-input" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   placeholder="Enter your email address"
                                   required autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label for="signupPassword" class="form-label">Password</label>
                         <div class="password-input-wrapper">
                            <input type="password" id="signupPassword" name="password" class="form-input" 
                                   placeholder="Create a strong password"
                                   required autocomplete="new-password" minlength="6">
                             <button type="button" class="password-toggle" onclick="togglePassword('signupPassword')">
                                 <i class="bi bi-eye" id="signupPasswordIcon"></i>
                             </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="signupConfirmPassword" class="form-label">Confirm Password</label>
                         <div class="password-input-wrapper">
                            <input type="password" id="signupConfirmPassword" name="confirm_password" class="form-input" 
                                   placeholder="Confirm your password"
                                   required autocomplete="new-password" minlength="6">
                             <button type="button" class="password-toggle" onclick="togglePassword('signupConfirmPassword')">
                                 <i class="bi bi-eye" id="signupConfirmPasswordIcon"></i>
                             </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="bi bi-person-plus"></i>
                        <span>Create Account</span>
                    </button>
                </form>


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
     <script>
         // Password toggle functionality
         function togglePassword(inputId) {
             const input = document.getElementById(inputId);
             const icon = document.getElementById(inputId + 'Icon');
             
             if (input.type === 'password') {
                 input.type = 'text';
                 icon.className = 'bi bi-eye-slash';
             } else {
                 input.type = 'password';
                 icon.className = 'bi bi-eye';
             }
         }

         // Form toggle functionality with slider animation
        document.querySelectorAll('.toggle-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const formType = this.getAttribute('data-form');
                const loginForm = document.getElementById('loginForm');
                const registerForm = document.getElementById('registerForm');
                const loginTitle = document.querySelector('.auth-title');
                const loginSubtitle = document.querySelector('.auth-subtitle');
                const slider = document.querySelector('.toggle-slider');
                
                // Update active button
                document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Animate slider
                if (formType === 'login') {
                    slider.classList.remove('slide-right');
                } else {
                    slider.classList.add('slide-right');
                }
                
                // Update forms with animation
                if (formType === 'login') {
                    registerForm.classList.remove('active');
                    setTimeout(() => {
                    loginForm.classList.add('active');
                    loginTitle.textContent = 'Welcome Back';
                    loginSubtitle.textContent = 'Access your trading education account';
                    }, 150);
                } else {
                    loginForm.classList.remove('active');
                    setTimeout(() => {
                    registerForm.classList.add('active');
                    loginTitle.textContent = 'Create Account';
                    loginSubtitle.textContent = 'Join our trading education community';
                    }, 150);
                }
            });
        });

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

        // Handle form visibility based on PHP variables
        <?php if (isset($show_login_form) && $show_login_form): ?>
            showLoginForm();
        <?php endif; ?>


        // Form visibility functions
        function showLoginForm() {
            hideAllForms();
            document.getElementById('loginForm').style.display = 'block';
            document.querySelector('.form-toggle').style.display = 'flex';
            updateHeader('Welcome Back', 'Access your trading education account');
        }

        function showRegisterForm() {
            hideAllForms();
            document.getElementById('registerForm').style.display = 'block';
            document.querySelector('.form-toggle').style.display = 'flex';
            updateHeader('Create Account', 'Join our trading education community');
        }

        function hideAllForms() {
            const forms = ['loginForm', 'registerForm'];
            forms.forEach(formId => {
                const form = document.getElementById(formId);
                if (form) form.style.display = 'none';
            });
        }

        function updateHeader(title, subtitle) {
            const titleEl = document.querySelector('.auth-title');
            const subtitleEl = document.querySelector('.auth-subtitle');
            if (titleEl) titleEl.textContent = title;
            if (subtitleEl) subtitleEl.textContent = subtitle;
        }

    </script>
</body>
</html>
