<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/db_functions.php';

// Handle cookie consent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cookie_consent') {
    $consent_type = $_POST['consent_type'] ?? 'declined';
    $essential_cookies = isset($_POST['essential_cookies']) ? 1 : 0;
    $analytics_cookies = isset($_POST['analytics_cookies']) ? 1 : 0;
    $marketing_cookies = isset($_POST['marketing_cookies']) ? 1 : 0;
    $functional_cookies = isset($_POST['functional_cookies']) ? 1 : 0;
    
    try {
        $pdo = getDB();
        $userId = $_SESSION['user_id'] ?? null;
        $sessionId = session_id();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Check if consent already exists for this session
        $stmt = $pdo->prepare("SELECT id FROM cookie_consent WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $existingConsent = $stmt->fetch();
        
        if ($existingConsent) {
            // Update existing consent
            $stmt = $pdo->prepare("UPDATE cookie_consent SET 
                consent_type = ?, 
                essential_cookies = ?, 
                analytics_cookies = ?, 
                marketing_cookies = ?, 
                functional_cookies = ?,
                last_updated = NOW()
                WHERE session_id = ?");
            $stmt->execute([$consent_type, $essential_cookies, $analytics_cookies, $marketing_cookies, $functional_cookies, $sessionId]);
        } else {
        // Insert new consent
        $stmt = $pdo->prepare("INSERT INTO cookie_consent 
            (user_id, session_id, ip_address, consent_type, essential_cookies, analytics_cookies, marketing_cookies, functional_cookies, consent_given_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, $sessionId, $ipAddress, $consent_type, $essential_cookies, $analytics_cookies, $marketing_cookies, $functional_cookies]);
        }
        
        // Set cookie to remember consent
        $consentData = [
            'type' => $consent_type,
            'essential' => $essential_cookies,
            'analytics' => $analytics_cookies,
            'marketing' => $marketing_cookies,
            'functional' => $functional_cookies,
            'timestamp' => time()
        ];
        
        // Set cookie with proper parameters
        setcookie('cookie_consent', json_encode($consentData), [
            'expires' => time() + (365 * 24 * 60 * 60), // 1 year
            'path' => '/',
            'secure' => false, // Set to true in production with HTTPS
            'httponly' => false, // Allow JavaScript access
            'samesite' => 'Lax'
        ]);
        
        // Log user activity if logged in
        if ($userId) {
            logUserActivity($userId, 'other', 'Cookie consent updated', $ipAddress, $userAgent, json_encode($consentData));
        }
        
        echo json_encode(['success' => true, 'message' => 'Cookie consent saved successfully']);
        exit;
        
    } catch (Exception $e) {
        error_log("Cookie consent error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to save cookie consent: ' . $e->getMessage()]);
        exit;
    }
}

// Check if user has already given consent
function hasCookieConsent() {
    // Check cookie first (faster)
    if (isset($_COOKIE['cookie_consent'])) {
        $consentData = json_decode($_COOKIE['cookie_consent'], true);
        if ($consentData && isset($consentData['timestamp']) && isset($consentData['type'])) {
            // Check if consent is not older than 1 year and not declined
            if ((time() - $consentData['timestamp']) < (365 * 24 * 60 * 60) && $consentData['type'] !== 'declined') {
                return true;
            }
        }
    }
    
    // Check database as fallback
    try {
        $pdo = getDB();
        $sessionId = session_id();
        if ($sessionId) {
            $stmt = $pdo->prepare("SELECT consent_type FROM cookie_consent WHERE session_id = ? AND consent_given_at > DATE_SUB(NOW(), INTERVAL 1 YEAR) ORDER BY consent_given_at DESC LIMIT 1");
            $stmt->execute([$sessionId]);
            $consent = $stmt->fetch();
            
            if ($consent && $consent['consent_type'] !== 'declined') {
                return true;
            }
        }
    } catch (Exception $e) {
        error_log("Cookie consent check error: " . $e->getMessage());
    }
    
    return false;
}

// Get current consent status
function getCookieConsentStatus() {
    if (isset($_COOKIE['cookie_consent'])) {
        return json_decode($_COOKIE['cookie_consent'], true);
    }
    return null;
}
?>