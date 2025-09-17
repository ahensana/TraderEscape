<?php
session_start();
require_once __DIR__ . '/includes/db_functions.php';

header('Content-Type: application/json');

try {
    $pdo = getDB();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    
    $userId = $_SESSION['user_id'];
    $email = $_SESSION['email'] ?? 'not set';
    
    // Check if user exists in admins table
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user exists in users table
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'email' => $email,
        'in_admins_table' => $admin ? true : false,
        'admin_data' => $admin,
        'in_users_table' => $user ? true : false,
        'user_data' => $user,
        'session_data' => $_SESSION
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
