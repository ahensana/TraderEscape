<?php
/**
 * Authentication Check Endpoint
 * Returns user authentication status and user data
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/includes/auth_functions.php';

try {
    if (isLoggedIn()) {
        $user = getCurrentUser();
        echo json_encode([
            'authenticated' => true,
            'user' => $user
        ]);
    } else {
        echo json_encode([
            'authenticated' => false,
            'user' => null
        ]);
    }
} catch (Exception $e) {
    error_log("Auth check error: " . $e->getMessage());
    echo json_encode([
        'authenticated' => false,
        'user' => null,
        'error' => 'Authentication check failed'
    ]);
}
?>
