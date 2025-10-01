<?php
session_start();

// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Check if admin reset session exists
if (!isset($_SESSION['admin_reset_email']) || !isset($_SESSION['admin_reset_otp'])) {
    $_SESSION['error_message'] = 'Please request a new OTP.';
    header('Location: forgot_password.php');
    exit;
}

// Check if OTP has expired - show warning but allow resend
$otpExpired = isset($_SESSION['admin_otp_expires']) && time() > $_SESSION['admin_otp_expires'];

// Handle POST request before displaying anything
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp'] ?? '');
    
    if (empty($entered_otp)) {
        $_SESSION['error_message'] = 'Please enter the OTP code.';
    } elseif (isset($_SESSION['admin_otp_expires']) && time() > $_SESSION['admin_otp_expires']) {
        $_SESSION['error_message'] = 'OTP has expired. Please resend a new code.';
    } elseif ($entered_otp !== $_SESSION['admin_reset_otp']) {
        $_SESSION['error_message'] = 'Invalid OTP code. Please try again.';
    } else {
        // OTP is correct
        $_SESSION['admin_otp_verified'] = true;
        header('Location: reset_password.php');
        exit;
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get and clear messages after POST redirect
$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';

// Show expired message if OTP has expired
if ($otpExpired && empty($error_message)) {
    $error_message = 'Your OTP has expired. Please click "Resend OTP" to get a new code.';
}

unset($_SESSION['error_message']);
unset($_SESSION['success_message']);

// Calculate remaining time for OTP expiry
$remainingTime = isset($_SESSION['admin_otp_expires']) ? $_SESSION['admin_otp_expires'] - time() : 0;

// Calculate resend cooldown (30 seconds from last OTP send)
$resendCooldown = 0;
if (isset($_SESSION['admin_otp_last_sent'])) {
    $timeSinceLastSend = time() - $_SESSION['admin_otp_last_sent'];
    $resendCooldown = max(0, 30 - $timeSinceLastSend);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - Admin Portal</title>
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

        .admin-verify-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .admin-verify-card {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
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
            margin-bottom: 10px;
        }

        .email-display {
            text-align: center;
            color: #3b82f6;
            font-weight: 600;
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

        .otp-inputs-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 10px;
        }

        .otp-input {
            width: 50px;
            height: 60px;
            background: rgba(15, 23, 42, 0.6);
            border: 2px solid rgba(59, 130, 246, 0.3);
            border-radius: 12px;
            color: #ffffff;
            font-size: 1.8rem;
            text-align: center;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .otp-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: rgba(15, 23, 42, 0.8);
        }

        .otp-input::-webkit-outer-spin-button,
        .otp-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .otp-input[type=number] {
            -moz-appearance: textfield;
        }

        #otpHidden {
            position: absolute;
            left: -9999px;
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
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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

        .resend-link {
            text-align: center;
            margin-top: 20px;
        }

        .resend-link button {
            background: none;
            border: none;
            color: #3b82f6;
            text-decoration: none;
            font-size: 0.9rem;
            cursor: pointer;
            padding: 5px 10px;
        }

        .resend-link button:hover:not(:disabled) {
            text-decoration: underline;
        }

        .resend-link button:disabled {
            color: #64748b;
            cursor: not-allowed;
        }

        .resend-cooldown {
            color: #94a3b8;
            font-size: 0.85rem;
            margin-top: 5px;
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
            .admin-verify-card {
                padding: 30px 20px;
            }

            .admin-title {
                font-size: 1.5rem;
            }

            .otp-inputs-container {
                gap: 8px;
            }

            .otp-input {
                width: 45px;
                height: 55px;
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .otp-inputs-container {
                gap: 6px;
            }

            .otp-input {
                width: 40px;
                height: 50px;
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-verify-container">
        <div class="admin-verify-card">
            <div class="admin-logo">
                <img src="../assets/logo.png" alt="Logo">
                <h1 class="admin-title">Verify OTP</h1>
                <p class="admin-subtitle">Enter the 6-digit code sent to</p>
                <p class="email-display"><?php echo htmlspecialchars($_SESSION['admin_reset_email']); ?></p>
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

            <form method="POST" action="" id="otpForm">
                <div class="form-group">
                    <label class="form-label">OTP Code</label>
                    <div class="otp-inputs-container">
                        <input type="number" class="otp-input" maxlength="1" pattern="[0-9]" required autocomplete="off" data-index="0">
                        <input type="number" class="otp-input" maxlength="1" pattern="[0-9]" required autocomplete="off" data-index="1">
                        <input type="number" class="otp-input" maxlength="1" pattern="[0-9]" required autocomplete="off" data-index="2">
                        <input type="number" class="otp-input" maxlength="1" pattern="[0-9]" required autocomplete="off" data-index="3">
                        <input type="number" class="otp-input" maxlength="1" pattern="[0-9]" required autocomplete="off" data-index="4">
                        <input type="number" class="otp-input" maxlength="1" pattern="[0-9]" required autocomplete="off" data-index="5">
                    </div>
                    <input type="hidden" name="otp" id="otpHidden">
                </div>

                <button type="submit" class="btn" id="verifyBtn">
                    <span class="btn-spinner"></span>
                    <span class="btn-text">Verify OTP</span>
                </button>
            </form>

            <div class="resend-link">
                <button type="button" id="resendBtn">Resend OTP</button>
                <div class="resend-cooldown" id="resendCooldown" style="display: none;"></div>
            </div>

            <div class="back-link">
                <a href="admin_login.php">← Back to Admin Login</a>
            </div>
        </div>
    </div>

    <script>
        // OTP input handling
        const otpInputs = document.querySelectorAll('.otp-input');
        const otpHidden = document.getElementById('otpHidden');
        const form = document.getElementById('otpForm');

        // Auto-focus first input
        otpInputs[0].focus();

        otpInputs.forEach((input, index) => {
            // Handle input
            input.addEventListener('input', function(e) {
                // Only allow single digit
                if (this.value.length > 1) {
                    this.value = this.value.slice(0, 1);
                }

                // Update hidden field with complete OTP
                updateOTP();

                // Move to next input
                if (this.value !== '' && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
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
                const pastedData = e.clipboardData.getData('text').trim();
                const digits = pastedData.match(/\d/g);
                
                if (digits) {
                    digits.forEach((digit, i) => {
                        if (index + i < otpInputs.length) {
                            otpInputs[index + i].value = digit;
                        }
                    });
                    updateOTP();
                    
                    // Focus on next empty input or last input
                    const nextIndex = Math.min(index + digits.length, otpInputs.length - 1);
                    otpInputs[nextIndex].focus();
                }
            });

            // Prevent typing non-digits
            input.addEventListener('keypress', function(e) {
                if (!/^\d$/.test(e.key)) {
                    e.preventDefault();
                }
            });
        });

        function updateOTP() {
            const otp = Array.from(otpInputs).map(input => input.value).join('');
            otpHidden.value = otp;
        }

        // Validate form submission
        const verifyBtn = document.getElementById('verifyBtn');
        const btnText = verifyBtn.querySelector('.btn-text');
        
        form.addEventListener('submit', function(e) {
            updateOTP();
            if (otpHidden.value.length !== 6) {
                e.preventDefault();
                alert('Please enter all 6 digits of the OTP code.');
                return false;
            }
            
            // Show loading state
            verifyBtn.classList.add('loading');
            verifyBtn.disabled = true;
            btnText.textContent = 'Verifying...';
            
            // Make OTP inputs readonly instead of disabled (so values are still sent)
            otpInputs.forEach(input => input.setAttribute('readonly', true));
        });

        // Auto-hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // Resend OTP functionality
        const resendBtn = document.getElementById('resendBtn');
        const resendCooldown = document.getElementById('resendCooldown');
        let resendCooldownTime = <?php echo $resendCooldown; ?>;
        let resendTimer = null;

        // Start cooldown on page load if there's remaining time
        if (resendCooldownTime > 0) {
            startResendCooldown();
        }

        function startResendCooldown(initialTime = null) {
            if (initialTime !== null) {
                resendCooldownTime = initialTime;
            }
            
            if (resendCooldownTime <= 0) {
                resendBtn.disabled = false;
                resendCooldown.style.display = 'none';
                return;
            }
            
            resendBtn.disabled = true;
            resendCooldown.style.display = 'block';
            resendCooldown.textContent = `Wait ${resendCooldownTime}s to resend`;
            
            if (resendTimer) {
                clearInterval(resendTimer);
            }
            
            resendTimer = setInterval(() => {
                resendCooldownTime--;
                resendCooldown.textContent = `Wait ${resendCooldownTime}s to resend`;
                
                if (resendCooldownTime <= 0) {
                    clearInterval(resendTimer);
                    resendBtn.disabled = false;
                    resendCooldown.style.display = 'none';
                }
            }, 1000);
        }

        resendBtn.addEventListener('click', function() {
            if (this.disabled) return;
            
            // Show loading state
            this.disabled = true;
            this.textContent = 'Sending...';
            
            // Send AJAX request to resend OTP
            fetch('forgot_password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'email=<?php echo urlencode($_SESSION['admin_reset_email']); ?>'
            })
            .then(response => response.text())
            .then(data => {
                // Show success message
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success';
                alertDiv.innerHTML = '<span>✓</span><span>New OTP sent to your email</span>';
                document.querySelector('.admin-verify-card').insertBefore(alertDiv, document.querySelector('form'));
                
                // Hide alert after 3 seconds
                setTimeout(() => {
                    alertDiv.style.opacity = '0';
                    setTimeout(() => alertDiv.remove(), 300);
                }, 3000);
                
                // Reset button text and start cooldown with full 30 seconds
                this.textContent = 'Resend OTP';
                startResendCooldown(30);
            })
            .catch(error => {
                console.error('Error:', error);
                this.disabled = false;
                this.textContent = 'Resend OTP';
                alert('Failed to resend OTP. Please try again.');
            });
        });
    </script>
</body>
</html>

