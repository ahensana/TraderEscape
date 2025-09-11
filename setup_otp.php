<?php
/**
 * Setup script for OTP functionality
 * Run this script once to add the necessary database fields
 */

require_once __DIR__ . '/includes/db_functions.php';

try {
    $pdo = getDB();
    
    // Check if OTP fields already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'otp_code'");
    if ($stmt->rowCount() > 0) {
        echo "OTP fields already exist in the users table.\n";
        exit;
    }
    
    // Add OTP-related fields
    $sql = "
    ALTER TABLE users 
    ADD COLUMN otp_code VARCHAR(6) DEFAULT NULL,
    ADD COLUMN otp_expires DATETIME DEFAULT NULL,
    ADD COLUMN otp_verified TINYINT(1) DEFAULT 0,
    ADD COLUMN password_reset_otp VARCHAR(6) DEFAULT NULL,
    ADD COLUMN password_reset_otp_expires DATETIME DEFAULT NULL;
    ";
    
    $pdo->exec($sql);
    
    echo "✅ OTP fields added successfully to the users table!\n";
    echo "The following fields were added:\n";
    echo "- otp_code (VARCHAR(6)) - stores the OTP code\n";
    echo "- otp_expires (DATETIME) - OTP expiration time\n";
    echo "- otp_verified (TINYINT(1)) - tracks if user has verified OTP\n";
    echo "- password_reset_otp (VARCHAR(6)) - OTP for password reset\n";
    echo "- password_reset_otp_expires (DATETIME) - password reset OTP expiration\n\n";
    
    echo "🎉 OTP functionality is now ready to use!\n";
    echo "You can now:\n";
    echo "1. Register new accounts (will require email verification)\n";
    echo "2. Login with OTP verification\n";
    echo "3. Reset passwords using OTP\n\n";
    
    echo "📧 Email Configuration:\n";
    echo "The system uses PHP's built-in mail() function to send OTP emails.\n";
    echo "Make sure your server is configured to send emails.\n";
    echo "For production, consider using services like SendGrid, Mailgun, or AWS SES.\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up OTP functionality: " . $e->getMessage() . "\n";
    echo "Please check your database connection and try again.\n";
}
?>
