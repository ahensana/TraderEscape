<?php
session_start();
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/auth_functions.php';

// Track page view
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
trackPageView('reset_password', $userId, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, session_id());

// Check if OTP is verified
if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
    header('Location: forgot_password.php');
    exit;
}

// Check if OTP session still exists
if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp'])) {
    $_SESSION['error_message'] = 'Session expired. Please request a new OTP.';
    header('Location: forgot_password.php');
    exit;
}

// Handle password reset
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $_SESSION['error_message'] = 'Please fill in all fields.';
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = 'Passwords do not match.';
    } elseif (strlen($new_password) < 8) {
        $_SESSION['error_message'] = 'Password must be at least 8 characters long.';
    } elseif (strlen($new_password) > 128) {
        $_SESSION['error_message'] = 'Password must be no more than 128 characters long.';
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $_SESSION['error_message'] = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $_SESSION['error_message'] = 'Password must contain at least one number.';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
        $_SESSION['error_message'] = 'Password must contain at least one special character.';
    } else {
        try {
            $pdo = getDB();
            
            // Update the user's password
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ?");
            $stmt->execute([$hashedPassword, $_SESSION['reset_email']]);
            
            if ($stmt->rowCount() > 0) {
                // Clear all reset session data
                unset($_SESSION['reset_otp']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['otp_expires']);
                unset($_SESSION['otp_verified']);
                
                // Log the password reset
                $user = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $user->execute([$_SESSION['reset_email']]);
                $userId = $user->fetchColumn();
                
                if ($userId) {
                    logUserActivity($userId, 'password_reset_complete', 'Password reset completed successfully', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['action' => 'password_reset_complete']));
                }
                
                $_SESSION['success_message'] = 'Password reset successfully! You can now log in with your new password.';
                header('Location: login.php');
                exit;
            } else {
                $_SESSION['error_message'] = 'Failed to update password. Please try again.';
            }
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            $_SESSION['error_message'] = 'Unable to reset password. Please try again.';
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get messages from session
$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';

// Clear session messages after displaying
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);

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

        /* Disable all floating animations */
        * {
            animation: none !important;
            transition: none !important;
            transform: none !important;
        }

        .trading-background,
        .bg-grid,
        .bg-particles,
        .bg-trading-elements,
        .bg-glow {
            animation: none !important;
            transition: none !important;
            transform: none !important;
        }

        /* Auth Container Styles */
        .auth-container {
            max-width: 480px;
            margin: 0 auto;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 1.5rem;
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
            margin-bottom: 1.5rem;
            padding-top: 0;
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

        /* Forms */
        .auth-form {
            display: block;
        }

        .form-group {
            margin-bottom: 1rem;
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
            padding: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            font-size: 1rem;
            backdrop-filter: blur(10px);
            line-height: 1.5;
        }

        .form-input:focus {
            outline: none;
            border-color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
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
            position: absolute !important;
            right: 0.75rem !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            background: none !important;
            border: none !important;
            color: #94a3b8 !important;
            cursor: pointer !important;
            padding: 0.5rem !important;
            border-radius: 6px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            z-index: 10 !important;
            width: 2.5rem !important;
            height: 2.5rem !important;
            line-height: 1 !important;
            margin: 0 !important;
        }

        .password-toggle:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
        }

        .password-toggle:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.1);
        }

        .password-toggle:active {
            transform: translateY(-50%) scale(0.95);
        }

        .password-toggle i {
            font-size: 1.1rem;
            line-height: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Hide browser native password toggle buttons */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }

        input[type="password"]::-webkit-credentials-auto-fill-button {
            display: none !important;
        }

        input[type="password"]::-webkit-textfield-decoration-container {
            display: none !important;
        }

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }

        .strength-requirements {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.25rem;
            margin-top: 0.5rem;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: #94a3b8;
        }

        .requirement.valid {
            color: #10b981;
        }

        .requirement.invalid {
            color: #ef4444;
        }

        .requirement i {
            font-size: 0.75rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            text-transform: none;
            letter-spacing: 0.025em;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            color: #1e293b;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15), 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-full {
            width: 100%;
            padding: 1.25rem 1.5rem;
            font-size: 1.125rem;
        }

        .btn-secondary {
            background: transparent;
            color: #94a3b8;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
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

        /* Back Link */
        .back-link {
            text-align: center;
            margin-top: 1rem;
        }

        .back-link a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .back-link a:hover {
            color: #ffffff;
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
                    <h1 class="auth-title">Reset Password</h1>
                    <p class="auth-subtitle">Enter your new password</p>
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

                <!-- Password Reset Form -->
                <form method="POST" action="" id="resetForm">
                    <div class="form-group">
                        <label for="newPassword" class="form-label">New Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="newPassword" name="new_password" class="form-input" 
                                   placeholder="Enter your new password"
                                   required autocomplete="new-password" minlength="8" maxlength="128">
                            <button type="button" class="password-toggle" data-target="newPassword">
                                <i class="bi bi-eye" id="newPasswordIcon"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-requirements">
                                <div class="requirement" id="length-req">
                                    <i class="bi bi-circle"></i>
                                    <span>8+ characters</span>
                                </div>
                                <div class="requirement" id="uppercase-req">
                                    <i class="bi bi-circle"></i>
                                    <span>Uppercase letter</span>
                                </div>
                                <div class="requirement" id="number-req">
                                    <i class="bi bi-circle"></i>
                                    <span>Number</span>
                                </div>
                                <div class="requirement" id="special-req">
                                    <i class="bi bi-circle"></i>
                                    <span>Special character</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="confirmPassword" name="confirm_password" class="form-input" 
                                   placeholder="Confirm your new password"
                                   required autocomplete="new-password" minlength="8" maxlength="128">
                            <button type="button" class="password-toggle" data-target="confirmPassword">
                                <i class="bi bi-eye" id="confirmPasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="bi bi-shield-check"></i>
                        <span>Reset Password</span>
                    </button>
                </form>

                <!-- Back to Login Link -->
                <div class="back-link">
                    <a href="login.php">
                        <i class="bi bi-arrow-left"></i>
                        Back to Sign In
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        // Password toggle functionality
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + 'Icon');
            
            if (input && icon) {
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'bi bi-eye-slash';
                } else {
                    input.type = 'password';
                    icon.className = 'bi bi-eye';
                }
            }
        }

        // Initialize password toggle on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Password toggle functionality loaded');
            
            // Add direct event listeners to all password toggle buttons
            document.querySelectorAll('.password-toggle').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Get the target input ID from data-target attribute
                    const targetId = this.getAttribute('data-target');
                    if (targetId) {
                        togglePassword(targetId);
                    }
                });
            });
        });

        // Password strength checker
        function checkPasswordStrength(password) {
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };

            // Update visual indicators
            document.getElementById('length-req').className = requirements.length ? 'requirement valid' : 'requirement invalid';
            document.getElementById('uppercase-req').className = requirements.uppercase ? 'requirement valid' : 'requirement invalid';
            document.getElementById('number-req').className = requirements.number ? 'requirement valid' : 'requirement invalid';
            document.getElementById('special-req').className = requirements.special ? 'requirement valid' : 'requirement invalid';

            // Update icons
            const icons = {
                length: document.querySelector('#length-req i'),
                uppercase: document.querySelector('#uppercase-req i'),
                number: document.querySelector('#number-req i'),
                special: document.querySelector('#special-req i')
            };

            Object.keys(icons).forEach(key => {
                if (requirements[key]) {
                    icons[key].className = 'bi bi-check-circle-fill';
                } else {
                    icons[key].className = 'bi bi-circle';
                }
            });

            return Object.values(requirements).every(req => req);
        }

        // Add real-time password strength checking
        document.getElementById('newPassword').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });

        // Password confirmation validation
        function validatePasswordMatch() {
            const password = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (confirmPassword && password !== confirmPassword) {
                document.getElementById('confirmPassword').setCustomValidity('Passwords do not match');
            } else {
                document.getElementById('confirmPassword').setCustomValidity('');
            }
        }

        document.getElementById('confirmPassword').addEventListener('input', validatePasswordMatch);
        document.getElementById('newPassword').addEventListener('input', validatePasswordMatch);

        // Form validation
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const password = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            // Password validation
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return false;
            }
            
            if (password.length > 128) {
                e.preventDefault();
                alert('Password must be no more than 128 characters long.');
                return false;
            }
            
            if (!/[A-Z]/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one uppercase letter.');
                return false;
            }
            
            if (!/[0-9]/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one number.');
                return false;
            }
            
            if (!/[^A-Za-z0-9]/.test(password)) {
                e.preventDefault();
                alert('Password must contain at least one special character.');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
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
    </script>
</body>
</html>
