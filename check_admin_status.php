<?php
session_start();
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/community_functions.php';

header('Content-Type: application/json');

try {
    echo json_encode([
        'session_user_id' => $_SESSION['user_id'] ?? 'not set',
        'is_logged_in' => isLoggedIn(),
        'current_user' => getCurrentUser(),
        'is_admin_check' => isAdmin(),
        'session_data' => $_SESSION
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
