<?php
/**
 * Authentication Functions for TraderEscape
 * Provides functions for user authentication and session management
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Check if user is currently logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require user to be authenticated, redirect to login if not
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: ./login.php');
        exit();
    }
}

/**
 * Logout the current user
 */
function logoutUser() {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Get current logged in user data
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, username, email, full_name, is_active FROM users WHERE id = ? AND is_active = TRUE");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return null;
    }
}

/**
 * Log user activity
 * @param int $user_id
 * @param string $activity_type
 * @param string $description
 * @param string $ip_address
 * @param string $user_agent
 * @return bool
 */
function logUserActivity($user_id, $activity_type, $description, $ip_address = null, $user_agent = null) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            INSERT INTO user_activity_log (user_id, activity_type, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$user_id, $activity_type, $description, $ip_address, $user_agent]);
    } catch (Exception $e) {
        error_log("Error logging user activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user dashboard data
 * @param int $user_id
 * @return array
 */
function getUserDashboardData($user_id) {
    try {
        $pdo = getDB();
        
        // Get basic user info
        $userStmt = $pdo->prepare("SELECT username, email, full_name, created_at, last_login FROM users WHERE id = ?");
        $userStmt->execute([$user_id]);
        $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get tool usage count
        $toolStmt = $pdo->prepare("SELECT COUNT(DISTINCT tool_id) as tools_used FROM user_tool_usage WHERE user_id = ?");
        $toolStmt->execute([$user_id]);
        $toolData = $toolStmt->fetch(PDO::FETCH_ASSOC);
        
        // Get content progress count
        $contentStmt = $pdo->prepare("SELECT COUNT(*) as content_accessed FROM user_learning_progress WHERE user_id = ?");
        $contentStmt->execute([$user_id]);
        $contentData = $contentStmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'user' => $userData,
            'tools_used' => $toolData['tools_used'] ?? 0,
            'content_accessed' => $contentData['content_accessed'] ?? 0
        ];
    } catch (Exception $e) {
        error_log("Error getting user dashboard data: " . $e->getMessage());
        return [
            'user' => null,
            'tools_used' => 0,
            'content_accessed' => 0
        ];
    }
}
?>
