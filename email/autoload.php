<?php
/**
 * Simple autoloader for PHPMailer without Composer
 */

// Define the phpmailer directory
$phpmailerDir = __DIR__ . '/../phpmailer';

// Check if PHPMailer exists in the phpmailer folder
if (file_exists($phpmailerDir . '/PHPMailer.php')) {
    require_once $phpmailerDir . '/PHPMailer.php';
    require_once $phpmailerDir . '/SMTP.php';
    require_once $phpmailerDir . '/Exception.php';
} else {
    // Fallback: Try to load from vendor directory
    $vendorDir = __DIR__ . '/../vendor';
    if (file_exists($vendorDir . '/phpmailer/phpmailer/src/PHPMailer.php')) {
        require_once $vendorDir . '/phpmailer/phpmailer/src/PHPMailer.php';
        require_once $vendorDir . '/phpmailer/phpmailer/src/SMTP.php';
        require_once $vendorDir . '/phpmailer/phpmailer/src/Exception.php';
    } else {
        die('PHPMailer not found. Please check if the phpmailer folder exists.');
    }
}
