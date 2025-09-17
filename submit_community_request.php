<?php
/**
 * Handle Community Join Request Submission
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

session_start();
require_once __DIR__ . '/includes/community_functions.php';
require_once __DIR__ . '/includes/auth_functions.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get form data
    $message = trim($_POST['message'] ?? '');
    
    // Check if user is logged in first
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in to request community access']);
        exit;
    }
    
    $currentUser = getCurrentUser();
    if (!$currentUser) {
        echo json_encode(['success' => false, 'message' => 'User session invalid. Please log in again.']);
        exit;
    }
    
    // Use the logged-in user's information
    $userId = $currentUser['id'];
    $username = $currentUser['username'];
    $fullName = $currentUser['full_name'];
    $email = $currentUser['email']; // Use the logged-in user's email
    
    // Submit the community request
    $result = submitCommunityRequest($email, $message, $userId, $username, $fullName);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Error in submit_community_request.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>
