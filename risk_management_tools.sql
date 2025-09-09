-- =====================================================
-- Comprehensive Risk Management Tools Database Schema
-- The Trader's Escape - Enhanced Trading Tools
-- =====================================================

-- Use the existing database
USE traderescape_db;

-- =====================================================
-- 1. ENHANCED TRADING TOOLS TABLE
-- =====================================================

-- Update existing trading tools with comprehensive risk management features
UPDATE trading_tools SET 
    description = 'Calculate optimal position size based on risk tolerance, account size, and market volatility.',
    tool_type = 'calculator',
    requires_auth = FALSE,
    tool_order = 1
WHERE slug = 'position-size-calculator';

UPDATE trading_tools SET 
    description = 'Analyze risk-to-reward ratios and calculate win rates needed for profitable trading.',
    tool_type = 'calculator',
    requires_auth = FALSE,
    tool_order = 2
WHERE slug = 'risk-reward-calculator';

UPDATE trading_tools SET 
    description = 'Calculate potential profits and losses with multiple exit strategies and scenarios.',
    tool_type = 'calculator',
    requires_auth = FALSE,
    tool_order = 3
WHERE slug = 'profit-loss-calculator';

UPDATE trading_tools SET 
    description = 'Determine margin requirements, leverage calculations, and capital efficiency metrics.',
    tool_type = 'calculator',
    requires_auth = FALSE,
    tool_order = 4
WHERE slug = 'margin-calculator';

UPDATE trading_tools SET 
    description = 'Comprehensive portfolio analysis with risk metrics, correlation analysis, and performance tracking.',
    tool_type = 'analyzer',
    requires_auth = TRUE,
    tool_order = 5
WHERE slug = 'portfolio-analyzer';

UPDATE trading_tools SET 
    description = 'Practice trading with virtual money in a risk-free environment with real market data.',
    tool_type = 'simulator',
    requires_auth = TRUE,
    tool_order = 6
WHERE slug = 'market-simulator';

UPDATE trading_tools SET 
    description = 'Advanced charting and technical analysis tools with risk management overlays.',
    tool_type = 'chart',
    requires_auth = TRUE,
    tool_order = 7
WHERE slug = 'chart-analysis-tool';

-- Add new comprehensive risk management tool
INSERT INTO trading_tools (name, slug, description, tool_type, requires_auth, tool_order) VALUES
('Advanced Risk Management', 'advanced-risk-management', 'Comprehensive risk management suite with position sizing, trade journal, analytics, and risk budgeting.', 'analyzer', TRUE, 8),
('Volatility Calculator', 'volatility-calculator', 'Calculate historical and implied volatility for better risk assessment.', 'calculator', FALSE, 9),
('Correlation Analyzer', 'correlation-analyzer', 'Analyze correlation between assets to manage portfolio diversification risk.', 'analyzer', TRUE, 10),
('Drawdown Calculator', 'drawdown-calculator', 'Calculate maximum drawdown and recovery time for risk assessment.', 'calculator', FALSE, 11),
('Kelly Criterion Calculator', 'kelly-criterion-calculator', 'Calculate optimal position size using Kelly Criterion for maximum growth.', 'calculator', FALSE, 12),
('VaR Calculator', 'var-calculator', 'Calculate Value at Risk (VaR) for portfolio risk assessment.', 'calculator', TRUE, 13),
('Stress Test Simulator', 'stress-test-simulator', 'Simulate portfolio performance under various market stress scenarios.', 'simulator', TRUE, 14),
('Risk Budget Manager', 'risk-budget-manager', 'Allocate and manage risk budget across different trading strategies.', 'analyzer', TRUE, 15);

-- =====================================================
-- 2. USER TRADING PROFILES TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS user_trading_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    profile_name VARCHAR(100) NOT NULL,
    account_size DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    risk_percentage DECIMAL(5,2) NOT NULL DEFAULT 2.00,
    max_daily_loss_percentage DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    max_open_risk_percentage DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    preferred_trading_style ENUM('scalping', 'day_trading', 'swing_trading', 'position_trading') DEFAULT 'swing_trading',
    risk_tolerance ENUM('conservative', 'moderate', 'aggressive') DEFAULT 'moderate',
    max_positions INT NOT NULL DEFAULT 5,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB;

