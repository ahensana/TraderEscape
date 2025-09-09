<?php
/**
 * Database Functions for TraderEscape
 * Common database operations used across all pages
 */

// Include database configuration
require_once __DIR__ . '/../config/database.php';

/**
 * Get page data from database
 */
function getPageData($slug) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE slug = ? AND is_published = TRUE");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error getting page data for slug '$slug': " . $e->getMessage());
        return false;
    }
}

/**
 * Get all published pages
 */
function getAllPublishedPages() {
    try {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT * FROM pages WHERE is_published = TRUE ORDER BY slug");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting all published pages: " . $e->getMessage());
        return [];
    }
}

/**
 * Get trading tools
 */
function getTradingTools($requireAuth = null) {
    try {
        $pdo = getDB();
        $sql = "SELECT * FROM trading_tools WHERE is_active = TRUE";
        
        if ($requireAuth !== null) {
            $sql .= " AND requires_auth = " . ($requireAuth ? 'TRUE' : 'FALSE');
        }
        
        $sql .= " ORDER BY tool_order, name";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting trading tools: " . $e->getMessage());
        return [];
    }
}

/**
 * Get site settings
 */
function getSiteSettings($publicOnly = true) {
    try {
        $pdo = getDB();
        $sql = "SELECT * FROM site_settings";
        
        if ($publicOnly) {
            $sql .= " WHERE is_public = TRUE";
        }
        
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll();
        
        // Convert to associative array for easy access
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    } catch (Exception $e) {
        error_log("Error getting site settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Get a specific site setting
 */
function getSiteSetting($key, $default = null) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        error_log("Error getting site setting '$key': " . $e->getMessage());
        return $default;
    }
}

/**
 * Track page view
 */
function trackPageView($pageSlug, $userId = null, $ipAddress = null, $userAgent = null, $referrer = null, $sessionId = null) {
    try {
        $pdo = getDB();
        
        // Use stored procedure if available
        $stmt = $pdo->prepare("CALL TrackPageView(?, ?, ?, ?, ?, ?)");
        $stmt->execute([$pageSlug, $userId, $ipAddress, $userAgent, $referrer, $sessionId]);
        
        return true;
    } catch (Exception $e) {
        // Fallback to direct insert if stored procedure fails
        try {
            $stmt = $pdo->prepare("INSERT INTO page_views (page_slug, user_id, ip_address, user_agent, referrer, session_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$pageSlug, $userId, $ipAddress, $userAgent, $referrer, $sessionId]);
            
            // Update educational content view count if applicable
            $stmt = $pdo->prepare("UPDATE educational_content SET view_count = view_count + 1 WHERE slug = ?");
            $stmt->execute([$pageSlug]);
            
            return true;
        } catch (Exception $fallbackError) {
            error_log("Error tracking page view: " . $fallbackError->getMessage());
            return false;
        }
    }
}

/**
 * Log user activity
 */
function logUserActivity($userId, $activityType, $description = '', $ipAddress = null, $userAgent = null, $metadata = null) {
    try {
        $pdo = getDB();
        
        // Use stored procedure if available
        $stmt = $pdo->prepare("CALL LogUserActivity(?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $activityType, $description, $ipAddress, $userAgent, $metadata]);
        
        return true;
    } catch (Exception $e) {
        // Fallback to direct insert if stored procedure fails
        try {
            $stmt = $pdo->prepare("INSERT INTO user_activity_log (user_id, activity_type, description, ip_address, user_agent, metadata) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $activityType, $description, $ipAddress, $userAgent, $metadata]);
            return true;
        } catch (Exception $fallbackError) {
            error_log("Error logging user activity: " . $fallbackError->getMessage());
            return false;
        }
    }
}

/**
 * Get educational content
 */
function getEducationalContent($type = null, $difficulty = null, $limit = null) {
    try {
        $pdo = getDB();
        $sql = "SELECT * FROM educational_content WHERE is_published = TRUE";
        $params = [];
        
        if ($type) {
            $sql .= " AND content_type = ?";
            $params[] = $type;
        }
        
        if ($difficulty) {
            $sql .= " AND difficulty_level = ?";
            $params[] = $difficulty;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting educational content: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user statistics
 */
function getUserStats($userId) {
    try {
        $pdo = getDB();
        
        // Use stored procedure if available
        $stmt = $pdo->prepare("CALL GetUserStats(?)");
        $stmt->execute([$userId]);
        return $stmt->fetch();
        
    } catch (Exception $e) {
        // Fallback to direct query if stored procedure fails
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    u.username,
                    u.full_name,
                    u.email,
                    u.created_at as member_since,
                    u.last_login,
                    COUNT(DISTINCT utu.tool_id) as tools_used,
                    COUNT(DISTINCT ulp.content_id) as content_accessed,
                    SUM(CASE WHEN ulp.is_completed = TRUE THEN 1 ELSE 0 END) as content_completed,
                    SUM(utu.usage_duration) as total_tool_usage_time
                FROM users u
                LEFT JOIN user_tool_usage utu ON u.id = utu.user_id
                LEFT JOIN user_learning_progress ulp ON u.id = ulp.user_id
                WHERE u.id = ?
                GROUP BY u.username, u.full_name, u.email, u.created_at, u.last_login
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch();
            
        } catch (Exception $fallbackError) {
            error_log("Error getting user stats: " . $fallbackError->getMessage());
            return false;
        }
    }
}

/**
 * Check if database is available
 */
function isDatabaseAvailable() {
    return isDatabaseConnected();
}

/**
 * Get database status information
 */
function getDatabaseStatus() {
    try {
        $pdo = getDB();
        
        $status = [
            'connected' => true,
            'tables' => 0,
            'pages' => 0,
            'tools' => 0,
            'settings' => 0
        ];
        
        // Count tables
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
        $result = $stmt->fetch();
        $status['tables'] = $result['count'];
        
        // Count pages
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM pages");
        $result = $stmt->fetch();
        $status['pages'] = $result['count'];
        
        // Count tools
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM trading_tools");
        $result = $stmt->fetch();
        $status['tools'] = $result['count'];
        
        // Count settings
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM site_settings");
        $result = $stmt->fetch();
        $status['settings'] = $result['count'];
        
        return $status;
        
    } catch (Exception $e) {
        return [
            'connected' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Check if user has given cookie consent
 */
function hasUserGivenCookieConsent($sessionId = null) {
    try {
        $pdo = getDB();
        $sessionId = $sessionId ?: session_id();
        
        $stmt = $pdo->prepare("SELECT consent_type FROM cookie_consent WHERE session_id = ? AND consent_given_at > DATE_SUB(NOW(), INTERVAL 1 YEAR)");
        $stmt->execute([$sessionId]);
        $consent = $stmt->fetch();
        
        return $consent && $consent['consent_type'] !== 'declined';
    } catch (Exception $e) {
        error_log("Error checking cookie consent: " . $e->getMessage());
        return false;
    }
}

/**
 * Get cookie consent details
 */
function getCookieConsentDetails($sessionId = null) {
    try {
        $pdo = getDB();
        $sessionId = $sessionId ?: session_id();
        
        $stmt = $pdo->prepare("SELECT * FROM cookie_consent WHERE session_id = ? ORDER BY consent_given_at DESC LIMIT 1");
        $stmt->execute([$sessionId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error getting cookie consent details: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user trading profiles
 */
function getTradingProfiles($userId) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM user_trading_profiles WHERE user_id = ? ORDER BY is_default DESC, created_at ASC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting trading profiles for user $userId: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user risk metrics
 */
function getRiskMetrics($userId) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT rm.*, utp.profile_name 
            FROM risk_metrics rm 
            JOIN user_trading_profiles utp ON rm.profile_id = utp.id 
            WHERE rm.user_id = ? 
            ORDER BY rm.calculation_date DESC 
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error getting risk metrics for user $userId: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user portfolio positions
 */
function getPortfolioPositions($userId) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT pp.*, utp.profile_name 
            FROM portfolio_positions pp 
            JOIN user_trading_profiles utp ON pp.profile_id = utp.id 
            WHERE pp.user_id = ? 
            ORDER BY pp.last_updated DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting portfolio positions for user $userId: " . $e->getMessage());
        return [];
    }
}

/**
 * Get recent trades
 */
function getRecentTrades($userId, $limit = 10) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT tj.*, utp.profile_name 
            FROM trade_journal tj 
            JOIN user_trading_profiles utp ON tj.profile_id = utp.id 
            WHERE tj.user_id = ? 
            ORDER BY tj.trade_date DESC, tj.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting recent trades for user $userId: " . $e->getMessage());
        return [];
    }
}

/**
 * Create or update trading profile
 */
function saveTradingProfile($userId, $profileData) {
    try {
        $pdo = getDB();
        
        if (isset($profileData['id']) && $profileData['id']) {
            // Update existing profile
            $stmt = $pdo->prepare("
                UPDATE user_trading_profiles 
                SET profile_name = ?, account_size = ?, risk_percentage = ?, 
                    max_daily_loss_percentage = ?, max_open_risk_percentage = ?, 
                    preferred_trading_style = ?, risk_tolerance = ?, max_positions = ?
                WHERE id = ? AND user_id = ?
            ");
            $result = $stmt->execute([
                $profileData['profile_name'],
                $profileData['account_size'],
                $profileData['risk_percentage'],
                $profileData['max_daily_loss_percentage'],
                $profileData['max_open_risk_percentage'],
                $profileData['preferred_trading_style'],
                $profileData['risk_tolerance'],
                $profileData['max_positions'],
                $profileData['id'],
                $userId
            ]);
            return $result ? $profileData['id'] : false;
        } else {
            // Create new profile
            $stmt = $pdo->prepare("
                INSERT INTO user_trading_profiles 
                (user_id, profile_name, account_size, risk_percentage, max_daily_loss_percentage, 
                 max_open_risk_percentage, preferred_trading_style, risk_tolerance, max_positions, is_default)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([
                $userId,
                $profileData['profile_name'],
                $profileData['account_size'],
                $profileData['risk_percentage'],
                $profileData['max_daily_loss_percentage'],
                $profileData['max_open_risk_percentage'],
                $profileData['preferred_trading_style'],
                $profileData['risk_tolerance'],
                $profileData['max_positions'],
                $profileData['is_default'] ?? false
            ]);
            return $result ? $pdo->lastInsertId() : false;
        }
    } catch (Exception $e) {
        error_log("Error saving trading profile for user $userId: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user risk alerts
 */
function getRiskAlerts($userId, $limit = 10) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT ra.*, utp.profile_name 
            FROM risk_alerts ra 
            JOIN user_trading_profiles utp ON ra.profile_id = utp.id 
            WHERE ra.user_id = ? AND ra.is_acknowledged = FALSE 
            ORDER BY ra.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting risk alerts for user $userId: " . $e->getMessage());
        return [];
    }
}

/**
 * Acknowledge risk alert
 */
function acknowledgeRiskAlert($userId, $alertId) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            UPDATE risk_alerts 
            SET is_acknowledged = TRUE, acknowledged_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$alertId, $userId]);
    } catch (Exception $e) {
        error_log("Error acknowledging risk alert $alertId for user $userId: " . $e->getMessage());
        return false;
    }
}
?>
