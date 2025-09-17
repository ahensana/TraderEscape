<?php
session_start();
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/auth_functions.php';

header('Content-Type: application/json');

try {
    echo json_encode([
        'session_user_id' => $_SESSION['user_id'] ?? 'not set',
        'session_email' => $_SESSION['email'] ?? 'not set',
        'getCurrentUser_result' => getCurrentUser(),
        'is_logged_in' => isLoggedIn()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
