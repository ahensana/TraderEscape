<?php
/**
 * Tool Usage Tracking Endpoint
 * Tracks when users interact with trading tools
 */

session_start();
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/db_functions.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['tool_id']) || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required parameters']);
        exit();
    }
    
    $toolId = (int)$input['tool_id'];
    $action = $input['action'];
    $userId = $_SESSION['user_id'];
    
    // Validate tool exists
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id FROM trading_tools WHERE id = ? AND is_active = TRUE");
    $stmt->execute([$toolId]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Tool not found']);
        exit();
    }
    
    // Track tool usage
    if ($action === 'open') {
        // Start a new tool usage session
        $stmt = $pdo->prepare("INSERT INTO user_tool_usage (user_id, tool_id, session_start) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $toolId]);
        
        // Log activity
        logUserActivity($userId, 'tool_usage', "Opened tool ID: $toolId", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['tool_id' => $toolId, 'action' => 'open']));
        
        echo json_encode(['success' => true, 'message' => 'Tool usage tracked']);
        
    } elseif ($action === 'view_details') {
        // Log activity for viewing details
        logUserActivity($userId, 'tool_usage', "Viewed details for tool ID: $toolId", $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['tool_id' => $toolId, 'action' => 'view_details']));
        
        echo json_encode(['success' => true, 'message' => 'Tool details view tracked']);
        
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Error tracking tool usage: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
