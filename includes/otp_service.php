<?php
/**
 * OTP Email Service
 * Handles sending OTP codes via email with better email support
 */

class OTPService {
    private $pdo;
    
    public function __construct() {
        require_once __DIR__ . '/db_functions.php';
        $this->pdo = getDB();
    }
    
    /**
     * Generate a 6-digit OTP code
     */
    public function generateOTP() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Send OTP via email using the new email service
     */
    public function sendOTPEmail($email, $otp, $type = 'login') {
        // Use the new email service
        require_once __DIR__ . '/email_service.php';
        return sendOTPEmail($email, $otp, $type);
    }
    
    /**
     * Send email via Gmail SMTP using fsockopen
     */
    private function sendViaGmailSMTP($to, $subject, $message) {
        // Gmail SMTP configuration
        $smtp_host = 'smtp.gmail.com';
        $smtp_port = 587;
        $smtp_user = 'your-email@gmail.com'; // Change this to your Gmail
        $smtp_pass = 'your-app-password'; // Change this to your Gmail app password
        
        // For now, return false to try other methods
        // You can implement Gmail SMTP here if needed
        return false;
    }
    
    /**
     * Send email via PHP mail() function
     */
    private function sendViaPHPMail($to, $subject, $message) {
        $headers = [
            'From: TraderEscape <noreply@traderescape.com>',
            'Reply-To: support@traderescape.com',
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8'
        ];
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
    }
    
    /**
     * Send email via file method (for testing)
     */
    private function sendViaFileMethod($to, $subject, $message) {
        $emailData = [
            'to' => $to,
            'subject' => $subject,
            'body' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'pending'
        ];
        
        $emailFile = __DIR__ . '/../pending_emails.json';
        $emails = [];
        
        if (file_exists($emailFile)) {
            $emails = json_decode(file_get_contents($emailFile), true) ?: [];
        }
        
        $emails[] = $emailData;
        file_put_contents($emailFile, json_encode($emails, JSON_PRETTY_PRINT));
        
        return true; // Always return true for testing
    }
    
    /**
     * Alternative email sending method
     */
    private function sendViaAlternativeMethod($to, $subject, $message) {
        // Simple file-based email simulation for testing
        $emailFile = __DIR__ . '/../email_log.txt';
        $emailEntry = "TO: $to\nSUBJECT: $subject\nMESSAGE: $message\n" . str_repeat('-', 50) . "\n";
        file_put_contents($emailFile, $emailEntry, FILE_APPEND | LOCK_EX);
        
        return true; // Always return true for testing
    }
    
