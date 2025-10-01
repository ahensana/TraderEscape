<?php
session_start();

// Prevent page caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . '/../includes/db_functions.php';
require_once __DIR__ . '/../email/functions.php';

// Handle POST request before displaying anything
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $_SESSION['error_message'] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = 'Please enter a valid email address.';
    } else {
        try {
            $pdo = getDB();
            
            // Check if email exists in admins table
            $stmt = $pdo->prepare("SELECT id, username, email FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                // Check for OTP cooldown (30 seconds)
                if (isset($_SESSION['admin_otp_last_sent']) && (time() - $_SESSION['admin_otp_last_sent']) < 30) {
                    $remainingTime = 30 - (time() - $_SESSION['admin_otp_last_sent']);
                    $_SESSION['error_message'] = "Please wait {$remainingTime} seconds before requesting another OTP.";
                } else {
                    // Generate 6-digit OTP
                    $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                    
                    // Store OTP in session with expiration (5 minutes)
                    $_SESSION['admin_reset_otp'] = $otp;
                    $_SESSION['admin_reset_email'] = $email;
                    $_SESSION['admin_otp_expires'] = time() + 300; // 5 minutes from now
                    $_SESSION['admin_otp_last_sent'] = time(); // Track when OTP was sent
                    
                    // Send OTP via email
                    $emailResult = sendOTPEmail($email, $otp, $admin['username']);
                    
                    if ($emailResult['success']) {
                        $_SESSION['success_message'] = $emailResult['message'];
                        
                        // Redirect to OTP verification page
                        header('Location: verify_otp.php');
                        exit;
                    } else {
                        $_SESSION['error_message'] = $emailResult['message'];
                    }
                }
                
            } else {
                $_SESSION['error_message'] = 'No admin account found with this email address.';
            }
        } catch (Exception $e) {
            error_log("Admin password reset error: " . $e->getMessage());
            $_SESSION['error_message'] = 'Unable to process your request. Please try again.';
        }
    }
    
    // Redirect to prevent form resubmission
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
    <title>Admin Password Reset - The Trader's Escape</title>
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
            overflow-x: hidden;
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
            max-width: 450px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .admin-reset-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.5), transparent);
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

        .form-input {
            width: 100%;
            padding: 15px 20px;
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
            background: rgba(15, 23, 42, 0.8);
        }

        .form-input::placeholder {
            color: rgba(255, 255, 255, 0.5);
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
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-spinner {
            width: 18px;
            height: 18px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        .btn.loading .btn-spinner {
            display: inline-block;
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
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #3b82f6;
        }

        .cooldown-timer {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            text-align: center;
            color: #93c5fd;
            font-size: 0.9rem;
            display: none;
        }

        @media (max-width: 768px) {
            .admin-reset-card {
                padding: 30px 20px;
            }

            .admin-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-reset-container">
        <div class="admin-reset-card">
            <div class="admin-logo">
                <img src="../assets/logo.png" alt="Logo">
                <h1 class="admin-title">Admin Password Reset</h1>
                <p class="admin-subtitle">Enter your admin email to receive a verification code</p>
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
                    <label for="email" class="form-label">Admin Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           placeholder="Enter your admin email address"
                           required autocomplete="email">
                </div>

                    <button type="submit" class="btn" id="otpButton">
                        <span class="btn-spinner"></span>
                        <span id="buttonText">Send OTP</span>
                    </button>
                
                <div id="cooldownTimer" class="cooldown-timer">
                    <span>Please wait <span id="countdown">30</span> seconds before requesting another OTP</span>
                </div>
            </form>

            <div class="back-link">
                <a href="admin_login.php">← Back to Admin Login</a>
            </div>
        </div>
    </div>

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
            
            button.disabled = true;
            buttonText.textContent = 'Please wait...';
            cooldownTimer.style.display = 'block';
            
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

        document.addEventListener('DOMContentLoaded', function() {
            const errorMessage = document.querySelector('.alert-error span:last-child');
            if (errorMessage && errorMessage.textContent.includes('Please wait')) {
                startCooldownTimer();
            }
        });

        // Form submission with loading spinner
        document.getElementById('otpForm').addEventListener('submit', function(e) {
            const button = document.getElementById('otpButton');
            const buttonText = document.getElementById('buttonText');
            
            // Add loading state
            button.classList.add('loading');
            button.disabled = true;
            buttonText.textContent = 'Sending...';
        });
    </script>
</body>
</html>

