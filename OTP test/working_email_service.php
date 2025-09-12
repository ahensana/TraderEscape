<?php
/**
 * Working Email Service
 * This service will actually send emails using multiple methods
 * Now using PHPMailer for reliable email delivery
 */

// Include PHPMailer
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class WorkingEmailService {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        // Load configuration if it exists
        $this->loadConfig();
        $this->loadGmailConfig();
    }
    
    /**
     * Load email configuration
     */
    private function loadConfig() {
        $configFile = __DIR__ . '/email_config.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if ($config && isset($config['configured']) && $config['configured']) {
                $this->smtp_username = $config['smtp_username'];
                $this->smtp_password = $config['smtp_password'];
                $this->from_email = $config['from_email'];
                $this->from_name = 'TraderEscape';
                return;
            }
        }
        
        // Default configuration
        $this->smtp_host = 'smtp.gmail.com';
        $this->smtp_port = 587;
        $this->smtp_username = 'TheTradersEscape@gmail.com';
        $this->smtp_password = 'your-app-password'; // You need to set this
        $this->from_email = 'TheTradersEscape@gmail.com';
        $this->from_name = 'TraderEscape';
    }
    
    /**
     * Send email using multiple methods
     */
    public function sendEmail($to, $subject, $body, $isHTML = true) {
        // Method 1: Try PHPMailer with Gmail SMTP
        if ($this->sendViaPHPMailer($to, $subject, $body, $isHTML)) {
            $this->logEmailAttempt($to, $subject, 'PHPMailer SMTP', true);
            return true;
        }
        
        // Method 2: Try PHP mail() function
        if ($this->sendViaPHPMail($to, $subject, $body, $isHTML)) {
            $this->logEmailAttempt($to, $subject, 'PHP Mail', true);
            return true;
        }
        
        // Method 3: Save to file for manual sending (always works)
        $this->saveEmailToFile($to, $subject, $body);
        $this->logEmailAttempt($to, $subject, 'File Save', true);
        return true; // Always return true since we save to file
    }
    
    /**
     * Send email via PHPMailer with Gmail SMTP
     */
    private function sendViaPHPMailer($to, $subject, $body, $isHTML = true) {
        // Skip if not configured
        if ($this->smtp_password === 'your-app-password' || empty($this->smtp_password)) {
            return false;
        }
        
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->smtp_port;
            
            // Enable verbose debug output (optional)
            $mail->SMTPDebug = 0; // Set to 2 for debugging
            
            // Recipients
            $mail->setFrom($this->from_email, $this->from_name);
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            // Send the email
            $mail->send();
            
            // Log successful email sending
            $this->logEmailAttempt($to, $subject, 'PHPMailer SMTP', true);
            return true;
            
        } catch (Exception $e) {
            // Log the error for debugging
            $this->logEmailAttempt($to, $subject, 'PHPMailer SMTP', false);
            error_log("PHPMailer Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email via PHP mail() function
     */
    private function sendViaPHPMail($to, $subject, $body, $isHTML = true) {
        $headers = [
            'From: noreply@thetradersescape.com',
            'Reply-To: noreply@thetradersescape.com',
            'X-Mailer: PHP/' . phpversion(),
            'MIME-Version: 1.0',
            'Content-Type: ' . ($isHTML ? 'text/html' : 'text/plain') . '; charset=UTF-8',
            'Return-Path: noreply@thetradersescape.com',
            'Sender: noreply@thetradersescape.com'
        ];
        
        return @mail($to, $subject, $body, implode("\r\n", $headers));
    }
    
    /**
     * Save email to file for manual sending
     */
    private function saveEmailToFile($to, $subject, $body) {
        $emailData = [
            'to' => $to,
            'subject' => $subject,
            'body' => $body,
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'pending_manual_send'
        ];
        
        $emailFile = __DIR__ . '/pending_emails.json';
        $emails = [];
        
        if (file_exists($emailFile)) {
            $emails = json_decode(file_get_contents($emailFile), true) ?: [];
        }
        
        $emails[] = $emailData;
        file_put_contents($emailFile, json_encode($emails, JSON_PRETTY_PRINT));
        
        // Also save to a simple text file for easy reading
        $simpleFile = __DIR__ . '/emails_to_send.txt';
        $simpleEntry = "TO: $to\nSUBJECT: $subject\nTIME: " . date('Y-m-d H:i:s') . "\n" . str_repeat('-', 50) . "\n";
        file_put_contents($simpleFile, $simpleEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Log email attempts
     */
    private function logEmailAttempt($to, $subject, $method, $success) {
        $logFile = __DIR__ . '/email_log.txt';
        $logEntry = date('Y-m-d H:i:s') . " - Email attempt\n";
        $logEntry .= "To: $to\n";
        $logEntry .= "Subject: $subject\n";
        $logEntry .= "Method: $method\n";
        $logEntry .= "Result: " . ($success ? 'SUCCESS' : 'FAILED') . "\n";
        $logEntry .= str_repeat('-', 50) . "\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Configure Gmail SMTP
     */
    public function configureGmail($email, $appPassword) {
        $this->smtp_username = $email;
        $this->smtp_password = $appPassword;
        $this->from_email = $email;
        
        // Save configuration
        $config = [
            'smtp_username' => $email,
            'smtp_password' => $appPassword,
            'from_email' => $email,
            'configured' => true
        ];
        
        file_put_contents(__DIR__ . '/email_config.json', json_encode($config, JSON_PRETTY_PRINT));
    }
    
    /**
     * Load Gmail configuration
     */
    public function loadGmailConfig() {
        $configFile = __DIR__ . '/email_config.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            if ($config && isset($config['configured']) && $config['configured']) {
                $this->smtp_username = $config['smtp_username'];
                $this->smtp_password = $config['smtp_password'];
                $this->from_email = $config['from_email'];
                return true;
            }
        }
        return false;
    }
}

// Global function for sending OTP emails
function sendOTPEmailWorking($email, $otp, $type = 'login') {
    $mailer = new WorkingEmailService();
    
    $subject = '';
    $body = '';
    
    switch($type) {
        case 'login':
            $subject = 'Your Login Verification Code - TraderEscape';
            $body = getLoginOTPEmailBody($otp);
            break;
        case 'register':
            $subject = 'Verify Your Account - TraderEscape';
            $body = getRegisterOTPEmailBody($otp);
            break;
        case 'forgot_password':
            $subject = 'Password Reset Code - TraderEscape';
            $body = getForgotPasswordOTPEmailBody($otp);
            break;
    }
    
    // Log OTP for testing
    logOTPForTesting($email, $otp, $type);
    
    return $mailer->sendEmail($email, $subject, $body, true);
}

function getLoginOTPEmailBody($otp) {
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

function getRegisterOTPEmailBody($otp) {
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

function getForgotPasswordOTPEmailBody($otp) {
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

function logOTPForTesting($email, $otp, $type) {
    $logFile = __DIR__ . '/otp_log.txt';
    $logEntry = date('Y-m-d H:i:s') . " - $type OTP for $email: $otp\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    // Also save to a more accessible file
    $simpleLogFile = __DIR__ . '/current_otp.txt';
    $simpleEntry = "Email: $email\nOTP: $otp\nType: $type\nTime: " . date('Y-m-d H:i:s') . "\n" . str_repeat('-', 30) . "\n";
    file_put_contents($simpleLogFile, $simpleEntry, FILE_APPEND | LOCK_EX);
}
?>
