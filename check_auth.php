<?php
/**
 * Authentication Check Endpoint
 * Returns user authentication status and user data
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
        $user = getCurrentUser();
        echo json_encode([
            'authenticated' => true,
            'user' => $user,
            'debug' => $debug
        ]);
    } else {
        echo json_encode([
            'authenticated' => false,
            'user' => null,
            'debug' => $debug
        ]);
    }
} catch (Exception $e) {
    error_log("Auth check error: " . $e->getMessage());
    echo json_encode([
        'authenticated' => false,
        'user' => null,
        'error' => 'Authentication check failed',
        'debug' => [
            'session_id' => session_id(),
            'session_status' => session_status(),
            'user_id' => $_SESSION['user_id'] ?? 'NOT SET',
            'error_message' => $e->getMessage()
        ]
    ]);
}
?>
