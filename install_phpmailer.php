<?php
/**
 * PHPMailer Installation Script
 * This will download and install PHPMailer for email functionality
 */

echo "Installing PHPMailer...\n";

// Create vendor directory if it doesn't exist
if (!is_dir(__DIR__ . '/vendor')) {
    mkdir(__DIR__ . '/vendor', 0755, true);
    echo "Created vendor directory\n";
}

// Download PHPMailer using Composer (if available) or manual download
$phpmailerPath = __DIR__ . '/vendor/phpmailer/phpmailer';

if (!is_dir($phpmailerPath)) {
    echo "PHPMailer not found. Please install it manually:\n\n";
    echo "Option 1 - Using Composer (Recommended):\n";
    echo "1. Install Composer from https://getcomposer.org/\n";
    echo "2. Run: composer require phpmailer/phpmailer\n\n";
    
    echo "Option 2 - Manual Download:\n";
    echo "1. Go to https://github.com/PHPMailer/PHPMailer/releases\n";
    echo "2. Download the latest release\n";
    echo "3. Extract to: " . $phpmailerPath . "\n\n";
    
    echo "Option 3 - Quick Download (if you have wget/curl):\n";
    
    // Try to download using file_get_contents
    $downloadUrl = 'https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip';
    $zipPath = __DIR__ . '/phpmailer.zip';
    
    echo "Attempting to download PHPMailer...\n";
    
    $zipContent = @file_get_contents($downloadUrl);
    if ($zipContent !== false) {
        file_put_contents($zipPath, $zipContent);
        echo "Downloaded PHPMailer zip file\n";
        
        // Extract using ZipArchive if available
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) === TRUE) {
                $zip->extractTo(__DIR__ . '/vendor/');
                $zip->close();
                
                // Rename the extracted folder
                $extractedPath = __DIR__ . '/vendor/PHPMailer-master';
                if (is_dir($extractedPath)) {
                    rename($extractedPath, $phpmailerPath);
                    echo "Extracted and installed PHPMailer successfully!\n";
                }
                
                // Clean up
                unlink($zipPath);
            } else {
                echo "Failed to extract zip file\n";
            }
        } else {
            echo "ZipArchive not available. Please extract manually.\n";
        }
    } else {
        echo "Failed to download PHPMailer. Please install manually.\n";
    }
} else {
    echo "PHPMailer already installed!\n";
}

// Check if PHPMailer is properly installed
if (is_dir($phpmailerPath)) {
    echo "✅ PHPMailer installation complete!\n";
    echo "Path: " . $phpmailerPath . "\n";
} else {
    echo "❌ PHPMailer installation failed. Please install manually.\n";
}
?>
