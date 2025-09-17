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
        
        // First try to get from users table (including admin status)
        $stmt = $pdo->prepare("SELECT id, username, email, full_name, is_active, COALESCE(is_admin, 0) as is_admin FROM users WHERE id = ? AND is_active = TRUE");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            return $user;
        }
        
        // If not found in users table, try to get from admins table (fallback for existing admins)
        $stmt = $pdo->prepare("SELECT id, username, email FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            // Create a user-like array for admin
            return [
                'id' => $admin['id'],
                'username' => $admin['username'] ?? 'admin',
                'email' => $admin['email'],
                'full_name' => $admin['username'] ?? 'Admin',
                'is_active' => true,
                'is_admin' => true
            ];
        }
        
        // If still not found, try by email (in case ID doesn't match)
        if (isset($_SESSION['email'])) {
            $stmt = $pdo->prepare("SELECT id, username, email FROM admins WHERE email = ?");
            $stmt->execute([$_SESSION['email']]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                return [
                    'id' => $admin['id'],
                    'username' => $admin['username'] ?? 'admin',
                    'email' => $admin['email'],
                    'full_name' => $admin['username'] ?? 'Admin',
                    'is_active' => true,
                    'is_admin' => true
                ];
            }
        }
        
        return null;
    } catch (Exception $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return null;
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
