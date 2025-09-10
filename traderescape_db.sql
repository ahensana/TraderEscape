-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 09, 2025 at 12:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `traderescape_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `CalculatePositionSize` (IN `user_id_param` INT, IN `profile_id_param` INT, IN `symbol_param` VARCHAR(20), IN `entry_price_param` DECIMAL(10,2), IN `stop_loss_param` DECIMAL(10,2), IN `risk_percentage_param` DECIMAL(5,2))   BEGIN
    DECLARE account_size DECIMAL(15,2);
    DECLARE risk_amount DECIMAL(10,2);
    DECLARE price_difference DECIMAL(10,2);
    DECLARE position_size INT;
    DECLARE position_value DECIMAL(15,2);
    
    -- Get account size from profile
    SELECT account_size INTO account_size 
    FROM user_trading_profiles 
    WHERE id = profile_id_param AND user_id = user_id_param;
    
    -- Calculate risk amount
    SET risk_amount = account_size * (risk_percentage_param / 100);
    
    -- Calculate price difference
    SET price_difference = ABS(entry_price_param - stop_loss_param);
    
    -- Calculate position size
    SET position_size = FLOOR(risk_amount / price_difference);
    
    -- Calculate position value
    SET position_value = position_size * entry_price_param;
    
    -- Return results
    SELECT 
        position_size as shares,
        position_value as position_value,
        risk_amount as risk_amount,
        (risk_amount / account_size) * 100 as actual_risk_percentage,
        price_difference as price_difference;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `CheckRiskLimits` (IN `user_id_param` INT, IN `profile_id_param` INT)   BEGIN
    DECLARE daily_loss DECIMAL(10,2);
    DECLARE max_daily_loss DECIMAL(10,2);
    DECLARE total_risk DECIMAL(10,2);
    DECLARE max_risk DECIMAL(10,2);
    DECLARE account_size DECIMAL(15,2);
    
    -- Get profile settings
    SELECT 
        account_size,
        max_daily_loss_percentage,
        max_open_risk_percentage
    INTO account_size, max_daily_loss, max_risk
    FROM user_trading_profiles 
    WHERE id = profile_id_param AND user_id = user_id_param;
    
    -- Calculate current daily loss
    SELECT COALESCE(SUM(pnl), 0) INTO daily_loss
    FROM trade_journal 
    WHERE user_id = user_id_param AND profile_id = profile_id_param 
    AND trade_date = CURDATE() AND pnl < 0;
    
    -- Calculate current total risk
    SELECT COALESCE(SUM(risk_amount), 0) INTO total_risk
    FROM trade_journal 
    WHERE user_id = user_id_param AND profile_id = profile_id_param 
    AND trade_status = 'open';
    
    -- Check daily loss limit
    IF daily_loss < -(account_size * max_daily_loss / 100) THEN
        INSERT INTO risk_alerts (user_id, profile_id, alert_type, alert_message, current_value, threshold_value, severity)
        VALUES (user_id_param, profile_id_param, 'daily_loss_limit', 
                CONCAT('Daily loss limit exceeded: ₹', ABS(daily_loss)), 
                ABS(daily_loss), account_size * max_daily_loss / 100, 'critical');
    END IF;
    
    -- Check max risk limit
    IF total_risk > (account_size * max_risk / 100) THEN
        INSERT INTO risk_alerts (user_id, profile_id, alert_type, alert_message, current_value, threshold_value, severity)
        VALUES (user_id_param, profile_id_param, 'max_risk_limit', 
                CONCAT('Maximum risk limit exceeded: ₹', total_risk), 
                total_risk, account_size * max_risk / 100, 'high');
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserStats` (IN `user_id_param` INT)   BEGIN
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
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `LogUserActivity` (IN `user_id_param` INT, IN `activity_type_param` VARCHAR(20), IN `description_param` TEXT, IN `ip_address_param` VARCHAR(45), IN `user_agent_param` TEXT, IN `metadata_param` TEXT)   BEGIN
    INSERT INTO user_activity_log (user_id, activity_type, description, ip_address, user_agent, metadata)
    VALUES (user_id_param, activity_type_param, description_param, ip_address_param, user_agent_param, metadata_param);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `TrackPageView` (IN `page_slug_param` VARCHAR(100), IN `user_id_param` INT, IN `ip_address_param` VARCHAR(45), IN `user_agent_param` TEXT, IN `referrer_param` VARCHAR(500), IN `session_id_param` VARCHAR(255))   BEGIN
    INSERT INTO page_views (page_slug, user_id, ip_address, user_agent, referrer, session_id)
    VALUES (page_slug_param, user_id_param, ip_address_param, user_agent_param, referrer_param, session_id_param);
    UPDATE educational_content SET view_count = view_count + 1 WHERE slug = page_slug_param;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateRiskMetrics` (IN `user_id_param` INT, IN `profile_id_param` INT)   BEGIN
    DECLARE total_equity DECIMAL(15,2);
    DECLARE total_risk DECIMAL(10,2);
    DECLARE total_trades INT;
    DECLARE winning_trades INT;
    DECLARE losing_trades INT;
    DECLARE total_pnl DECIMAL(10,2);
    DECLARE win_rate DECIMAL(5,2);
    DECLARE avg_win DECIMAL(10,2);
    DECLARE avg_loss DECIMAL(10,2);
    DECLARE profit_factor DECIMAL(5,2);
    DECLARE max_drawdown DECIMAL(5,2);
    
    -- Get current equity from profile
    SELECT account_size INTO total_equity 
    FROM user_trading_profiles 
    WHERE id = profile_id_param AND user_id = user_id_param;
    
    -- Calculate trade statistics
    SELECT 
        COUNT(*) as total_trades,
        SUM(CASE WHEN pnl > 0 THEN 1 ELSE 0 END) as winning_trades,
        SUM(CASE WHEN pnl < 0 THEN 1 ELSE 0 END) as losing_trades,
        SUM(COALESCE(pnl, 0)) as total_pnl,
        AVG(CASE WHEN pnl > 0 THEN pnl ELSE NULL END) as avg_win,
        AVG(CASE WHEN pnl < 0 THEN pnl ELSE NULL END) as avg_loss
    INTO total_trades, winning_trades, losing_trades, total_pnl, avg_win, avg_loss
    FROM trade_journal 
    WHERE user_id = user_id_param AND profile_id = profile_id_param;
    
    -- Calculate win rate
    SET win_rate = (winning_trades / total_trades) * 100;
    
    -- Calculate profit factor
    SET profit_factor = ABS(avg_win * winning_trades) / ABS(avg_loss * losing_trades);
    
    -- Calculate total risk (sum of all open positions risk)
    SELECT COALESCE(SUM(risk_amount), 0) INTO total_risk
    FROM trade_journal 
    WHERE user_id = user_id_param AND profile_id = profile_id_param AND trade_status = 'open';
    
    -- Calculate max drawdown (simplified)
    SET max_drawdown = 0; -- This would need more complex calculation
    
    -- Insert or update risk metrics
    INSERT INTO risk_metrics (
        user_id, profile_id, calculation_date, total_equity, total_risk,
        risk_percentage, max_drawdown, win_rate, avg_win, avg_loss,
        profit_factor, total_trades, winning_trades, losing_trades,
        largest_win, largest_loss, consecutive_wins, consecutive_losses
    ) VALUES (
        user_id_param, profile_id_param, CURDATE(), total_equity, total_risk,
        (total_risk / total_equity) * 100, max_drawdown, win_rate, 
        COALESCE(avg_win, 0), COALESCE(avg_loss, 0), COALESCE(profit_factor, 0),
        total_trades, winning_trades, losing_trades, 0, 0, 0, 0
    ) ON DUPLICATE KEY UPDATE
        total_equity = VALUES(total_equity),
        total_risk = VALUES(total_risk),
        risk_percentage = VALUES(risk_percentage),
        max_drawdown = VALUES(max_drawdown),
        win_rate = VALUES(win_rate),
        avg_win = VALUES(avg_win),
        avg_loss = VALUES(avg_loss),
        profit_factor = VALUES(profit_factor),
        total_trades = VALUES(total_trades),
        winning_trades = VALUES(winning_trades),
        losing_trades = VALUES(losing_trades);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `content_popularity`
-- (See below for the actual view)
--
CREATE TABLE `content_popularity` (
`id` int(11)
,`title` varchar(200)
,`slug` varchar(100)
,`content_type` enum('article','course','video','tutorial')
,`difficulty_level` enum('beginner','intermediate','advanced')
,`view_count` int(11)
,`users_started` bigint(21)
,`users_completed` decimal(22,0)
,`completion_rate` decimal(28,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `cookie_consent`
--

CREATE TABLE `cookie_consent` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `consent_type` enum('accepted','declined','partial') DEFAULT 'declined',
  `essential_cookies` tinyint(1) DEFAULT 0,
  `analytics_cookies` tinyint(1) DEFAULT 0,
  `marketing_cookies` tinyint(1) DEFAULT 0,
  `functional_cookies` tinyint(1) DEFAULT 0,
  `consent_given_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cookie_consent`
--

INSERT INTO `cookie_consent` (`id`, `user_id`, `session_id`, `ip_address`, `consent_type`, `essential_cookies`, `analytics_cookies`, `marketing_cookies`, `functional_cookies`, `consent_given_at`, `last_updated`) VALUES
(1, 1, 'er84jofoe3vjbp9r2h0f13g88t', '::1', 'accepted', 1, 1, 1, 1, '2025-09-08 09:17:51', '2025-09-08 09:17:51'),
(2, 2, 'ps3b74t4h4gk3nj9c44v33vuah', '::1', 'accepted', 1, 1, 1, 1, '2025-09-08 10:45:55', '2025-09-08 10:45:55');

-- --------------------------------------------------------

--
-- Table structure for table `correlation_data`
--

CREATE TABLE `correlation_data` (
  `id` int(11) NOT NULL,
  `symbol1` varchar(20) NOT NULL,
  `symbol2` varchar(20) NOT NULL,
  `correlation_period` enum('1D','1W','1M','3M','6M','1Y') NOT NULL,
  `correlation_value` decimal(5,4) NOT NULL,
  `calculation_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `educational_content`
--

CREATE TABLE `educational_content` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `content_type` enum('article','course','video','tutorial') DEFAULT 'article',
  `content` longtext NOT NULL,
  `excerpt` text DEFAULT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
  `is_published` tinyint(1) DEFAULT 1,
  `author_id` int(11) DEFAULT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `view_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `market_data`
--

CREATE TABLE `market_data` (
  `id` int(11) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `segment` enum('Equity Cash','Equity F&O','Currency','Commodity','Crypto') NOT NULL,
  `date` date NOT NULL,
  `open_price` decimal(10,2) NOT NULL,
  `high_price` decimal(10,2) NOT NULL,
  `low_price` decimal(10,2) NOT NULL,
  `close_price` decimal(10,2) NOT NULL,
  `volume` bigint(20) NOT NULL DEFAULT 0,
  `volatility` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `title` varchar(200) NOT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `content_type` enum('static','dynamic','blog') DEFAULT 'static',
  `is_published` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `slug`, `title`, `meta_description`, `meta_keywords`, `content_type`, `is_published`, `created_at`, `updated_at`) VALUES
(1, 'home', 'Home - The Trader\'s Escape', 'Empowering traders with comprehensive educational content and advanced tools for stock market success.', NULL, 'static', 1, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(2, 'about', 'About Us - The Trader\'s Escape', 'Learn about The Trader\'s Escape - your comprehensive platform for trading education, tools, and market insights.', NULL, 'static', 1, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(3, 'tools', 'Trading Tools - The Trader\'s Escape', 'Access our comprehensive suite of trading tools, calculators, and analysis instruments.', NULL, 'static', 1, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(4, 'disclaimer', 'Disclaimer - The Trader\'s Escape', 'Important disclaimers and legal information about our trading education platform.', NULL, 'static', 1, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(5, 'risk', 'Risk Disclosure - The Trader\'s Escape', 'Understanding the risks of trading and investing in financial markets.', NULL, 'static', 1, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(6, 'privacy', 'Privacy Policy - The Trader\'s Escape', 'How we protect your personal information and maintain your privacy.', NULL, 'static', 1, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(7, 'terms', 'Terms & Conditions - The Trader\'s Escape', 'Our terms of service and usage policies for The Trader\'s Escape platform.', NULL, 'static', 1, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(8, 'cookies', 'Cookies Policy - The Trader\'s Escape', 'Learn about how we use cookies to enhance your browsing experience.', NULL, 'static', 1, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(9, 'contact', 'Contact Us - The Trader\'s Escape', 'Get in touch with our team for support and inquiries.', NULL, 'static', 1, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(10, 'login', 'Login - The Trader\'s Escape', 'Access your trading education account.', NULL, 'static', 1, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(11, 'account', 'My Account - The Trader\'s Escape', 'Manage your profile and account settings.', NULL, 'static', 1, '2025-09-03 11:03:50', '2025-09-03 11:03:50');

-- --------------------------------------------------------

--
-- Table structure for table `page_content`
--

CREATE TABLE `page_content` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` longtext NOT NULL,
  `meta_description` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `page_views`
