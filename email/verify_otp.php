<?php
session_start();
require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/functions.php';

// Track page view
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
trackPageView('verify_otp', $userId, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, session_id());

// Handle OTP verification
$error_message = '';
$success_message = '';

// Check if user has a valid OTP session
if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['otp_expires']) || !isset($_SESSION['reset_email'])) {
    header('Location: ../forgot_password.php');
    exit;
}

// Check if OTP has expired
if (time() > $_SESSION['otp_expires']) {
    unset($_SESSION['reset_otp']);
    unset($_SESSION['otp_expires']);
    unset($_SESSION['reset_email']);
    $_SESSION['error_message'] = 'OTP has expired. Please request a new one.';
    header('Location: ../forgot_password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is a resend OTP request
    if (isset($_POST['resend_otp'])) {
        // Check for OTP cooldown (30 seconds)
        if (isset($_SESSION['otp_last_sent']) && (time() - $_SESSION['otp_last_sent']) < 30) {
            $remainingTime = 30 - (time() - $_SESSION['otp_last_sent']);
            $_SESSION['error_message'] = "Please wait {$remainingTime} seconds before requesting another OTP.";
        } else {
            // Generate new 6-digit OTP
            $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // Update OTP in session with new expiration (5 minutes)
            $_SESSION['reset_otp'] = $otp;
            $_SESSION['otp_expires'] = time() + 300; // 5 minutes from now
            $_SESSION['otp_last_sent'] = time(); // Track when OTP was sent
            
            // Send new OTP via email
            $emailResult = sendOTPEmail($_SESSION['reset_email'], $otp, 'User');
            
            if ($emailResult['success']) {
                $_SESSION['success_message'] = 'New OTP has been sent to your email.';
            } else {
                $_SESSION['error_message'] = $emailResult['message'];
            }
        }
        
        // Redirect to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    // Handle OTP verification
    $otp = trim($_POST['otp'] ?? '');
    
    if (empty($otp)) {
        $_SESSION['error_message'] = 'Please enter the OTP code.';
    } elseif (strlen($otp) !== 6 || !is_numeric($otp)) {
        $_SESSION['error_message'] = 'Please enter a valid 6-digit OTP code.';
    } else {
        $result = validateOTP($otp);
        
        if ($result['success']) {
            // OTP is valid, redirect to password reset page
            $_SESSION['otp_verified'] = true;
            header('Location: ../reset_password.php');
            exit;
        } else {
            $_SESSION['error_message'] = $result['message'];
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

include 'header.php';
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
            text-align: center;
            letter-spacing: 0.5rem;
            font-family: 'Courier New', monospace;
            font-weight: 700;
        }

        .form-input:focus {
            outline: none;
            border-color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
        }

        .form-input::placeholder {
            color: #64748b;
            letter-spacing: normal;
        }

        /* OTP Container */
        .otp-container {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            margin: 1rem 0;
        }

        .otp-input {
            width: 3rem;
            height: 3rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            font-family: 'Courier New', monospace;
            backdrop-filter: blur(10px);
            transition: all 0.2s ease;
        }

        .otp-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            transform: scale(1.05);
        }

        .otp-input:valid {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }

        .otp-input:invalid:not(:placeholder-shown) {
            border-color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
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

        /* OTP Info */
        .otp-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .otp-info-text {
            color: #93c5fd;
            font-size: 0.9rem;
            margin: 0;
        }

        /* Resend Section */
        .resend-section {
            margin-top: 1.5rem;
            text-align: center;
        }

        .resend-text {
            color: #94a3b8;
            font-size: 0.9rem;
            margin: 0 0 0.5rem 0;
            font-weight: 400;
        }

        .resend-form {
            display: inline-block;
        }

        .resend-link {
            background: none;
            border: none;
            color: #3b82f6;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0;
            transition: color 0.2s ease;
        }

        .resend-link:hover {
            color: #2563eb;
            text-decoration: none;
        }

        .resend-link:disabled {
            color: #64748b;
            cursor: not-allowed;
        }

        .countdown-timer {
            color: #94a3b8;
            font-weight: 400;
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
                    <h1 class="auth-title">Verify OTP</h1>
                    <p class="auth-subtitle">Enter the 6-digit code sent to your email</p>
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

                <!-- OTP Info -->
                <div class="otp-info">
                    <p class="otp-info-text">
                        <i class="bi bi-envelope"></i>
                        OTP sent to: <?php echo htmlspecialchars($_SESSION['reset_email']); ?>
                    </p>
                </div>

                <!-- OTP Verification Form -->
                <form method="POST" action="" id="otpForm">
                    <div class="form-group">
                        <label class="form-label">Enter OTP Code</label>
                        <div class="otp-container">
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                            <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" required>
                        </div>
                        <input type="hidden" name="otp" id="otpValue">
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="bi bi-shield-check"></i>
                        <span>Verify OTP</span>
                    </button>
                </form>

                <!-- Resend OTP Section -->
                <div class="resend-section">
                    <p class="resend-text">Didn't receive code?</p>
                    <form method="POST" action="" id="resendForm" class="resend-form">
                        <input type="hidden" name="resend_otp" value="1">
                        <button type="submit" class="resend-link" id="resendButton">
                            <span id="resendText">Resend</span>
                            <span id="resendCountdown" class="countdown-timer" style="display: none;">- 00 : 30</span>
                        </button>
                    </form>
                </div>

                <!-- Back to Forgot Password Link -->
                <div class="back-link">
                    <a href="../forgot_password.php">
                        <i class="bi bi-arrow-left"></i>
                        Back to Forgot Password
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script>
        // OTP Input Handling
        const otpInputs = document.querySelectorAll('.otp-input');
        const otpValue = document.getElementById('otpValue');
        const otpForm = document.getElementById('otpForm');

        // Auto-focus on first input
        otpInputs[0].focus();

        // Handle OTP input logic
        otpInputs.forEach((input, index) => {
            // Only allow numbers
            input.addEventListener('input', function(e) {
                // Remove non-numeric characters
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Move to next input if current is filled
                if (this.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
                
                // Update hidden field
                updateOTPValue();
            });

            // Handle backspace
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });

            // Handle paste
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
                
                // Fill inputs with pasted data
                for (let i = 0; i < Math.min(pastedData.length, otpInputs.length); i++) {
                    otpInputs[i].value = pastedData[i];
                }
                
                // Focus on next empty input or last input
                const nextEmptyIndex = Math.min(pastedData.length, otpInputs.length - 1);
                otpInputs[nextEmptyIndex].focus();
                
                updateOTPValue();
            });
        });

        // Update hidden field with complete OTP
        function updateOTPValue() {
            const otp = Array.from(otpInputs).map(input => input.value).join('');
            otpValue.value = otp;
        }

        // Auto-submit when all fields are filled
        function checkAutoSubmit() {
            const otp = otpValue.value;
            if (otp.length === 6) {
                // Small delay for better UX
                setTimeout(() => {
                    otpForm.submit();
                }, 500);
            }
        }

        // Check for auto-submit on each input
        otpInputs.forEach(input => {
            input.addEventListener('input', checkAutoSubmit);
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // Resend OTP Cooldown Timer
        function startResendCooldownTimer() {
            const button = document.getElementById('resendButton');
            const buttonText = document.getElementById('resendText');
            const countdown = document.getElementById('resendCountdown');
            
            let timeLeft = 30;
            
            // Disable button and show timer
            button.disabled = true;
            buttonText.textContent = 'Resend';
            countdown.style.display = 'inline';
            
            const timer = setInterval(() => {
                timeLeft--;
                
                // Format as MM:SS
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                const formattedTime = `${minutes.toString().padStart(2, '0')} : ${seconds.toString().padStart(2, '0')}`;
                countdown.textContent = `- ${formattedTime}`;
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    button.disabled = false;
                    buttonText.textContent = 'Resend';
                    countdown.style.display = 'none';
                }
            }, 1000);
        }

        // Check if there's an error message about cooldown and start timer
        document.addEventListener('DOMContentLoaded', function() {
            const errorMessage = document.querySelector('.alert-error span');
            if (errorMessage && errorMessage.textContent.includes('Please wait')) {
                startResendCooldownTimer();
            } else {
                // Start cooldown timer immediately when page loads (from forgot_password.php)
                startResendCooldownTimer();
            }
        });

        // Add click handler to resend button to start cooldown
        document.getElementById('resendButton').addEventListener('click', function(e) {
            // Start cooldown timer immediately when clicked
            startResendCooldownTimer();
        });
    </script>
</body>
</html>