-- =====================================================
-- 3. TRADE JOURNAL TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS trade_journal (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    profile_id INT NOT NULL,
    trade_date DATE NOT NULL,
    symbol VARCHAR(20) NOT NULL,
    segment ENUM('Equity Cash', 'Equity F&O', 'Currency', 'Commodity', 'Crypto') NOT NULL,
    side ENUM('Long', 'Short') NOT NULL,
    entry_price DECIMAL(10,2) NOT NULL,
    stop_loss DECIMAL(10,2) NOT NULL,
    target_price DECIMAL(10,2) DEFAULT NULL,
    exit_price DECIMAL(10,2) DEFAULT NULL,
    quantity INT NOT NULL,
    position_size DECIMAL(15,2) NOT NULL,
    risk_amount DECIMAL(10,2) NOT NULL,
    r_multiple DECIMAL(5,2) DEFAULT NULL,
    pnl DECIMAL(10,2) DEFAULT NULL,
    fees DECIMAL(10,2) DEFAULT 0.00,
    net_pnl DECIMAL(10,2) DEFAULT NULL,
    tags JSON DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    trade_status ENUM('open', 'closed', 'cancelled') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (profile_id) REFERENCES user_trading_profiles(id) ON DELETE CASCADE,
    INDEX idx_user_trade_date (user_id, trade_date),
    INDEX idx_symbol (symbol),
    INDEX idx_trade_status (trade_status),
    INDEX idx_pnl (pnl)
) ENGINE=InnoDB;

-- =====================================================
-- 4. RISK METRICS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS risk_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    profile_id INT NOT NULL,
    calculation_date DATE NOT NULL,
    total_equity DECIMAL(15,2) NOT NULL,
    total_risk DECIMAL(10,2) NOT NULL,
    risk_percentage DECIMAL(5,2) NOT NULL,
    max_drawdown DECIMAL(5,2) NOT NULL,
    sharpe_ratio DECIMAL(5,2) DEFAULT NULL,
    win_rate DECIMAL(5,2) NOT NULL,
    avg_win DECIMAL(10,2) NOT NULL,
    avg_loss DECIMAL(10,2) NOT NULL,
    profit_factor DECIMAL(5,2) NOT NULL,
    total_trades INT NOT NULL,
    winning_trades INT NOT NULL,
    losing_trades INT NOT NULL,
    largest_win DECIMAL(10,2) NOT NULL,
    largest_loss DECIMAL(10,2) NOT NULL,
    consecutive_wins INT NOT NULL DEFAULT 0,
    consecutive_losses INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (profile_id) REFERENCES user_trading_profiles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_profile_date (user_id, profile_id, calculation_date),
    INDEX idx_user_date (user_id, calculation_date)
) ENGINE=InnoDB;

-- =====================================================
-- 5. PORTFOLIO POSITIONS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS portfolio_positions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    profile_id INT NOT NULL,
    symbol VARCHAR(20) NOT NULL,
    segment ENUM('Equity Cash', 'Equity F&O', 'Currency', 'Commodity', 'Crypto') NOT NULL,
    side ENUM('Long', 'Short') NOT NULL,
    quantity INT NOT NULL,
    average_price DECIMAL(10,2) NOT NULL,
    current_price DECIMAL(10,2) NOT NULL,
    market_value DECIMAL(15,2) NOT NULL,
    unrealized_pnl DECIMAL(10,2) NOT NULL,
    unrealized_pnl_percentage DECIMAL(5,2) NOT NULL,
    risk_amount DECIMAL(10,2) NOT NULL,
    stop_loss DECIMAL(10,2) DEFAULT NULL,
    target_price DECIMAL(10,2) DEFAULT NULL,
    position_date DATE NOT NULL,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (profile_id) REFERENCES user_trading_profiles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_symbol (user_id, symbol, segment),
    INDEX idx_user_profile (user_id, profile_id),
    INDEX idx_symbol (symbol)
) ENGINE=InnoDB;

-- =====================================================
-- 6. RISK ALERTS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS risk_alerts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    profile_id INT NOT NULL,
    alert_type ENUM('daily_loss_limit', 'max_risk_limit', 'drawdown_limit', 'position_size_limit', 'correlation_risk') NOT NULL,
    alert_message TEXT NOT NULL,
    current_value DECIMAL(10,2) NOT NULL,
    threshold_value DECIMAL(10,2) NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    is_acknowledged BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (profile_id) REFERENCES user_trading_profiles(id) ON DELETE CASCADE,
    INDEX idx_user_alert (user_id, alert_type),
    INDEX idx_severity (severity),
    INDEX idx_acknowledged (is_acknowledged)
) ENGINE=InnoDB;

