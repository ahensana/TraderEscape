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
    $rawInput = file_get_contents('php://input');
    error_log("Raw input: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    error_log("Decoded input: " . print_r($input, true));
    
    if (!$input || !isset($input['user_id'])) {
        error_log("Invalid request data - missing user_id");
        echo json_encode(['success' => false, 'message' => 'Invalid request data']);
        exit;
    }
    
    $userId = (int)$input['user_id'];
    error_log("Processing user ID: $userId");
    
    $pdo = getDB();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("User lookup result: " . print_r($user, true));
    
    if (!$user) {
        error_log("User not found with ID: $userId");
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Check if user is already an admin
    if (isset($user['is_admin']) && $user['is_admin']) {
        error_log("User is already an admin: ID $userId");
        echo json_encode(['success' => false, 'message' => 'User is already an admin']);
        exit;
    }
    
    // Update user to admin status
    $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
    $result = $stmt->execute([$userId]);
    
    error_log("Update result: " . ($result ? 'success' : 'failed'));
    
    if ($result) {
        // Log the action
        error_log("User {$user['username']} (ID: {$userId}) promoted to admin by admin ID: {$currentUser['id']}");
        
        echo json_encode([
            'success' => true, 
            'message' => 'User promoted to admin successfully'
        ]);
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log("Update failed: " . print_r($errorInfo, true));
        echo json_encode(['success' => false, 'message' => 'Failed to promote user to admin']);
    }
    
} catch (Exception $e) {
    error_log("Error making user admin: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
