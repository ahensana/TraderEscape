<?php
session_start();
require_once __DIR__ . '/../includes/db_functions.php';

// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Check if OTP is verified
if (!isset($_SESSION['admin_otp_verified']) || !$_SESSION['admin_otp_verified']) {
    header('Location: forgot_password.php');
    exit;
}

// Check if OTP session still exists
if (!isset($_SESSION['admin_reset_email']) || !isset($_SESSION['admin_reset_otp'])) {
    $_SESSION['error_message'] = 'Session expired. Please request a new OTP.';
    header('Location: forgot_password.php');
    exit;
}

// Handle POST request before displaying anything
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
            
            // Update the admin's password
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE email = ?");
            $stmt->execute([$hashedPassword, $_SESSION['admin_reset_email']]);
            
            if ($stmt->rowCount() > 0) {
                // Clear all reset session data
                unset($_SESSION['admin_reset_otp']);
                unset($_SESSION['admin_reset_email']);
                unset($_SESSION['admin_otp_expires']);
                unset($_SESSION['admin_otp_verified']);
                unset($_SESSION['admin_otp_last_sent']);
                
                $_SESSION['success_message'] = 'Password reset successfully! You can now log in with your new password.';
                header('Location: admin_login.php');
                exit;
            } else {
                $_SESSION['error_message'] = 'Failed to update password. Please try again.';
            }
        } catch (Exception $e) {
            error_log("Admin password reset error: " . $e->getMessage());
            $_SESSION['error_message'] = 'Unable to reset password. Please try again.';
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get and clear messages after POST redirect
$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';

unset($_SESSION['error_message']);
unset($_SESSION['success_message']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Admin Portal</title>
    <link rel="icon" type="image/png" href="../assets/logo.png">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0f172a;
            color: #ffffff;
            line-height: 1.6;
            min-height: 100vh;
        }

        .admin-reset-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .admin-reset-card {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
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
        }

        .admin-title {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .password-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-input {
            width: 100%;
            padding: 15px 50px 15px 20px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 12px;
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 8px;
            font-size: 1.2rem;
        }

        .password-toggle:hover {
            color: #ffffff;
        }

        .password-strength {
            margin-top: 10px;
        }

        .strength-requirements {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 8px;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .requirement.valid {
            color: #10b981;
        }

        .requirement.invalid {
            color: #ef4444;
        }

        .btn {
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
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.4);
        }

        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-spinner {
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        .btn.loading .btn-spinner {
            display: inline-block;
        }

        .btn.loading .btn-text {
            opacity: 0.7;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .back-link {
            text-align: center;
            margin-top: 30px;
        }

        .back-link a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link a:hover {
            color: #3b82f6;
        }

        @media (max-width: 768px) {
            .admin-reset-card {
                padding: 30px 20px;
            }

            .admin-title {
                font-size: 1.5rem;
            }

            .strength-requirements {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-reset-container">
        <div class="admin-reset-card">
            <div class="admin-logo">
                <img src="../assets/logo.png" alt="Logo">
                <h1 class="admin-title">Reset Password</h1>
                <p class="admin-subtitle">Enter your new admin password</p>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <span>⚠</span>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <span>✓</span>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="resetForm">
                <div class="form-group">
                    <label for="newPassword" class="form-label">New Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" id="newPassword" name="new_password" class="form-input" 
                               placeholder="Enter your new password"
                               required autocomplete="new-password" minlength="8" maxlength="128">
                        <button type="button" class="password-toggle" onclick="togglePassword('newPassword')" id="toggleNewPassword">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="newPasswordIcon">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <div class="password-strength">
                        <div class="strength-requirements">
                            <div class="requirement" id="length-req">
                                <span>○</span>
                                <span>8+ characters</span>
                            </div>
                            <div class="requirement" id="uppercase-req">
                                <span>○</span>
                                <span>Uppercase letter</span>
                            </div>
                            <div class="requirement" id="number-req">
                                <span>○</span>
                                <span>Number</span>
                            </div>
                            <div class="requirement" id="special-req">
                                <span>○</span>
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
                        <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')" id="toggleConfirmPassword">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" id="confirmPasswordIcon">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn" id="resetBtn">
                    <span class="btn-spinner"></span>
                    <span class="btn-text">Reset Password</span>
                </button>
            </form>

            <div class="back-link">
                <a href="admin_login.php">← Back to Admin Login</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + 'Icon');
            
            if (input && icon) {
                const type = input.type === 'password' ? 'text' : 'password';
                input.type = type;
                
                // Toggle icon
                if (type === 'text') {
                    // Eye slash icon (password visible)
                    icon.innerHTML = `
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    `;
                } else {
                    // Eye icon (password hidden)
                    icon.innerHTML = `
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    `;
                }
            }
        }

        function checkPasswordStrength(password) {
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };

            document.getElementById('length-req').className = requirements.length ? 'requirement valid' : 'requirement invalid';
            document.getElementById('uppercase-req').className = requirements.uppercase ? 'requirement valid' : 'requirement invalid';
            document.getElementById('number-req').className = requirements.number ? 'requirement valid' : 'requirement invalid';
            document.getElementById('special-req').className = requirements.special ? 'requirement valid' : 'requirement invalid';

            return Object.values(requirements).every(req => req);
        }

        document.getElementById('newPassword').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });

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

        const resetBtn = document.getElementById('resetBtn');
        const btnText = resetBtn.querySelector('.btn-text');
        
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const password = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password.length < 8 || password.length > 128) {
                e.preventDefault();
                alert('Password must be between 8 and 128 characters.');
                return false;
            }
            
            if (!/[A-Z]/.test(password) || !/[0-9]/.test(password) || !/[^A-Za-z0-9]/.test(password)) {
                e.preventDefault();
                alert('Password must contain uppercase, number, and special character.');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return false;
            }
            
            // Show loading state
            resetBtn.classList.add('loading');
            resetBtn.disabled = true;
            btnText.textContent = 'Resetting Password...';
            
            // Make inputs readonly instead of disabled (so values are still sent)
            document.getElementById('newPassword').setAttribute('readonly', true);
            document.getElementById('confirmPassword').setAttribute('readonly', true);
            document.querySelectorAll('.password-toggle').forEach(btn => btn.style.pointerEvents = 'none');
        });

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

