<?php
/**
 * OTP Test Index - Easy navigation to all OTP test pages
 */
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
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .test-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1rem 0;
            border-left: 4px solid #3b82f6;
        }
        .test-card h3 {
            color: #60a5fa;
            margin-top: 0;
        }
        .test-card p {
            color: #d1d5db;
            margin-bottom: 1rem;
        }
        .btn {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        .btn:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
        }
        .btn-secondary:hover {
            background: linear-gradient(135deg, #4b5563, #374151);
        }
        .status {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            text-align: center;
        }
        .back-link {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            margin-bottom: 1rem;
        }
        .back-link:hover {
            background: rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../index.php" class="back-link">← Back to Main Site</a>
        
        <h1>📧 OTP Test Center</h1>
        <p>All OTP testing tools in one place. Choose the test you want to run.</p>
        
        <div class="status">
            <h3>✅ System Ready</h3>
            <p>All OTP test files are organized and ready to use.</p>
        </div>
        
        <div class="test-card">
            <h3>🔧 1. Setup Gmail</h3>
            <p>First-time setup: Get your Gmail App Password and configure the system.</p>
            <a href="get_gmail_password.php" class="btn">Setup Gmail</a>
        </div>
        
        <div class="test-card">
            <h3>📧 2. Simple OTP Test</h3>
            <p>Easiest test: Just enter your email and click send. Perfect for quick testing.</p>
            <a href="simple_otp_test.php" class="btn">Simple Test</a>
        </div>
        
        <div class="test-card">
            <h3>🚀 3. Advanced OTP Test</h3>
            <p>Full-featured test with detailed logs, email history, and system status.</p>
            <a href="test_working_otp.php" class="btn">Advanced Test</a>
        </div>
        
        <div class="test-card">
            <h3>⚡ 4. Email Test (Multiple Methods)</h3>
            <p>Test email sending with multiple fallback methods. Great for troubleshooting.</p>
            <a href="working_email_fixed.php" class="btn">Email Test</a>
        </div>
        
        <div class="test-card">
            <h3>📋 Quick Reference</h3>
            <p><strong>Sender Email:</strong> TheTradersEscape@gmail.com (fixed)</p>
            <p><strong>Setup:</strong> You need a Gmail App Password (not regular password)</p>
            <p><strong>Testing:</strong> Start with Simple Test, then try Advanced Test</p>
        </div>
    </div>
</body>
</html>
