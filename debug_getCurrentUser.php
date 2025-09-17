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
    
    $userId = $_SESSION['user_id'] ?? 'not set';
    $email = $_SESSION['email'] ?? 'not set';
    
    // Test 1: Check users table by ID
    $stmt = $pdo->prepare("SELECT id, username, email, full_name, is_active, COALESCE(is_admin, 0) as is_admin FROM users WHERE id = ? AND is_active = TRUE");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Test 2: Check admins table by ID
    $stmt = $pdo->prepare("SELECT id, username, email FROM admins WHERE id = ?");
    $stmt->execute([$userId]);
    $adminById = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Test 3: Check admins table by email
    $stmt = $pdo->prepare("SELECT id, username, email FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $adminByEmail = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'session_user_id' => $userId,
        'session_email' => $email,
        'users_table_by_id' => $user,
        'admins_table_by_id' => $adminById,
        'admins_table_by_email' => $adminByEmail,
        'all_admins' => $pdo->query("SELECT * FROM admins")->fetchAll(PDO::FETCH_ASSOC)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
