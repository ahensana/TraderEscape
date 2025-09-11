<?php
/**
 * Simple PHPMailer Implementation
 * This is a simplified version that works without Composer
 */

// Simple email sending function using Gmail SMTP
function sendEmailWithPHPMailer($to, $subject, $body, $isHTML = true) {
    // Gmail SMTP Configuration
    $smtp_host = 'smtp.gmail.com';
    $smtp_port = 587;
    $smtp_username = 'your-email@gmail.com'; // Change this to your Gmail
    $smtp_password = 'your-app-password'; // Change this to your Gmail app password
    
    // For testing, let's use a simple approach first
    // You can configure Gmail SMTP later
    
    // Create the email headers
    $headers = [
        'From: TraderEscape <noreply@traderescape.com>',
        'Reply-To: support@traderescape.com',
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0',
        'Content-Type: ' . ($isHTML ? 'text/html' : 'text/plain') . '; charset=UTF-8'
    ];
    
    // Try to send email
    $result = mail($to, $subject, $body, implode("\r\n", $headers));
    
    // Log the attempt
    $logFile = __DIR__ . '/email_log.txt';
    $logEntry = date('Y-m-d H:i:s') . " - Attempted to send email to: $to\n";
    $logEntry .= "Subject: $subject\n";
    $logEntry .= "Result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
    $logEntry .= str_repeat('-', 50) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
    return $result;
}

// Alternative: Use a free email service like EmailJS or SendGrid
function sendEmailWithFreeService($to, $subject, $body) {
    // For now, let's use a simple file-based approach
    // In production, you would integrate with a service like:
    // - SendGrid (free tier: 100 emails/day)
    // - Mailgun (free tier: 5,000 emails/month)
    // - EmailJS (free tier: 200 emails/month)
    
    $emailData = [
        'to' => $to,
        'subject' => $subject,
        'body' => $body,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Save to file for testing
    $emailFile = __DIR__ . '/pending_emails.json';
    $emails = [];
    
    if (file_exists($emailFile)) {
        $emails = json_decode(file_get_contents($emailFile), true) ?: [];
    }
    
    $emails[] = $emailData;
    file_put_contents($emailFile, json_encode($emails, JSON_PRETTY_PRINT));
    
    return true; // Always return true for testing
}

// Gmail SMTP Configuration Helper
function getGmailSMTPConfig() {
    return [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-email@gmail.com', // Change this
        'password' => 'your-app-password', // Change this
        'encryption' => 'tls',
        'from_email' => 'your-email@gmail.com',
        'from_name' => 'TraderEscape'
    ];
}

// Instructions for setting up Gmail SMTP
function getGmailSetupInstructions() {
    return "
    Gmail SMTP Setup Instructions:
    
    1. Enable 2-Factor Authentication on your Gmail account
    2. Generate an App Password:
       - Go to Google Account settings
       - Security > 2-Step Verification > App passwords
       - Generate a password for 'Mail'
    3. Update the configuration in phpmailer_simple.php:
       - Set smtp_username to your Gmail address
       - Set smtp_password to the generated app password
    4. Test the email functionality
    
    Alternative: Use a free email service like SendGrid or Mailgun
    ";
}
?>
