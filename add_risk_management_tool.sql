-- Add Risk Management Tool to the trading_tools table
INSERT INTO trading_tools (name, slug, description, tool_type, requires_auth, tool_order) VALUES
('Advanced Risk Management', 'risk-management', 'Comprehensive risk management tool with position sizing, trade journal, and analytics.', 'analyzer', TRUE, 8);

