<?php
session_start();
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/community_functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        echo json_encode(['success' => false, 'message' => 'User session invalid']);
        exit;
    }
    
    // Check if user has community access
    if (!hasCommunityAccess($currentUser['id'])) {
        echo json_encode(['success' => false, 'message' => 'User does not have community access']);
        exit;
    }
    
    $pdo = getDB();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Remove community access from user
    $stmt = $pdo->prepare("
        UPDATE users 
        SET community_access = 0
        WHERE id = ? AND is_active = 1
    ");
    $stmt->execute([$currentUser['id']]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Failed to remove community access']);
        exit;
    }
    
    // Update the existing approved request to show user left (instead of creating new row)
    try {
        $stmt = $pdo->prepare("
            UPDATE community_requests 
            SET status = 'left', 
                processed_at = NOW(), 
                processed_by = ?, 
                admin_notes = 'User voluntarily left the chat'
            WHERE user_id = ? AND status = 'approved'
            ORDER BY processed_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$currentUser['id'], $currentUser['id']]);
    } catch (Exception $logError) {
        // If community_requests table doesn't exist, just log the error but don't fail
        error_log("Could not update leave action in community_requests table: " . $logError->getMessage());
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Successfully left the chat. You can request to rejoin anytime.'
    ]);

} catch (Exception $e) {
    error_log("Error in leave_chat.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while leaving the chat',
        'debug' => $e->getMessage()
    ]);
}
?>
