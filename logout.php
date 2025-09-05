<?php
/**
 * Logout Endpoint
 * Handles user logout requests
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/includes/auth_functions.php';

try {
    if (isLoggedIn()) {
        logoutUser();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
    }
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Logout failed']);
}
?>