-- =====================================================
-- 7. MARKET DATA TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS market_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(20) NOT NULL,
    segment ENUM('Equity Cash', 'Equity F&O', 'Currency', 'Commodity', 'Crypto') NOT NULL,
    date DATE NOT NULL,
    open_price DECIMAL(10,2) NOT NULL,
    high_price DECIMAL(10,2) NOT NULL,
    low_price DECIMAL(10,2) NOT NULL,
    close_price DECIMAL(10,2) NOT NULL,
    volume BIGINT NOT NULL DEFAULT 0,
    volatility DECIMAL(5,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_symbol_date (symbol, segment, date),
    INDEX idx_symbol_date (symbol, date),
    INDEX idx_segment (segment)
) ENGINE=InnoDB;

-- =====================================================
-- 8. CORRELATION DATA TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS correlation_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    symbol1 VARCHAR(20) NOT NULL,
    symbol2 VARCHAR(20) NOT NULL,
    correlation_period ENUM('1D', '1W', '1M', '3M', '6M', '1Y') NOT NULL,
    correlation_value DECIMAL(5,4) NOT NULL,
    calculation_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_symbols_period (symbol1, symbol2, correlation_period, calculation_date),
    INDEX idx_symbol1 (symbol1),
    INDEX idx_symbol2 (symbol2),
    INDEX idx_calculation_date (calculation_date)
) ENGINE=InnoDB;

-- =====================================================
-- 9. USER TOOL SETTINGS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS user_tool_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    tool_slug VARCHAR(100) NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_tool_setting (user_id, tool_slug, setting_key),
    INDEX idx_user_tool (user_id, tool_slug)
) ENGINE=InnoDB;

-- =====================================================
-- 10. TRADING SESSIONS TABLE
-- =====================================================

CREATE TABLE IF NOT EXISTS trading_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    profile_id INT NOT NULL,
    session_date DATE NOT NULL,
    session_start TIME NOT NULL,
    session_end TIME DEFAULT NULL,
    total_trades INT NOT NULL DEFAULT 0,
    winning_trades INT NOT NULL DEFAULT 0,
    losing_trades INT NOT NULL DEFAULT 0,
    total_pnl DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    max_drawdown_session DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    session_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (profile_id) REFERENCES user_trading_profiles(id) ON DELETE CASCADE,
    INDEX idx_user_session_date (user_id, session_date),
    INDEX idx_profile_id (profile_id)
) ENGINE=InnoDB;

-- =====================================================
-- 11. STORED PROCEDURES FOR RISK MANAGEMENT
-- =====================================================

DELIMITER //

-- Procedure to calculate position size
DROP PROCEDURE IF EXISTS CalculatePositionSize //
CREATE PROCEDURE CalculatePositionSize(
    IN user_id_param INT,
    IN profile_id_param INT,
    IN symbol_param VARCHAR(20),
    IN entry_price_param DECIMAL(10,2),
    IN stop_loss_param DECIMAL(10,2),
    IN risk_percentage_param DECIMAL(5,2)
)
BEGIN
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
END //

-- Procedure to update risk metrics
DROP PROCEDURE IF EXISTS UpdateRiskMetrics //
CREATE PROCEDURE UpdateRiskMetrics(IN user_id_param INT, IN profile_id_param INT)
BEGIN
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
END //

-- Procedure to check risk limits
DROP PROCEDURE IF EXISTS CheckRiskLimits //
CREATE PROCEDURE CheckRiskLimits(IN user_id_param INT, IN profile_id_param INT)
BEGIN
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
END //

DELIMITER ;

-- =====================================================
-- 12. VIEWS FOR ANALYTICS
-- =====================================================

-- View for user trading performance
DROP VIEW IF EXISTS user_trading_performance;
CREATE VIEW user_trading_performance AS
SELECT 
    u.id as user_id,
    u.username,
    utp.id as profile_id,
    utp.profile_name,
    COUNT(tj.id) as total_trades,
    SUM(CASE WHEN tj.pnl > 0 THEN 1 ELSE 0 END) as winning_trades,
    SUM(CASE WHEN tj.pnl < 0 THEN 1 ELSE 0 END) as losing_trades,
    ROUND((SUM(CASE WHEN tj.pnl > 0 THEN 1 ELSE 0 END) / COUNT(tj.id)) * 100, 2) as win_rate,
    SUM(COALESCE(tj.pnl, 0)) as total_pnl,
    AVG(CASE WHEN tj.pnl > 0 THEN tj.pnl ELSE NULL END) as avg_win,
    AVG(CASE WHEN tj.pnl < 0 THEN tj.pnl ELSE NULL END) as avg_loss,
    ROUND(ABS(AVG(CASE WHEN tj.pnl > 0 THEN tj.pnl ELSE NULL END) * SUM(CASE WHEN tj.pnl > 0 THEN 1 ELSE 0 END)) / 
          ABS(AVG(CASE WHEN tj.pnl < 0 THEN tj.pnl ELSE NULL END) * SUM(CASE WHEN tj.pnl < 0 THEN 1 ELSE 0 END)), 2) as profit_factor,
    MAX(tj.pnl) as largest_win,
    MIN(tj.pnl) as largest_loss
