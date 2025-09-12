<?php
session_start();
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/working_otp_service.php';

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

// OTP Authentication function
function authenticateUserWithOTP($email, $password, $otp) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Check OTP verification
            $otpService = new WorkingOTPService();
            if ($otpService->verifyOTP($email, $otp, 'login')) {
                // Update last_login timestamp
                $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                return $user;
            }
        }
        return false;
    } catch (Exception $e) {
        error_log("OTP Authentication error: " . $e->getMessage());
        return false;
    }
}

// Send OTP for login
function sendLoginOTP($email) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND is_active = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Email not found.'];
        }
        
        $otpService = new WorkingOTPService();
        $otp = $otpService->generateOTP();
        
        if ($otpService->storeOTP($email, $otp, 'login') && $otpService->sendOTPEmail($email, $otp, 'login')) {
            return ['success' => true, 'message' => 'OTP sent to your email.'];
        } else {
            return ['success' => false, 'message' => 'Failed to send OTP. Please try again.'];
        }
    } catch (Exception $e) {
        error_log("Send OTP error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to send OTP. Please try again.'];
    }
}

// Send forgot password OTP
function sendForgotPasswordOTP($email) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND is_active = TRUE");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Email not found.'];
        }
        
        $otpService = new WorkingOTPService();
        $otp = $otpService->generateOTP();
        
        if ($otpService->storeOTP($email, $otp, 'forgot_password') && $otpService->sendOTPEmail($email, $otp, 'forgot_password')) {
            return ['success' => true, 'message' => 'Password reset code sent to your email.'];
        } else {
            return ['success' => false, 'message' => 'Failed to send reset code. Please try again.'];
        }
    } catch (Exception $e) {
        error_log("Send forgot password OTP error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to send reset code. Please try again.'];
    }
}

