<?php
session_start();
require_once __DIR__ . '/cookie_consent_handler.php';

echo "<h1>Cookie Consent Debug</h1>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Is Logged In:</strong> " . (isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) ? 'YES' : 'NO') . "</p>";
echo "<p><strong>Has Cookie Consent:</strong> " . (hasCookieConsent() ? 'YES' : 'NO') . "</p>";

echo "<h2>Session Data:</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<h2>Cookie Data:</h2>";
echo "<pre>" . print_r($_COOKIE, true) . "</pre>";

echo "<h2>Cookie Consent Status:</h2>";
$status = getCookieConsentStatus();
echo "<pre>" . print_r($status, true) . "</pre>";

// Test the banner condition
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$shouldShowBanner = $isLoggedIn && !hasCookieConsent();
echo "<p><strong>Should Show Banner:</strong> " . ($shouldShowBanner ? 'YES' : 'NO') . "</p>";
echo "<p><em>Banner shows ONLY when user IS logged in AND has NOT given consent</em></p>";
?>
