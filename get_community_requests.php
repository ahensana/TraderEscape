<?php
session_start();
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/community_functions.php';

header('Content-Type: application/json');

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
    
    // Get all community requests
    $requests = getAllCommunityRequests();
    
    echo json_encode([
        'success' => true,
        'requests' => $requests
    ]);

} catch (Exception $e) {
    error_log("Error in get_community_requests.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while fetching requests']);
}
?>