// Reset password with OTP
function resetPasswordWithOTP($email, $otp, $newPassword) {
    try {
        $pdo = getDB();
        $otpService = new WorkingOTPService();
        
        if ($otpService->verifyOTP($email, $otp, 'forgot_password')) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ?");
            $stmt->execute([$hashedPassword, $email]);
            
            return ['success' => true, 'message' => 'Password reset successfully. You can now log in with your new password.'];
        } else {
            return ['success' => false, 'message' => 'Invalid or expired reset code.'];
        }
    } catch (Exception $e) {
        error_log("Reset password error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to reset password. Please try again.'];
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
        
        // Store registration data temporarily in session (not in database yet)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $_SESSION['pending_registration'] = [
            'full_name' => $full_name,
            'username' => $username,
            'email' => $email,
            'password_hash' => $hashedPassword,
            'timestamp' => time()
        ];
        
        // Send OTP for email verification
        $otpService = new WorkingOTPService();
        $otp = $otpService->generateOTP();
        $otpService->storeOTP($email, $otp, 'register');
        $otpService->sendOTPEmail($email, $otp, 'register');
        
        return ['success' => true, 'message' => 'Please check your email for verification code to complete registration.', 'requires_otp' => true];
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

// Verify registration OTP and create account
function verifyRegistrationOTP($email, $otp) {
    try {
        // Check if we have pending registration data
        if (!isset($_SESSION['pending_registration'])) {
            return ['success' => false, 'message' => 'No pending registration found. Please register again.'];
        }
        
        $pendingData = $_SESSION['pending_registration'];
        
        // Check if the email matches
        if ($pendingData['email'] !== $email) {
            return ['success' => false, 'message' => 'Email mismatch. Please register again.'];
        }
        
        // Check if registration data is not too old (1 hour)
        if (time() - $pendingData['timestamp'] > 3600) {
            unset($_SESSION['pending_registration']);
            return ['success' => false, 'message' => 'Registration session expired. Please register again.'];
        }
        
        $otpService = new WorkingOTPService();
        if ($otpService->verifyOTP($email, $otp, 'register')) {
            // OTP verified, now create the user account
            $pdo = getDB();
            
            // Double-check email and username don't exist (in case someone else registered)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $pendingData['username']]);
            if ($stmt->fetch()) {
                unset($_SESSION['pending_registration']);
                return ['success' => false, 'message' => 'Email or username already exists. Please try again.'];
            }
            
            // Create the user account
            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, is_active, is_verified, created_at) VALUES (?, ?, ?, ?, TRUE, TRUE, NOW())");
            $stmt->execute([
                $pendingData['full_name'],
                $pendingData['username'],
                $pendingData['email'],
                $pendingData['password_hash']
            ]);
            
            $userId = $pdo->lastInsertId();
            
            // Clear pending registration data
            unset($_SESSION['pending_registration']);
            
            // Log successful registration
            logUserActivity($userId, 'register', 'User registered and verified successfully', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['action' => 'register', 'verified' => true]));
            
            return ['success' => true, 'message' => 'Account created and verified successfully! You can now log in.', 'user_id' => $userId];
        } else {
            return ['success' => false, 'message' => 'Invalid or expired verification code.'];
        }
    } catch (Exception $e) {
        error_log("Registration OTP verification error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Verification failed. Please try again.'];
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
        $otp = trim($_POST['otp'] ?? '');
        
        if (empty($email) || empty($password)) {
            $error_message = 'Please fill in all fields.';
        } else {
            if (!empty($otp)) {
                // OTP verification login
                $user = authenticateUserWithOTP($email, $password, $otp);
                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['full_name'] = $user['full_name'];
                    
                    // Log successful login
                    logUserActivity($user['id'], 'login', 'User logged in successfully with OTP', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['action' => 'login', 'method' => 'otp']));
                    
                    header('Location: ./');
                    exit;
                } else {
                    $error_message = 'Invalid email, password, or OTP code.';
                }
            } else {
                // Regular login - check if OTP is required
                $user = authenticateUser($email, $password);
                if ($user) {
                    // Check if user needs OTP verification
                    $otpService = new WorkingOTPService();
                    if (!$otpService->isOTPVerified($email)) {
                        // Send OTP and show OTP form
                        $otpResult = sendLoginOTP($email);
                        if ($otpResult['success']) {
                            $_SESSION['pending_login'] = ['email' => $email, 'password' => $password];
                            $success_message = $otpResult['message'];
                            $show_otp_form = true;
                        } else {
                            $error_message = $otpResult['message'];
                        }
                    } else {
                        // User is verified, proceed with login
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['full_name'] = $user['full_name'];
                        
                        // Log successful login
                        logUserActivity($user['id'], 'login', 'User logged in successfully', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['action' => 'login']));
                        
                        header('Location: ./');
                        exit;
                    }
                } else {
                    $error_message = 'Invalid email or password.';
                }
            }
        }
    } elseif ($action === 'register') {
        $full_name = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $otp = trim($_POST['otp'] ?? '');
        
        if (empty($full_name) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error_message = 'Please fill in all fields.';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error_message = 'Password must be at least 6 characters long.';
        } else {
            if (!empty($otp)) {
                // Verify registration OTP
                $result = verifyRegistrationOTP($email, $otp);
                if ($result['success']) {
                    $success_message = $result['message'];
                    $show_login_form = true;
                } else {
                    $error_message = $result['message'];
                }
            } else {
                // Initial registration
                $result = registerUser($full_name, $username, $email, $password);
                if ($result['success']) {
                    if (isset($result['requires_otp']) && $result['requires_otp']) {
                        $success_message = $result['message'];
                        $show_otp_verification = true;
                    } else {
                        $success_message = 'Account created successfully! You can now log in.';
                    }
                    
                    // Log successful registration
                    if (isset($result['user_id'])) {
                        logUserActivity($result['user_id'], 'register', 'User registered successfully', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['action' => 'register']));
                    }
                } else {
                    $error_message = $result['message'];
                }
            }
        }
    } elseif ($action === 'forgot_password') {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $error_message = 'Please enter your email address.';
        } else {
            $result = sendForgotPasswordOTP($email);
            if ($result['success']) {
                $success_message = $result['message'];
                $show_forgot_password_otp = true;
                $_SESSION['forgot_password_email'] = $email;
            } else {
                $error_message = $result['message'];
            }
        }
    } elseif ($action === 'reset_password') {
        $email = $_SESSION['forgot_password_email'] ?? '';
        $otp = trim($_POST['otp'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($email) || empty($otp) || empty($new_password) || empty($confirm_password)) {
            $error_message = 'Please fill in all fields.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $error_message = 'Password must be at least 6 characters long.';
        } else {
            $result = resetPasswordWithOTP($email, $otp, $new_password);
            if ($result['success']) {
                $success_message = $result['message'];
                $show_login_form = true;
                unset($_SESSION['forgot_password_email']);
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
        
        /* OTP Input Styling */
        .otp-input {
            width: 100%;
            padding: 1.5rem;
            border: 3px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
            font-size: 2rem;
            font-weight: 800;
            text-align: center;
            letter-spacing: 1rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(20px);
            line-height: 1.2;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .otp-input:focus {
            outline: none;
            border-color: #60a5fa;
            background: rgba(255, 255, 255, 0.12);
            box-shadow: 0 0 0 6px rgba(96, 165, 250, 0.2), 0 12px 40px rgba(0, 0, 0, 0.4);
            transform: translateY(-3px);
        }

        .otp-input::placeholder {
            color: #94a3b8;
            font-size: 1.2rem;
            letter-spacing: 0.3rem;
            font-weight: 400;
        }
        
        /* OTP Info Box */
        .otp-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(59, 130, 246, 0.1));
            border: 2px solid rgba(59, 130, 246, 0.4);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 8px 32px rgba(59, 130, 246, 0.1);
        }
        
        .otp-info-icon {
            font-size: 3rem;
            color: #60a5fa;
            margin-bottom: 1rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .otp-info-text {
            color: #93c5fd;
            font-size: 1.1rem;
            margin: 0;
            font-weight: 500;
            line-height: 1.6;
        }
        
        /* OTP Form Container */
        .otp-form-container {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            border: 2px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 2.5rem;
            margin: 1rem 0;
            backdrop-filter: blur(20px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        /* OTP Label */
        .otp-label {
            display: block;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            text-align: center;
        }
        
        /* OTP Instructions */
        .otp-instructions {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            text-align: center;
            color: #94a3b8;
            font-size: 0.9rem;
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
                    
                    <div class="form-group" style="text-align: center; margin-top: 1rem;">
                        <a href="#" class="forgot-password-link" style="color: #94a3b8; text-decoration: none; font-size: 0.9rem;">
                            Forgot your password?
                        </a>
                    </div>
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

                <!-- OTP Verification Form for Login -->
                <form id="otpLoginForm" class="auth-form" method="POST" action="" style="display: none;">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="email" id="otpLoginEmail">
                    <input type="hidden" name="password" id="otpLoginPassword">
                    
                    <div class="otp-form-container">
                        <div class="otp-info">
                            <div class="otp-info-icon">📧</div>
                            <p class="otp-info-text">We've sent a 6-digit verification code to your email address</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="loginOTP" class="otp-label">Enter Verification Code</label>
                            <input type="text" id="loginOTP" name="otp" class="otp-input" 
                                   placeholder="000000"
                                   maxlength="6" pattern="[0-9]{6}" required autocomplete="off">
                            <div class="otp-instructions">
                                Enter the 6-digit code from your email
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full">
                            <i class="bi bi-shield-check"></i>
                            <span>Verify & Sign In</span>
                        </button>
                        
                        <div class="form-group" style="text-align: center; margin-top: 1.5rem;">
                            <a href="#" class="resend-otp-link" style="color: #94a3b8; text-decoration: none; font-size: 0.9rem;">
                                Didn't receive code? Resend
                            </a>
                        </div>
                    </div>
                </form>

                <!-- OTP Verification Form for Registration -->
                <form id="otpRegisterForm" class="auth-form" method="POST" action="" style="display: none;">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="full_name" id="otpRegisterFullName">
                    <input type="hidden" name="username" id="otpRegisterUsername">
                    <input type="hidden" name="email" id="otpRegisterEmail">
                    <input type="hidden" name="password" id="otpRegisterPassword">
                    <input type="hidden" name="confirm_password" id="otpRegisterConfirmPassword">
                    
                    <div class="otp-form-container">
                        <div class="otp-info">
                            <div class="otp-info-icon">🎉</div>
                            <p class="otp-info-text">Welcome! Please verify your email address to complete registration</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="registerOTP" class="otp-label">Enter Verification Code</label>
                            <input type="text" id="registerOTP" name="otp" class="otp-input" 
                                   placeholder="000000"
                                   maxlength="6" pattern="[0-9]{6}" required autocomplete="off">
                            <div class="otp-instructions">
                                Enter the 6-digit code from your email
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full">
                            <i class="bi bi-shield-check"></i>
                            <span>Verify Email</span>
                        </button>
                        
                        <div class="form-group" style="text-align: center; margin-top: 1.5rem;">
                            <a href="#" class="resend-register-otp-link" style="color: #94a3b8; text-decoration: none; font-size: 0.9rem;">
                                Didn't receive code? Resend
                            </a>
                        </div>
                    </div>
                </form>

                <!-- Forgot Password Form -->
                <form id="forgotPasswordForm" class="auth-form" method="POST" action="" style="display: none;">
                    <input type="hidden" name="action" value="forgot_password">
                    
                    <div class="form-group">
                        <label for="forgotEmail" class="form-label">Email Address</label>
                        <input type="email" id="forgotEmail" name="email" class="form-input" 
                               placeholder="Enter your email address"
                               required autocomplete="email">
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="bi bi-envelope"></i>
                        <span>Send Reset Code</span>
                    </button>
                    
                    <div class="form-group" style="text-align: center; margin-top: 1rem;">
                        <a href="#" class="back-to-login-link" style="color: #94a3b8; text-decoration: none; font-size: 0.9rem;">
                            Back to Sign In
                        </a>
                    </div>
                </form>

                <!-- Reset Password Form -->
                <form id="resetPasswordForm" class="auth-form" method="POST" action="" style="display: none;">
                    <input type="hidden" name="action" value="reset_password">
                    
                    <div class="otp-form-container">
                        <div class="otp-info">
                            <div class="otp-info-icon">🔑</div>
                            <p class="otp-info-text">Enter the reset code sent to your email and create a new password</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="resetOTP" class="otp-label">Enter Reset Code</label>
                            <input type="text" id="resetOTP" name="otp" class="otp-input" 
                                   placeholder="000000"
                                   maxlength="6" pattern="[0-9]{6}" required autocomplete="off">
                            <div class="otp-instructions">
                                Enter the 6-digit code from your email
                            </div>
                        </div>

                    <div class="form-group">
                        <label for="newPassword" class="form-label">New Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="newPassword" name="new_password" class="form-input" 
                                   placeholder="Enter new password"
                                   required autocomplete="new-password" minlength="6">
                            <button type="button" class="password-toggle" onclick="togglePassword('newPassword')">
                                <i class="bi bi-eye" id="newPasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirmNewPassword" class="form-label">Confirm New Password</label>
                        <div class="password-input-wrapper">
                            <input type="password" id="confirmNewPassword" name="confirm_password" class="form-input" 
                                   placeholder="Confirm new password"
                                   required autocomplete="new-password" minlength="6">
                            <button type="button" class="password-toggle" onclick="togglePassword('confirmNewPassword')">
                                <i class="bi bi-eye" id="confirmNewPasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="bi bi-key"></i>
                        <span>Reset Password</span>
                    </button>
                    
                        <div class="form-group" style="text-align: center; margin-top: 1.5rem;">
                            <a href="#" class="resend-reset-otp-link" style="color: #94a3b8; text-decoration: none; font-size: 0.9rem;">
                                Didn't receive code? Resend
                            </a>
                        </div>
                    </div>
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
        <?php if (isset($show_otp_form) && $show_otp_form): ?>
            showOTPLoginForm();
        <?php endif; ?>
        
        <?php if (isset($show_otp_verification) && $show_otp_verification): ?>
            showOTPRegisterForm();
        <?php endif; ?>
        
        <?php if (isset($show_forgot_password_otp) && $show_forgot_password_otp): ?>
            showResetPasswordForm();
        <?php endif; ?>
        
        <?php if (isset($show_login_form) && $show_login_form): ?>
            showLoginForm();
        <?php endif; ?>

        // Forgot password link
        document.querySelector('.forgot-password-link')?.addEventListener('click', function(e) {
            e.preventDefault();
            showForgotPasswordForm();
        });

        // Back to login link
        document.querySelector('.back-to-login-link')?.addEventListener('click', function(e) {
            e.preventDefault();
            showLoginForm();
        });

        // Resend OTP links
        document.querySelector('.resend-otp-link')?.addEventListener('click', function(e) {
            e.preventDefault();
            resendOTP('login');
        });

        document.querySelector('.resend-register-otp-link')?.addEventListener('click', function(e) {
            e.preventDefault();
            resendOTP('register');
        });

        document.querySelector('.resend-reset-otp-link')?.addEventListener('click', function(e) {
            e.preventDefault();
            resendOTP('forgot_password');
        });

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

        function showOTPLoginForm() {
            hideAllForms();
            document.querySelector('.form-toggle').style.display = 'none';
            updateHeader('Verify Login', 'Enter the code sent to your email');
            
            // Populate hidden fields
            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            document.getElementById('otpLoginEmail').value = email;
            document.getElementById('otpLoginPassword').value = password;
            
            // Show with animation
            showOTPFormWithAnimation('otpLoginForm');
        }

        function showOTPRegisterForm() {
            hideAllForms();
            document.querySelector('.form-toggle').style.display = 'none';
            updateHeader('Verify Email', 'Enter the code sent to your email');
            
            // Populate hidden fields
            document.getElementById('otpRegisterFullName').value = document.getElementById('signupFullName').value;
            document.getElementById('otpRegisterUsername').value = document.getElementById('signupUsername').value;
            document.getElementById('otpRegisterEmail').value = document.getElementById('signupEmail').value;
            document.getElementById('otpRegisterPassword').value = document.getElementById('signupPassword').value;
            document.getElementById('otpRegisterConfirmPassword').value = document.getElementById('signupConfirmPassword').value;
            
            // Show with animation
            showOTPFormWithAnimation('otpRegisterForm');
        }

        function showForgotPasswordForm() {
            hideAllForms();
            document.getElementById('forgotPasswordForm').style.display = 'block';
            document.querySelector('.form-toggle').style.display = 'none';
            updateHeader('Reset Password', 'Enter your email to receive a reset code');
        }

        function showResetPasswordForm() {
            hideAllForms();
            document.querySelector('.form-toggle').style.display = 'none';
            updateHeader('Reset Password', 'Enter the code and your new password');
            
            // Show with animation
            showOTPFormWithAnimation('resetPasswordForm');
        }

        function hideAllForms() {
            const forms = ['loginForm', 'registerForm', 'otpLoginForm', 'otpRegisterForm', 'forgotPasswordForm', 'resetPasswordForm'];
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

        function resendOTP(type) {
            const email = getEmailForOTP(type);
            if (!email) {
                alert('Email not found. Please try again.');
                return;
            }

            // Create a form to resend OTP
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = type === 'forgot_password' ? 'forgot_password' : 'login';
            
            const emailInput = document.createElement('input');
            emailInput.type = 'hidden';
            emailInput.name = 'email';
            emailInput.value = email;
            
            form.appendChild(actionInput);
            form.appendChild(emailInput);
            document.body.appendChild(form);
            form.submit();
        }

        function getEmailForOTP(type) {
            switch(type) {
                case 'login':
                    return document.getElementById('otpLoginEmail')?.value || document.getElementById('loginEmail')?.value;
                case 'register':
                    return document.getElementById('otpRegisterEmail')?.value || document.getElementById('signupEmail')?.value;
                case 'forgot_password':
                    return document.getElementById('forgotEmail')?.value;
                default:
                    return null;
            }
        }

        // OTP input formatting and visual feedback
        document.querySelectorAll('input[name="otp"]').forEach(input => {
            input.addEventListener('input', function(e) {
                // Only allow numbers
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
                
                // Add visual feedback for each digit
                if (e.target.value.length > 0) {
                    e.target.style.borderColor = '#10b981';
                    e.target.style.background = 'rgba(16, 185, 129, 0.1)';
                } else {
                    e.target.style.borderColor = 'rgba(255, 255, 255, 0.2)';
                    e.target.style.background = 'rgba(255, 255, 255, 0.08)';
                }
                
                // Auto-submit when 6 digits are entered
                if (e.target.value.length === 6) {
                    // Add success animation
                    e.target.style.borderColor = '#10b981';
                    e.target.style.background = 'rgba(16, 185, 129, 0.2)';
                    e.target.style.transform = 'scale(1.05)';
                    
                    setTimeout(() => {
                        e.target.closest('form').submit();
                    }, 800);
                }
            });
            
            // Add focus effects
            input.addEventListener('focus', function(e) {
                e.target.style.transform = 'scale(1.02)';
                e.target.style.boxShadow = '0 0 0 6px rgba(96, 165, 250, 0.3), 0 12px 40px rgba(0, 0, 0, 0.4)';
            });
            
            input.addEventListener('blur', function(e) {
                e.target.style.transform = 'scale(1)';
                e.target.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.3)';
            });
        });
        
        // Add animation when OTP form is shown
        function showOTPFormWithAnimation(formId) {
            const form = document.getElementById(formId);
            if (form) {
                form.style.display = 'block';
                form.style.opacity = '0';
                form.style.transform = 'translateY(30px)';
                
                setTimeout(() => {
                    form.style.transition = 'all 0.5s ease-out';
                    form.style.opacity = '1';
                    form.style.transform = 'translateY(0)';
                }, 100);
                
                // Focus on OTP input
                setTimeout(() => {
                    const otpInput = form.querySelector('input[name="otp"]');
                    if (otpInput) {
                        otpInput.focus();
                    }
                }, 600);
            }
        }
    </script>
</body>
</html>
