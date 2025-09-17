<?php
// Temporary admin promotion script that works with current system
session_start();
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/auth_functions.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    
    // Check if current user is admin (direct database check)
    $pdo = getDB();
    $isAdmin = false;
    
    // Check if user is admin in admins table
    if (isset($_SESSION['email'])) {
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
        $stmt->execute([$_SESSION['email']]);
        if ($stmt->fetch() !== false) {
            $isAdmin = true;
        }
    }
    
    // Check if user is admin in users table (new system)
    if (!$isAdmin && isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND is_admin = 1");
        $stmt->execute([$_SESSION['user_id']]);
        if ($stmt->fetch() !== false) {
            $isAdmin = true;
        }
    }
    
    if (!$isAdmin) {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request data']);
        exit;
    }
    
    $userId = (int)$input['user_id'];
    $pdo = getDB();
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Check if user is already an admin
    if (isset($user['is_admin']) && $user['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'User is already an admin']);
        exit;
    }
    
    // Check if user is already an admin in admins table
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$user['email']]);
    if ($stmt->fetch() !== false) {
        echo json_encode(['success' => false, 'message' => 'User is already an admin']);
        exit;
    }
    
    // Add is_admin column if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0");
    }
    
    // Update user to admin status
    $stmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
    $result = $stmt->execute([$userId]);
    
    if ($result) {
        // Log the action
        error_log("User {$user['username']} (ID: {$userId}) promoted to admin by admin ID: {$_SESSION['user_id']}");
        
        echo json_encode([
            'success' => true, 
            'message' => 'User promoted to admin successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to promote user to admin']);
    }
    
} catch (Exception $e) {
    error_log("Error making user admin: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
