<?php
session_start();
require_once __DIR__ . '/includes/db_functions.php';
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/community_functions.php';

header('Content-Type: application/json');

// Generate a consistent color for each user based on their ID
function generateUserColor($userId) {
    $colors = [
        '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
        '#06b6d4', '#84cc16', '#f97316', '#ec4899', '#6366f1'
    ];
    return $colors[$userId % count($colors)];
}

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    
    $pdo = getDB();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Get all community members: approved users from community_requests + all admins
    $stmt = $pdo->prepare("
        (
            SELECT 
                cr.user_id as id,
                cr.username,
                cr.full_name,
                cr.email,
                cr.processed_at as joined_at,
                u.last_login,
                u.is_active,
                COALESCE(u.is_admin, 0) as is_admin,
                CASE WHEN u.is_admin = 1 THEN 'admin' ELSE 'user' END as member_type
            FROM community_requests cr
            LEFT JOIN users u ON cr.user_id = u.id
            WHERE cr.status = 'approved' 
            AND u.is_active = 1
        )
        UNION ALL
        (
            SELECT 
                a.id as id,
                a.username,
                a.username as full_name,
                a.email,
                a.created_at as joined_at,
                NULL as last_login,
                1 as is_active,
                1 as is_admin,
                'admin' as member_type
            FROM admins a
        )
        ORDER BY 
            CASE WHEN member_type = 'admin' THEN 0 ELSE 1 END,
            joined_at DESC
    ");
    
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the number of members found
    error_log("Found " . count($members) . " approved community members");
    
    // Format the data for the frontend
    $formattedMembers = [];
    foreach ($members as $member) {
        $formattedMembers[] = [
            'id' => (int)$member['id'], // Ensure it's an integer for comparison
            'name' => $member['username'], // Use username instead of full_name
            'username' => $member['username'],
            'email' => $member['email'],
            'joined_at' => $member['joined_at'], // This is now processed_at from community_requests
            'last_login' => $member['last_login'],
            'is_online' => false, // We'll determine this based on socket connections
            'is_admin' => ($member['member_type'] === 'admin'), // Add admin status
            'member_type' => $member['member_type'], // Add member type
            'color' => generateUserColor($member['id'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'members' => $formattedMembers,
        'debug' => [
            'total_members' => count($members),
            'formatted_count' => count($formattedMembers)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error getting community members: " . $e->getMessage());
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
