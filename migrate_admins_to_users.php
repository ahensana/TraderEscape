<?php
session_start();
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/auth_functions.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in and is admin
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    
    $currentUser = getCurrentUser();
    if (!$currentUser || !$currentUser['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    $pdo = getDB();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // First, add is_admin column if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0");
        error_log("Added is_admin column to users table");
    }
    
    // Get all admins from admins table
    $stmt = $pdo->query("SELECT * FROM admins");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $migrated = 0;
    $errors = [];
    
    foreach ($admins as $admin) {
        try {
            // Check if user exists in users table with same email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$admin['email']]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingUser) {
                // Update existing user to admin
                $updateStmt = $pdo->prepare("UPDATE users SET is_admin = 1 WHERE id = ?");
                $updateStmt->execute([$existingUser['id']]);
                $migrated++;
                error_log("Updated user {$admin['username']} to admin status");
            } else {
                // Create new user with admin status
                $insertStmt = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, full_name, is_active, is_admin, created_at, last_login) 
                    VALUES (?, ?, ?, ?, 1, 1, NOW(), NOW())
                ");
                $insertStmt->execute([
                    $admin['username'],
                    $admin['email'],
                    $admin['password'],
                    $admin['username']
                ]);
                $migrated++;
                error_log("Created new user {$admin['username']} with admin status");
            }
        } catch (Exception $e) {
            $errors[] = "Error migrating admin {$admin['username']}: " . $e->getMessage();
            error_log("Error migrating admin {$admin['username']}: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Migration completed. Migrated {$migrated} admins to users table.",
        'migrated_count' => $migrated,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    error_log("Error during migration: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Migration failed: ' . $e->getMessage()]);
}
?>