--

CREATE TABLE `page_views` (
  `id` int(11) NOT NULL,
  `page_slug` varchar(100) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `referrer` varchar(500) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `page_views`
--

INSERT INTO `page_views` (`id`, `page_slug`, `user_id`, `ip_address`, `user_agent`, `referrer`, `session_id`, `viewed_at`) VALUES
(1, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:31:33'),
(2, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:31:36'),
(3, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:33:17'),
(4, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:33:19'),
(5, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:33:20'),
(6, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:33:22'),
(7, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:33:23'),
(8, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:33:23'),
(9, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:33:24'),
(10, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:33:24'),
(11, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:33:24'),
(12, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:33:24'),
(13, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:33:30'),
(14, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:33:30'),
(15, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:33:31'),
(16, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:33:32'),
(17, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:34:28'),
(18, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:36:00'),
(19, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:36:48'),
(20, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:39:54'),
(21, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:39:56'),
(22, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:39:57'),
(23, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:39:58'),
(24, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:39:58'),
(25, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:39:58'),
(26, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:39:59'),
(27, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:39:59'),
(28, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:39:59'),
(29, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:39:59'),
(30, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:39:59'),
(31, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:00'),
(32, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:00'),
(33, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:00'),
(34, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:00'),
(35, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:01'),
(36, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:01'),
(37, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:01'),
(38, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:01'),
(39, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:02'),
(40, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:02'),
(41, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:02'),
(42, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:02'),
(43, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:02'),
(44, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:02'),
(45, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:03'),
(46, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:03'),
(47, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:03'),
(48, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:03'),
(49, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:03'),
(50, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:04'),
(51, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:04'),
(52, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:04'),
(53, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:40:04'),
(54, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:41:12'),
(55, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:41:23'),
(56, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', '0vgoa3rop43o1es0n0bcief5ge', '2025-09-03 11:41:32'),
(57, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:40'),
(58, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:43'),
(59, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:44'),
(60, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:45'),
(61, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:45'),
(62, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:46'),
(63, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:46'),
(64, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:46'),
(65, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:46'),
(66, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:47'),
(67, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:47'),
(68, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:55'),
(69, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:55'),
(70, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:56'),
(71, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:56'),
(72, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:56'),
(73, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:57'),
(74, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:57'),
(75, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:57'),
(76, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:57'),
(77, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:57'),
(78, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:57'),
(79, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:57'),
(80, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:58'),
(81, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:58'),
(82, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:58'),
(83, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'bvomhlforuk4ms6agk7s49brfn', '2025-09-03 19:29:58'),
(84, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:00:10'),
(85, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:00:11'),
(86, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:00:12'),
(87, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:00:12'),
(88, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:00:13'),
(89, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:00:13'),
(90, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:00:13'),
(91, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:00:13'),
(92, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:00:13'),
(93, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:00:14'),
(94, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:00:14'),
(95, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:00:14'),
(96, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:00:14'),
(97, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:54:49'),
(98, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:56:16'),
(99, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:56:58'),
(100, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:57:37'),
(101, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:57:38'),
(102, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:57:44'),
(103, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:57:49'),
(104, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:59:14'),
(105, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:59:14'),
(106, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:59:22'),
(107, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:59:23'),
(108, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:59:23'),
(109, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:59:23'),
(110, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:59:23'),
(111, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:59:23'),
(112, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:59:23'),
(113, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:59:23'),
(114, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 06:59:23'),
(115, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 07:02:28'),
(116, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 07:02:30'),
(117, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'ju4ot2m56fqbq0j7abauft70bb', '2025-09-04 07:06:22'),
(118, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', '18355uol6jsudlknfr7sc8d8ml', '2025-09-04 07:06:35'),
(119, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', '18355uol6jsudlknfr7sc8d8ml', '2025-09-04 07:06:35'),
(120, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', '18355uol6jsudlknfr7sc8d8ml', '2025-09-04 07:06:35'),
(121, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', '18355uol6jsudlknfr7sc8d8ml', '2025-09-04 07:06:35'),
(122, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', '18355uol6jsudlknfr7sc8d8ml', '2025-09-04 07:06:35'),
(123, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', '18355uol6jsudlknfr7sc8d8ml', '2025-09-04 07:06:35'),
(124, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', '18355uol6jsudlknfr7sc8d8ml', '2025-09-04 07:06:35'),
(125, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', '18355uol6jsudlknfr7sc8d8ml', '2025-09-04 07:06:35'),
(126, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', '18355uol6jsudlknfr7sc8d8ml', '2025-09-04 07:06:35'),
(127, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/terms.php', '18355uol6jsudlknfr7sc8d8ml', '2025-09-04 07:10:58'),
(128, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/terms.php', '18355uol6jsudlknfr7sc8d8ml', '2025-09-04 07:10:59'),
(129, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', '18355uol6jsudlknfr7sc8d8ml', '2025-09-04 07:11:03'),
(130, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 's0ktd3d07r6bk0tprp17itnhs0', '2025-09-04 07:11:11'),
(131, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 's0ktd3d07r6bk0tprp17itnhs0', '2025-09-04 07:11:11'),
(132, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 's0ktd3d07r6bk0tprp17itnhs0', '2025-09-04 07:11:11'),
(133, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 's0ktd3d07r6bk0tprp17itnhs0', '2025-09-04 07:11:11'),
(134, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 's0ktd3d07r6bk0tprp17itnhs0', '2025-09-04 07:11:11'),
(135, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 's0ktd3d07r6bk0tprp17itnhs0', '2025-09-04 07:11:11'),
(136, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 's0ktd3d07r6bk0tprp17itnhs0', '2025-09-04 07:11:11'),
(137, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 's0ktd3d07r6bk0tprp17itnhs0', '2025-09-04 07:11:11'),
(138, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 's0ktd3d07r6bk0tprp17itnhs0', '2025-09-04 07:11:11'),
(139, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 's0ktd3d07r6bk0tprp17itnhs0', '2025-09-04 07:13:35'),
(140, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 's0ktd3d07r6bk0tprp17itnhs0', '2025-09-04 07:17:23'),
(141, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/terms.php', 's0ktd3d07r6bk0tprp17itnhs0', '2025-09-04 07:17:30'),
(142, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 's0ktd3d07r6bk0tprp17itnhs0', '2025-09-04 07:17:38'),
(143, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 's0ktd3d07r6bk0tprp17itnhs0', '2025-09-04 07:17:52'),
(144, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:18:00'),
(145, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:18:00'),
(146, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:18:00'),
(147, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:18:00'),
(148, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:18:00'),
(149, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:18:00'),
(150, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:18:00'),
(151, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:18:00'),
(152, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:18:00'),
(153, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:21:15'),
(154, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:37:36'),
(155, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:37:41'),
(156, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:37:46'),
(157, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:37:47'),
(158, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:37:48'),
(159, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:37:49'),
(160, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:38:12'),
(161, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:50:25'),
(162, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:50:50'),
(163, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:51:29'),
(164, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:51:42'),
(165, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:51:44'),
(166, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:51:44'),
(167, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:51:45'),
(168, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:51:45'),
(169, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:51:45'),
(170, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:52:06'),
(171, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:52:09'),
(172, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:52:11'),
(173, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 07:52:13'),
(174, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:36:28'),
(175, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:37:22'),
(176, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:37:25'),
(177, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:37:56'),
(178, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:00'),
(179, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:03'),
(180, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:04'),
(181, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:04'),
(182, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:04'),
(183, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:05'),
(184, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:05'),
(185, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:05'),
(186, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:05'),
(187, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:14'),
(188, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:14'),
(189, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:14'),
(190, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:15'),
(191, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:15'),
(192, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:15'),
(193, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:15'),
(194, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:15'),
(195, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:15'),
(196, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:16'),
(197, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:16'),
(198, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:16'),
(199, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:16'),
(200, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:31'),
(201, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:32'),
(202, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:32'),
(203, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:33'),
(204, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:33'),
(205, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:34'),
(206, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:34'),
(207, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:38:34'),
(208, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:39:29'),
(209, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:39:30'),
(210, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:41:13'),
(211, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:41:36'),
(212, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'glnr7s26il36d0g0h9nkqgrra9', '2025-09-04 08:51:10'),
(213, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:51:17'),
(214, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:51:17'),
(215, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:51:17');
INSERT INTO `page_views` (`id`, `page_slug`, `user_id`, `ip_address`, `user_agent`, `referrer`, `session_id`, `viewed_at`) VALUES
(216, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:51:17'),
(217, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:51:17'),
(218, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:51:17'),
(219, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:51:17'),
(220, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:51:17'),
(221, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:51:17'),
(222, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:51:17'),
(223, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:51:26'),
(224, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:52:14'),
(225, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/disclaimer.php', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:52:22'),
(226, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:52:25'),
(227, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:52:33'),
(228, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:53:11'),
(229, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 08:53:13'),
(230, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 09:18:49'),
(231, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 09:18:50'),
(232, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 09:18:50'),
(233, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 09:18:50'),
(234, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 09:18:50'),
(235, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 09:18:50'),
(236, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 09:18:50'),
(237, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 09:18:50'),
(238, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'p3je69159pupd9vgi26rh0n7gb', '2025-09-04 09:18:50'),
(239, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', '3f2i2d6h5qj31h3h7agu5i5g6o', '2025-09-04 09:18:57'),
(240, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', '3f2i2d6h5qj31h3h7agu5i5g6o', '2025-09-04 09:18:57'),
(241, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', '3f2i2d6h5qj31h3h7agu5i5g6o', '2025-09-04 09:18:57'),
(242, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', '3f2i2d6h5qj31h3h7agu5i5g6o', '2025-09-04 09:18:57'),
(243, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', '3f2i2d6h5qj31h3h7agu5i5g6o', '2025-09-04 09:18:57'),
(244, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', '3f2i2d6h5qj31h3h7agu5i5g6o', '2025-09-04 09:18:57'),
(245, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', '3f2i2d6h5qj31h3h7agu5i5g6o', '2025-09-04 09:18:57'),
(246, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', '3f2i2d6h5qj31h3h7agu5i5g6o', '2025-09-04 09:18:57'),
(247, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', '3f2i2d6h5qj31h3h7agu5i5g6o', '2025-09-04 09:18:57'),
(248, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'u04iocoijv7uh9455uclvcnj3f', '2025-09-04 09:58:56'),
(249, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'u04iocoijv7uh9455uclvcnj3f', '2025-09-04 09:58:56'),
(250, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'u04iocoijv7uh9455uclvcnj3f', '2025-09-04 09:58:56'),
(251, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'u04iocoijv7uh9455uclvcnj3f', '2025-09-04 09:58:56'),
(252, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'u04iocoijv7uh9455uclvcnj3f', '2025-09-04 09:58:56'),
(253, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'u04iocoijv7uh9455uclvcnj3f', '2025-09-04 09:58:56'),
(254, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'u04iocoijv7uh9455uclvcnj3f', '2025-09-04 09:58:56'),
(255, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/sw.js', 'u04iocoijv7uh9455uclvcnj3f', '2025-09-04 09:58:56'),
(256, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:06:05'),
(257, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:06:22'),
(258, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:08:21'),
(259, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:08:23'),
(260, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:08:25'),
(261, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:08:26'),
(262, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:08:26'),
(263, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:08:27'),
(264, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:08:28'),
(265, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:08:53'),
(266, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:09:00'),
(267, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:09:03'),
(268, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/tools.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:09:19'),
(269, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:09:20'),
(270, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/tools.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:09:21'),
(271, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/tools.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:09:26'),
(272, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/tools.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:09:28'),
(273, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/tools.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:09:29'),
(274, 'about', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:09:31'),
(275, 'contact', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/about.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:09:32'),
(276, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/contact.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:09:33'),
(277, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:09:34'),
(278, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:09:47'),
(279, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:09:48'),
(280, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:09:50'),
(281, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:09:51'),
(282, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:10:32'),
(283, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:10:33'),
(284, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:10:34'),
(285, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:10:35'),
(286, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:10:35'),
(287, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:10:36'),
(288, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:10:37'),
(289, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:10:45'),
(290, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:10:46'),
(291, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:10:46'),
(292, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:10:47'),
(293, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:11:07'),
(294, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/disclaimer.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:11:08'),
(295, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:11:09'),
(296, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:12:16'),
(297, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'v441id5ob7pvfqq2tq4sgr2s8l', '2025-09-04 10:12:17'),
(298, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, '7r317vfrv1040rbot64urflt99', '2025-09-08 04:45:58'),
(299, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', '7r317vfrv1040rbot64urflt99', '2025-09-08 04:46:07'),
(300, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', '7r317vfrv1040rbot64urflt99', '2025-09-08 04:46:10'),
(301, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', '7r317vfrv1040rbot64urflt99', '2025-09-08 04:48:13'),
(302, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', '7r317vfrv1040rbot64urflt99', '2025-09-08 04:48:14'),
(303, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', '7r317vfrv1040rbot64urflt99', '2025-09-08 04:48:15'),
(304, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', '7r317vfrv1040rbot64urflt99', '2025-09-08 04:48:16'),
(305, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', '7r317vfrv1040rbot64urflt99', '2025-09-08 04:48:17'),
(306, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', '7r317vfrv1040rbot64urflt99', '2025-09-08 04:48:18'),
(307, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', '7r317vfrv1040rbot64urflt99', '2025-09-08 04:48:19'),
(308, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', '7r317vfrv1040rbot64urflt99', '2025-09-08 04:50:03'),
(309, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', '7r317vfrv1040rbot64urflt99', '2025-09-08 04:50:05'),
(310, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', '7r317vfrv1040rbot64urflt99', '2025-09-08 04:50:06'),
(311, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, '7r317vfrv1040rbot64urflt99', '2025-09-08 04:50:34'),
(312, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', '7r317vfrv1040rbot64urflt99', '2025-09-08 04:51:17'),
(313, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', '7r317vfrv1040rbot64urflt99', '2025-09-08 04:51:21'),
(314, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', '7r317vfrv1040rbot64urflt99', '2025-09-08 04:51:25'),
(315, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', '7r317vfrv1040rbot64urflt99', '2025-09-08 04:51:47'),
(316, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, '7r317vfrv1040rbot64urflt99', '2025-09-08 04:51:50'),
(317, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, '7r317vfrv1040rbot64urflt99', '2025-09-08 04:52:26'),
(318, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', '5u5g445e2pd2ql44j7na15euh2', '2025-09-08 04:52:40'),
(319, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', '5u5g445e2pd2ql44j7na15euh2', '2025-09-08 04:56:12'),
(320, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', '5u5g445e2pd2ql44j7na15euh2', '2025-09-08 04:57:11'),
(321, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', '5u5g445e2pd2ql44j7na15euh2', '2025-09-08 04:59:59'),
(322, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', '5u5g445e2pd2ql44j7na15euh2', '2025-09-08 05:00:02'),
(323, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', '5u5g445e2pd2ql44j7na15euh2', '2025-09-08 05:00:06'),
(324, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', '5u5g445e2pd2ql44j7na15euh2', '2025-09-08 05:00:09'),
(325, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', '5u5g445e2pd2ql44j7na15euh2', '2025-09-08 05:00:11'),
(326, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', '5u5g445e2pd2ql44j7na15euh2', '2025-09-08 05:00:13'),
(327, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', '5u5g445e2pd2ql44j7na15euh2', '2025-09-08 05:00:17'),
(328, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'kilscgsoq8ps6c0cvvm2oltm7a', '2025-09-08 05:00:48'),
(329, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'kilscgsoq8ps6c0cvvm2oltm7a', '2025-09-08 05:01:03'),
(330, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'kilscgsoq8ps6c0cvvm2oltm7a', '2025-09-08 05:01:10'),
(331, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'kilscgsoq8ps6c0cvvm2oltm7a', '2025-09-08 05:01:13'),
(332, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'kilscgsoq8ps6c0cvvm2oltm7a', '2025-09-08 05:02:05'),
(333, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'kilscgsoq8ps6c0cvvm2oltm7a', '2025-09-08 05:02:07'),
(334, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'kilscgsoq8ps6c0cvvm2oltm7a', '2025-09-08 05:02:09'),
(335, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:02:19'),
(336, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:02:27'),
(337, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:02:28'),
(338, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:05:03'),
(339, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:05:06'),
(340, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:14:16'),
(341, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:14:19'),
(342, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757308458023', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:14:20'),
(343, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/disclaimer.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:14:21'),
(344, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:14:22'),
(345, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/privacy.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:14:23'),
(346, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/terms.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:14:24'),
(347, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/terms.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:15:25'),
(348, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/terms.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:15:27'),
(349, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:16:01'),
(350, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:16:05'),
(351, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:16:18'),
(352, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:16:24'),
(353, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:16:25'),
(354, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:17:31'),
(355, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:19:11'),
(356, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:19:41'),
(357, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757308778501', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:19:51'),
(358, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757308778501', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:19:54'),
(359, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757308778501', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:19:59'),
(360, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/disclaimer.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:20:02'),
(361, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:20:03'),
(362, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/privacy.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:20:10'),
(363, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/terms.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:20:13'),
(364, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:20:20'),
(365, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/disclaimer.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:20:32'),
(366, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/disclaimer.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:21:00'),
(367, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/terms.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:21:02'),
(368, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/terms.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:21:03'),
(369, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/terms.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:22:52'),
(370, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/disclaimer.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:22:57'),
(371, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/terms.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:23:52'),
(372, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:24:02'),
(373, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:24:07'),
(374, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757309045993', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:24:11'),
(375, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/disclaimer.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:24:13'),
(376, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:24:16'),
(377, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/privacy.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:24:18'),
(378, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/terms.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:24:20'),
(379, 'cookies', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:24:34'),
(380, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:24:37'),
(381, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:24:48'),
(382, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:24:50'),
(383, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:26:07'),
(384, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:28:08'),
(385, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:31:22'),
(386, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:31:53'),
(387, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:33:31'),
(388, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:33:32'),
(389, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:33:51'),
(390, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:33:52'),
(391, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:34:11'),
(392, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:34:18'),
(393, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:35:26'),
(394, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:36:53'),
(395, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:40:14'),
(396, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:42:20'),
(397, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:46:52'),
(398, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:46:52'),
(399, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:49:06'),
(400, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:49:06'),
(401, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:49:11'),
(402, 'terms', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:49:13'),
(403, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:49:13'),
(404, 'risk', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/terms.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:49:15'),
(405, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/terms.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:49:15'),
(406, 'contact', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/risk.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:49:23'),
(407, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:49:36'),
(408, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:49:36'),
(409, 'account', 1, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:49:57'),
(410, 'account', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/', 'nd519fqid0phpfii5elig4tst3', '2025-09-08 05:49:57'),
(411, 'home', 1, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/login.php', 'e6dg23fq208dsbqo096n1st4r4', '2025-09-08 05:50:18'),
(412, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/login.php', 'e6dg23fq208dsbqo096n1st4r4', '2025-09-08 05:50:18'),
(413, 'account', 1, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/index.php', 'e6dg23fq208dsbqo096n1st4r4', '2025-09-08 05:51:03'),
(414, 'account', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/index.php', 'e6dg23fq208dsbqo096n1st4r4', '2025-09-08 05:51:04'),
(415, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'e6dg23fq208dsbqo096n1st4r4', '2025-09-08 05:56:20'),
(416, 'account', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'e6dg23fq208dsbqo096n1st4r4', '2025-09-08 05:56:24'),
(417, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'e6dg23fq208dsbqo096n1st4r4', '2025-09-08 05:56:24'),
(418, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 06:29:04'),
(419, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 06:29:04'),
(420, 'cookies', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 06:37:07'),
(421, 'cookies', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 06:37:07'),
(422, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 06:49:30'),
(423, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 06:49:30');
INSERT INTO `page_views` (`id`, `page_slug`, `user_id`, `ip_address`, `user_agent`, `referrer`, `session_id`, `viewed_at`) VALUES
(424, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:06:53'),
(425, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:06:53'),
(426, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:07:39'),
(427, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:07:39'),
(428, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:08:31'),
(429, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:08:31'),
(430, 'login', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:10:13'),
(431, 'login', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:10:13'),
(432, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:11:03'),
(433, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:11:03'),
(434, 'login', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:14:07'),
(435, 'login', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:14:07'),
(436, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:14:25'),
(437, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:14:25'),
(438, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:14:31'),
(439, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:14:31'),
(440, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:14:53'),
(441, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:14:53'),
(442, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:18:42'),
(443, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:18:42'),
(444, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'tbr8fkr3gbecqsvj9ss6m1qrlv', '2025-09-08 08:18:42'),
(445, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:18:47'),
(446, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:18:47'),
(447, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:18:55'),
(448, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:18:55'),
(449, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:18:56'),
(450, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:18:56'),
(451, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:03'),
(452, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:03'),
(453, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:05'),
(454, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:05'),
(455, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:06'),
(456, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:06'),
(457, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:08'),
(458, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:08'),
(459, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:10'),
(460, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:10'),
(461, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:20'),
(462, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:20'),
(463, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/disclaimer.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:22'),
(464, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/disclaimer.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:22'),
(465, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:23'),
(466, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:23'),
(467, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/privacy.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:24'),
(468, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/privacy.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:24'),
(469, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/privacy.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:28'),
(470, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/privacy.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:28'),
(471, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/terms.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:30'),
(472, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:31'),
(473, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:31'),
(474, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/privacy.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:32'),
(475, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/privacy.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:32'),
(476, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:39'),
(477, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:39'),
(478, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:41'),
(479, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:41'),
(480, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:43'),
(481, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:43'),
(482, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/disclaimer.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:44'),
(483, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/disclaimer.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:44'),
(484, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:45'),
(485, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:45'),
(486, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/privacy.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:49'),
(487, 'terms', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/privacy.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:49'),
(488, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/terms.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:19:50'),
(489, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:21:53'),
(490, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:21:54'),
(491, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:21:55'),
(492, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:21:55'),
(493, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:22:23'),
(494, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:22:23'),
(495, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:22:31'),
(496, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:22:31'),
(497, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:23:27'),
(498, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:23:27'),
(499, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:23:28'),
(500, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:23:28'),
(501, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:24:40'),
(502, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:24:40'),
(503, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:24:40'),
(504, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:24:40'),
(505, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:25:00'),
(506, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:25:00'),
(507, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:26:56'),
(508, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:26:56'),
(509, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:27:03'),
(510, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:27:03'),
(511, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:35:14'),
(512, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:35:14'),
(513, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:35:25'),
(514, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:35:25'),
(515, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:36:05'),
(516, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:36:05'),
(517, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:36:12'),
(518, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:36:12'),
(519, 'home', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:37:26'),
(520, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:37:26'),
(521, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:38:04'),
(522, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:38:04'),
(523, 'home', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:47:57'),
(524, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:47:57'),
(525, 'home', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:49:45'),
(526, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:49:45'),
(527, 'home', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:49:48'),
(528, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:49:48'),
(529, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:50:34'),
(530, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:50:34'),
(531, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:50:36'),
(532, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:50:36'),
(533, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:50:37'),
(534, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:50:37'),
(535, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:50:38'),
(536, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:50:38'),
(537, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:50:39'),
(538, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:50:39'),
(539, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:50:40'),
(540, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:50:40'),
(541, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:50:57'),
(542, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:50:57'),
(543, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:51:04'),
(544, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:51:04'),
(545, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:51:05'),
(546, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:51:05'),
(547, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:51:07'),
(548, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:51:07'),
(549, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:51:11'),
(550, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:51:11'),
(551, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:51:13'),
(552, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:51:13'),
(553, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:51:15'),
(554, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:51:15'),
(555, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:51:25'),
(556, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 08:51:25'),
(557, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:04:11'),
(558, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:04:11'),
(559, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:05:13'),
(560, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:05:13'),
(561, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:05:14'),
(562, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:05:14'),
(563, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:05:24'),
(564, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:05:24'),
(565, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:05:27'),
(566, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:05:27'),
(567, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:05:28'),
(568, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:05:28'),
(569, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:05:43'),
(570, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:05:43'),
(571, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:09:08'),
(572, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:09:08'),
(573, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:10:29'),
(574, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:10:29'),
(575, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:10:32'),
(576, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:10:32'),
(577, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:10:37'),
(578, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:10:37'),
(579, 'home', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:12:19'),
(580, 'index', NULL, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:12:19'),
(581, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:12:24'),
(582, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:12:24'),
(583, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:14:35'),
(584, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:14:35'),
(585, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:14:36'),
(586, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:14:36'),
(587, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:14:38'),
(588, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:14:38'),
(589, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:14:44'),
(590, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:14:44'),
(591, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:14:44'),
(592, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:15:08'),
(593, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:15:08'),
(594, 'account', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:15:14'),
(595, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:15:14'),
(596, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:15:51'),
(597, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:15:51'),
(598, 'cookies', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:15:56'),
(599, 'cookies', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:15:56'),
(600, 'cookies', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:16:43'),
(601, 'cookies', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'v2o6b35elu4mr609ab19p3oh72', '2025-09-08 09:16:43'),
(602, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:16:46'),
(603, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:16:46'),
(604, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:16:51'),
(605, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:16:51'),
(606, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:16:51'),
(607, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:17:15'),
(608, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:17:15'),
(609, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:17:35'),
(610, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:17:49'),
(611, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:17:49'),
(612, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:18:12'),
(613, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:20:27'),
(614, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:24:43'),
(615, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:27:25'),
(616, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:28:49'),
(617, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:29:42'),
(618, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:30:02'),
(619, 'account', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:32:39'),
(620, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:32:39'),
(621, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:33:51'),
(622, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:35:42'),
(623, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:50:10'),
(624, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:53:15'),
(625, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 09:53:18'),
(626, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 10:12:48'),
(627, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 10:14:54'),
(628, 'riskmanagement', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 10:14:58'),
(629, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 10:16:11'),
(630, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 10:22:20'),
(631, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 10:26:45'),
(632, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 10:40:07'),
(633, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 10:41:23');
INSERT INTO `page_views` (`id`, `page_slug`, `user_id`, `ip_address`, `user_agent`, `referrer`, `session_id`, `viewed_at`) VALUES
(634, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'er84jofoe3vjbp9r2h0f13g88t', '2025-09-08 10:41:24'),
(635, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/?v=1757328009265', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:41:30'),
(636, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/?v=1757328009265', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:41:30'),
(637, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:41:50'),
(638, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:41:51'),
(639, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:41:51'),
(640, 'account', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:41:56'),
(641, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:41:56'),
(642, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:42:40'),
(643, 'about', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:42:50'),
(644, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:42:50'),
(645, 'contact', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757328161804', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:42:56'),
(646, 'risk', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:43:05'),
(647, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:43:05'),
(648, 'privacy', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:43:08'),
(649, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:43:08'),
(650, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/privacy.php', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:43:11'),
(651, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/privacy.php', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:43:11'),
(652, 'privacy', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:44:33'),
(653, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:44:33'),
(654, 'risk', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:44:34'),
(655, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/contact.php', '5s1i2867pq53l67tjcdalttq3o', '2025-09-08 10:44:34'),
(656, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', '61e9g2ljaro7lrl81crba0ceb0', '2025-09-08 10:44:36'),
(657, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', '61e9g2ljaro7lrl81crba0ceb0', '2025-09-08 10:44:36'),
(658, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', '61e9g2ljaro7lrl81crba0ceb0', '2025-09-08 10:45:00'),
(659, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', '61e9g2ljaro7lrl81crba0ceb0', '2025-09-08 10:45:01'),
(660, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', '61e9g2ljaro7lrl81crba0ceb0', '2025-09-08 10:45:07'),
(661, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', '61e9g2ljaro7lrl81crba0ceb0', '2025-09-08 10:45:07'),
(662, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', '61e9g2ljaro7lrl81crba0ceb0', '2025-09-08 10:45:12'),
(663, 'home', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', '61e9g2ljaro7lrl81crba0ceb0', '2025-09-08 10:45:12'),
(664, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', '61e9g2ljaro7lrl81crba0ceb0', '2025-09-08 10:45:12'),
(665, 'risk', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', '61e9g2ljaro7lrl81crba0ceb0', '2025-09-08 10:45:24'),
(666, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', '61e9g2ljaro7lrl81crba0ceb0', '2025-09-08 10:45:24'),
(667, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 10:45:33'),
(668, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 10:45:33'),
(669, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 10:45:35'),
(670, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 10:45:35'),
(671, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 10:45:44'),
(672, 'home', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 10:45:44'),
(673, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 10:45:44'),
(674, 'cookies', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 10:45:58'),
(675, 'cookies', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 10:45:58'),
(676, 'tools', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 10:46:08'),
(677, 'tools', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 10:46:14'),
(678, 'tools', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 10:48:26'),
(679, 'tools', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/cookies.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 10:49:05'),
(680, 'home', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 10:58:21'),
(681, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 10:58:21'),
(682, 'tools', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 10:58:22'),
(683, 'tools', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:02:03'),
(684, 'tools', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:02:04'),
(685, 'tools', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:02:20'),
(686, 'tools', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:02:34'),
(687, 'tools', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:07:11'),
(688, 'tools', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:08:53'),
(689, 'tools', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:10:01'),
(690, 'home', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:14:16'),
(691, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:14:16'),
(692, 'about', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/?v=1757329801728', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:16:48'),
(693, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/?v=1757329801728', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:16:48'),
(694, 'tools', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:16:49'),
(695, 'disclaimer', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:18:57'),
(696, 'disclaimer', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:18:57'),
(697, 'risk', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/disclaimer.php?v=1757330209903', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:19:07'),
(698, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/disclaimer.php?v=1757330209903', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:19:07'),
(699, 'privacy', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:19:09'),
(700, 'privacy', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:19:09'),
(701, 'home', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/privacy.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:24:37'),
(702, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/privacy.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:24:37'),
(703, 'about', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:24:40'),
(704, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:24:40'),
(705, 'home', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:24:42'),
(706, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:24:42'),
(707, 'home', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:24:44'),
(708, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:24:44'),
(709, 'home', 2, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:24:46'),
(710, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'ps3b74t4h4gk3nj9c44v33vuah', '2025-09-08 11:24:46'),
(711, 'home', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 04:58:46'),
(712, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 04:58:46'),
(713, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 04:58:51'),
(714, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/index.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 04:58:51'),
(715, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 04:58:58'),
(716, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 04:58:58'),
(717, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 04:58:58'),
(718, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 04:59:29'),
(719, 'chat', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 04:59:32'),
(720, 'risk', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:00:04'),
(721, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:00:04'),
(722, 'risk', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:01:56'),
(723, 'risk', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:01:56'),
(724, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:01:57'),
(725, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/risk.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:01:57'),
(726, 'chat', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:01:58'),
(727, 'chat', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:03:47'),
(728, 'chat', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:07:41'),
(729, 'chat', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:07:46'),
(730, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/chat.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:07:47'),
(731, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/chat.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:07:47'),
(732, 'chat', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:07:49'),
(733, 'chat', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:11:04'),
(734, 'chat', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:11:49'),
(735, 'chat', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:11:51'),
(736, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/chat.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:14:35'),
(737, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/chat.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:50:01'),
(738, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/chat.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:53:57'),
(739, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/chat.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 05:53:59'),
(740, 'account', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 06:01:12'),
(741, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 06:01:13'),
(742, 'account', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 06:04:15'),
(743, 'account', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 06:04:15'),
(744, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 06:04:19'),
(745, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 06:09:01'),
(746, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/account.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 06:09:03'),
(747, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 06:09:04'),
(748, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 06:09:04'),
(749, 'chat', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/?v=1757398143259', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 06:09:36'),
(750, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/?v=1757398143259', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 06:09:44'),
(751, 'about', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:13:07'),
(752, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:13:07'),
(753, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:13:08'),
(754, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:31:31'),
(755, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:31:41'),
(756, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:31:41'),
(757, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:32:37'),
(758, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:32:38'),
(759, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:38:24'),
(760, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:39:43'),
(761, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:40:08'),
(762, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:40:37'),
(763, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:41:02'),
(764, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:41:15'),
(765, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:41:26'),
(766, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:41:28'),
(767, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:41:54'),
(768, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:44:56'),
(769, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:44:57'),
(770, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:45:00'),
(771, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:45:46'),
(772, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:45:47'),
(773, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:45:49'),
(774, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:45:53'),
(775, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:48:08'),
(776, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757398185190', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:48:43'),
(777, 'about', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:48:50'),
(778, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:48:50'),
(779, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:48:51'),
(780, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:49:44'),
(781, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:50:57'),
(782, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:51:33'),
(783, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:52:26'),
(784, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:52:31'),
(785, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:52:43'),
(786, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:55:22'),
(787, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:55:48'),
(788, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:55:48'),
(789, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:55:48'),
(790, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:58:36'),
(791, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:58:41'),
(792, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 07:58:59'),
(793, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 08:04:10'),
(794, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 08:05:12'),
(795, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 08:10:12'),
(796, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 08:13:24'),
(797, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 08:17:12'),
(798, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 08:17:53'),
(799, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 08:17:59'),
(800, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 08:19:29'),
(801, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 08:19:46'),
(802, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 08:20:15'),
(803, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 08:21:06'),
(804, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 09:17:27'),
(805, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php?v=1757404123937', 'o5lb0bndhov7kibekl5mc310nh', '2025-09-09 10:01:46'),
(806, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:02:04'),
(807, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:02:04'),
(808, 'login', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:02:11'),
(809, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:02:11'),
(810, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/login.php', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:02:11'),
(811, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:02:13'),
(812, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:03:59'),
(813, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:04:33'),
(814, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:05:07'),
(815, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:09:47'),
(816, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:09:51'),
(817, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:10:15'),
(818, 'home', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:10:56'),
(819, 'index', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/tools.php', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:10:56'),
(820, 'about', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/?v=1757412615485', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:10:59'),
(821, 'about', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/?v=1757412615485', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:10:59'),
(822, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:11:03'),
(823, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:11:33'),
(824, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:12:29'),
(825, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:14:50'),
(826, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:15:40'),
(827, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:15:46'),
(828, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:16:01'),
(829, 'tools', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 'http://localhost/TraderEscape/about.php', 'n66tfs1t9grrelch5glq1gl7ut', '2025-09-09 10:20:03');

-- --------------------------------------------------------

--
-- Table structure for table `portfolio_positions`
--

CREATE TABLE `portfolio_positions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `segment` enum('Equity Cash','Equity F&O','Currency','Commodity','Crypto') NOT NULL,
  `side` enum('Long','Short') NOT NULL,
  `quantity` int(11) NOT NULL,
  `average_price` decimal(10,2) NOT NULL,
  `current_price` decimal(10,2) NOT NULL,
  `market_value` decimal(15,2) NOT NULL,
  `unrealized_pnl` decimal(10,2) NOT NULL,
  `unrealized_pnl_percentage` decimal(5,2) NOT NULL,
  `risk_amount` decimal(10,2) NOT NULL,
  `stop_loss` decimal(10,2) DEFAULT NULL,
  `target_price` decimal(10,2) DEFAULT NULL,
  `position_date` date NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `privacy_policy_acceptance`
--

CREATE TABLE `privacy_policy_acceptance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `policy_version` varchar(50) NOT NULL,
  `accepted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `risk_alerts`
--

CREATE TABLE `risk_alerts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `alert_type` enum('daily_loss_limit','max_risk_limit','drawdown_limit','position_size_limit','correlation_risk') NOT NULL,
  `alert_message` text NOT NULL,
  `current_value` decimal(10,2) NOT NULL,
  `threshold_value` decimal(10,2) NOT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `is_acknowledged` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `acknowledged_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `risk_dashboard`
-- (See below for the actual view)
--
CREATE TABLE `risk_dashboard` (
`user_id` int(11)
,`username` varchar(50)
,`profile_id` int(11)
,`profile_name` varchar(100)
,`account_size` decimal(15,2)
,`risk_percentage` decimal(5,2)
,`max_daily_loss_percentage` decimal(5,2)
,`max_open_risk_percentage` decimal(5,2)
,`current_risk` decimal(32,2)
,`current_risk_percentage` decimal(38,2)
,`daily_loss` decimal(32,2)
,`daily_loss_percentage` decimal(38,2)
,`open_positions` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `risk_metrics`
--

CREATE TABLE `risk_metrics` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `calculation_date` date NOT NULL,
  `total_equity` decimal(15,2) NOT NULL,
  `total_risk` decimal(10,2) NOT NULL,
  `risk_percentage` decimal(5,2) NOT NULL,
  `max_drawdown` decimal(5,2) NOT NULL,
  `sharpe_ratio` decimal(5,2) DEFAULT NULL,
  `win_rate` decimal(5,2) NOT NULL,
  `avg_win` decimal(10,2) NOT NULL,
  `avg_loss` decimal(10,2) NOT NULL,
  `profit_factor` decimal(5,2) NOT NULL,
  `total_trades` int(11) NOT NULL,
  `winning_trades` int(11) NOT NULL,
  `losing_trades` int(11) NOT NULL,
  `largest_win` decimal(10,2) NOT NULL,
  `largest_loss` decimal(10,2) NOT NULL,
  `consecutive_wins` int(11) NOT NULL DEFAULT 0,
  `consecutive_losses` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `risk_metrics`
--

INSERT INTO `risk_metrics` (`id`, `user_id`, `profile_id`, `calculation_date`, `total_equity`, `total_risk`, `risk_percentage`, `max_drawdown`, `sharpe_ratio`, `win_rate`, `avg_win`, `avg_loss`, `profit_factor`, `total_trades`, `winning_trades`, `losing_trades`, `largest_win`, `largest_loss`, `consecutive_wins`, `consecutive_losses`, `created_at`) VALUES
(1, 1, 1, '2025-09-09', 1000000.00, 20000.00, 2.00, 5.50, 1.20, 65.00, 2500.00, -1200.00, 2.10, 25, 16, 9, 5000.00, -2500.00, 3, 2, '2025-09-09 10:23:17');

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'The Trader\'s Escape', 'string', 'Website name', 1, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(2, 'site_description', 'Empowering traders with comprehensive educational content and advanced tools for stock market success.', 'string', 'Website description', 1, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(3, 'site_url', 'https://thetradersescape.com', 'string', 'Website URL', 1, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(4, 'contact_email', 'contact@thetradersescape.com', 'string', 'Contact email address', 1, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(5, 'privacy_email', 'privacy@thetradersescape.com', 'string', 'Privacy policy contact email', 1, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(6, 'maintenance_mode', 'false', 'boolean', 'Maintenance mode status', 0, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(7, 'registration_enabled', 'true', 'boolean', 'User registration status', 0, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(8, 'social_login_enabled', 'true', 'boolean', 'Social login functionality status', 0, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(9, 'cookie_consent_required', 'true', 'boolean', 'Cookie consent requirement status', 0, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(10, 'max_login_attempts', '5', 'number', 'Maximum login attempts before lockout', 0, '2025-09-03 11:03:50', '2025-09-03 11:03:50'),
(11, 'session_timeout_minutes', '30', 'number', 'User session timeout in minutes', 0, '2025-09-03 11:03:50', '2025-09-03 11:03:50');

-- --------------------------------------------------------

--
-- Stand-in structure for view `tool_usage_stats`
-- (See below for the actual view)
--
CREATE TABLE `tool_usage_stats` (
`id` int(11)
,`name` varchar(100)
,`slug` varchar(100)
,`tool_type` enum('calculator','analyzer','simulator','chart','other')
,`total_usage_count` bigint(21)
,`unique_users` bigint(21)
,`avg_session_duration` decimal(14,4)
,`total_usage_time` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `trade_journal`
--

CREATE TABLE `trade_journal` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `trade_date` date NOT NULL,
  `symbol` varchar(20) NOT NULL,
  `segment` enum('Equity Cash','Equity F&O','Currency','Commodity','Crypto') NOT NULL,
  `side` enum('Long','Short') NOT NULL,
  `entry_price` decimal(10,2) NOT NULL,
  `stop_loss` decimal(10,2) NOT NULL,
  `target_price` decimal(10,2) DEFAULT NULL,
  `exit_price` decimal(10,2) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `position_size` decimal(15,2) NOT NULL,
  `risk_amount` decimal(10,2) NOT NULL,
  `r_multiple` decimal(5,2) DEFAULT NULL,
  `pnl` decimal(10,2) DEFAULT NULL,
  `fees` decimal(10,2) DEFAULT 0.00,
  `net_pnl` decimal(10,2) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `notes` text DEFAULT NULL,
  `trade_status` enum('open','closed','cancelled') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `trade_journal`
--
DELIMITER $$
CREATE TRIGGER `update_risk_metrics_on_trade_close` AFTER UPDATE ON `trade_journal` FOR EACH ROW BEGIN
    IF OLD.trade_status != 'closed' AND NEW.trade_status = 'closed' THEN
        CALL UpdateRiskMetrics(NEW.user_id, NEW.profile_id);
        CALL CheckRiskLimits(NEW.user_id, NEW.profile_id);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `trading_sessions`
--

CREATE TABLE `trading_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `session_start` time NOT NULL,
  `session_end` time DEFAULT NULL,
  `total_trades` int(11) NOT NULL DEFAULT 0,
  `winning_trades` int(11) NOT NULL DEFAULT 0,
  `losing_trades` int(11) NOT NULL DEFAULT 0,
  `total_pnl` decimal(10,2) NOT NULL DEFAULT 0.00,
  `max_drawdown_session` decimal(5,2) NOT NULL DEFAULT 0.00,
  `session_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trading_tools`
--

CREATE TABLE `trading_tools` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `tool_type` enum('calculator','analyzer','simulator','chart','other') DEFAULT 'other',
  `is_active` tinyint(1) DEFAULT 1,
  `requires_auth` tinyint(1) DEFAULT 0,
  `tool_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trading_tools`
--

INSERT INTO `trading_tools` (`id`, `name`, `slug`, `description`, `tool_type`, `is_active`, `requires_auth`, `tool_order`, `created_at`, `updated_at`) VALUES
(1, 'Position Size Calculator', 'position-size-calculator', 'Calculate optimal position size based on risk tolerance, account size, and market volatility.', 'calculator', 1, 0, 1, '2025-09-03 11:03:50', '2025-09-09 05:52:29'),
(2, 'Risk Reward Calculator', 'risk-reward-calculator', 'Analyze risk-to-reward ratios and calculate win rates needed for profitable trading.', 'calculator', 1, 0, 2, '2025-09-03 11:03:50', '2025-09-09 05:52:29'),
(3, 'Profit Loss Calculator', 'profit-loss-calculator', 'Calculate potential profits and losses with multiple exit strategies and scenarios.', 'calculator', 1, 0, 3, '2025-09-03 11:03:50', '2025-09-09 05:52:29'),
(4, 'Margin Calculator', 'margin-calculator', 'Determine margin requirements, leverage calculations, and capital efficiency metrics.', 'calculator', 1, 0, 4, '2025-09-03 11:03:50', '2025-09-09 05:52:29'),
(5, 'Portfolio Analyzer', 'portfolio-analyzer', 'Comprehensive portfolio analysis with risk metrics, correlation analysis, and performance tracking.', 'analyzer', 1, 1, 5, '2025-09-03 11:03:50', '2025-09-09 05:52:29'),
(6, 'Market Simulator', 'market-simulator', 'Practice trading with virtual money in a risk-free environment with real market data.', 'simulator', 1, 1, 6, '2025-09-03 11:03:50', '2025-09-09 05:52:29'),
(7, 'Chart Analysis Tool', 'chart-analysis-tool', 'Advanced charting and technical analysis tools with risk management overlays.', 'chart', 1, 1, 7, '2025-09-03 11:03:50', '2025-09-09 05:52:29'),
(8, 'Advanced Risk Management', 'advanced-risk-management', 'Comprehensive risk management suite with position sizing, trade journal, analytics, and risk budgeting.', 'analyzer', 1, 1, 8, '2025-09-09 05:52:29', '2025-09-09 05:52:29'),
(9, 'Volatility Calculator', 'volatility-calculator', 'Calculate historical and implied volatility for better risk assessment.', 'calculator', 1, 0, 9, '2025-09-09 05:52:29', '2025-09-09 05:52:29'),
(10, 'Correlation Analyzer', 'correlation-analyzer', 'Analyze correlation between assets to manage portfolio diversification risk.', 'analyzer', 1, 1, 10, '2025-09-09 05:52:29', '2025-09-09 05:52:29'),
(11, 'Drawdown Calculator', 'drawdown-calculator', 'Calculate maximum drawdown and recovery time for risk assessment.', 'calculator', 1, 0, 11, '2025-09-09 05:52:29', '2025-09-09 05:52:29'),
(12, 'Kelly Criterion Calculator', 'kelly-criterion-calculator', 'Calculate optimal position size using Kelly Criterion for maximum growth.', 'calculator', 1, 0, 12, '2025-09-09 05:52:29', '2025-09-09 05:52:29'),
(13, 'VaR Calculator', 'var-calculator', 'Calculate Value at Risk (VaR) for portfolio risk assessment.', 'calculator', 1, 1, 13, '2025-09-09 05:52:29', '2025-09-09 05:52:29'),
(14, 'Stress Test Simulator', 'stress-test-simulator', 'Simulate portfolio performance under various market stress scenarios.', 'simulator', 1, 1, 14, '2025-09-09 05:52:29', '2025-09-09 05:52:29'),
(15, 'Risk Budget Manager', 'risk-budget-manager', 'Allocate and manage risk budget across different trading strategies.', 'analyzer', 1, 1, 15, '2025-09-09 05:52:29', '2025-09-09 05:52:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `profile_avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_verified` tinyint(1) DEFAULT 0,
  `email_verification_token` varchar(255) DEFAULT NULL,
  `password_reset_token` varchar(255) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_provider` enum('local','google','facebook') DEFAULT 'local',
  `social_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `profile_avatar`, `is_active`, `is_verified`, `email_verification_token`, `password_reset_token`, `password_reset_expires`, `last_login`, `login_provider`, `social_id`, `created_at`, `updated_at`) VALUES
(1, 'Oga', 'classicmalsawma@gmail.com', '$2y$10$gvT5Zp/VPtEl37A3G/UBX.3ESffGngm/UeOfgaM/v9E/Cixk.UC3a', 'Malsawmdawngliana', NULL, 1, 0, NULL, NULL, NULL, '2025-09-09 10:02:11', 'local', NULL, '2025-09-04 10:04:47', '2025-09-09 10:02:11'),
(2, 'Rosie', 'rosie@gmail.com', '$2y$10$Q/p5KqpfVnDuB64UlSgXh.aa59MT3HUqKHRHwv8m5ucPOzO6WsgyO', 'Rose Ch', NULL, 1, 0, NULL, NULL, NULL, '2025-09-08 10:45:44', 'local', NULL, '2025-09-08 10:45:00', '2025-09-08 10:45:44');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` enum('login','logout','page_view','tool_usage','content_access','profile_update','other') DEFAULT 'other',
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_activity_log`
--

INSERT INTO `user_activity_log` (`id`, `user_id`, `activity_type`, `description`, `ip_address`, `user_agent`, `metadata`, `created_at`) VALUES
(1, 1, 'other', 'User registered successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, '2025-09-04 10:04:47'),
(2, 1, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, '2025-09-04 10:05:05'),
(3, 1, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, '2025-09-04 10:06:04'),
(4, 1, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, '2025-09-08 04:46:07'),
(5, 1, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, '2025-09-08 04:52:40'),
(6, 1, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, '2025-09-08 05:01:10'),
(7, 1, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', NULL, '2025-09-08 05:02:27'),
(8, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 05:46:52'),
(9, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 05:49:06'),
(10, 1, 'page_view', 'Viewed terms page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"terms\"}', '2025-09-08 05:49:13'),
(11, 1, 'page_view', 'Viewed risk page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"risk\"}', '2025-09-08 05:49:15'),
(12, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 05:49:36'),
(13, 1, 'page_view', 'Viewed account dashboard', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', '{\"page\":\"account\"}', '2025-09-08 05:49:57'),
(14, 1, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', '{\"action\":\"logout\"}', '2025-09-08 05:50:04'),
(15, 1, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', NULL, '2025-09-08 05:50:18'),
(16, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', '{\"page\":\"home\"}', '2025-09-08 05:50:18'),
(17, 1, 'page_view', 'Viewed account dashboard', '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.6 Mobile/15E148 Safari/604.1', '{\"page\":\"account\"}', '2025-09-08 05:51:03'),
(18, 1, 'page_view', 'Viewed account dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"account\"}', '2025-09-08 05:56:24'),
(19, 1, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"action\":\"logout\"}', '2025-09-08 06:01:33'),
(20, 1, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"action\":\"login\"}', '2025-09-08 08:18:42'),
(21, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 08:18:42'),
(22, 1, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"action\":\"logout\"}', '2025-09-08 08:18:47'),
(23, 1, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"action\":\"login\"}', '2025-09-08 09:14:44'),
(24, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 09:14:44'),
(25, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 09:15:08'),
(26, 1, 'page_view', 'Viewed account dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"account\"}', '2025-09-08 09:15:14'),
(27, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 09:15:51'),
(28, 1, 'page_view', 'Viewed cookies page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"cookies\"}', '2025-09-08 09:15:56'),
(29, 1, 'page_view', 'Viewed cookies page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"cookies\"}', '2025-09-08 09:16:43'),
(30, 1, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"action\":\"logout\"}', '2025-09-08 09:16:46'),
(31, 1, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"action\":\"login\"}', '2025-09-08 09:16:51'),
(32, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 09:16:51'),
(33, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 09:17:15'),
(34, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 09:17:49'),
(35, 1, 'other', 'Cookie consent updated', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"type\":\"accepted\",\"essential\":1,\"analytics\":1,\"marketing\":1,\"functional\":1,\"timestamp\":1757323071}', '2025-09-08 09:17:51'),
(36, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 09:18:12'),
(37, 1, 'tool_usage', 'Opened tool ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":2,\"action\":\"open\"}', '2025-09-08 09:18:22'),
(38, 1, 'tool_usage', 'Opened tool ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":3,\"action\":\"open\"}', '2025-09-08 09:18:31'),
(39, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 09:20:27'),
(40, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 09:24:43'),
(41, 1, 'tool_usage', 'Opened tool ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":1,\"action\":\"open\"}', '2025-09-08 09:27:22'),
(42, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 09:27:25'),
(43, 1, 'tool_usage', 'Opened tool ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":1,\"action\":\"open\"}', '2025-09-08 09:27:26'),
(44, 1, 'tool_usage', 'Accessed Position Size Calculator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool\":\"position-size-calculator\"}', '2025-09-08 09:27:26'),
(45, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 09:28:49'),
(46, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 09:29:42'),
(47, 1, 'tool_usage', 'Viewed details for tool ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":7,\"action\":\"view_details\"}', '2025-09-08 09:30:00'),
(48, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 09:30:02'),
(49, 1, 'tool_usage', 'Viewed details for tool ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":1,\"action\":\"view_details\"}', '2025-09-08 09:30:04'),
(50, 1, 'page_view', 'Viewed account dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"account\"}', '2025-09-08 09:32:39'),
(51, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 09:33:51'),
(52, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 09:35:42'),
(53, 1, 'tool_usage', 'Opened tool ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":7,\"action\":\"open\"}', '2025-09-08 09:35:45'),
(54, 1, 'tool_usage', 'Accessed Chart Analysis Tool', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool\":\"chart-analysis-tool\"}', '2025-09-08 09:35:45'),
(55, 1, 'tool_usage', 'Accessed Risk Reward Calculator', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool\":\"risk-reward-calculator\"}', '2025-09-08 09:46:45'),
(56, 1, 'tool_usage', 'Opened tool ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":2,\"action\":\"open\"}', '2025-09-08 09:46:46'),
(57, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 09:50:10'),
(58, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 09:53:15'),
(59, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 09:53:18'),
(60, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 10:12:48'),
(61, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 10:14:54'),
(62, 1, 'page_view', 'Viewed risk management tool', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"riskmanagement\"}', '2025-09-08 10:14:58'),
(63, 1, 'tool_usage', 'Opened tool ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":7,\"action\":\"open\"}', '2025-09-08 10:15:06'),
(64, 1, 'tool_usage', 'Accessed Chart Analysis Tool', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool\":\"chart-analysis-tool\"}', '2025-09-08 10:15:06'),
(65, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 10:16:11'),
(66, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 10:22:20'),
(67, 1, 'tool_usage', 'Opened tool ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":1,\"action\":\"open\"}', '2025-09-08 10:25:03'),
(68, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 10:26:45'),
(69, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 10:40:07'),
(70, 1, 'tool_usage', 'Opened tool ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":1,\"action\":\"open\"}', '2025-09-08 10:40:14'),
(71, 1, 'tool_usage', 'Opened tool ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":2,\"action\":\"open\"}', '2025-09-08 10:40:22'),
(72, 1, 'tool_usage', 'Opened tool ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":3,\"action\":\"open\"}', '2025-09-08 10:40:27'),
(73, 1, 'tool_usage', 'Opened tool ID: 4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":4,\"action\":\"open\"}', '2025-09-08 10:40:30'),
(74, 1, 'tool_usage', 'Opened tool ID: 5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":5,\"action\":\"open\"}', '2025-09-08 10:40:33'),
(75, 1, 'tool_usage', 'Opened tool ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":6,\"action\":\"open\"}', '2025-09-08 10:40:36'),
(76, 1, 'tool_usage', 'Opened tool ID: 7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":7,\"action\":\"open\"}', '2025-09-08 10:40:43'),
(77, 1, 'tool_usage', 'Opened tool ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":1,\"action\":\"open\"}', '2025-09-08 10:41:10'),
(78, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 10:41:23'),
(79, 1, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"action\":\"logout\"}', '2025-09-08 10:41:30'),
(80, 1, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"action\":\"login\"}', '2025-09-08 10:41:51'),
(81, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 10:41:51'),
(82, 1, 'page_view', 'Viewed account dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"account\"}', '2025-09-08 10:41:56'),
(83, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 10:42:40'),
(84, 1, 'page_view', 'Viewed about page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"about\"}', '2025-09-08 10:42:50'),
(85, 1, 'page_view', 'Viewed risk page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"risk\"}', '2025-09-08 10:43:05'),
(86, 1, 'page_view', 'Viewed privacy page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"privacy\"}', '2025-09-08 10:43:08'),
(87, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 10:43:11'),
(88, 1, 'page_view', 'Viewed privacy page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"privacy\"}', '2025-09-08 10:44:33'),
(89, 1, 'page_view', 'Viewed risk page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"risk\"}', '2025-09-08 10:44:34'),
(90, 1, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"action\":\"logout\"}', '2025-09-08 10:44:36'),
(91, 2, '', 'User registered successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"action\":\"register\"}', '2025-09-08 10:45:00'),
(92, 2, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"action\":\"login\"}', '2025-09-08 10:45:12'),
(93, 2, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 10:45:12'),
(94, 2, 'page_view', 'Viewed risk page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"risk\"}', '2025-09-08 10:45:24'),
(95, 2, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"action\":\"login\"}', '2025-09-08 10:45:44'),
(96, 2, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 10:45:44'),
(97, 2, 'other', 'Cookie consent updated', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"type\":\"accepted\",\"essential\":1,\"analytics\":1,\"marketing\":1,\"functional\":1,\"timestamp\":1757328355}', '2025-09-08 10:45:55'),
(98, 2, 'page_view', 'Viewed cookies page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"cookies\"}', '2025-09-08 10:45:58'),
(99, 2, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 10:46:08'),
(100, 2, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 10:46:14'),
(101, 2, 'tool_usage', 'Viewed details for tool ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":2,\"action\":\"view_details\"}', '2025-09-08 10:48:09'),
(102, 2, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 10:48:26'),
(103, 2, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 10:49:05'),
(104, 2, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 10:58:21'),
(105, 2, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 10:58:22'),
(106, 2, 'tool_usage', 'Opened tool ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":1,\"action\":\"open\"}', '2025-09-08 10:58:34'),
(107, 2, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 11:02:03'),
(108, 2, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 11:02:04'),
(109, 2, 'tool_usage', 'Viewed details for tool ID: 5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":5,\"action\":\"view_details\"}', '2025-09-08 11:02:12'),
(110, 2, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 11:02:20'),
(111, 2, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 11:02:34'),
(112, 2, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 11:07:11'),
(113, 2, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 11:08:53'),
(114, 2, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 11:10:01'),
(115, 2, 'tool_usage', 'Opened tool ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":1,\"action\":\"open\"}', '2025-09-08 11:10:29'),
(116, 2, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 11:14:16'),
(117, 2, 'page_view', 'Viewed about page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"about\"}', '2025-09-08 11:16:48'),
(118, 2, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-08 11:16:49'),
(119, 2, 'tool_usage', 'Opened tool ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":2,\"action\":\"open\"}', '2025-09-08 11:16:54'),
(120, 2, 'tool_usage', 'Opened tool ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":1,\"action\":\"open\"}', '2025-09-08 11:17:34'),
(121, 2, 'tool_usage', 'Opened tool ID: 3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":3,\"action\":\"open\"}', '2025-09-08 11:18:01'),
(122, 2, 'page_view', 'Viewed disclaimer page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"disclaimer\"}', '2025-09-08 11:18:57'),
(123, 2, 'page_view', 'Viewed risk page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"risk\"}', '2025-09-08 11:19:07'),
(124, 2, 'page_view', 'Viewed privacy page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"privacy\"}', '2025-09-08 11:19:09'),
(125, 2, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 11:24:37'),
(126, 2, 'page_view', 'Viewed about page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"about\"}', '2025-09-08 11:24:40'),
(127, 2, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 11:24:42'),
(128, 2, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 11:24:44'),
(129, 2, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-08 11:24:46'),
(130, 1, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"action\":\"login\"}', '2025-09-09 04:58:58'),
(131, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-09 04:58:58'),
(132, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 04:59:29'),
(133, 1, 'page_view', 'Viewed risk page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"risk\"}', '2025-09-09 05:00:04'),
(134, 1, 'page_view', 'Viewed risk page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"risk\"}', '2025-09-09 05:01:56'),
(135, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-09 05:01:57'),
(136, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-09 05:07:47'),
(137, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 05:14:35'),
(138, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 05:50:01'),
(139, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 05:53:57'),
(140, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 05:53:59'),
(141, 1, 'tool_usage', 'Opened tool ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":1,\"action\":\"open\"}', '2025-09-09 05:54:25'),
(142, 1, 'page_view', 'Viewed account dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"account\"}', '2025-09-09 06:01:12'),
(143, 1, 'page_view', 'Viewed account dashboard', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"account\"}', '2025-09-09 06:04:15'),
(144, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 06:04:19'),
(145, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 06:09:01'),
(146, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 06:09:03'),
(147, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-09 06:09:04'),
(148, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 06:09:44'),
(149, 1, 'tool_usage', 'Opened tool ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":1,\"action\":\"open\"}', '2025-09-09 06:09:48'),
(150, 1, 'page_view', 'Viewed about page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"about\"}', '2025-09-09 07:13:07'),
(151, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:13:08'),
(152, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:31:31'),
(153, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:31:41'),
(154, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:31:41'),
(155, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:32:37'),
(156, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:32:38'),
(157, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:38:24'),
(158, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:39:43'),
(159, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:40:08'),
(160, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:40:37'),
(161, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:41:02'),
(162, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:41:15'),
(163, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:41:26'),
(164, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:41:28'),
(165, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:41:54'),
(166, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:44:56'),
(167, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:44:57'),
(168, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:45:00'),
(169, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:45:46'),
(170, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:45:47'),
(171, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:45:49'),
(172, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:45:53'),
(173, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:48:08'),
(174, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:48:43'),
(175, 1, 'page_view', 'Viewed about page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"about\"}', '2025-09-09 07:48:50'),
(176, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:48:51'),
(177, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:49:44'),
(178, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:50:57'),
(179, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:51:33'),
(180, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:52:26'),
(181, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:52:31'),
(182, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:52:43'),
(183, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:55:22'),
(184, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:55:48'),
(185, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:55:48'),
(186, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:55:48'),
(187, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:58:36'),
(188, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:58:41'),
(189, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 07:58:59'),
(190, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 08:04:10'),
(191, 1, 'tool_usage', 'Viewed details for tool ID: 13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":13,\"action\":\"view_details\"}', '2025-09-09 08:04:22'),
(192, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 08:05:12'),
(193, 1, 'tool_usage', 'Viewed details for tool ID: 6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":6,\"action\":\"view_details\"}', '2025-09-09 08:05:15'),
(194, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 08:10:12'),
(195, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 08:13:24'),
(196, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 08:17:12'),
(197, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 08:17:53'),
(198, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 08:17:59'),
(199, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 08:19:29'),
(200, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 08:19:46'),
(201, 1, 'tool_usage', 'Opened tool ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":1,\"action\":\"open\"}', '2025-09-09 08:19:58'),
(202, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 08:20:15'),
(203, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 08:21:06'),
(204, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 09:17:27'),
(205, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 10:01:46'),
(206, 1, 'logout', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"action\":\"logout\"}', '2025-09-09 10:02:04'),
(207, 1, 'login', 'User logged in successfully', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"action\":\"login\"}', '2025-09-09 10:02:11'),
(208, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-09 10:02:11'),
(209, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 10:02:13'),
(210, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 10:03:59'),
(211, 1, 'tool_usage', 'Opened tool ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":1,\"action\":\"open\"}', '2025-09-09 10:04:11'),
(212, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 10:04:33'),
(213, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 10:05:07'),
(214, 1, 'tool_usage', 'Opened tool ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":2,\"action\":\"open\"}', '2025-09-09 10:05:32'),
(215, 1, 'tool_usage', 'Opened tool ID: 1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":1,\"action\":\"open\"}', '2025-09-09 10:05:46'),
(216, 1, 'tool_usage', 'Opened tool ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":2,\"action\":\"open\"}', '2025-09-09 10:06:02'),
(217, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 10:09:47'),
(218, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 10:09:51'),
(219, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 10:10:15'),
(220, 1, 'page_view', 'Viewed home page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"home\"}', '2025-09-09 10:10:56'),
(221, 1, 'page_view', 'Viewed about page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"about\"}', '2025-09-09 10:10:59'),
(222, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 10:11:03'),
(223, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 10:11:33'),
(224, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 10:12:29'),
(225, 1, 'tool_usage', 'Opened tool ID: 2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"tool_id\":2,\"action\":\"open\"}', '2025-09-09 10:13:04'),
(226, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 10:14:50'),
(227, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 10:15:40');
INSERT INTO `user_activity_log` (`id`, `user_id`, `activity_type`, `description`, `ip_address`, `user_agent`, `metadata`, `created_at`) VALUES
(228, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 10:15:46'),
(229, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 10:16:01'),
(230, 1, 'page_view', 'Viewed trading tools page', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '{\"page\":\"tools\"}', '2025-09-09 10:20:03');

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_dashboard_summary`
-- (See below for the actual view)
--
CREATE TABLE `user_dashboard_summary` (
`id` int(11)
,`username` varchar(50)
,`full_name` varchar(100)
,`email` varchar(100)
,`member_since` timestamp
,`last_login` datetime
,`tools_used` bigint(21)
,`content_accessed` bigint(21)
,`content_completed` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `user_learning_progress`
--

CREATE TABLE `user_learning_progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `progress_percentage` decimal(5,2) DEFAULT 0.00,
  `is_completed` tinyint(1) DEFAULT 0,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `last_accessed` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `preference_key` varchar(100) NOT NULL,
  `preference_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_tool_settings`
--

CREATE TABLE `user_tool_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tool_slug` varchar(100) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_tool_usage`
--

CREATE TABLE `user_tool_usage` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tool_id` int(11) NOT NULL,
  `session_start` datetime NOT NULL,
  `session_end` datetime DEFAULT NULL,
  `usage_duration` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_tool_usage`
--

INSERT INTO `user_tool_usage` (`id`, `user_id`, `tool_id`, `session_start`, `session_end`, `usage_duration`, `created_at`) VALUES
(1, 1, 2, '2025-09-08 09:18:22', NULL, NULL, '2025-09-08 09:18:22'),
(2, 1, 3, '2025-09-08 09:18:30', NULL, NULL, '2025-09-08 09:18:30'),
(3, 1, 1, '2025-09-08 09:27:22', NULL, NULL, '2025-09-08 09:27:22'),
(4, 1, 1, '2025-09-08 09:27:26', NULL, NULL, '2025-09-08 09:27:26'),
(5, 1, 7, '2025-09-08 09:35:45', NULL, NULL, '2025-09-08 09:35:45'),
(6, 1, 2, '2025-09-08 09:46:46', NULL, NULL, '2025-09-08 09:46:46'),
(7, 1, 7, '2025-09-08 10:15:06', NULL, NULL, '2025-09-08 10:15:06'),
(8, 1, 1, '2025-09-08 10:25:03', NULL, NULL, '2025-09-08 10:25:03'),
(9, 1, 1, '2025-09-08 10:40:14', NULL, NULL, '2025-09-08 10:40:14'),
(10, 1, 2, '2025-09-08 10:40:22', NULL, NULL, '2025-09-08 10:40:22'),
(11, 1, 3, '2025-09-08 10:40:27', NULL, NULL, '2025-09-08 10:40:27'),
(12, 1, 4, '2025-09-08 10:40:30', NULL, NULL, '2025-09-08 10:40:30'),
(13, 1, 5, '2025-09-08 10:40:33', NULL, NULL, '2025-09-08 10:40:33'),
(14, 1, 6, '2025-09-08 10:40:36', NULL, NULL, '2025-09-08 10:40:36'),
(15, 1, 7, '2025-09-08 10:40:43', NULL, NULL, '2025-09-08 10:40:43'),
(16, 1, 1, '2025-09-08 10:41:10', NULL, NULL, '2025-09-08 10:41:10'),
(17, 2, 1, '2025-09-08 10:58:34', NULL, NULL, '2025-09-08 10:58:34'),
(18, 2, 1, '2025-09-08 11:10:29', NULL, NULL, '2025-09-08 11:10:29'),
(19, 2, 2, '2025-09-08 11:16:53', NULL, NULL, '2025-09-08 11:16:53'),
(20, 2, 1, '2025-09-08 11:17:34', NULL, NULL, '2025-09-08 11:17:34'),
(21, 2, 3, '2025-09-08 11:18:01', NULL, NULL, '2025-09-08 11:18:01'),
(22, 1, 1, '2025-09-09 05:54:24', NULL, NULL, '2025-09-09 05:54:24'),
(23, 1, 1, '2025-09-09 06:09:48', NULL, NULL, '2025-09-09 06:09:48'),
(24, 1, 1, '2025-09-09 08:19:58', NULL, NULL, '2025-09-09 08:19:58'),
(25, 1, 1, '2025-09-09 10:04:11', NULL, NULL, '2025-09-09 10:04:11'),
(26, 1, 2, '2025-09-09 10:05:32', NULL, NULL, '2025-09-09 10:05:32'),
(27, 1, 1, '2025-09-09 10:05:46', NULL, NULL, '2025-09-09 10:05:46'),
(28, 1, 2, '2025-09-09 10:06:02', NULL, NULL, '2025-09-09 10:06:02'),
(29, 1, 2, '2025-09-09 10:13:04', NULL, NULL, '2025-09-09 10:13:04');

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_trading_performance`
-- (See below for the actual view)
--
CREATE TABLE `user_trading_performance` (
`user_id` int(11)
,`username` varchar(50)
,`profile_id` int(11)
,`profile_name` varchar(100)
,`total_trades` bigint(21)
,`winning_trades` decimal(22,0)
,`losing_trades` decimal(22,0)
,`win_rate` decimal(28,2)
,`total_pnl` decimal(32,2)
,`avg_win` decimal(14,6)
,`avg_loss` decimal(14,6)
,`profit_factor` decimal(39,2)
,`largest_win` decimal(10,2)
,`largest_loss` decimal(10,2)
);

-- --------------------------------------------------------

--
-- Table structure for table `user_trading_profiles`
--

CREATE TABLE `user_trading_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `profile_name` varchar(100) NOT NULL,
  `account_size` decimal(15,2) NOT NULL DEFAULT 0.00,
  `risk_percentage` decimal(5,2) NOT NULL DEFAULT 2.00,
  `max_daily_loss_percentage` decimal(5,2) NOT NULL DEFAULT 5.00,
  `max_open_risk_percentage` decimal(5,2) NOT NULL DEFAULT 10.00,
  `preferred_trading_style` enum('scalping','day_trading','swing_trading','position_trading') DEFAULT 'swing_trading',
  `risk_tolerance` enum('conservative','moderate','aggressive') DEFAULT 'moderate',
  `max_positions` int(11) NOT NULL DEFAULT 5,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_trading_profiles`
--

INSERT INTO `user_trading_profiles` (`id`, `user_id`, `profile_name`, `account_size`, `risk_percentage`, `max_daily_loss_percentage`, `max_open_risk_percentage`, `preferred_trading_style`, `risk_tolerance`, `max_positions`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 1, 'Default Profile', 100000.00, 2.00, 5.00, 10.00, 'swing_trading', 'moderate', 5, 1, '2025-09-09 05:52:29', '2025-09-09 05:52:29'),
(2, 2, 'Default Profile', 100000.00, 2.00, 5.00, 10.00, 'swing_trading', 'moderate', 5, 1, '2025-09-09 05:52:29', '2025-09-09 05:52:29');

-- --------------------------------------------------------

--
-- Structure for view `content_popularity`
--
DROP TABLE IF EXISTS `content_popularity`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `content_popularity`  AS SELECT `ec`.`id` AS `id`, `ec`.`title` AS `title`, `ec`.`slug` AS `slug`, `ec`.`content_type` AS `content_type`, `ec`.`difficulty_level` AS `difficulty_level`, `ec`.`view_count` AS `view_count`, count(`ulp`.`user_id`) AS `users_started`, sum(case when `ulp`.`is_completed` = 1 then 1 else 0 end) AS `users_completed`, round(sum(case when `ulp`.`is_completed` = 1 then 1 else 0 end) / nullif(count(`ulp`.`user_id`),0) * 100.0,2) AS `completion_rate` FROM (`educational_content` `ec` left join `user_learning_progress` `ulp` on(`ec`.`id` = `ulp`.`content_id`)) WHERE `ec`.`is_published` = 1 GROUP BY `ec`.`id`, `ec`.`title`, `ec`.`slug`, `ec`.`content_type`, `ec`.`difficulty_level`, `ec`.`view_count` ;

-- --------------------------------------------------------

--
-- Structure for view `risk_dashboard`
--
DROP TABLE IF EXISTS `risk_dashboard`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `risk_dashboard`  AS SELECT `u`.`id` AS `user_id`, `u`.`username` AS `username`, `utp`.`id` AS `profile_id`, `utp`.`profile_name` AS `profile_name`, `utp`.`account_size` AS `account_size`, `utp`.`risk_percentage` AS `risk_percentage`, `utp`.`max_daily_loss_percentage` AS `max_daily_loss_percentage`, `utp`.`max_open_risk_percentage` AS `max_open_risk_percentage`, coalesce(sum(case when `tj`.`trade_status` = 'open' then `tj`.`risk_amount` else 0 end),0) AS `current_risk`, round(coalesce(sum(case when `tj`.`trade_status` = 'open' then `tj`.`risk_amount` else 0 end),0) / `utp`.`account_size` * 100,2) AS `current_risk_percentage`, coalesce(sum(case when `tj`.`trade_date` = curdate() and `tj`.`pnl` < 0 then `tj`.`pnl` else 0 end),0) AS `daily_loss`, round(coalesce(sum(case when `tj`.`trade_date` = curdate() and `tj`.`pnl` < 0 then `tj`.`pnl` else 0 end),0) / `utp`.`account_size` * 100,2) AS `daily_loss_percentage`, count(case when `tj`.`trade_status` = 'open' then 1 end) AS `open_positions` FROM ((`users` `u` join `user_trading_profiles` `utp` on(`u`.`id` = `utp`.`user_id`)) left join `trade_journal` `tj` on(`utp`.`id` = `tj`.`profile_id`)) GROUP BY `u`.`id`, `u`.`username`, `utp`.`id`, `utp`.`profile_name`, `utp`.`account_size`, `utp`.`risk_percentage`, `utp`.`max_daily_loss_percentage`, `utp`.`max_open_risk_percentage` ;

-- --------------------------------------------------------

--
-- Structure for view `tool_usage_stats`
--
DROP TABLE IF EXISTS `tool_usage_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `tool_usage_stats`  AS SELECT `tt`.`id` AS `id`, `tt`.`name` AS `name`, `tt`.`slug` AS `slug`, `tt`.`tool_type` AS `tool_type`, count(`utu`.`id`) AS `total_usage_count`, count(distinct `utu`.`user_id`) AS `unique_users`, avg(`utu`.`usage_duration`) AS `avg_session_duration`, sum(`utu`.`usage_duration`) AS `total_usage_time` FROM (`trading_tools` `tt` left join `user_tool_usage` `utu` on(`tt`.`id` = `utu`.`tool_id`)) WHERE `tt`.`is_active` = 1 GROUP BY `tt`.`id`, `tt`.`name`, `tt`.`slug`, `tt`.`tool_type` ;

-- --------------------------------------------------------

--
-- Structure for view `user_dashboard_summary`
--
DROP TABLE IF EXISTS `user_dashboard_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_dashboard_summary`  AS SELECT `u`.`id` AS `id`, `u`.`username` AS `username`, `u`.`full_name` AS `full_name`, `u`.`email` AS `email`, `u`.`created_at` AS `member_since`, `u`.`last_login` AS `last_login`, count(distinct `utu`.`tool_id`) AS `tools_used`, count(distinct `ulp`.`content_id`) AS `content_accessed`, sum(case when `ulp`.`is_completed` = 1 then 1 else 0 end) AS `content_completed` FROM ((`users` `u` left join `user_tool_usage` `utu` on(`u`.`id` = `utu`.`user_id`)) left join `user_learning_progress` `ulp` on(`u`.`id` = `ulp`.`user_id`)) GROUP BY `u`.`id`, `u`.`username`, `u`.`full_name`, `u`.`email`, `u`.`created_at`, `u`.`last_login` ;

-- --------------------------------------------------------

--
-- Structure for view `user_trading_performance`
--
DROP TABLE IF EXISTS `user_trading_performance`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_trading_performance`  AS SELECT `u`.`id` AS `user_id`, `u`.`username` AS `username`, `utp`.`id` AS `profile_id`, `utp`.`profile_name` AS `profile_name`, count(`tj`.`id`) AS `total_trades`, sum(case when `tj`.`pnl` > 0 then 1 else 0 end) AS `winning_trades`, sum(case when `tj`.`pnl` < 0 then 1 else 0 end) AS `losing_trades`, round(sum(case when `tj`.`pnl` > 0 then 1 else 0 end) / count(`tj`.`id`) * 100,2) AS `win_rate`, sum(coalesce(`tj`.`pnl`,0)) AS `total_pnl`, avg(case when `tj`.`pnl` > 0 then `tj`.`pnl` else NULL end) AS `avg_win`, avg(case when `tj`.`pnl` < 0 then `tj`.`pnl` else NULL end) AS `avg_loss`, round(abs(avg(case when `tj`.`pnl` > 0 then `tj`.`pnl` else NULL end) * sum(case when `tj`.`pnl` > 0 then 1 else 0 end)) / abs(avg(case when `tj`.`pnl` < 0 then `tj`.`pnl` else NULL end) * sum(case when `tj`.`pnl` < 0 then 1 else 0 end)),2) AS `profit_factor`, max(`tj`.`pnl`) AS `largest_win`, min(`tj`.`pnl`) AS `largest_loss` FROM ((`users` `u` join `user_trading_profiles` `utp` on(`u`.`id` = `utp`.`user_id`)) left join `trade_journal` `tj` on(`utp`.`id` = `tj`.`profile_id`)) GROUP BY `u`.`id`, `u`.`username`, `utp`.`id`, `utp`.`profile_name` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cookie_consent`
--
ALTER TABLE `cookie_consent`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_session_id` (`session_id`),
  ADD KEY `idx_consent_type` (`consent_type`),
  ADD KEY `idx_cookie_consent_time` (`consent_given_at`);

--
-- Indexes for table `correlation_data`
--
ALTER TABLE `correlation_data`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_symbols_period` (`symbol1`,`symbol2`,`correlation_period`,`calculation_date`),
  ADD KEY `idx_symbol1` (`symbol1`),
  ADD KEY `idx_symbol2` (`symbol2`),
  ADD KEY `idx_calculation_date` (`calculation_date`),
  ADD KEY `idx_correlation_data_symbols` (`symbol1`,`symbol2`);

--
-- Indexes for table `educational_content`
--
ALTER TABLE `educational_content`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_content_type` (`content_type`),
  ADD KEY `idx_difficulty_level` (`difficulty_level`),
  ADD KEY `idx_is_published` (`is_published`),
  ADD KEY `idx_educational_content_difficulty` (`difficulty_level`),
  ADD KEY `idx_educational_content_author` (`author_id`);

--
-- Indexes for table `market_data`
--
ALTER TABLE `market_data`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_symbol_date` (`symbol`,`segment`,`date`),
  ADD KEY `idx_symbol_date` (`symbol`,`date`),
  ADD KEY `idx_segment` (`segment`),
  ADD KEY `idx_market_data_symbol_date` (`symbol`,`date`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_is_published` (`is_published`);

--
-- Indexes for table `page_content`
--
ALTER TABLE `page_content`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_page_version` (`page_id`,`version`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_page_content_page_id` (`page_id`);

--
-- Indexes for table `page_views`
--
ALTER TABLE `page_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_page_slug` (`page_slug`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_viewed_at` (`viewed_at`),
  ADD KEY `idx_page_views_session` (`session_id`);

--
-- Indexes for table `portfolio_positions`
--
ALTER TABLE `portfolio_positions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_symbol` (`user_id`,`symbol`,`segment`),
  ADD KEY `profile_id` (`profile_id`),
  ADD KEY `idx_user_profile` (`user_id`,`profile_id`),
  ADD KEY `idx_symbol` (`symbol`),
  ADD KEY `idx_portfolio_positions_user_symbol` (`user_id`,`symbol`);

--
-- Indexes for table `privacy_policy_acceptance`
--
ALTER TABLE `privacy_policy_acceptance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_policy_version` (`policy_version`),
  ADD KEY `idx_privacy_policy_acceptance_time` (`accepted_at`);

--
-- Indexes for table `risk_alerts`
--
ALTER TABLE `risk_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profile_id` (`profile_id`),
  ADD KEY `idx_user_alert` (`user_id`,`alert_type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_acknowledged` (`is_acknowledged`),
  ADD KEY `idx_risk_alerts_user_severity` (`user_id`,`severity`);

--
-- Indexes for table `risk_metrics`
--
ALTER TABLE `risk_metrics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_profile_date` (`user_id`,`profile_id`,`calculation_date`),
  ADD KEY `profile_id` (`profile_id`),
  ADD KEY `idx_user_date` (`user_id`,`calculation_date`),
  ADD KEY `idx_risk_metrics_user_date` (`user_id`,`calculation_date`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `trade_journal`
--
ALTER TABLE `trade_journal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profile_id` (`profile_id`),
  ADD KEY `idx_user_trade_date` (`user_id`,`trade_date`),
  ADD KEY `idx_symbol` (`symbol`),
  ADD KEY `idx_trade_status` (`trade_status`),
  ADD KEY `idx_pnl` (`pnl`),
  ADD KEY `idx_trade_journal_user_date_status` (`user_id`,`trade_date`,`trade_status`),
  ADD KEY `idx_trade_journal_symbol_date` (`symbol`,`trade_date`);

--
-- Indexes for table `trading_sessions`
--
ALTER TABLE `trading_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_session_date` (`user_id`,`session_date`),
  ADD KEY `idx_profile_id` (`profile_id`);

--
-- Indexes for table `trading_tools`
--
ALTER TABLE `trading_tools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_tool_type` (`tool_type`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `idx_trading_tools_order` (`tool_order`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_social_id` (`social_id`),
  ADD KEY `idx_users_created_at` (`created_at`),
  ADD KEY `idx_users_last_login` (`last_login`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_activity_type` (`activity_type`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_user_activity_log_type_time` (`activity_type`,`created_at`);

--
-- Indexes for table `user_learning_progress`
--
ALTER TABLE `user_learning_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_content` (`user_id`,`content_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_content_id` (`content_id`),
  ADD KEY `idx_user_learning_progress_completed` (`is_completed`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_preference` (`user_id`,`preference_key`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_preference_key` (`preference_key`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `user_tool_settings`
--
ALTER TABLE `user_tool_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_tool_setting` (`user_id`,`tool_slug`,`setting_key`),
  ADD KEY `idx_user_tool` (`user_id`,`tool_slug`);

--
-- Indexes for table `user_tool_usage`
--
ALTER TABLE `user_tool_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tool_id` (`tool_id`),
  ADD KEY `idx_user_tool` (`user_id`,`tool_id`),
  ADD KEY `idx_session_start` (`session_start`);

--
-- Indexes for table `user_trading_profiles`
--
ALTER TABLE `user_trading_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_default` (`is_default`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cookie_consent`
--
ALTER TABLE `cookie_consent`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `correlation_data`
--
ALTER TABLE `correlation_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `educational_content`
--
ALTER TABLE `educational_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `market_data`
--
ALTER TABLE `market_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `page_content`
--
ALTER TABLE `page_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `page_views`
--
ALTER TABLE `page_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=830;

--
-- AUTO_INCREMENT for table `portfolio_positions`
--
ALTER TABLE `portfolio_positions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `privacy_policy_acceptance`
--
ALTER TABLE `privacy_policy_acceptance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `risk_alerts`
--
ALTER TABLE `risk_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `risk_metrics`
--
ALTER TABLE `risk_metrics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `trade_journal`
--
ALTER TABLE `trade_journal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trading_sessions`
--
ALTER TABLE `trading_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trading_tools`
--
ALTER TABLE `trading_tools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=231;

--
-- AUTO_INCREMENT for table `user_learning_progress`
--
ALTER TABLE `user_learning_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_tool_settings`
--
ALTER TABLE `user_tool_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_tool_usage`
--
ALTER TABLE `user_tool_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `user_trading_profiles`
--
ALTER TABLE `user_trading_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cookie_consent`
--
ALTER TABLE `cookie_consent`
  ADD CONSTRAINT `cookie_consent_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `educational_content`
--
ALTER TABLE `educational_content`
  ADD CONSTRAINT `educational_content_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `page_content`
--
ALTER TABLE `page_content`
  ADD CONSTRAINT `page_content_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `page_content_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `page_views`
--
ALTER TABLE `page_views`
  ADD CONSTRAINT `page_views_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `portfolio_positions`
--
ALTER TABLE `portfolio_positions`
  ADD CONSTRAINT `portfolio_positions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `portfolio_positions_ibfk_2` FOREIGN KEY (`profile_id`) REFERENCES `user_trading_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `privacy_policy_acceptance`
--
ALTER TABLE `privacy_policy_acceptance`
  ADD CONSTRAINT `privacy_policy_acceptance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `risk_alerts`
--
ALTER TABLE `risk_alerts`
  ADD CONSTRAINT `risk_alerts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `risk_alerts_ibfk_2` FOREIGN KEY (`profile_id`) REFERENCES `user_trading_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `risk_metrics`
--
ALTER TABLE `risk_metrics`
  ADD CONSTRAINT `risk_metrics_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `risk_metrics_ibfk_2` FOREIGN KEY (`profile_id`) REFERENCES `user_trading_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trade_journal`
--
ALTER TABLE `trade_journal`
  ADD CONSTRAINT `trade_journal_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trade_journal_ibfk_2` FOREIGN KEY (`profile_id`) REFERENCES `user_trading_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trading_sessions`
--
ALTER TABLE `trading_sessions`
  ADD CONSTRAINT `trading_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trading_sessions_ibfk_2` FOREIGN KEY (`profile_id`) REFERENCES `user_trading_profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD CONSTRAINT `user_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_learning_progress`
--
ALTER TABLE `user_learning_progress`
  ADD CONSTRAINT `user_learning_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_learning_progress_ibfk_2` FOREIGN KEY (`content_id`) REFERENCES `educational_content` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_tool_settings`
--
ALTER TABLE `user_tool_settings`
  ADD CONSTRAINT `user_tool_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_tool_usage`
--
ALTER TABLE `user_tool_usage`
  ADD CONSTRAINT `user_tool_usage_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_tool_usage_ibfk_2` FOREIGN KEY (`tool_id`) REFERENCES `trading_tools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_trading_profiles`
--
ALTER TABLE `user_trading_profiles`
  ADD CONSTRAINT `user_trading_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
