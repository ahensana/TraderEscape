<?php
/**
 * OTP Test Page
 * Use this to test if OTP functionality is working
 */

require_once __DIR__ . '/includes/otp_service.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = trim($_POST['email'] ?? '');
    
    if ($action === 'test_otp') {
        try {
            $otpService = new OTPService();
            $otp = $otpService->generateOTP();
            
            if ($otpService->storeOTP($email, $otp, 'login') && $otpService->sendOTPEmail($email, $otp, 'login')) {
                $message = "✅ OTP sent successfully! Check your email and the log files.";
            } else {
                $error = "❌ Failed to send OTP. Check the logs.";
            }
        } catch (Exception $e) {
            $error = "❌ Error: " . $e->getMessage();
        }
    }
}

// Check if log files exist
$otpLogExists = file_exists(__DIR__ . '/otp_log.txt');
$emailLogExists = file_exists(__DIR__ . '/email_log.txt');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Test - TraderEscape</title>
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
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        input[type="email"] {
            width: 100%;
            padding: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            font-size: 1rem;
        }
        button {
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            color: #1e293b;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        button:hover {
            transform: translateY(-2px);
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 OTP System Test</h1>
        <p>Use this page to test if the OTP system is working correctly.</p>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="test_otp">
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email address">
            </div>
            <button type="submit">Send Test OTP</button>
        </form>
        
        <div class="log-section">
            <h3>📋 Log Files Status</h3>
            <p><strong>OTP Log:</strong> <?php echo $otpLogExists ? '✅ Exists' : '❌ Not found'; ?></p>
            <p><strong>Email Log:</strong> <?php echo $emailLogExists ? '✅ Exists' : '❌ Not found'; ?></p>
            
            <?php if ($otpLogExists): ?>
                <h4>OTP Log Content:</h4>
                <div class="log-file">
                    <?php echo htmlspecialchars(file_get_contents(__DIR__ . '/otp_log.txt')); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($emailLogExists): ?>
                <h4>Email Log Content:</h4>
                <div class="log-file">
                    <?php echo htmlspecialchars(file_get_contents(__DIR__ . '/email_log.txt')); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="log-section">
            <h3>🔧 Troubleshooting</h3>
            <ul>
                <li><strong>No email received:</strong> Check your spam folder and email log above</li>
                <li><strong>OTP not working:</strong> Check OTP log above for the generated code</li>
                <li><strong>Database errors:</strong> Make sure you've run the SQL setup</li>
                <li><strong>PHP mail issues:</strong> Configure your server's mail settings</li>
            </ul>
        </div>
    </div>
</body>
</html>
