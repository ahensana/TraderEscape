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
    
    // Add is_admin column to users table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    $columnExists = $stmt->fetch();
    
    if (!$columnExists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0");
        echo json_encode(['success' => true, 'message' => 'Added is_admin column to users table']);
    } else {
        echo json_encode(['success' => true, 'message' => 'is_admin column already exists']);
    }
    
} catch (Exception $e) {
    error_log("Error adding admin column: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