    /**
     * Log OTP to file for testing (remove in production)
     */
    private function logOTPForTesting($email, $otp, $type) {
        $logFile = __DIR__ . '/../otp_log.txt';
        $logEntry = date('Y-m-d H:i:s') . " - $type OTP for $email: $otp\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Store OTP in database or session
     */
    public function storeOTP($email, $otp, $type = 'login', $expiryMinutes = 10) {
        try {
            $expires = date('Y-m-d H:i:s', time() + ($expiryMinutes * 60));
            
            if ($type === 'register') {
                // For registration, store OTP in session since user doesn't exist yet
                $_SESSION['registration_otp'] = [
                    'email' => $email,
                    'otp' => $otp,
                    'expires' => time() + ($expiryMinutes * 60),
                    'timestamp' => time()
                ];
                return true;
            } elseif ($type === 'forgot_password') {
                $stmt = $this->pdo->prepare("UPDATE users SET password_reset_otp = ?, password_reset_otp_expires = ? WHERE email = ?");
                $stmt->execute([$otp, $expires, $email]);
            } else {
                $stmt = $this->pdo->prepare("UPDATE users SET otp_code = ?, otp_expires = ?, otp_verified = 0 WHERE email = ?");
                $stmt->execute([$otp, $expires, $email]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("OTP storage error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify OTP code
     */
    public function verifyOTP($email, $otp, $type = 'login') {
        try {
            if ($type === 'register') {
                // For registration, verify OTP from session
                if (!isset($_SESSION['registration_otp'])) {
                    return false;
                }
                
                $sessionOTP = $_SESSION['registration_otp'];
                
                // Check if OTP matches and hasn't expired
                if ($sessionOTP['email'] === $email && 
                    $sessionOTP['otp'] === $otp && 
                    $sessionOTP['expires'] > time()) {
                    
                    // Clear the session OTP
                    unset($_SESSION['registration_otp']);
                    return true;
                }
                
                return false;
            } elseif ($type === 'forgot_password') {
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND password_reset_otp = ? AND password_reset_otp_expires > NOW()");
            } else {
                $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND otp_code = ? AND otp_expires > NOW()");
            }
            
            if ($type !== 'register') {
                $stmt->execute([$email, $otp]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // Mark OTP as verified and clear it
                    if ($type === 'forgot_password') {
                        $updateStmt = $this->pdo->prepare("UPDATE users SET password_reset_otp = NULL, password_reset_otp_expires = NULL WHERE email = ?");
                    } else {
                        $updateStmt = $this->pdo->prepare("UPDATE users SET otp_verified = 1, otp_code = NULL, otp_expires = NULL WHERE email = ?");
                    }
                    $updateStmt->execute([$email]);
                    
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("OTP verification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user has verified OTP
     */
    public function isOTPVerified($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT otp_verified FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user && $user['otp_verified'] == 1;
        } catch (Exception $e) {
            error_log("OTP verification check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear OTP data
     */
    public function clearOTP($email, $type = 'login') {
        try {
            if ($type === 'register') {
                // Clear registration OTP from session
                unset($_SESSION['registration_otp']);
                return true;
            } elseif ($type === 'forgot_password') {
                $stmt = $this->pdo->prepare("UPDATE users SET password_reset_otp = NULL, password_reset_otp_expires = NULL WHERE email = ?");
            } else {
                $stmt = $this->pdo->prepare("UPDATE users SET otp_code = NULL, otp_expires = NULL, otp_verified = 0 WHERE email = ?");
            }
            
            if ($type !== 'register') {
                $stmt->execute([$email]);
            }
            return true;
        } catch (Exception $e) {
            error_log("OTP clear error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get login OTP email template
     */
    private function getLoginOTPMessage($otp) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Login Verification</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .otp-code { background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
                .otp-number { font-size: 32px; font-weight: bold; color: #495057; letter-spacing: 5px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔐 Login Verification</h1>
                    <p>Secure access to your TraderEscape account</p>
                </div>
                <div class='content'>
                    <h2>Hello!</h2>
                    <p>You're trying to log in to your TraderEscape account. Use the verification code below to complete your login:</p>
                    
                    <div class='otp-code'>
                        <div class='otp-number'>{$otp}</div>
                    </div>
                    
                    <p><strong>Important:</strong></p>
                    <ul>
                        <li>This code will expire in 10 minutes</li>
                        <li>Never share this code with anyone</li>
                        <li>If you didn't request this code, please ignore this email</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p>This is an automated message from TraderEscape. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get registration OTP email template
     */
    private function getRegisterOTPMessage($otp) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Account Verification</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .otp-code { background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
                .otp-number { font-size: 32px; font-weight: bold; color: #495057; letter-spacing: 5px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🎉 Welcome to TraderEscape!</h1>
                    <p>Verify your email to get started</p>
                </div>
                <div class='content'>
                    <h2>Thank you for joining us!</h2>
                    <p>To complete your account setup, please verify your email address using the code below:</p>
                    
                    <div class='otp-code'>
                        <div class='otp-number'>{$otp}</div>
                    </div>
                    
                    <p><strong>Next steps:</strong></p>
                    <ul>
                        <li>Enter this code in the verification form</li>
                        <li>This code will expire in 10 minutes</li>
                        <li>Once verified, you can access all features</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p>Welcome to the TraderEscape community!</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Get forgot password OTP email template
     */
    private function getForgotPasswordOTPMessage($otp) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Password Reset</title>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .otp-code { background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
                .otp-number { font-size: 32px; font-weight: bold; color: #495057; letter-spacing: 5px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔑 Password Reset</h1>
                    <p>Secure your account with a new password</p>
                </div>
                <div class='content'>
                    <h2>Password Reset Request</h2>
                    <p>We received a request to reset your password. Use the verification code below to proceed:</p>
                    
                    <div class='otp-code'>
                        <div class='otp-number'>{$otp}</div>
                    </div>
                    
                    <p><strong>Security Notice:</strong></p>
                    <ul>
                        <li>This code will expire in 10 minutes</li>
                        <li>If you didn't request this reset, please ignore this email</li>
                        <li>Your password will remain unchanged until you complete the reset</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p>Keep your account secure - never share verification codes.</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
?>
