-- Admin setup for The Trader's Escape
-- Since you already have the admins table, this will just add a default admin

-- Insert default admin (password: admin123)
-- Email: admin@tradersescape.com
-- Password: admin123
INSERT INTO `admins` (`username`, `email`, `password`) VALUES 
('Admin', 'admin@tradersescape.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);

-- Note: The default password is 'admin123'
-- You should change this password after first login for security

-- If you want to add last_login and is_active columns to your existing admins table, run these:
-- ALTER TABLE `admins` ADD COLUMN `last_login` timestamp NULL DEFAULT NULL AFTER `updated_at`;
-- ALTER TABLE `admins` ADD COLUMN `is_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `last_login`;
