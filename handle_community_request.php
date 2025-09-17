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
    
    // Check if user is admin
    if (!isAdmin($currentUser['id'])) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action']) || !isset($input['id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }
    
    $action = $input['action'];
    $id = (int)$input['id'];
    $adminId = $currentUser['id'];
    $adminNotes = '';
    
    $result = null;
    
    switch ($action) {
        case 'approve':
            $result = approveCommunityRequest($id, $adminId, $adminNotes);
            break;
            
        case 'reject':
            $result = rejectCommunityRequest($id, $adminId, $adminNotes);
            break;
            
        case 'remove':
            $result = removeUserFromCommunity($id, $adminId, $adminNotes);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
    
    echo json_encode($result);

} catch (Exception $e) {
    error_log("Error in handle_community_request.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing the request']);
}
?>
