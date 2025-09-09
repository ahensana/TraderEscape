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
?>
