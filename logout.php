<?php
/**
 * Logout Endpoint
 * Handles user logout requests
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/includes/auth_functions.php';

try {
    // Debug information
    $debug = [
        'session_id' => session_id(),
        'session_status' => session_status(),
        'user_id' => $_SESSION['user_id'] ?? 'NOT SET',
        'is_logged_in' => isLoggedIn()
    ];
    
    if (isLoggedIn()) {
        logoutUser();
        echo json_encode([
            'success' => true, 
            'message' => 'Logged out successfully',
            'debug' => $debug
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Not logged in',
            'debug' => $debug
        ]);
    }
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Logout failed',
        'error' => $e->getMessage(),
        'debug' => [
            'session_id' => session_id(),
            'session_status' => session_status(),
            'user_id' => $_SESSION['user_id'] ?? 'NOT SET'
        ]
    ]);
}
?>
