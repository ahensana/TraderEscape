<?php
/**
 * Email Configuration for TraderEscape
 * Gmail SMTP Settings
 */

// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'ahensananingthemcha@gmail.com');
define('SMTP_PASSWORD', 'irelgxhyraptvexn');
define('SMTP_FROM_EMAIL', 'ahensananingthemcha@gmail.com');
define('SMTP_FROM_NAME', 'The Traders Escape');

// Email Settings
define('SMTP_SECURE', 'tls'); // Use 'tls' for port 587, 'ssl' for port 465
define('SMTP_DEBUG', false); // Set to true for debugging

// OTP Settings
define('OTP_EXPIRY_MINUTES', 5);
define('OTP_LENGTH', 6);

// Email Templates
define('OTP_EMAIL_SUBJECT', 'Your Password Reset Code - The Trader\'s Escape');
define('OTP_EMAIL_TEMPLATE', 'otp_email_template.html');
