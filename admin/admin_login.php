<?php
session_start();

// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Handle POST request before displaying anything
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mysqli = new mysqli("localhost", "root", "", "traderescape_db");
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT id, username, password FROM admins WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $username, $hashedPassword);
        $stmt->fetch();

        if (password_verify($password, $hashedPassword)) {
            $_SESSION['admin_id'] = $id;
            $_SESSION['admin_name'] = $username;
            $stmt->close();
            $mysqli->close();
            header("Location: admin_dashboard.php");
            exit;
        } else {
            $_SESSION['error'] = "Incorrect password.";
        }
    } else {
        $_SESSION['error'] = "Email not found.";
    }
    $stmt->close();
    $mysqli->close();
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Get and clear error message after POST redirect
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - The Trader's Escape</title>
    <link rel="icon" type="image/png" href="../assets/logo.png">
    
    <!-- Trading Background Script -->
    <script src="../assets/trading-background.js" defer></script>
    
    <style>
        /* ===== ESSENTIAL STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        *::-webkit-scrollbar {
            display: none;
        }

        html, body {
            margin: 0 !important;
            padding: 0 !important;
            top: 0 !important;
            height: 100%;
        }

        body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0f172a;
            color: #ffffff;
            line-height: 1.6;
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* ===== TRADING BACKGROUND ===== */
        .trading-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            min-height: 100%;
            z-index: -1;
            pointer-events: none;
            overflow: hidden;
            will-change: transform;
            transform: translateZ(0);
            backface-visibility: hidden;
            perspective: 1000px;
        }

        .bg-grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            min-height: 100vh;
            background-image: linear-gradient(
                rgba(59, 130, 246, 0.1) 1px,
                transparent 1px
            ),
            linear-gradient(90deg, rgba(59, 130, 246, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            background-repeat: repeat;
            animation: grid-move 30s linear infinite;
            opacity: 0.3;
            will-change: transform;
            transform: translateZ(0);
        }

        .bg-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            min-height: 100vh;
        }

        .bg-glow {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            min-height: 100vh;
            background: radial-gradient(
                circle at 20% 80%,
                rgba(59, 130, 246, 0.1) 0%,
                transparent 50%
            ),
            radial-gradient(
                circle at 80% 20%,
                rgba(147, 51, 234, 0.1) 0%,
                transparent 50%
            ),
            radial-gradient(
                circle at 40% 40%,
                rgba(16, 185, 129, 0.05) 0%,
                transparent 50%
            );
            animation: glow-pulse 8s ease-in-out infinite alternate;
        }

        @keyframes grid-move {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        @keyframes glow-pulse {
            0% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        /* ===== ADMIN LOGIN CONTAINER ===== */
        .admin-login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        .admin-login-card {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }

        .admin-login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(59, 130, 246, 0.5),
                transparent
            );
        }

        .admin-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .admin-logo img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 15px;
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.3);
        }

        .admin-title {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6, #06b6d4);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
            text-align: center;
        }

        .admin-subtitle {
            color: rgba(255, 255, 255, 0.7);
            text-align: center;
            font-size: 1rem;
            margin-bottom: 30px;
        }

        /* ===== FORM STYLES ===== */
        .admin-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            position: relative;
        }

        .form-label {
            display: block;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-input {
            width: 100%;
            padding: 15px 20px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 12px;
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 
                0 0 0 3px rgba(59, 130, 246, 0.1),
                0 0 20px rgba(59, 130, 246, 0.2);
            background: rgba(15, 23, 42, 0.8);
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        /* Password Input Wrapper */
        .password-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .password-input-wrapper .form-input {
            padding-right: 50px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            padding: 8px;
            font-size: 1.2rem;
            transition: color 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .password-toggle:hover {
            color: rgba(255, 255, 255, 0.9);
        }

        .password-toggle:focus {
            outline: none;
        }

        /* ===== BUTTON STYLES ===== */
        .admin-login-btn {
            width: 100%;
            padding: 15px 20px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-top: 10px;
        }

        .admin-login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: left 0.5s ease;
        }

        .admin-login-btn:hover::before {
            left: 100%;
        }

        .admin-login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
        }

        .admin-login-btn:active {
            transform: translateY(0);
        }

        /* ===== ERROR MESSAGE ===== */
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            padding: 12px 16px;
            color: #fca5a5;
            font-size: 0.9rem;
            text-align: center;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }

        /* ===== BACK TO HOME LINK ===== */
        .back-to-home {
            text-align: center;
            margin-top: 30px;
        }

        .back-to-home a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-to-home a:hover {
            color: #3b82f6;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .admin-login-card {
                padding: 30px 20px;
                margin: 20px;
            }

            .admin-title {
                font-size: 1.5rem;
            }

            .form-input {
                padding: 12px 16px;
            }

            .admin-login-btn {
                padding: 12px 16px;
            }
        }

        /* ===== LOADING STATE ===== */
        .admin-login-btn.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .admin-login-btn.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Trading Background -->
    <div class="trading-background">
        <div class="bg-grid"></div>
        <div class="bg-particles"></div>
        <div class="bg-glow"></div>
    </div>

    <!-- Admin Login Container -->
    <div class="admin-login-container">
        <div class="admin-login-card">
            <!-- Logo and Title -->
            <div class="admin-logo">
                <img src="../assets/logo.png" alt="The Trader's Escape Logo">
                <h1 class="admin-title">Admin Portal</h1>
                <p class="admin-subtitle">Secure access to The Trader's Escape</p>
            </div>

            <!-- Error Message -->
            <?php if($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="" class="admin-form" id="adminForm">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        id="email"
                        name="email" 
                        class="form-input" 
                        placeholder="Enter your admin email"
                        required
                        autocomplete="email"
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-input-wrapper">
                        <input 
                            type="password" 
                            id="password"
                            name="password" 
                            class="form-input" 
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="password-toggle" id="togglePassword">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="eyeIcon">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="admin-login-btn" id="loginBtn">
                    <span>Sign In to Admin Portal</span>
                </button>
            </form>

            <!-- Forgot Password Link -->
            <div class="back-to-home" style="margin-top: 20px;">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>

            <!-- Back to Home -->
            <div class="back-to-home">
                <a href="../index.php">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9,22 9,12 15,12 15,22"></polyline>
                    </svg>
                    Back to Home
                </a>
            </div>
        </div>
    </div>

    <script>
        // Form submission with loading state
        document.getElementById('adminForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            const btnText = loginBtn.querySelector('span');
            
            // Add loading state
            loginBtn.classList.add('loading');
            btnText.textContent = 'Signing In...';
            
            // Re-enable after 3 seconds (in case of slow response)
            setTimeout(() => {
                loginBtn.classList.remove('loading');
                btnText.textContent = 'Sign In to Admin Portal';
            }, 3000);
        });

        // Add focus effects
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });

        // Add enter key support
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                const form = document.getElementById('adminForm');
                if (form) {
                    form.submit();
                }
            }
        });

        // Auto-hide error message after 3 seconds
        const errorMessage = document.querySelector('.error-message');
        if (errorMessage) {
            setTimeout(() => {
                errorMessage.style.transition = 'opacity 0.3s ease';
                errorMessage.style.opacity = '0';
                setTimeout(() => {
                    errorMessage.remove();
                }, 300);
            }, 3000);
        }

        // Password toggle functionality
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        togglePassword.addEventListener('click', function() {
            // Toggle password visibility
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            
            // Toggle icon
            if (type === 'text') {
                // Eye slash icon (password visible)
                eyeIcon.innerHTML = `
                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                    <line x1="1" y1="1" x2="23" y2="23"></line>
                `;
            } else {
                // Eye icon (password hidden)
                eyeIcon.innerHTML = `
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                `;
            }
        });
    </script>
</body>
</html>
