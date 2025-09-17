<?php
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
    if (!$currentUser || !$currentUser['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    $pdo = getDB();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Find and clean up duplicate entries for users who are now admins
    $stmt = $pdo->prepare("
        SELECT cr.user_id, cr.username, cr.email, a.id as admin_id
        FROM community_requests cr
        INNER JOIN admins a ON cr.email = a.email
        WHERE cr.status = 'approved'
    ");
    $stmt->execute();
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cleaned = 0;
    foreach ($duplicates as $duplicate) {
        // Remove from community_requests
        $removeStmt = $pdo->prepare("DELETE FROM community_requests WHERE user_id = ? AND status = 'approved'");
        $removeStmt->execute([$duplicate['user_id']]);
        
        // Update users table community_access
        $updateStmt = $pdo->prepare("UPDATE users SET community_access = 0 WHERE id = ?");
        $updateStmt->execute([$duplicate['user_id']]);
        
        $cleaned++;
        error_log("Cleaned up duplicate for user: {$duplicate['username']} (ID: {$duplicate['user_id']})");
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Cleaned up {$cleaned} duplicate entries",
        'cleaned_count' => $cleaned
    ]);
    
} catch (Exception $e) {
    error_log("Error cleaning up duplicates: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
