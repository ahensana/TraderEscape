<?php
/**
 * Community Functions for TraderEscape
 * Handles community join requests and access management
 */

require_once __DIR__ . '/db_functions.php';

/**
 * Submit a community join request
 */
function submitCommunityRequest($email, $message = '', $userId = null, $username = null, $fullName = null) {
    try {
        $pdo = getDB();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        // Only allow registered users to submit requests
        if (!$userId) {
            return ['success' => false, 'message' => 'You must be logged in to request community access'];
        }

        // Verify the user exists and is active
        $stmt = $pdo->prepare("SELECT id, community_access FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'User account not found or inactive'];
        }

        // Check if user already has community access
        if ($user['community_access']) {
            return ['success' => false, 'message' => 'You already have community access'];
        }

        // Check if user already has a pending request
        $stmt = $pdo->prepare("SELECT id FROM community_requests WHERE user_id = ? AND status = 'pending'");
        $stmt->execute([$userId]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'You already have a pending request'];
        }

        // Check if user has a previous request (approved or left) and update it instead of creating new row
        $stmt = $pdo->prepare("SELECT id FROM community_requests WHERE user_id = ? AND status IN ('approved', 'left') ORDER BY requested_at DESC LIMIT 1");
        $stmt->execute([$userId]);
        $existingRequest = $stmt->fetch();
        
        if ($existingRequest) {
            // Update existing request to pending
            $stmt = $pdo->prepare("
                UPDATE community_requests 
                SET status = 'pending', 
                    request_message = ?, 
                    requested_at = NOW(),
                    processed_at = NULL,
                    processed_by = NULL,
                    admin_notes = NULL
                WHERE id = ?
            ");
            $result = $stmt->execute([$message, $existingRequest['id']]);
        } else {
            // Insert new request only if no previous request exists
            $stmt = $pdo->prepare("
                INSERT INTO community_requests 
                (user_id, email, username, full_name, request_message, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $userId,
                $email,
                $username,
                $fullName,
                $message,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        }

        if ($result) {
            // Log the activity if user is logged in
            if ($userId) {
                logUserActivity($userId, 'community_request', 'Submitted community join request', 
                    $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, 
                    json_encode(['action' => 'community_request']));
            }
            
            return ['success' => true, 'message' => 'Your request has been submitted successfully!'];
        } else {
            return ['success' => false, 'message' => 'Failed to submit request'];
        }
    } catch (Exception $e) {
        error_log("Error submitting community request: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while submitting your request'];
    }
}

/**
 * Get all pending community requests
 */
function getPendingCommunityRequests() {
    try {
        $pdo = getDB();
        if (!$pdo) {
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT cr.*, u.username as user_username, u.full_name as user_full_name
            FROM community_requests cr
            LEFT JOIN users u ON cr.user_id = u.id
            WHERE cr.status = 'pending'
            ORDER BY cr.requested_at ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error getting pending community requests: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all community requests (for admin sidebar)
 */
function getAllCommunityRequests() {
    try {
        $pdo = getDB();
        if (!$pdo) {
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT 
                cr.*,
                u.username,
                u.full_name,
                u.email as user_email,
                a.username as processed_by_username
            FROM community_requests cr
            LEFT JOIN users u ON cr.user_id = u.id
            LEFT JOIN admins a ON cr.processed_by = a.id
            WHERE cr.status = 'pending'
            ORDER BY cr.requested_at DESC
            LIMIT 50
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting all community requests: " . $e->getMessage());
        return [];
    }
}


/**
 * Approve a community request
 */
function approveCommunityRequest($requestId, $adminId, $adminNotes = '') {
    try {
        $pdo = getDB();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        // Start transaction
        $pdo->beginTransaction();

        // Get the request details
        $stmt = $pdo->prepare("SELECT * FROM community_requests WHERE id = ? AND status = 'pending'");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();

        if (!$request) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Request not found or already processed'];
        }

        // Update the request status
        $stmt = $pdo->prepare("
            UPDATE community_requests 
            SET status = 'approved', processed_at = NOW(), processed_by = ?, admin_notes = ?
            WHERE id = ?
        ");
        $stmt->execute([$adminId, $adminNotes, $requestId]);

        // Only grant community access to registered users
        if ($request['user_id']) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET community_access = 1, community_joined_at = NOW()
                WHERE id = ? AND is_active = 1
            ");
            $stmt->execute([$request['user_id']]);
            
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'User not found or inactive'];
            }
        } else {
            // For non-registered users, we can't grant access
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Only registered users can be granted community access'];
        }

        $pdo->commit();
        return ['success' => true, 'message' => 'Request approved successfully'];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error approving community request: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to approve request'];
    }
}

/**
 * Reject a community request
 */
function rejectCommunityRequest($requestId, $adminId, $adminNotes = '') {
    try {
        $pdo = getDB();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        $stmt = $pdo->prepare("
            UPDATE community_requests 
            SET status = 'rejected', processed_at = NOW(), processed_by = ?, admin_notes = ?
            WHERE id = ? AND status = 'pending'
        ");
        
        $result = $stmt->execute([$adminId, $adminNotes, $requestId]);
        
        if ($result && $stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Request rejected successfully'];
        } else {
            return ['success' => false, 'message' => 'Request not found or already processed'];
        }
    } catch (Exception $e) {
        error_log("Error rejecting community request: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to reject request'];
    }
}

/**
 * Remove user from community access
 * Revokes community access for a registered user
 */
function removeUserFromCommunity($userId, $adminId, $adminNotes = '') {
    try {
        $pdo = getDB();
        if (!$pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }

        $pdo->beginTransaction();

        // Check if user exists and has community access
        $stmt = $pdo->prepare("SELECT id, email, username, full_name, community_access FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'User not found or inactive'];
        }

        if (!$user['community_access']) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'User does not have community access'];
        }

        // Remove community access
        $stmt = $pdo->prepare("
            UPDATE users 
            SET community_access = 0
            WHERE id = ? AND is_active = 1
        ");
        $stmt->execute([$userId]);

        // Log the removal in community_requests table
        $stmt = $pdo->prepare("
            INSERT INTO community_requests 
            (user_id, email, username, full_name, request_message, status, processed_at, processed_by, admin_notes, requested_at) 
            VALUES (?, ?, ?, ?, 'Community access removed by admin', 'removed', NOW(), ?, ?, NOW())
        ");
        $stmt->execute([$userId, $user['email'], $user['username'], $user['full_name'], $adminId, $adminNotes]);

        $pdo->commit();
        return ['success' => true, 'message' => 'User removed from community successfully'];

    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error removing user from community: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to remove user from community'];
    }
}

/**
 * Check if user is an admin
 */
function isAdmin($userId = null, $email = null) {
    try {
        // First check if user is logged in and has is_admin flag
        if (isset($_SESSION['user']) && isset($_SESSION['user']['is_admin']) && $_SESSION['user']['is_admin']) {
            return true;
        }
        
        $pdo = getDB();
        if (!$pdo) {
            return false;
        }

        if ($userId) {
            // Check if user is an admin by user ID in users table
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND is_admin = 1");
            $stmt->execute([$userId]);
            if ($stmt->fetch() !== false) {
                return true;
            }
            
            // Fallback: check admins table for existing admins
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch() !== false;
        } elseif ($email) {
            // Check if user is an admin by email in users table
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND is_admin = 1");
            $stmt->execute([$email]);
            if ($stmt->fetch() !== false) {
                return true;
            }
            
            // Fallback: check admins table for existing admins
            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch() !== false;
        }

        return false;
    } catch (Exception $e) {
        error_log("Error checking admin status: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user has community access
 * Only allows registered users in the users table or admins
 */
function hasCommunityAccess($userId = null, $email = null) {
    try {
        // First check if user is logged in and get current user data
        if (isLoggedIn()) {
            $currentUser = getCurrentUser();
            if ($currentUser && isset($currentUser['is_admin']) && $currentUser['is_admin']) {
                return true; // Admins automatically have access
            }
        }
        
        $pdo = getDB();
        if (!$pdo) {
            return false;
        }

        if ($userId) {
            // Check if registered user has community access
            $stmt = $pdo->prepare("SELECT community_access FROM users WHERE id = ? AND is_active = 1");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            return $user && $user['community_access'] == 1;
        } elseif ($email) {
            // Check if registered user with this email has access
            $stmt = $pdo->prepare("SELECT community_access FROM users WHERE email = ? AND is_active = 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            return $user && $user['community_access'] == 1;
        }

        return false;
    } catch (Exception $e) {
        error_log("Error checking community access: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all community users (users with community access)
 */
function getAllCommunityUsers() {
    try {
        $pdo = getDB();
        if (!$pdo) {
            return [];
        }

        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.email,
                u.username,
                u.full_name,
                u.community_joined_at,
                u.community_removed_at,
                u.created_at,
                u.last_login_at
            FROM users u 
            WHERE u.community_access = 1 AND u.is_active = 1
            ORDER BY u.community_joined_at DESC
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting community users: " . $e->getMessage());
        return [];
    }
}

/**
 * Get community statistics for admin dashboard
 */
function getCommunityStats() {
    try {
        $pdo = getDB();
        if (!$pdo) {
            return [];
        }

        $stats = [];

        // Total requests
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM community_requests");
        $stats['total_requests'] = $stmt->fetch()['total'];

        // Pending requests
        $stmt = $pdo->query("SELECT COUNT(*) as pending FROM community_requests WHERE status = 'pending'");
        $stats['pending_requests'] = $stmt->fetch()['pending'];

        // Approved requests
        $stmt = $pdo->query("SELECT COUNT(*) as approved FROM community_requests WHERE status = 'approved'");
        $stats['approved_requests'] = $stmt->fetch()['approved'];

        // Users with community access
        $stmt = $pdo->query("SELECT COUNT(*) as community_users FROM users WHERE community_access = 1");
        $stats['community_users'] = $stmt->fetch()['community_users'];

        return $stats;
    } catch (Exception $e) {
        error_log("Error getting community stats: " . $e->getMessage());
        return [];
    }
}
?>
