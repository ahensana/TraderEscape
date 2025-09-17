<?php
// Disable error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);

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
    if (!$currentUser) {
        echo json_encode(['success' => false, 'message' => 'Could not get current user']);
        exit;
    }
    
    if (!isset($currentUser['is_admin']) || !$currentUser['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        exit;
    }
    
    // Get request data
    $rawInput = file_get_contents('php://input');
    error_log("Raw input: " . $rawInput);
    
    $input = json_decode($rawInput, true);
    error_log("Decoded input: " . print_r($input, true));
    
    if (!$input || !isset($input['user_id']) || !isset($input['username']) || !isset($input['email'])) {
        error_log("Invalid request data - missing required fields");
        echo json_encode(['success' => false, 'message' => 'Invalid request data']);
        exit;
    }
    
    $userId = (int)$input['user_id'];
    $username = $input['username'];
    $email = $input['email'];
    
    error_log("Processing user: ID=$userId, Username=$username, Email=$email");
    
    $pdo = getDB();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Check if user exists and is not already an admin
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("User lookup result: " . print_r($user, true));
    error_log("User password field: " . (isset($user['password']) ? 'exists' : 'missing'));
    error_log("User password value: " . ($user['password'] ?? 'NULL'));
    
    if (!$user) {
        error_log("User not found with ID: $userId");
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    // Check if user is already an admin
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $existingAdmin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Existing admin check result: " . print_r($existingAdmin, true));
    
    if ($existingAdmin) {
        error_log("User is already an admin: $email");
        echo json_encode(['success' => false, 'message' => 'User is already an admin']);
        exit;
    }
    
    // Get user's password hash from users table
    $userPassword = $user['password']; // This should be the hashed password
    
    // Check if password exists, if not, create a default password
    if (empty($userPassword)) {
        error_log("User password is empty for user ID: $userId, creating default password");
        // Create a default password that the user can change later
        $userPassword = password_hash('admin123', PASSWORD_DEFAULT);
        error_log("Created default password for user: $username");
    }
    
    // Insert user into admins table
    $stmt = $pdo->prepare("
        INSERT INTO admins (username, email, password, created_at, updated_at) 
        VALUES (?, ?, ?, NOW(), NOW())
    ");
    
    error_log("Attempting to insert admin: username=$username, email=$email, password_length=" . strlen($userPassword));
    
    $result = $stmt->execute([$username, $email, $userPassword]);
    
    error_log("Insert result: " . ($result ? 'success' : 'failed'));
    
    if ($result) {
        // Remove user from community_requests table since they're now an admin
        try {
            $removeStmt = $pdo->prepare("DELETE FROM community_requests WHERE user_id = ? AND status = 'approved'");
            $removeResult = $removeStmt->execute([$userId]);
            
            if ($removeResult) {
                error_log("Removed user {$username} (ID: {$userId}) from community_requests table");
            } else {
                error_log("Warning: Could not remove user {$username} from community_requests table");
            }
        } catch (Exception $removeError) {
            error_log("Error removing user from community_requests: " . $removeError->getMessage());
        }
        
        // Update the original user's password to match the admin password for consistency
        try {
            $updateStmt = $pdo->prepare("UPDATE users SET community_access = 0, password_hash = ? WHERE id = ?");
            $updateStmt->execute([$userPassword, $userId]);
            error_log("Updated user {$username} password and community_access");
        } catch (Exception $updateError) {
            error_log("Error updating user password and community_access: " . $updateError->getMessage());
        }
        
        // Log the action
        error_log("User {$username} (ID: {$userId}) promoted to admin by admin ID: {$currentUser['id']}");
        
        echo json_encode([
            'success' => true, 
            'message' => 'User promoted to admin successfully and removed from community requests'
        ]);
    } else {
        $errorInfo = $stmt->errorInfo();
        error_log("Insert failed: " . print_r($errorInfo, true));
        echo json_encode(['success' => false, 'message' => 'Failed to promote user to admin']);
    }
    
} catch (Exception $e) {
    error_log("Error making user admin: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}
?>