FROM users u
JOIN user_trading_profiles utp ON u.id = utp.user_id
LEFT JOIN trade_journal tj ON utp.id = tj.profile_id
GROUP BY u.id, u.username, utp.id, utp.profile_name;

-- View for risk dashboard
DROP VIEW IF EXISTS risk_dashboard;
CREATE VIEW risk_dashboard AS
SELECT 
    u.id as user_id,
    u.username,
    utp.id as profile_id,
    utp.profile_name,
    utp.account_size,
    utp.risk_percentage,
    utp.max_daily_loss_percentage,
    utp.max_open_risk_percentage,
    COALESCE(SUM(CASE WHEN tj.trade_status = 'open' THEN tj.risk_amount ELSE 0 END), 0) as current_risk,
    ROUND((COALESCE(SUM(CASE WHEN tj.trade_status = 'open' THEN tj.risk_amount ELSE 0 END), 0) / utp.account_size) * 100, 2) as current_risk_percentage,
    COALESCE(SUM(CASE WHEN tj.trade_date = CURDATE() AND tj.pnl < 0 THEN tj.pnl ELSE 0 END), 0) as daily_loss,
    ROUND((COALESCE(SUM(CASE WHEN tj.trade_date = CURDATE() AND tj.pnl < 0 THEN tj.pnl ELSE 0 END), 0) / utp.account_size) * 100, 2) as daily_loss_percentage,
    COUNT(CASE WHEN tj.trade_status = 'open' THEN 1 END) as open_positions
FROM users u
JOIN user_trading_profiles utp ON u.id = utp.user_id
LEFT JOIN trade_journal tj ON utp.id = tj.profile_id
GROUP BY u.id, u.username, utp.id, utp.profile_name, utp.account_size, utp.risk_percentage, utp.max_daily_loss_percentage, utp.max_open_risk_percentage;

-- =====================================================
-- 13. SAMPLE DATA FOR TESTING
-- =====================================================

-- Insert sample trading profile for existing users (if any)
INSERT IGNORE INTO user_trading_profiles (user_id, profile_name, account_size, risk_percentage, max_daily_loss_percentage, max_open_risk_percentage, preferred_trading_style, risk_tolerance, max_positions, is_default)
SELECT 
    id,
    'Default Profile',
    100000.00,
    2.00,
    5.00,
    10.00,
    'swing_trading',
    'moderate',
    5,
    TRUE
FROM users 
WHERE id NOT IN (SELECT user_id FROM user_trading_profiles);

-- =====================================================
-- 14. INDEXES FOR PERFORMANCE
-- =====================================================

-- Additional indexes for better performance
CREATE INDEX idx_trade_journal_user_date_status ON trade_journal(user_id, trade_date, trade_status);
CREATE INDEX idx_trade_journal_symbol_date ON trade_journal(symbol, trade_date);
CREATE INDEX idx_risk_metrics_user_date ON risk_metrics(user_id, calculation_date);
CREATE INDEX idx_portfolio_positions_user_symbol ON portfolio_positions(user_id, symbol);
CREATE INDEX idx_risk_alerts_user_severity ON risk_alerts(user_id, severity);
CREATE INDEX idx_market_data_symbol_date ON market_data(symbol, date);
CREATE INDEX idx_correlation_data_symbols ON correlation_data(symbol1, symbol2);

-- =====================================================
-- 15. TRIGGERS FOR AUTOMATIC UPDATES
-- =====================================================

-- Trigger to update risk metrics when trade is closed
DELIMITER //
DROP TRIGGER IF EXISTS update_risk_metrics_on_trade_close //
CREATE TRIGGER update_risk_metrics_on_trade_close
AFTER UPDATE ON trade_journal
FOR EACH ROW
BEGIN
    IF OLD.trade_status != 'closed' AND NEW.trade_status = 'closed' THEN
        CALL UpdateRiskMetrics(NEW.user_id, NEW.profile_id);
        CALL CheckRiskLimits(NEW.user_id, NEW.profile_id);
    END IF;
END //
DELIMITER ;

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================

SELECT 'Risk Management Tools Database Schema Created Successfully!' as Status,
       'All tables, procedures, views, and triggers have been created.' as Message,
       NOW() as Created_At;
