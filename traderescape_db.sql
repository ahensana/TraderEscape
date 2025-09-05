CREATE DATABASE IF NOT EXISTS traderescape_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE traderescape_db;

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    profile_avatar VARCHAR(255) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255) DEFAULT NULL,
    password_reset_token VARCHAR(255) DEFAULT NULL,
    password_reset_expires DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    login_provider ENUM('local', 'google', 'facebook') DEFAULT 'local',
    social_id VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_social_id (social_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_session_token (session_token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) UNIQUE NOT NULL,
    title VARCHAR(200) NOT NULL,
    meta_description TEXT DEFAULT NULL,
    meta_keywords TEXT DEFAULT NULL,
    content_type ENUM('static', 'dynamic', 'blog') DEFAULT 'static',
    is_published BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_is_published (is_published)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS page_content (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_id INT NOT NULL,
    version INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content LONGTEXT NOT NULL,
    meta_description TEXT DEFAULT NULL,
    meta_keywords TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_page_version (page_id, version),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS trading_tools (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT DEFAULT NULL,
    tool_type ENUM('calculator', 'analyzer', 'simulator', 'chart', 'other') DEFAULT 'other',
    is_active BOOLEAN DEFAULT TRUE,
    requires_auth BOOLEAN DEFAULT FALSE,
    tool_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_tool_type (tool_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_tool_usage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    tool_id INT NOT NULL,
    session_start DATETIME NOT NULL,
    session_end DATETIME DEFAULT NULL,
    usage_duration INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tool_id) REFERENCES trading_tools(id) ON DELETE CASCADE,
    INDEX idx_user_tool (user_id, tool_id),
    INDEX idx_session_start (session_start)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS educational_content (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    content_type ENUM('article', 'course', 'video', 'tutorial') DEFAULT 'article',
    content LONGTEXT NOT NULL,
    excerpt TEXT DEFAULT NULL,
    difficulty_level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    is_published BOOLEAN DEFAULT TRUE,
    author_id INT DEFAULT NULL,
    featured_image VARCHAR(255) DEFAULT NULL,
    tags JSON DEFAULT NULL,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_content_type (content_type),
    INDEX idx_difficulty_level (difficulty_level),
    INDEX idx_is_published (is_published)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_learning_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    content_id INT NOT NULL,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    is_completed BOOLEAN DEFAULT FALSE,
    started_at DATETIME DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    last_accessed DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (content_id) REFERENCES educational_content(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_content (user_id, content_id),
    INDEX idx_user_id (user_id),
    INDEX idx_content_id (content_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS page_views (
    id INT PRIMARY KEY AUTO_INCREMENT,
    page_slug VARCHAR(100) NOT NULL,
    user_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    referrer VARCHAR(500) DEFAULT NULL,
    session_id VARCHAR(255) DEFAULT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_page_slug (page_slug),
    INDEX idx_user_id (user_id),
    INDEX idx_viewed_at (viewed_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    activity_type ENUM('login', 'logout', 'page_view', 'tool_usage', 'content_access', 'profile_update', 'other') DEFAULT 'other',
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS site_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT DEFAULT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT DEFAULT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preference (user_id, preference_key),
    INDEX idx_user_id (user_id),
    INDEX idx_preference_key (preference_key)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cookie_consent (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    consent_type ENUM('accepted', 'declined', 'partial') DEFAULT 'declined',
    essential_cookies BOOLEAN DEFAULT FALSE,
    analytics_cookies BOOLEAN DEFAULT FALSE,
    marketing_cookies BOOLEAN DEFAULT FALSE,
    functional_cookies BOOLEAN DEFAULT FALSE,
    consent_given_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_consent_type (consent_type)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS privacy_policy_acceptance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    policy_version VARCHAR(50) NOT NULL,
    accepted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_policy_version (policy_version)
) ENGINE=InnoDB;

INSERT INTO pages (slug, title, meta_description, content_type) VALUES
('home', 'Home - The Trader''s Escape', 'Empowering traders with comprehensive educational content and advanced tools for stock market success.', 'static'),
('about', 'About Us - The Trader''s Escape', 'Learn about The Trader''s Escape - your comprehensive platform for trading education, tools, and market insights.', 'static'),
('tools', 'Trading Tools - The Trader''s Escape', 'Access our comprehensive suite of trading tools, calculators, and analysis instruments.', 'static'),
('disclaimer', 'Disclaimer - The Trader''s Escape', 'Important disclaimers and legal information about our trading education platform.', 'static'),
('risk', 'Risk Disclosure - The Trader''s Escape', 'Understanding the risks of trading and investing in financial markets.', 'static'),
('privacy', 'Privacy Policy - The Trader''s Escape', 'How we protect your personal information and maintain your privacy.', 'static'),
('terms', 'Terms & Conditions - The Trader''s Escape', 'Our terms of service and usage policies for The Trader''s Escape platform.', 'static'),
('cookies', 'Cookies Policy - The Trader''s Escape', 'Learn about how we use cookies to enhance your browsing experience.', 'static'),
('contact', 'Contact Us - The Trader''s Escape', 'Get in touch with our team for support and inquiries.', 'static'),
('login', 'Login - The Trader''s Escape', 'Access your trading education account.', 'static'),
('account', 'My Account - The Trader''s Escape', 'Manage your profile and account settings.', 'static');

INSERT INTO trading_tools (name, slug, description, tool_type, requires_auth, tool_order) VALUES
('Position Size Calculator', 'position-size-calculator', 'Calculate the optimal position size based on your risk tolerance and account size.', 'calculator', FALSE, 1),
('Risk Reward Calculator', 'risk-reward-calculator', 'Analyze the risk-to-reward ratio of your trading strategies.', 'calculator', FALSE, 2),
('Profit Loss Calculator', 'profit-loss-calculator', 'Calculate potential profits and losses for your trades.', 'calculator', FALSE, 3),
('Margin Calculator', 'margin-calculator', 'Determine margin requirements for your trading positions.', 'calculator', FALSE, 4),
('Portfolio Analyzer', 'portfolio-analyzer', 'Analyze your portfolio performance and risk metrics.', 'analyzer', TRUE, 5),
('Market Simulator', 'market-simulator', 'Practice trading with virtual money in a risk-free environment.', 'simulator', TRUE, 6),
('Chart Analysis Tool', 'chart-analysis-tool', 'Advanced charting and technical analysis tools.', 'chart', TRUE, 7);

INSERT INTO site_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('site_name', 'The Trader''s Escape', 'string', 'Website name', TRUE),
('site_description', 'Empowering traders with comprehensive educational content and advanced tools for stock market success.', 'string', 'Website description', TRUE),
('site_url', 'https://thetradersescape.com', 'string', 'Website URL', TRUE),
('contact_email', 'contact@thetradersescape.com', 'string', 'Contact email address', TRUE),
('privacy_email', 'privacy@thetradersescape.com', 'string', 'Privacy policy contact email', TRUE),
('maintenance_mode', 'false', 'boolean', 'Maintenance mode status', FALSE),
('registration_enabled', 'true', 'boolean', 'User registration status', FALSE),
('social_login_enabled', 'true', 'boolean', 'Social login functionality status', FALSE),
('cookie_consent_required', 'true', 'boolean', 'Cookie consent requirement status', FALSE),
('max_login_attempts', '5', 'number', 'Maximum login attempts before lockout', FALSE),
('session_timeout_minutes', '30', 'number', 'User session timeout in minutes', FALSE);

CREATE INDEX idx_users_created_at ON users(created_at);
CREATE INDEX idx_users_last_login ON users(last_login);
CREATE INDEX idx_page_content_page_id ON page_content(page_id);
CREATE INDEX idx_trading_tools_order ON trading_tools(tool_order);
CREATE INDEX idx_educational_content_difficulty ON educational_content(difficulty_level);
CREATE INDEX idx_educational_content_author ON educational_content(author_id);
CREATE INDEX idx_user_learning_progress_completed ON user_learning_progress(is_completed);
CREATE INDEX idx_page_views_session ON page_views(session_id);
CREATE INDEX idx_user_activity_log_type_time ON user_activity_log(activity_type, created_at);
CREATE INDEX idx_cookie_consent_time ON cookie_consent(consent_given_at);
CREATE INDEX idx_privacy_policy_acceptance_time ON privacy_policy_acceptance(accepted_at);

DROP VIEW IF EXISTS user_dashboard_summary;
CREATE VIEW user_dashboard_summary AS
SELECT 
    u.id,
    u.username,
    u.full_name,
    u.email,
    u.created_at AS member_since,
    u.last_login,
    COUNT(DISTINCT utu.tool_id) AS tools_used,
    COUNT(DISTINCT ulp.content_id) AS content_accessed,
    SUM(CASE WHEN ulp.is_completed = TRUE THEN 1 ELSE 0 END) AS content_completed
FROM users u
LEFT JOIN user_tool_usage utu ON u.id = utu.user_id
LEFT JOIN user_learning_progress ulp ON u.id = ulp.user_id
GROUP BY u.id, u.username, u.full_name, u.email, u.created_at, u.last_login;

DROP VIEW IF EXISTS tool_usage_stats;
CREATE VIEW tool_usage_stats AS
SELECT 
    tt.id,
    tt.name,
    tt.slug,
    tt.tool_type,
    COUNT(utu.id) AS total_usage_count,
    COUNT(DISTINCT utu.user_id) AS unique_users,
    AVG(utu.usage_duration) AS avg_session_duration,
    SUM(utu.usage_duration) AS total_usage_time
FROM trading_tools tt
LEFT JOIN user_tool_usage utu ON tt.id = utu.tool_id
WHERE tt.is_active = TRUE
GROUP BY tt.id, tt.name, tt.slug, tt.tool_type;

DROP VIEW IF EXISTS content_popularity;
CREATE VIEW content_popularity AS
SELECT 
    ec.id,
    ec.title,
    ec.slug,
    ec.content_type,
    ec.difficulty_level,
    ec.view_count,
    COUNT(ulp.user_id) AS users_started,
    SUM(CASE WHEN ulp.is_completed = TRUE THEN 1 ELSE 0 END) AS users_completed,
    ROUND((SUM(CASE WHEN ulp.is_completed = TRUE THEN 1 ELSE 0 END) / NULLIF(COUNT(ulp.user_id), 0)) * 100.0, 2) AS completion_rate
FROM educational_content ec
LEFT JOIN user_learning_progress ulp ON ec.id = ulp.content_id
WHERE ec.is_published = TRUE
GROUP BY ec.id, ec.title, ec.slug, ec.content_type, ec.difficulty_level, ec.view_count;

DELIMITER //
DROP PROCEDURE IF EXISTS GetUserStats //
CREATE PROCEDURE GetUserStats(IN user_id_param INT)
BEGIN
    SELECT 
        u.username,
        u.full_name,
        u.email,
        u.created_at AS member_since,
        u.last_login,
        COUNT(DISTINCT utu.tool_id) AS tools_used,
        COUNT(DISTINCT ulp.content_id) AS content_accessed,
        SUM(CASE WHEN ulp.is_completed = TRUE THEN 1 ELSE 0 END) AS content_completed,
        SUM(utu.usage_duration) AS total_tool_usage_time
    FROM users u
    LEFT JOIN user_tool_usage utu ON u.id = utu.user_id
    LEFT JOIN user_learning_progress ulp ON u.id = ulp.user_id
    WHERE u.id = user_id_param
    GROUP BY u.username, u.full_name, u.email, u.created_at, u.last_login;
END //
DROP PROCEDURE IF EXISTS TrackPageView //
CREATE PROCEDURE TrackPageView(
    IN page_slug_param VARCHAR(100),
    IN user_id_param INT,
    IN ip_address_param VARCHAR(45),
    IN user_agent_param TEXT,
    IN referrer_param VARCHAR(500),
    IN session_id_param VARCHAR(255)
)
BEGIN
    INSERT INTO page_views (page_slug, user_id, ip_address, user_agent, referrer, session_id)
    VALUES (page_slug_param, user_id_param, ip_address_param, user_agent_param, referrer_param, session_id_param);
    UPDATE educational_content SET view_count = view_count + 1 WHERE slug = page_slug_param;
END //
DROP PROCEDURE IF EXISTS LogUserActivity //
CREATE PROCEDURE LogUserActivity(
    IN user_id_param INT,
    IN activity_type_param VARCHAR(20),
    IN description_param TEXT,
    IN ip_address_param VARCHAR(45),
    IN user_agent_param TEXT,
    IN metadata_param TEXT
)
BEGIN
    INSERT INTO user_activity_log (user_id, activity_type, description, ip_address, user_agent, metadata)
    VALUES (user_id_param, activity_type_param, description_param, ip_address_param, user_agent_param, metadata_param);
END //
DELIMITER ;
