<?php
// Disable error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/auth_functions.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in and is admin
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        echo json_encode(['success' => false, 'message' => 'Could not get current user']);
        exit;
    }
    
    if (!isset($currentUser['is_admin']) || !$currentUser['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request data']);
        exit;
    }
    
    $userId = (int)$input['user_id'];
    
    $pdo = getDB();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Remove user from community (set community_access = 0)
    $stmt = $pdo->prepare("UPDATE users SET community_access = 0 WHERE id = ?");
    $result = $stmt->execute([$userId]);
    
    if ($result) {
        // Update community_requests table to mark as removed
        try {
            $stmt = $pdo->prepare("
                UPDATE community_requests 
                SET status = 'removed', 
                    processed_at = NOW(), 
                    processed_by = ?,
                    admin_notes = 'User removed from community by admin'
                WHERE user_id = ? AND status = 'approved'
                ORDER BY processed_at DESC
                LIMIT 1
            ");
            $stmt->execute([$currentUser['id'], $userId]);
        } catch (Exception $logError) {
            error_log("Could not update community_requests table: " . $logError->getMessage());
        }
        
        
        // Log the action
        error_log("User {$user['username']} (ID: {$userId}) removed from community by admin ID: {$currentUser['id']}");
        
        echo json_encode([
            'success' => true, 
            'message' => 'User removed from community successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove user from community']);
    }
    
} catch (Exception $e) {
    error_log("Error removing user from community: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}
?>
