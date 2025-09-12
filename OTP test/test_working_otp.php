<?php
/**
 * Test Working OTP System
 * This tests the new working email service
 */

session_start();
require_once __DIR__ . '/../includes/working_otp_service.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = trim($_POST['email'] ?? '');
    
    if ($action === 'test_otp') {
        try {
            $otpService = new WorkingOTPService();
            $otp = $otpService->generateOTP();
            
            if ($otpService->sendOTPEmail($email, $otp, 'login')) {
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
$otpLogExists = file_exists(__DIR__ . '/../otp_log.txt');
$emailLogExists = file_exists(__DIR__ . '/../email_log.txt');
$pendingEmailsExists = file_exists(__DIR__ . '/../pending_emails.json');
$emailsToSendExists = file_exists(__DIR__ . '/../emails_to_send.txt');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Working OTP Test - TraderEscape</title>
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
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        .status-item {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
        .status-item h4 {
            margin: 0 0 0.5rem 0;
            color: #60a5fa;
        }
        .instructions {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Working OTP System Test</h1>
        <p>This tests the new working email service that will actually send emails or save them to files.</p>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="action" value="test_otp">
            <div class="form-group">
                <label for="email">Your Email Address:</label>
                <input type="email" id="email" name="email" required placeholder="your-email@example.com">
            </div>
            <button type="submit">🚀 Send OTP Email</button>
        </form>
        
        <div class="status-grid">
            <div class="status-item">
                <h4>OTP Log</h4>
                <p><?php echo $otpLogExists ? '✅ Exists' : '❌ Not found'; ?></p>
            </div>
            <div class="status-item">
                <h4>Email Log</h4>
                <p><?php echo $emailLogExists ? '✅ Exists' : '❌ Not found'; ?></p>
            </div>
            <div class="status-item">
                <h4>Pending Emails</h4>
                <p><?php echo $pendingEmailsExists ? '✅ Exists' : '❌ Not found'; ?></p>
            </div>
            <div class="status-item">
                <h4>Emails to Send</h4>
                <p><?php echo $emailsToSendExists ? '✅ Exists' : '❌ Not found'; ?></p>
            </div>
        </div>
        
        <div class="instructions">
            <h3>📧 How This Works</h3>
            <p><strong>This system will:</strong></p>
            <ul>
                <li>✅ Always generate and log OTP codes</li>
                <li>✅ Try to send emails via Gmail SMTP (if configured)</li>
                <li>✅ Try to send emails via PHP mail() function</li>
                <li>✅ Save emails to files if SMTP fails (so you can see them)</li>
                <li>✅ Always return success (OTP is always available in logs)</li>
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
        
        <?php if ($pendingEmailsExists): ?>
            <div class="log-section">
                <h3>📧 Pending Emails (JSON)</h3>
                <div class="log-file">
                    <?php echo htmlspecialchars(file_get_contents(__DIR__ . '/../pending_emails.json')); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($emailsToSendExists): ?>
            <div class="log-section">
                <h3>📧 Emails to Send (Simple)</h3>
                <div class="log-file">
                    <?php echo htmlspecialchars(file_get_contents(__DIR__ . '/../emails_to_send.txt')); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($emailLogExists): ?>
            <div class="log-section">
                <h3>📋 Email Log</h3>
                <div class="log-file">
                    <?php echo htmlspecialchars(file_get_contents(__DIR__ . '/../email_log.txt')); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="instructions">
            <h3>🔧 Next Steps</h3>
            <ol>
                <li><strong>Test the system:</strong> Send a test OTP above</li>
                <li><strong>Check the OTP:</strong> Look at the "Current OTP" section</li>
                <li><strong>Configure Gmail:</strong> Visit <a href="get_gmail_password.php" style="color: #60a5fa;">get_gmail_password.php</a> to configure Gmail SMTP</li>
                <li><strong>Simple Test:</strong> Try the <a href="simple_otp_test.php" style="color: #60a5fa;">simple OTP test</a> for easier testing</li>
            </ol>
        </div>
    </div>
</body>
</html>
