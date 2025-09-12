<?php
/**
 * Simple OTP Test - Just enter email and click send
 * Uses pre-configured Gmail settings
 */

session_start();
require_once __DIR__ . '/working_email_service.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        try {
            // Generate OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // Send OTP email
            $result = sendOTPEmailWorking($email, $otp, 'login');
            
            if ($result) {
                $message = "✅ OTP sent successfully to $email! Check your inbox.";
            } else {
                $error = "❌ Failed to send OTP. Check if Gmail is configured.";
            }
        } catch (Exception $e) {
            $error = "❌ Error: " . $e->getMessage();
        }
    }
}

// Check if Gmail is configured
$isConfigured = false;
$currentEmail = '';
if (file_exists(__DIR__ . '/../email_config.json')) {
    $config = json_decode(file_get_contents(__DIR__ . '/../email_config.json'), true);
    $isConfigured = $config && isset($config['configured']) && $config['configured'];
    if ($isConfigured) {
        $currentEmail = $config['smtp_username'];
    }
}

// Check log files
$otpLogExists = file_exists(__DIR__ . '/../otp_log.txt');
$emailLogExists = file_exists(__DIR__ . '/../email_log.txt');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple OTP Test - TraderEscape</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #0f172a;
            color: #ffffff;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }
        input[type="email"] {
            width: 100%;
            padding: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            font-size: 1rem;
            box-sizing: border-box;
        }
        input[type="email"]:focus {
            outline: none;
            border-color: #3b82f6;
        }
        button {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-size: 1.1rem;
        }
        button:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #059669, #047857);
        }
        button:disabled {
            background: #6b7280;
            cursor: not-allowed;
            transform: none;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        .status {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            text-align: center;
        }
        .instructions {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        .warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        .log-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        .log-file {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 0.9rem;
            max-height: 200px;
            overflow-y: auto;
        }
        .links {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .links a {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
            flex: 1;
            text-align: center;
        }
        .links a:hover {
            background: rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Simple OTP Test</h1>
        <p>Just enter your email and click send. That's it!</p>
        
        <div class="status">
            <h3>System Status</h3>
            <p>✅ <strong>Sender Email:</strong> TheTradersEscape@gmail.com</p>
            <?php if ($isConfigured): ?>
                <p>✅ <strong>Gmail Configured:</strong> Yes</p>
                <p>🎉 Ready to send emails!</p>
            <?php else: ?>
                <p>❌ <strong>Gmail NOT Configured</strong></p>
                <p>Configure Gmail App Password first to send emails</p>
            <?php endif; ?>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!$isConfigured): ?>
            <div class="warning">
                <h3>⚠️ Gmail Setup Required</h3>
                <p>You need to configure Gmail first before you can send emails.</p>
                <p>Click the "Setup Gmail" button below to configure your Gmail account.</p>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Your Email Address:</label>
                <input type="email" id="email" name="email" required placeholder="your-email@example.com" <?php echo $isConfigured ? '' : 'disabled'; ?>>
            </div>
            
            <button type="submit" <?php echo $isConfigured ? '' : 'disabled'; ?>>
                <?php echo $isConfigured ? '🚀 Send OTP Email' : '❌ Gmail Not Configured'; ?>
            </button>
        </form>
        
        <div class="links">
            <a href="get_gmail_password.php">Setup Gmail</a>
            <a href="working_email_fixed.php">Test Email</a>
            <a href="test_working_otp.php">Advanced OTP Test</a>
        </div>
        
        <div class="instructions">
            <h3>📋 How This Works</h3>
            <ul>
                <li>✅ Enter your email address</li>
                <li>✅ Click "Send OTP Email"</li>
                <li>✅ Check your inbox for the OTP</li>
                <li>✅ OTP is also saved in logs below</li>
            </ul>
        </div>
        
        <div class="log-section">
            <h3>📋 Current OTP</h3>
            <?php if (file_exists(__DIR__ . '/../current_otp.txt')): ?>
                <div class="log-file">
                    <?php echo htmlspecialchars(file_get_contents(__DIR__ . '/../current_otp.txt')); ?>
                </div>
            <?php else: ?>
                <p>No OTP generated yet. Send a test OTP to see it here.</p>
            <?php endif; ?>
        </div>
        
        <?php if ($emailLogExists): ?>
            <div class="log-section">
                <h3>📧 Email Log</h3>
                <div class="log-file">
                    <?php 
                    $logContent = file_get_contents(__DIR__ . '/../email_log.txt');
                    $lines = explode("\n", $logContent);
                    $recentLines = array_slice($lines, -10); // Last 10 lines
                    echo htmlspecialchars(implode("\n", $recentLines));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
