<?php
/**
 * Test Registration Flow
 * This page tests the new registration flow where accounts are only created after OTP verification
 */

session_start();
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/otp_service.php';

$message = '';
$error = '';

// Function to check if user exists in database
function userExists($email) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = trim($_POST['email'] ?? '');
    
    if ($action === 'test_registration') {
        $full_name = 'Test User';
        $username = 'testuser' . time();
        $password = 'testpass123';
        
        // Check if user exists before registration
        $existsBefore = userExists($email);
        
        // Simulate registration process
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $_SESSION['pending_registration'] = [
            'full_name' => $full_name,
            'username' => $username,
            'email' => $email,
            'password_hash' => $hashedPassword,
            'timestamp' => time()
        ];
        
        // Send OTP
        $otpService = new OTPService();
        $otp = $otpService->generateOTP();
        $otpService->storeOTP($email, $otp, 'register');
        $otpService->sendOTPEmail($email, $otp, 'register');
        
        // Check if user exists after registration (should be false)
        $existsAfter = userExists($email);
        
        $message = "✅ Registration test completed!<br>";
        $message .= "User exists before registration: " . ($existsBefore ? 'Yes' : 'No') . "<br>";
        $message .= "User exists after registration: " . ($existsAfter ? 'Yes' : 'No') . "<br>";
        $message .= "OTP sent: " . $otp . "<br>";
        $message .= "Pending registration data stored in session: " . (isset($_SESSION['pending_registration']) ? 'Yes' : 'No');
        
    } elseif ($action === 'verify_otp') {
        $otp = trim($_POST['otp'] ?? '');
        
        if (isset($_SESSION['pending_registration'])) {
            $pendingData = $_SESSION['pending_registration'];
            $email = $pendingData['email'];
            
            $otpService = new OTPService();
            if ($otpService->verifyOTP($email, $otp, 'register')) {
                // Create user account
                $pdo = getDB();
                $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password_hash, is_active, is_verified, created_at) VALUES (?, ?, ?, ?, TRUE, TRUE, NOW())");
                $stmt->execute([
                    $pendingData['full_name'],
                    $pendingData['username'],
                    $pendingData['email'],
                    $pendingData['password_hash']
                ]);
                
                $userId = $pdo->lastInsertId();
                unset($_SESSION['pending_registration']);
                
                $message = "✅ Account created successfully!<br>";
                $message .= "User ID: " . $userId . "<br>";
                $message .= "Email: " . $email . "<br>";
                $message .= "Account now exists in database: " . (userExists($email) ? 'Yes' : 'No');
            } else {
                $error = "❌ Invalid OTP code";
            }
        } else {
            $error = "❌ No pending registration found";
        }
    }
}

// Check current status
$hasPendingRegistration = isset($_SESSION['pending_registration']);
$pendingEmail = $hasPendingRegistration ? $_SESSION['pending_registration']['email'] : '';
$userExists = $pendingEmail ? userExists($pendingEmail) : false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Flow Test - TraderEscape</title>
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
        input[type="email"], input[type="text"] {
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
        .otp-input {
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 0.5rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Registration Flow Test</h1>
        <p>This page tests the new registration flow where accounts are only created after OTP verification.</p>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="status-box">
            <h3>📊 Current Status</h3>
            <p><strong>Has Pending Registration:</strong> <?php echo $hasPendingRegistration ? 'Yes' : 'No'; ?></p>
            <?php if ($hasPendingRegistration): ?>
                <p><strong>Pending Email:</strong> <?php echo htmlspecialchars($pendingEmail); ?></p>
                <p><strong>User Exists in Database:</strong> <?php echo $userExists ? 'Yes' : 'No'; ?></p>
            <?php endif; ?>
        </div>
        
        <?php if (!$hasPendingRegistration): ?>
            <form method="POST">
                <input type="hidden" name="action" value="test_registration">
                <div class="form-group">
                    <label for="email">Email Address:</label>
                    <input type="email" id="email" name="email" required placeholder="Enter email for testing">
                </div>
                <button type="submit">Start Registration Test</button>
            </form>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="action" value="verify_otp">
                <div class="form-group">
                    <label for="otp">Enter OTP Code:</label>
                    <input type="text" id="otp" name="otp" class="otp-input" required placeholder="000000" maxlength="6">
                </div>
                <button type="submit">Verify OTP & Create Account</button>
            </form>
            
            <p><small>Check the OTP log file for the verification code.</small></p>
        <?php endif; ?>
        
        <div class="status-box">
            <h3>🔍 How This Works</h3>
            <ol>
                <li><strong>Step 1:</strong> User fills registration form</li>
                <li><strong>Step 2:</strong> Data is stored in session (NOT in database)</li>
                <li><strong>Step 3:</strong> OTP is sent to email</li>
                <li><strong>Step 4:</strong> User enters OTP code</li>
                <li><strong>Step 5:</strong> Only after OTP verification, account is created in database</li>
            </ol>
        </div>
    </div>
</body>
</html>
