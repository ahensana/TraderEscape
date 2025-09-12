<?php
/**
 * Get Gmail App Password Helper
 * This will guide you through getting your Gmail App Password
 */
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Gmail App Password - TraderEscape</title>
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
        .step {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1rem 0;
            border-left: 4px solid #3b82f6;
        }
        .step h3 {
            color: #60a5fa;
            margin-top: 0;
        }
        .step-number {
            background: #3b82f6;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 1rem;
        }
        .link-button {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem 0;
            transition: all 0.3s ease;
        }
        .link-button:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
        }
        .warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        .success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        .code {
            background: rgba(0, 0, 0, 0.3);
            padding: 0.5rem;
            border-radius: 4px;
            font-family: monospace;
            color: #fbbf24;
        }
        .links {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
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
        <h1>🔑 Get Gmail App Password</h1>
        <p>Follow these steps to get your Gmail App Password for sending emails.</p>
        
        <div class="warning">
            <h3>⚠️ Important</h3>
            <p>You need a Gmail App Password, NOT your regular Gmail password. This is a special 16-character password that Google generates for applications.</p>
        </div>
        
        <div class="step">
            <h3><span class="step-number">1</span>Go to Google Account Security</h3>
            <p>Click the button below to open your Google Account security settings:</p>
            <a href="https://myaccount.google.com/security" target="_blank" class="link-button">Open Google Account Security</a>
        </div>
        
        <div class="step">
            <h3><span class="step-number">2</span>Enable 2-Factor Authentication</h3>
            <p>If you haven't already, enable 2-Step Verification:</p>
            <ul>
                <li>Look for "2-Step Verification" in the security settings</li>
                <li>Click on it and follow the setup process</li>
                <li>You'll need your phone number for verification</li>
            </ul>
            <a href="https://myaccount.google.com/signinoptions/two-step-verification" target="_blank" class="link-button">Enable 2-Step Verification</a>
        </div>
        
        <div class="step">
            <h3><span class="step-number">3</span>Generate App Password</h3>
            <p>Once 2-Step Verification is enabled:</p>
            <ul>
                <li>Go back to the main security page</li>
                <li>Look for "App passwords" (it should appear after enabling 2-Step Verification)</li>
                <li>Click on "App passwords"</li>
                <li>Select "Mail" as the app type</li>
                <li>Click "Generate"</li>
            </ul>
            <a href="https://myaccount.google.com/apppasswords" target="_blank" class="link-button">Generate App Password</a>
        </div>
        
        <div class="step">
            <h3><span class="step-number">4</span>Copy the App Password</h3>
            <p>Google will show you a 16-character password like this:</p>
            <div class="code">abcd efgh ijkl mnop</div>
            <p><strong>Important:</strong> Copy this password exactly as shown. You'll use this in the email configuration form.</p>
        </div>
        
        <div class="success">
            <h3>✅ You're Ready!</h3>
            <p>Now you have your Gmail App Password. Go to the email test page and enter:</p>
            <ul>
                <li><strong>Sender Email:</strong> TheTradersEscape@gmail.com (fixed)</li>
                <li><strong>App Password:</strong> The 16-character password you just generated</li>
            </ul>
        </div>
        
        <div class="links">
            <a href="working_email_fixed.php">Test Email Now</a>
            <a href="simple_otp_test.php">Test OTP System</a>
        </div>
        
        <div class="warning">
            <h3>🔒 Security Note</h3>
            <p>App passwords are safer than using your regular password because:</p>
            <ul>
                <li>They can be revoked anytime</li>
                <li>They only work for specific apps</li>
                <li>They don't give access to your full Google account</li>
            </ul>
        </div>
    </div>
</body>
</html>
