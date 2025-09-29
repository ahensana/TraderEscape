<?php
/**
 * Email Functions for TraderEscape
 * Handles OTP sending via PHPMailer
 */

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send OTP email to user
 * @param string $email User's email address
 * @param string $otp 6-digit OTP code
 * @param string $userName User's full name
 * @return array Result array with success status and message
 */
function sendOTPEmail($email, $otp, $userName = '') {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        // Debug mode
        if (SMTP_DEBUG) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        }
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $userName);
        $mail->addReplyTo(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = OTP_EMAIL_SUBJECT;
        $mail->Body = getOTPEmailTemplate($otp, $userName);
        $mail->AltBody = getOTPEmailText($otp, $userName);
        
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'OTP sent successfully to your email address.'
        ];
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return [
            'success' => false,
            'message' => 'Failed to send OTP. Please try again later.'
        ];
    }
}

/**
 * Get HTML email template for OTP
 * @param string $otp 6-digit OTP code
 * @param string $userName User's full name
 * @return string HTML email content
 */
function getOTPEmailTemplate($otp, $userName = '') {
    $displayName = !empty($userName) ? $userName : 'Valued User';
    
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Password Reset - The Trader's Escape</title>
        <style>
            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                line-height: 1.6;
                color: #1e293b;
                background-color: #f8fafc;
                margin: 0;
                padding: 0;
            }
            .email-container {
                max-width: 600px;
                margin: 0 auto;
                background: #ffffff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            }
            .email-header {
                background: linear-gradient(135deg, #0f172a, #1e293b);
                color: #ffffff;
                padding: 2rem;
                text-align: center;
            }
            .email-header h1 {
                margin: 0;
                font-size: 1.5rem;
                font-weight: 700;
            }
            .email-content {
                padding: 2rem;
            }
            .otp-box {
                background: #f1f5f9;
                border: 2px solid #e2e8f0;
                border-radius: 8px;
                padding: 1.5rem;
                text-align: center;
                margin: 1.5rem 0;
            }
            .otp-code {
                font-size: 2rem;
                font-weight: 700;
                color: #0f172a;
                letter-spacing: 0.5rem;
                font-family: 'Courier New', monospace;
            }
            .otp-label {
                color: #64748b;
                font-size: 0.9rem;
                margin-bottom: 0.5rem;
            }
            .warning-box {
                background: #fef3c7;
                border: 1px solid #f59e0b;
                border-radius: 8px;
                padding: 1rem;
                margin: 1.5rem 0;
            }
            .warning-text {
                color: #92400e;
                font-size: 0.9rem;
                margin: 0;
            }
            .footer {
                background: #f8fafc;
                padding: 1.5rem;
                text-align: center;
                color: #64748b;
                font-size: 0.9rem;
            }
            .button {
                display: inline-block;
                background: #3b82f6;
                color: #ffffff;
                padding: 0.75rem 1.5rem;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                margin: 1rem 0;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='email-header'>
                <h1>🔐 Password Reset Request</h1>
            </div>
            
            <div class='email-content'>
                <h2>Hello " . htmlspecialchars($displayName) . "!</h2>
                
                <p>You requested a password reset for your TraderEscape account. Use the verification code below to reset your password:</p>
                
                <div class='otp-box'>
                    <div class='otp-label'>Your verification code:</div>
                    <div class='otp-code'>" . htmlspecialchars($otp) . "</div>
                </div>
                
                <div class='warning-box'>
                    <p class='warning-text'>
                        <strong>⚠️ Important:</strong> This code will expire in " . OTP_EXPIRY_MINUTES . " minutes. 
                        If you didn't request this reset, please ignore this email.
                    </p>
                </div>
                
                <p>Enter this code on the password reset page to continue with your password reset process.</p>
                
                <p>If you have any questions, please contact our support team.</p>
                
                <p>Best regards,<br>The Trader's Escape Team</p>
            </div>
            
            <div class='footer'>
                <p>This email was sent from The Trader's Escape password reset system.</p>
                <p>© " . date('Y') . " The Trader's Escape. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Get plain text email for OTP
 * @param string $otp 6-digit OTP code
 * @param string $userName User's full name
 * @return string Plain text email content
 */
function getOTPEmailText($otp, $userName = '') {
    $displayName = !empty($userName) ? $userName : 'Valued User';
    
    return "
PASSWORD RESET REQUEST - The Trader's Escape

Hello " . $displayName . "!

You requested a password reset for your TraderEscape account.

Your verification code is: " . $otp . "

This code will expire in " . OTP_EXPIRY_MINUTES . " minutes.

If you didn't request this reset, please ignore this email.

Enter this code on the password reset page to continue with your password reset process.

If you have any questions, please contact our support team.

Best regards,
The Trader's Escape Team

---
This email was sent from The Trader's Escape password reset system.
© " . date('Y') . " The Trader's Escape. All rights reserved.
";
}

/**
 * Validate OTP from session
 * @param string $inputOTP User input OTP
 * @return array Result array with success status and message
 */
function validateOTP($inputOTP) {
    if (!isset($_SESSION['reset_otp']) || !isset($_SESSION['otp_expires'])) {
        return [
            'success' => false,
            'message' => 'No OTP found. Please request a new one.'
        ];
    }
    
    if (time() > $_SESSION['otp_expires']) {
        unset($_SESSION['reset_otp']);
        unset($_SESSION['otp_expires']);
        unset($_SESSION['reset_email']);
        
        return [
            'success' => false,
            'message' => 'OTP has expired. Please request a new one.'
        ];
    }
    
    if ($inputOTP !== $_SESSION['reset_otp']) {
        return [
            'success' => false,
            'message' => 'Invalid OTP. Please check and try again.'
        ];
    }
    
    return [
        'success' => true,
        'message' => 'OTP verified successfully.'
    ];
}
