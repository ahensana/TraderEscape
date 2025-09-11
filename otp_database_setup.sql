-- OTP Database Setup for TraderEscape
-- Run this SQL in your MySQL database to add OTP functionality

-- Add OTP-related fields to the existing users table
ALTER TABLE users 
ADD COLUMN otp_code VARCHAR(6) DEFAULT NULL,
ADD COLUMN otp_expires DATETIME DEFAULT NULL,
ADD COLUMN otp_verified TINYINT(1) DEFAULT 0,
ADD COLUMN password_reset_otp VARCHAR(6) DEFAULT NULL,
ADD COLUMN password_reset_otp_expires DATETIME DEFAULT NULL;

-- Optional: Add indexes for better performance
CREATE INDEX idx_otp_code ON users(otp_code);
CREATE INDEX idx_otp_expires ON users(otp_expires);
CREATE INDEX idx_password_reset_otp ON users(password_reset_otp);
CREATE INDEX idx_password_reset_otp_expires ON users(password_reset_otp_expires);

-- Verify the changes
DESCRIBE users;
