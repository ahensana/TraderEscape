<?php
/**
 * Gmail SMTP Setup Page
 * Use this to configure Gmail SMTP for sending emails
 */

session_start();
require_once __DIR__ . '/includes/email_service.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'configure_gmail') {
        $email = trim($_POST['email'] ?? '');
        $app_password = trim($_POST['app_password'] ?? '');
        
        if (empty($email) || empty($app_password)) {
            $error = 'Please fill in all fields.';
        } else {
            $mailer = new SimplePHPMailer();
            $mailer->configureGmail($email, $app_password);
            $message = 'Gmail configuration saved successfully!';
        }
    } elseif ($action === 'test_email') {
        $test_email = trim($_POST['test_email'] ?? '');
        
        if (empty($test_email)) {
            $error = 'Please enter a test email address.';
        } else {
            $result = sendOTPEmail($test_email, '123456', 'login');
            if ($result) {
                $message = 'Test email sent successfully! Check the log files for details.';
            } else {
                $error = 'Failed to send test email.';
            }
        }
    }
}

// Check if Gmail is configured
$mailer = new SimplePHPMailer();
$isConfigured = $mailer->loadGmailConfig();

// Check log files
$otpLogExists = file_exists(__DIR__ . '/otp_log.txt');
$emailLogExists = file_exists(__DIR__ . '/email_log.txt');
$sentEmailsExists = file_exists(__DIR__ . '/sent_emails.json');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gmail SMTP Setup - TraderEscape</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #0f172a;
            color: #ffffff;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
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
        input[type="email"], input[type="password"], input[type="text"] {
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
            margin-right: 1rem;
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
        .status-box {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
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
        .instructions {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        .step {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
            position: relative;
        }
        .step::before {
            content: counter(step-counter);
            counter-increment: step-counter;
            position: absolute;
            left: 0;
            top: 0;
            background: #3b82f6;
            color: white;
            width: 1.2rem;
            height: 1.2rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .instructions {
            counter-reset: step-counter;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Gmail SMTP Setup</h1>
        <p>Configure Gmail SMTP to send OTP emails reliably.</p>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="status-box">
            <h3>📊 Current Status</h3>
            <p><strong>Gmail Configured:</strong> <?php echo $isConfigured ? '✅ Yes' : '❌ No'; ?></p>
            <p><strong>OTP Log:</strong> <?php echo $otpLogExists ? '✅ Exists' : '❌ Not found'; ?></p>
            <p><strong>Email Log:</strong> <?php echo $emailLogExists ? '✅ Exists' : '❌ Not found'; ?></p>
            <p><strong>Sent Emails:</strong> <?php echo $sentEmailsExists ? '✅ Exists' : '❌ Not found'; ?></p>
        </div>
        
        <div class="instructions">
            <h3>🔧 Gmail Setup Instructions</h3>
            <div class="step">
                <strong>Enable 2-Factor Authentication</strong><br>
                Go to your Google Account settings and enable 2-Step Verification
            </div>
            <div class="step">
                <strong>Generate App Password</strong><br>
                Go to Security > 2-Step Verification > App passwords and generate a password for "Mail"
            </div>
            <div class="step">
                <strong>Configure Below</strong><br>
                Enter your Gmail address and the generated app password
            </div>
            <div class="step">
                <strong>Test Email</strong><br>
                Send a test email to verify everything works
            </div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="configure_gmail">
            <div class="form-group">
                <label for="email">Gmail Address:</label>
                <input type="email" id="email" name="email" required placeholder="your-email@gmail.com">
            </div>
            <div class="form-group">
                <label for="app_password">Gmail App Password:</label>
                <input type="password" id="app_password" name="app_password" required placeholder="16-character app password">
            </div>
            <button type="submit">Save Gmail Configuration</button>
        </form>
        
        <hr style="margin: 2rem 0; border: 1px solid rgba(255, 255, 255, 0.1);">
        
        <form method="POST">
            <input type="hidden" name="action" value="test_email">
            <div class="form-group">
                <label for="test_email">Test Email Address:</label>
                <input type="email" id="test_email" name="test_email" required placeholder="test@example.com">
            </div>
            <button type="submit">Send Test Email</button>
        </form>
        
        <?php if ($otpLogExists): ?>
            <div class="log-file">
                <h4>OTP Log:</h4>
                <?php echo htmlspecialchars(file_get_contents(__DIR__ . '/otp_log.txt')); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($emailLogExists): ?>
            <div class="log-file">
                <h4>Email Log:</h4>
                <?php echo htmlspecialchars(file_get_contents(__DIR__ . '/email_log.txt')); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($sentEmailsExists): ?>
            <div class="log-file">
                <h4>Sent Emails:</h4>
                <?php echo htmlspecialchars(file_get_contents(__DIR__ . '/sent_emails.json')); ?>
            </div>
        <?php endif; ?>
        
        <div class="status-box">
            <h3>🚀 Alternative Email Services</h3>
            <p>If Gmail doesn't work, consider these free alternatives:</p>
            <ul>
                <li><strong>SendGrid:</strong> 100 emails/day free</li>
                <li><strong>Mailgun:</strong> 5,000 emails/month free</li>
                <li><strong>EmailJS:</strong> 200 emails/month free</li>
                <li><strong>AWS SES:</strong> Very affordable, pay per use</li>
            </ul>
        </div>
    </div>
</body>
</html>
