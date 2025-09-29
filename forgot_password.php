<?php
session_start();
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/email/functions.php';

// Track page view
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
trackPageView('forgot_password', $userId, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, session_id());

// Handle OTP sending
$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';

// Clear session messages after displaying
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $_SESSION['error_message'] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = 'Please enter a valid email address.';
    } else {
        try {
            $pdo = getDB();
            
            // Check if email exists in users table
            $stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE email = ? AND is_active = TRUE");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Check for OTP cooldown (30 seconds)
                if (isset($_SESSION['otp_last_sent']) && (time() - $_SESSION['otp_last_sent']) < 30) {
                    $remainingTime = 30 - (time() - $_SESSION['otp_last_sent']);
                    $_SESSION['error_message'] = "Please wait {$remainingTime} seconds before requesting another OTP.";
                } else {
                    // Generate 6-digit OTP
                    $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                    
                    // Store OTP in session with expiration (5 minutes)
                    $_SESSION['reset_otp'] = $otp;
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['otp_expires'] = time() + 300; // 5 minutes from now
                    $_SESSION['otp_last_sent'] = time(); // Track when OTP was sent
                    
                    // Send OTP via email
                    $emailResult = sendOTPEmail($email, $otp, $user['full_name']);
                    
                    if ($emailResult['success']) {
                        $_SESSION['success_message'] = $emailResult['message'];
                        
                        // Log the password reset request
                        logUserActivity($user['id'], 'password_reset_request', 'Password reset OTP requested and sent', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['action' => 'password_reset_request', 'email_sent' => true]));
                        
                        // Redirect to OTP verification page
                        header('Location: email/verify_otp.php');
                        exit;
                    } else {
                        $_SESSION['error_message'] = $emailResult['message'];
                        
                        // Log the failed email attempt
                        logUserActivity($user['id'], 'password_reset_request', 'Password reset OTP requested but email failed', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['action' => 'password_reset_request', 'email_sent' => false, 'error' => $emailResult['message']]));
                    }
                }
                
            } else {
                $_SESSION['error_message'] = 'No account found with this email address.';
            }
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            $_SESSION['error_message'] = 'Unable to process your request. Please try again.';
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
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

        /* Cooldown Timer */
        .cooldown-timer {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            text-align: center;
            color: #93c5fd;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .cooldown-timer i {
            font-size: 1.1rem;
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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
                    <p class="auth-subtitle">Enter your email to receive a verification code</p>
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

                <!-- Forgot Password Form -->
                <form method="POST" action="" id="otpForm">
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                               placeholder="Enter your email address"
                               required autocomplete="email">
                    </div>

                    <button type="submit" class="btn btn-primary btn-full" id="otpButton">
                        <i class="bi bi-send"></i>
                        <span id="buttonText">Send OTP</span>
                    </button>
                    
                    <!-- Cooldown Timer -->
                    <div id="cooldownTimer" class="cooldown-timer" style="display: none;">
                        <i class="bi bi-clock"></i>
                        <span>Please wait <span id="countdown">30</span> seconds before requesting another OTP</span>
                    </div>
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
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // OTP Cooldown Timer
        function startCooldownTimer() {
            const button = document.getElementById('otpButton');
            const buttonText = document.getElementById('buttonText');
            const cooldownTimer = document.getElementById('cooldownTimer');
            const countdown = document.getElementById('countdown');
            
            let timeLeft = 30;
            
            // Disable button and show timer
            button.disabled = true;
            buttonText.textContent = 'Please wait...';
            cooldownTimer.style.display = 'flex';
            
            const timer = setInterval(() => {
                timeLeft--;
                countdown.textContent = timeLeft;
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    button.disabled = false;
                    buttonText.textContent = 'Send OTP';
                    cooldownTimer.style.display = 'none';
                }
            }, 1000);
        }

        // Check if there's an error message about cooldown and start timer
        document.addEventListener('DOMContentLoaded', function() {
            const errorMessage = document.querySelector('.alert-error span');
            if (errorMessage && errorMessage.textContent.includes('Please wait')) {
                startCooldownTimer();
            }
        });
    </script>
</body>
</html>
