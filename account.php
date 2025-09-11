<?php
/**
 * User Account Profile Management for TraderEscape
 */

session_start();
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/db_functions.php';
requireAuth();

$currentUser = getCurrentUser();
$currentPage = 'account';

// Get real user statistics from database
$userStats = getUserStats($currentUser['id']);
$userDashboardData = getUserDashboardData($currentUser['id']);

// Get trading profiles and risk data
$tradingProfiles = getTradingProfiles($currentUser['id']);
$riskMetrics = getRiskMetrics($currentUser['id']);
$portfolioPositions = getPortfolioPositions($currentUser['id']);
$recentTrades = getRecentTrades($currentUser['id'], 10);

// Track page view
trackPageView('account', $currentUser['id'], $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, session_id());

// Log user activity
logUserActivity($currentUser['id'], 'page_view', 'Viewed account dashboard', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['page' => 'account']));

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $full_name = trim($_POST['full_name'] ?? '');
                
                if (empty($username) || empty($email) || empty($full_name)) {
                    $error = 'Please fill in all fields.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address.';
                } else {
                    try {
                        $pdo = getDB();
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, full_name = ? WHERE id = ?");
                        if ($stmt->execute([$username, $email, $full_name, $currentUser['id']])) {
                            $message = 'Profile updated successfully!';
                            // Update session data
                            $_SESSION['username'] = $username;
                            $_SESSION['email'] = $email;
                            $_SESSION['full_name'] = $full_name;
                            // Refresh user data
                            $currentUser = getCurrentUser();
                            // Log activity
                            logUserActivity($currentUser['id'], 'profile_update', 'Updated profile information', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['fields' => ['username', 'email', 'full_name']]));
                        } else {
                            $error = 'Failed to update profile.';
                        }
                    } catch (Exception $e) {
                        $error = 'Error updating profile: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'change_password':
                $old_password = $_POST['old_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
                    $error = 'Please fill in all password fields.';
                } elseif ($new_password !== $confirm_password) {
                    $error = 'New passwords do not match.';
                } elseif (strlen($new_password) < 8) {
                    $error = 'New password must be at least 8 characters long.';
                } else {
                    try {
                        $pdo = getDB();
                        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                        $stmt->execute([$currentUser['id']]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($user && password_verify($old_password, $user['password_hash'])) {
                            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                            $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                            if ($updateStmt->execute([$new_hash, $currentUser['id']])) {
                                $message = 'Password changed successfully!';
                                // Log activity
                                logUserActivity($currentUser['id'], 'profile_update', 'Changed password', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['action' => 'password_change']));
                            } else {
                                $error = 'Failed to update password.';
                            }
                        } else {
                            $error = 'Current password is incorrect.';
                        }
                    } catch (Exception $e) {
                        $error = 'Error changing password: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'save_trading_profile':
                $profileData = [
                    'profile_name' => trim($_POST['profile_name'] ?? ''),
                    'account_size' => floatval($_POST['account_size'] ?? 0),
                    'risk_percentage' => floatval($_POST['risk_percentage'] ?? 2),
                    'max_daily_loss_percentage' => floatval($_POST['max_daily_loss_percentage'] ?? 5),
                    'max_open_risk_percentage' => floatval($_POST['max_open_risk_percentage'] ?? 10),
                    'preferred_trading_style' => $_POST['preferred_trading_style'] ?? 'swing_trading',
                    'risk_tolerance' => $_POST['risk_tolerance'] ?? 'moderate',
                    'max_positions' => intval($_POST['max_positions'] ?? 5),
                    'is_default' => isset($_POST['is_default']) && $_POST['is_default']
                ];
                
                if (empty($profileData['profile_name']) || $profileData['account_size'] <= 0) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
                    exit;
                }
                
                $profileId = saveTradingProfile($currentUser['id'], $profileData);
                if ($profileId) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Profile saved successfully']);
                    exit;
                } else {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Failed to save profile']);
                    exit;
                }
                break;
                
            case 'set_default_profile':
                $profileId = intval($_POST['profile_id'] ?? 0);
                if ($profileId > 0) {
                    try {
                        $pdo = getDB();
                        // First, unset all default profiles for this user
                        $stmt = $pdo->prepare("UPDATE user_trading_profiles SET is_default = FALSE WHERE user_id = ?");
                        $stmt->execute([$currentUser['id']]);
                        
                        // Set the selected profile as default
                        $stmt = $pdo->prepare("UPDATE user_trading_profiles SET is_default = TRUE WHERE id = ? AND user_id = ?");
                        $result = $stmt->execute([$profileId, $currentUser['id']]);
                        
                        if ($result) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true, 'message' => 'Default profile updated']);
                            exit;
                        }
                    } catch (Exception $e) {
                        error_log("Error setting default profile: " . $e->getMessage());
                    }
                }
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to set default profile']);
                exit;
                break;
                
            case 'acknowledge_alert':
                $alertId = intval($_POST['alert_id'] ?? 0);
                if ($alertId > 0) {
                    $result = acknowledgeRiskAlert($currentUser['id'], $alertId);
                    if ($result) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Alert acknowledged']);
                        exit;
                    }
                }
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to acknowledge alert']);
                exit;
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - The Trader's Escape</title>
    <link rel="stylesheet" href="./assets/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <style>
    /* Enhanced Dashboard Panel Design */
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 3rem 0;
        border-bottom: 2px solid rgba(59, 130, 246, 0.2);
        margin-bottom: 3rem;
        position: relative;
    }

    .dashboard-header::before {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 100px;
        height: 2px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        border-radius: 1px;
    }

    .dashboard-welcome h1 {
        font-size: 3rem;
        font-weight: 800;
        margin: 0 0 0.75rem 0;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6, #ec4899);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: gradientShift 3s ease-in-out infinite;
    }

    @keyframes gradientShift {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }

    .dashboard-welcome p {
        color: #94a3b8;
        font-size: 1.2rem;
        margin: 0;
        font-weight: 500;
    }

    .dashboard-layout {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 2.5rem;
        min-height: 700px;
        margin-bottom: 4rem;
    }

    .dashboard-sidebar {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 1.5rem;
        padding: 2rem;
        height: fit-content;
        backdrop-filter: blur(20px);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 2rem;
    }

    .sidebar-nav {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .nav-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.25rem;
        border-radius: 0.75rem;
        color: #94a3b8;
        text-decoration: none;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 600;
        position: relative;
        overflow: hidden;
    }

    .nav-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
        transition: left 0.5s ease;
    }

    .nav-item:hover::before {
        left: 100%;
    }

    .nav-item:hover,
    .nav-item.active {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(139, 92, 246, 0.1));
        color: #3b82f6;
        border: 1px solid rgba(59, 130, 246, 0.3);
        transform: translateX(5px);
        box-shadow: 0 4px 20px rgba(59, 130, 246, 0.2);
    }

    .nav-item i {
        font-size: 1.25rem;
        transition: transform 0.3s ease;
    }

    .nav-item:hover i,
    .nav-item.active i {
        transform: scale(1.1);
    }

    .dashboard-content {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 1.5rem;
        padding: 2.5rem;
        backdrop-filter: blur(20px);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        min-height: 600px;
    }

    .dashboard-section {
        display: none;
        animation: fadeIn 0.5s ease-in-out;
    }

    .dashboard-section.active {
        display: block;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .section-header {
        margin-bottom: 2.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid rgba(59, 130, 246, 0.2);
        position: relative;
    }

    .section-header::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 60px;
        height: 2px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6);
        border-radius: 1px;
    }

    .section-header h2 {
        font-size: 2rem;
        font-weight: 700;
        margin: 0 0 0.75rem 0;
        color: #ffffff;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .section-header p {
        color: #94a3b8;
        margin: 0;
        font-size: 1.1rem;
        font-weight: 500;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
    }

    .stat-card {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 1rem;
        padding: 2rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .stat-card:hover::before {
        transform: scaleX(1);
    }

    .stat-card:hover {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.12), rgba(255, 255, 255, 0.04));
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 12px 40px rgba(59, 130, 246, 0.15);
        border-color: rgba(59, 130, 246, 0.4);
    }

    .stat-icon {
        width: 4rem;
        height: 4rem;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        box-shadow: 0 4px 20px rgba(59, 130, 246, 0.3);
        transition: transform 0.3s ease;
    }

    .stat-card:hover .stat-icon {
        transform: rotate(5deg) scale(1.1);
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 800;
        color: #ffffff;
        line-height: 1;
        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .stat-label {
        color: #94a3b8;
        font-size: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
    }

    .info-card {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 1rem;
        padding: 2rem;
        transition: all 0.3s ease;
    }

    .info-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(59, 130, 246, 0.1);
    }

    .info-card h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 1.5rem 0;
        color: #ffffff;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .info-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }

    .info-item:hover {
        background: rgba(255, 255, 255, 0.08);
        transform: translateX(5px);
    }

    .info-item .label {
        color: #94a3b8;
        font-weight: 600;
    }

    .info-item .value {
        color: #ffffff;
        font-weight: 700;
    }

    .status-active {
        color: #10b981 !important;
        text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
    }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1.5rem;
    }

    .action-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
        padding: 1.5rem;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(139, 92, 246, 0.1));
        border: 1px solid rgba(59, 130, 246, 0.3);
        border-radius: 1rem;
        color: #3b82f6;
        text-decoration: none;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 600;
        position: relative;
        overflow: hidden;
    }

    .action-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        transition: left 0.5s ease;
    }

    .action-btn:hover::before {
        left: 100%;
    }

    .action-btn:hover {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.25), rgba(139, 92, 246, 0.15));
        transform: translateY(-6px) scale(1.05);
        box-shadow: 0 12px 40px rgba(59, 130, 246, 0.2);
        border-color: rgba(59, 130, 246, 0.5);
    }

    .action-btn i {
        font-size: 2rem;
        transition: transform 0.3s ease;
    }

    .action-btn:hover i {
        transform: scale(1.2) rotate(5deg);
    }

    .form-card {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 1rem;
        padding: 2.5rem;
        transition: all 0.3s ease;
    }

    .form-card:hover {
        box-shadow: 0 8px 30px rgba(59, 130, 246, 0.1);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .form-group label {
        font-weight: 700;
        color: #e2e8f0;
        font-size: 1.1rem;
    }

    .form-group input,
    .form-group select {
        padding: 1rem;
        border: 2px solid rgba(255, 255, 255, 0.2);
        border-radius: 0.75rem;
        background: rgba(255, 255, 255, 0.05);
        color: #ffffff;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        background: rgba(255, 255, 255, 0.08);
        transform: translateY(-2px);
    }

    .form-group small {
        color: #94a3b8;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .activity-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
    }

    .activity-card {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 1rem;
        padding: 2rem;
        transition: all 0.3s ease;
    }

    .activity-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(59, 130, 246, 0.1);
    }

    .activity-card h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 1.5rem 0;
        color: #ffffff;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .activity-content p {
        color: #94a3b8;
        margin-bottom: 1.5rem;
        font-size: 1.1rem;
        line-height: 1.6;
    }

    .preferences-card {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.08), rgba(255, 255, 255, 0.02));
        border: 1px solid rgba(59, 130, 246, 0.2);
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
        transition: all 0.3s ease;
    }

    .preferences-card:hover {
        box-shadow: 0 8px 30px rgba(59, 130, 246, 0.1);
    }

    .preferences-card h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 1.5rem 0;
        color: #ffffff;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .preference-item {
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }

    .preference-item:hover {
        background: rgba(255, 255, 255, 0.08);
        transform: translateX(5px);
    }

    .preference-item label {
        display: flex;
        align-items: center;
        gap: 1rem;
        color: #e2e8f0;
        cursor: pointer;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .preference-item input[type="checkbox"] {
        width: 1.5rem;
        height: 1.5rem;
        accent-color: #3b82f6;
        transform: scale(1.2);
    }

    .alert {
        padding: 1.5rem;
        border-radius: 1rem;
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        font-weight: 600;
        font-size: 1.1rem;
        border: 2px solid;
        animation: slideIn 0.5s ease-out;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }

    .alert-success {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.05));
        border-color: rgba(16, 185, 129, 0.4);
        color: #10b981;
    }

    .alert-error {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.05));
        border-color: rgba(239, 68, 68, 0.4);
        color: #ef4444;
    }

    .btn-danger {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.05));
        border: 2px solid rgba(239, 68, 68, 0.4);
        color: #ef4444;
        padding: 1rem 2rem;
        border-radius: 0.75rem;
        font-weight: 700;
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }

    .btn-danger:hover {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.25), rgba(239, 68, 68, 0.1));
        border-color: rgba(239, 68, 68, 0.6);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.2);
    }

    /* Badge Styles */
    .badge {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        border-radius: 0.375rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }

    .badge-primary {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
    }

    .badge-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .badge-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }

    .badge-warning {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
    }

    .text-success {
        color: #10b981 !important;
    }

    .text-danger {
        color: #ef4444 !important;
    }

    .alert-warning {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.15), rgba(245, 158, 11, 0.05));
        border-color: rgba(245, 158, 11, 0.4);
        color: #f59e0b;
    }

    /* Enhanced spacing for footer */
    .dashboard-layout {
        margin-bottom: 6rem;
    }

    @media (max-width: 768px) {
        .dashboard-layout {
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin-bottom: 4rem;
        }

        .dashboard-sidebar {
            order: 2;
            position: static;
        }

        .dashboard-content {
            order: 1;
        }

        .sidebar-nav {
            flex-direction: row;
            overflow-x: auto;
            gap: 0.5rem;
            padding-bottom: 0.5rem;
            /* Hide scroll indicators */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* Internet Explorer 10+ */
        }
        
        .sidebar-nav::-webkit-scrollbar {
            display: none; /* WebKit browsers (Chrome, Safari, Edge) */
        }

        .nav-item {
            white-space: nowrap;
            min-width: fit-content;
            padding: 0.75rem 1rem;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .info-cards {
            grid-template-columns: 1fr;
        }

        .quick-actions {
            grid-template-columns: repeat(2, 1fr);
        }

        .dashboard-welcome h1 {
            font-size: 2rem;
        }

        .section-header h2 {
            font-size: 1.5rem;
        }
    }
    </style>

    <main id="main-content" role="main" style="padding-top: 0;">
        <div class="container">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <div class="dashboard-welcome">
                    <h1>Welcome back, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</h1>
                    <p>Manage your account, track your progress, and customize your experience.</p>
                </div>
                <div class="dashboard-actions">
                    <a href="./tools.php" class="btn btn-primary">
                        <i class="bi bi-tools"></i> Access Tools
                    </a>
                </div>
            </div>

            <!-- Dashboard Layout -->
            <div class="dashboard-layout">
                <!-- Sidebar Navigation -->
                <div class="dashboard-sidebar">
                    <nav class="sidebar-nav">
                        <a href="#overview" class="nav-item active" data-section="overview">
                            <i class="bi bi-speedometer2"></i>
                            <span>Overview</span>
                        </a>
                        <a href="#profile" class="nav-item" data-section="profile">
                            <i class="bi bi-person-gear"></i>
                            <span>Profile</span>
                        </a>
                        <a href="#security" class="nav-item" data-section="security">
                            <i class="bi bi-shield-lock"></i>
                            <span>Security</span>
                        </a>
                        <a href="#activity" class="nav-item" data-section="activity">
                            <i class="bi bi-graph-up"></i>
                            <span>Activity</span>
                        </a>
                        <a href="#trading-profiles" class="nav-item" data-section="trading-profiles">
                            <i class="bi bi-person-badge"></i>
                            <span>Trading Profiles</span>
                        </a>
                        <a href="#risk-management" class="nav-item" data-section="risk-management">
                            <i class="bi bi-shield-check"></i>
                            <span>Risk Management</span>
                        </a>
                        <a href="#portfolio" class="nav-item" data-section="portfolio">
                            <i class="bi bi-graph-up-arrow"></i>
                            <span>Portfolio</span>
                        </a>
                        <a href="#preferences" class="nav-item" data-section="preferences">
                            <i class="bi bi-gear"></i>
                            <span>Preferences</span>
                        </a>
                    </nav>
                </div>

                <!-- Main Content Area -->
                <div class="dashboard-content">
                    <!-- Messages -->
                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Overview Section -->
                    <div class="dashboard-section active" id="overview">
                        <div class="section-header">
                            <h2><i class="bi bi-speedometer2"></i> Dashboard Overview</h2>
                            <p>Your account summary and recent activity</p>
                        </div>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo $userStats ? max(1, floor((time() - strtotime($userStats['member_since'])) / 86400)) : 1; ?></div>
                                    <div class="stat-label">Days Active</div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="bi bi-tools"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo $userStats['tools_used'] ?? 0; ?></div>
                                    <div class="stat-label">Tools Used</div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="bi bi-book"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo $userStats['content_accessed'] ?? 0; ?></div>
                                    <div class="stat-label">Content Accessed</div>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="bi bi-trophy"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo $userStats['content_completed'] ?? 0; ?></div>
                                    <div class="stat-label">Completed</div>
                                </div>
                            </div>
                        </div>

                        <div class="info-cards">
                            <div class="info-card">
                                <h3><i class="bi bi-info-circle"></i> Account Information</h3>
                                <div class="info-list">
                                    <div class="info-item">
                                        <span class="label">Member Since:</span>
                                        <span class="value"><?php echo date('F j, Y', strtotime($userStats['member_since'] ?? $currentUser['created_at'] ?? 'now')); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Last Login:</span>
                                        <span class="value"><?php echo date('F j, Y g:i A', strtotime($userStats['last_login'] ?? $currentUser['last_login'] ?? 'now')); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Status:</span>
                                        <span class="value status-active"><?php echo $currentUser['is_active'] ? 'Active' : 'Inactive'; ?></span>
                                    </div>
                                    <div class="info-item">
                                        <span class="label">Total Tool Usage:</span>
                                        <span class="value"><?php echo $userStats['total_tool_usage_time'] ? gmdate('H:i:s', $userStats['total_tool_usage_time']) : '0:00:00'; ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="info-card">
                                <h3><i class="bi bi-lightning"></i> Quick Actions</h3>
                                <div class="quick-actions">
                                    <a href="./tools.php" class="action-btn">
                                        <i class="bi bi-tools"></i>
                                        <span>Access Tools</span>
                                    </a>
                                    <a href="./riskmanagement.php" class="action-btn">
                                        <i class="bi bi-shield-check"></i>
                                        <span>Risk Management</span>
                                    </a>
                                    <a href="#profile" class="action-btn" onclick="showSection('profile')">
                                        <i class="bi bi-person-gear"></i>
                                        <span>Edit Profile</span>
                                    </a>
                                    <a href="./contact.php" class="action-btn">
                                        <i class="bi bi-envelope"></i>
                                        <span>Contact Support</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Section -->
                    <div class="dashboard-section" id="profile">
                        <div class="section-header">
                            <h2><i class="bi bi-person-gear"></i> Profile Information</h2>
                            <p>Update your personal information and account details</p>
                        </div>
                        
                        <div class="form-card">
                            <form method="POST" action="" class="profile-form">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="full_name">Full Name</label>
                                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($currentUser['full_name']); ?>" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Profile
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Security Section -->
                    <div class="dashboard-section" id="security">
                        <div class="section-header">
                            <h2><i class="bi bi-shield-lock"></i> Security Settings</h2>
                            <p>Manage your password and security preferences</p>
                        </div>
                        
                        <div class="form-card">
                            <form method="POST" action="" class="profile-form">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="form-group">
                                    <label for="old_password">Current Password</label>
                                    <input type="password" id="old_password" name="old_password" required>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="new_password">New Password</label>
                                        <input type="password" id="new_password" name="new_password" minlength="8" required>
                                        <small>Password must be at least 8 characters long</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirm_password">Confirm New Password</label>
                                        <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-key"></i> Change Password
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Activity Section -->
                    <div class="dashboard-section" id="activity">
                        <div class="section-header">
                            <h2><i class="bi bi-graph-up"></i> Activity & Progress</h2>
                            <p>Track your learning progress and tool usage</p>
                        </div>
                        
                        <div class="activity-cards">
                            <div class="activity-card">
                                <h3><i class="bi bi-tools"></i> Tool Usage</h3>
                                <div class="activity-content">
                                    <p>No tools used yet. Start exploring our trading tools!</p>
                                    <a href="./tools.php" class="btn btn-secondary">Explore Tools</a>
                                </div>
                            </div>
                            
                            <div class="activity-card">
                                <h3><i class="bi bi-book"></i> Learning Progress</h3>
                                <div class="activity-content">
                                    <p>No learning progress yet. Begin your trading education journey!</p>
                                    <a href="./tools.php" class="btn btn-secondary">Start Learning</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trading Profiles Section -->
                    <div class="dashboard-section" id="trading-profiles">
                        <div class="section-header">
                            <h2><i class="bi bi-person-badge"></i> Trading Profiles</h2>
                            <p>Manage your trading profiles and risk settings</p>
                        </div>
                        
                        <div class="info-cards">
                            <?php if (empty($tradingProfiles)): ?>
                                <div class="info-card">
                                    <h3><i class="bi bi-plus-circle"></i> Create Your First Trading Profile</h3>
                                    <div class="activity-content">
                                        <p>Set up your trading profile to start using our risk management tools effectively.</p>
                                        <button class="btn btn-primary" onclick="showCreateProfileModal()">
                                            <i class="bi bi-plus"></i> Create Profile
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($tradingProfiles as $profile): ?>
                                    <div class="info-card">
                                        <h3>
                                            <i class="bi bi-person-badge"></i> 
                                            <?php echo htmlspecialchars($profile['profile_name']); ?>
                                            <?php if ($profile['is_default']): ?>
                                                <span class="badge badge-primary">Default</span>
                                            <?php endif; ?>
                                        </h3>
                                        <div class="info-list">
                                            <div class="info-item">
                                                <span class="label">Account Size:</span>
                                                <span class="value">₹<?php echo number_format($profile['account_size'], 2); ?></span>
                                            </div>
                                            <div class="info-item">
                                                <span class="label">Risk per Trade:</span>
                                                <span class="value"><?php echo $profile['risk_percentage']; ?>%</span>
                                            </div>
                                            <div class="info-item">
                                                <span class="label">Max Daily Loss:</span>
                                                <span class="value"><?php echo $profile['max_daily_loss_percentage']; ?>%</span>
                                            </div>
                                            <div class="info-item">
                                                <span class="label">Trading Style:</span>
                                                <span class="value"><?php echo ucwords(str_replace('_', ' ', $profile['preferred_trading_style'])); ?></span>
                                            </div>
                                            <div class="info-item">
                                                <span class="label">Risk Tolerance:</span>
                                                <span class="value"><?php echo ucwords($profile['risk_tolerance']); ?></span>
                                            </div>
                                        </div>
                                        <div class="quick-actions" style="margin-top: 1rem;">
                                            <button class="btn btn-secondary" onclick="editProfile(<?php echo $profile['id']; ?>)">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <?php if (!$profile['is_default']): ?>
                                                <button class="btn btn-primary" onclick="setDefaultProfile(<?php echo $profile['id']; ?>)">
                                                    <i class="bi bi-star"></i> Set Default
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="info-card">
                                    <h3><i class="bi bi-plus-circle"></i> Add New Profile</h3>
                                    <div class="activity-content">
                                        <p>Create additional trading profiles for different strategies or market conditions.</p>
                                        <button class="btn btn-primary" onclick="showCreateProfileModal()">
                                            <i class="bi bi-plus"></i> Create Profile
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Risk Management Section -->
                    <div class="dashboard-section" id="risk-management">
                        <div class="section-header">
                            <h2><i class="bi bi-shield-check"></i> Risk Management</h2>
                            <p>Monitor your risk metrics and alerts</p>
                        </div>
                        
                        <div class="stats-grid">
                            <?php if ($riskMetrics): ?>
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="bi bi-graph-up"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo $riskMetrics['win_rate']; ?>%</div>
                                        <div class="stat-label">Win Rate</div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="bi bi-currency-dollar"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo $riskMetrics['profit_factor']; ?></div>
                                        <div class="stat-label">Profit Factor</div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="bi bi-arrow-down"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo $riskMetrics['max_drawdown']; ?>%</div>
                                        <div class="stat-label">Max Drawdown</div>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="bi bi-shield-exclamation"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-number"><?php echo $riskMetrics['total_risk']; ?>%</div>
                                        <div class="stat-label">Current Risk</div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="info-card" style="grid-column: 1 / -1;">
                                    <h3><i class="bi bi-info-circle"></i> No Risk Data Available</h3>
                                    <div class="activity-content">
                                        <p>Start trading to see your risk metrics and performance analytics.</p>
                                        <a href="./tools.php" class="btn btn-primary">
                                            <i class="bi bi-tools"></i> Start Trading
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="info-cards">
                            <div class="info-card">
                                <h3><i class="bi bi-exclamation-triangle"></i> Risk Alerts</h3>
                                <div class="activity-content">
                                    <?php 
                                    $riskAlerts = getRiskAlerts($currentUser['id'], 5);
                                    if (empty($riskAlerts)): 
                                    ?>
                                        <p>No active risk alerts. Your trading is within safe limits.</p>
                                    <?php else: ?>
                                        <?php foreach ($riskAlerts as $alert): ?>
                                            <div class="alert alert-<?php echo $alert['severity'] === 'critical' ? 'error' : 'warning'; ?>">
                                                <i class="bi bi-exclamation-triangle"></i>
                                                <div>
                                                    <strong><?php echo ucwords(str_replace('_', ' ', $alert['alert_type'])); ?></strong>
                                                    <p><?php echo htmlspecialchars($alert['alert_message']); ?></p>
                                                </div>
                                                <button class="btn btn-sm btn-secondary" onclick="acknowledgeAlert(<?php echo $alert['id']; ?>)">
                                                    Dismiss
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <h3><i class="bi bi-graph-up"></i> Performance Summary</h3>
                                <div class="info-list">
                                    <?php if ($riskMetrics): ?>
                                        <div class="info-item">
                                            <span class="label">Total Trades:</span>
                                            <span class="value"><?php echo $riskMetrics['total_trades']; ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="label">Winning Trades:</span>
                                            <span class="value"><?php echo $riskMetrics['winning_trades']; ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="label">Losing Trades:</span>
                                            <span class="value"><?php echo $riskMetrics['losing_trades']; ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="label">Average Win:</span>
                                            <span class="value">₹<?php echo number_format($riskMetrics['avg_win'], 2); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="label">Average Loss:</span>
                                            <span class="value">₹<?php echo number_format($riskMetrics['avg_loss'], 2); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <p>No performance data available yet.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Portfolio Section -->
                    <div class="dashboard-section" id="portfolio">
                        <div class="section-header">
                            <h2><i class="bi bi-graph-up-arrow"></i> Portfolio Overview</h2>
                            <p>Track your current positions and portfolio performance</p>
                        </div>
                        
                        <div class="info-cards">
                            <div class="info-card">
                                <h3><i class="bi bi-briefcase"></i> Current Positions</h3>
                                <div class="activity-content">
                                    <?php if (empty($portfolioPositions)): ?>
                                        <p>No active positions. Start trading to build your portfolio!</p>
                                        <a href="./tools.php" class="btn btn-primary">
                                            <i class="bi bi-tools"></i> Start Trading
                                        </a>
                                    <?php else: ?>
                                        <div class="info-list">
                                            <?php foreach ($portfolioPositions as $position): ?>
                                                <div class="info-item">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($position['symbol']); ?></strong>
                                                        <span class="badge badge-<?php echo $position['side'] === 'Long' ? 'success' : 'danger'; ?>">
                                                            <?php echo $position['side']; ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="label">Qty:</span>
                                                        <span class="value"><?php echo $position['quantity']; ?></span>
                                                        <span class="label">P&L:</span>
                                                        <span class="value <?php echo $position['unrealized_pnl'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                            ₹<?php echo number_format($position['unrealized_pnl'], 2); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="info-card">
                                <h3><i class="bi bi-clock-history"></i> Recent Trades</h3>
                                <div class="activity-content">
                                    <?php if (empty($recentTrades)): ?>
                                        <p>No recent trades. Your trading history will appear here.</p>
                                    <?php else: ?>
                                        <div class="info-list">
                                            <?php foreach ($recentTrades as $trade): ?>
                                                <div class="info-item">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($trade['symbol']); ?></strong>
                                                        <span class="badge badge-<?php echo $trade['side'] === 'Long' ? 'success' : 'danger'; ?>">
                                                            <?php echo $trade['side']; ?>
                                                        </span>
                                                        <span class="badge badge-<?php echo $trade['trade_status'] === 'closed' ? 'primary' : 'warning'; ?>">
                                                            <?php echo ucwords($trade['trade_status']); ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="label">Date:</span>
                                                        <span class="value"><?php echo date('M j', strtotime($trade['trade_date'])); ?></span>
                                                        <?php if ($trade['pnl'] !== null): ?>
                                                            <span class="label">P&L:</span>
                                                            <span class="value <?php echo $trade['pnl'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                                ₹<?php echo number_format($trade['pnl'], 2); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Preferences Section -->
                    <div class="dashboard-section" id="preferences">
                        <div class="section-header">
                            <h2><i class="bi bi-gear"></i> Preferences & Settings</h2>
                            <p>Customize your experience and account settings</p>
                        </div>
                        
                        <div class="preferences-card">
                            <h3><i class="bi bi-bell"></i> Notifications</h3>
                            <div class="preference-item">
                                <label>
                                    <input type="checkbox" checked>
                                    <span>Email notifications for new content</span>
                                </label>
                            </div>
                            <div class="preference-item">
                                <label>
                                    <input type="checkbox" checked>
                                    <span>Weekly progress reports</span>
                                </label>
                            </div>
                            <div class="preference-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Marketing communications</span>
                                </label>
                            </div>
                        </div>

                        <div class="preferences-card">
                            <h3><i class="bi bi-gear-fill"></i> Account Settings</h3>
                            <div class="preference-item">
                                <label>
                                    <input type="checkbox" checked>
                                    <span>Enable email notifications</span>
                                </label>
                            </div>
                            <div class="preference-item">
                                <label>
                                    <input type="checkbox" checked>
                                    <span>Show activity reminders</span>
                                </label>
                            </div>
                            <div class="preference-item">
                                <label>
                                    <input type="checkbox">
                                    <span>Auto-save form data</span>
                                </label>
                            </div>
                        </div>

                        <div class="account-actions">
                            <button onclick="logout()" class="btn btn-danger">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="./assets/app.js"></script>
    
    <script>
    // Enhanced Dashboard Navigation
    class DashboardManager {
        constructor() {
            this.currentSection = 'overview';
            this.init();
        }

        init() {
            this.setupNavigation();
            this.setupFormValidation();
            this.setupAnimations();
            this.setupKeyboardNavigation();
        }

        setupNavigation() {
            document.querySelectorAll('.nav-item').forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    const sectionId = item.getAttribute('data-section');
                    this.showSection(sectionId);
                });
            });

            // Handle quick action buttons
            document.querySelectorAll('.action-btn[onclick*="showSection"]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const sectionId = btn.getAttribute('onclick').match(/showSection\('(\w+)'\)/)[1];
                    this.showSection(sectionId);
                });
            });
        }

        showSection(sectionId) {
            if (this.currentSection === sectionId) return;

            // Add loading state
            const content = document.querySelector('.dashboard-content');
            content.style.opacity = '0.7';
            content.style.pointerEvents = 'none';

            setTimeout(() => {
                // Hide all sections with animation
                document.querySelectorAll('.dashboard-section').forEach(section => {
                    section.classList.remove('active');
                });
                
                // Remove active class from all nav items
                document.querySelectorAll('.nav-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                // Show selected section
                const targetSection = document.getElementById(sectionId);
                if (targetSection) {
                    targetSection.classList.add('active');
                    
                    // Add active class to clicked nav item
                    const navItem = document.querySelector(`[data-section="${sectionId}"]`);
                    if (navItem) {
                        navItem.classList.add('active');
                    }
                }

                this.currentSection = sectionId;

                // Remove loading state
                content.style.opacity = '1';
                content.style.pointerEvents = 'auto';

                // Update URL hash
                window.history.pushState(null, null, `#${sectionId}`);
            }, 150);
        }

        setupFormValidation() {
            // Profile form validation
            const profileForm = document.querySelector('form[action=""][method="POST"]');
            if (profileForm) {
                profileForm.addEventListener('submit', (e) => {
                    if (!this.validateProfileForm()) {
                        e.preventDefault();
                    }
                });
            }

            // Password form validation
            const passwordForm = document.querySelector('form input[name="old_password"]')?.closest('form');
            if (passwordForm) {
                passwordForm.addEventListener('submit', (e) => {
                    if (!this.validatePasswordForm()) {
                        e.preventDefault();
                    }
                });

                // Real-time password confirmation
                const newPassword = document.getElementById('new_password');
                const confirmPassword = document.getElementById('confirm_password');
                
                if (newPassword && confirmPassword) {
                    confirmPassword.addEventListener('input', () => {
                        this.validatePasswordMatch();
                    });
                }
            }
        }

        validateProfileForm() {
            const username = document.getElementById('username');
            const email = document.getElementById('email');
            const fullName = document.getElementById('full_name');
            
            let isValid = true;

            // Clear previous errors
            this.clearFieldErrors();

            if (!username.value.trim()) {
                this.showFieldError(username, 'Username is required');
                isValid = false;
            }

            if (!email.value.trim()) {
                this.showFieldError(email, 'Email is required');
                isValid = false;
            } else if (!this.isValidEmail(email.value)) {
                this.showFieldError(email, 'Please enter a valid email address');
                isValid = false;
            }

            if (!fullName.value.trim()) {
                this.showFieldError(fullName, 'Full name is required');
                isValid = false;
            }

            return isValid;
        }

        validatePasswordForm() {
            const oldPassword = document.getElementById('old_password');
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            let isValid = true;

            // Clear previous errors
            this.clearFieldErrors();

            if (!oldPassword.value.trim()) {
                this.showFieldError(oldPassword, 'Current password is required');
                isValid = false;
            }

            if (!newPassword.value.trim()) {
                this.showFieldError(newPassword, 'New password is required');
                isValid = false;
            } else if (newPassword.value.length < 8) {
                this.showFieldError(newPassword, 'Password must be at least 8 characters long');
                isValid = false;
            }

            if (!confirmPassword.value.trim()) {
                this.showFieldError(confirmPassword, 'Please confirm your new password');
                isValid = false;
            } else if (newPassword.value !== confirmPassword.value) {
                this.showFieldError(confirmPassword, 'Passwords do not match');
                isValid = false;
            }

            return isValid;
        }

        validatePasswordMatch() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword.value && confirmPassword.value) {
                if (newPassword.value === confirmPassword.value) {
                    this.showFieldSuccess(confirmPassword, 'Passwords match');
                } else {
                    this.showFieldError(confirmPassword, 'Passwords do not match');
                }
            }
        }

        showFieldError(field, message) {
            field.style.borderColor = '#ef4444';
            field.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.1)';
            
            // Remove existing error message
            const existingError = field.parentNode.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }
            
            // Add error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            errorDiv.style.color = '#ef4444';
            errorDiv.style.fontSize = '0.875rem';
            errorDiv.style.marginTop = '0.25rem';
            errorDiv.textContent = message;
            field.parentNode.appendChild(errorDiv);
        }

        showFieldSuccess(field, message) {
            field.style.borderColor = '#10b981';
            field.style.boxShadow = '0 0 0 4px rgba(16, 185, 129, 0.1)';
            
            // Remove existing messages
            const existingMessage = field.parentNode.querySelector('.field-error, .field-success');
            if (existingMessage) {
                existingMessage.remove();
            }
            
            // Add success message
            const successDiv = document.createElement('div');
            successDiv.className = 'field-success';
            successDiv.style.color = '#10b981';
            successDiv.style.fontSize = '0.875rem';
            successDiv.style.marginTop = '0.25rem';
            successDiv.textContent = message;
            field.parentNode.appendChild(successDiv);
        }

        clearFieldErrors() {
            document.querySelectorAll('.field-error, .field-success').forEach(error => {
                error.remove();
            });
            
            document.querySelectorAll('input, select').forEach(field => {
                field.style.borderColor = '';
                field.style.boxShadow = '';
            });
        }

        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        setupAnimations() {
            // Add entrance animations to cards
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animation = 'fadeInUp 0.6s ease-out forwards';
                    }
                });
            }, { threshold: 0.1 });

            document.querySelectorAll('.stat-card, .info-card, .activity-card, .preferences-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                observer.observe(card);
            });
        }

        setupKeyboardNavigation() {
            document.addEventListener('keydown', (e) => {
                if (e.altKey) {
                    switch(e.key) {
                        case '1':
                            e.preventDefault();
                            this.showSection('overview');
                            break;
                        case '2':
                            e.preventDefault();
                            this.showSection('profile');
                            break;
                        case '3':
                            e.preventDefault();
                            this.showSection('security');
                            break;
                        case '4':
                            e.preventDefault();
                            this.showSection('activity');
                            break;
                        case '5':
                            e.preventDefault();
                            this.showSection('trading-profiles');
                            break;
                        case '6':
                            e.preventDefault();
                            this.showSection('risk-management');
                            break;
                        case '7':
                            e.preventDefault();
                            this.showSection('portfolio');
                            break;
                        case '8':
                            e.preventDefault();
                            this.showSection('preferences');
                            break;
                    }
                }
            });
        }
    }

    // Initialize dashboard when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        new DashboardManager();
        
        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(e) {
            const hash = window.location.hash.substring(1);
            if (hash && document.getElementById(hash)) {
                const dashboard = new DashboardManager();
                dashboard.showSection(hash);
            }
        });

        // Set initial section from URL hash
        const initialHash = window.location.hash.substring(1);
        if (initialHash && document.getElementById(initialHash)) {
            const dashboard = new DashboardManager();
            dashboard.showSection(initialHash);
        }
    });

    // Trading Profile Functions
    function showCreateProfileModal() {
        // Create modal for profile creation
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Create Trading Profile</h3>
                    <button class="modal-close" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="profileForm">
                        <div class="form-group">
                            <label for="profile_name">Profile Name</label>
                            <input type="text" id="profile_name" name="profile_name" required>
                        </div>
                        <div class="form-group">
                            <label for="account_size">Account Size (₹)</label>
                            <input type="number" id="account_size" name="account_size" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="risk_percentage">Risk per Trade (%)</label>
                            <input type="number" id="risk_percentage" name="risk_percentage" step="0.01" value="2" required>
                        </div>
                        <div class="form-group">
                            <label for="max_daily_loss_percentage">Max Daily Loss (%)</label>
                            <input type="number" id="max_daily_loss_percentage" name="max_daily_loss_percentage" step="0.01" value="5" required>
                        </div>
                        <div class="form-group">
                            <label for="max_open_risk_percentage">Max Open Risk (%)</label>
                            <input type="number" id="max_open_risk_percentage" name="max_open_risk_percentage" step="0.01" value="10" required>
                        </div>
                        <div class="form-group">
                            <label for="preferred_trading_style">Trading Style</label>
                            <select id="preferred_trading_style" name="preferred_trading_style" required>
                                <option value="scalping">Scalping</option>
                                <option value="day_trading">Day Trading</option>
                                <option value="swing_trading" selected>Swing Trading</option>
                                <option value="position_trading">Position Trading</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="risk_tolerance">Risk Tolerance</label>
                            <select id="risk_tolerance" name="risk_tolerance" required>
                                <option value="conservative">Conservative</option>
                                <option value="moderate" selected>Moderate</option>
                                <option value="aggressive">Aggressive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="max_positions">Max Positions</label>
                            <input type="number" id="max_positions" name="max_positions" value="5" required>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="is_default" name="is_default">
                                Set as Default Profile
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button class="btn btn-primary" onclick="saveProfile()">Save Profile</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    function closeModal() {
        const modal = document.querySelector('.modal-overlay');
        if (modal) {
            modal.remove();
        }
    }

    function saveProfile() {
        const form = document.getElementById('profileForm');
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Convert checkbox to boolean
        data.is_default = document.getElementById('is_default').checked;
        
        // Send AJAX request to save profile
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'save_trading_profile',
                ...data
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                closeModal();
                location.reload(); // Refresh to show new profile
            } else {
                alert('Error saving profile: ' + result.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving profile');
        });
    }

    function editProfile(profileId) {
        // Similar to create but with existing data
        alert('Edit profile functionality coming soon!');
    }

    function setDefaultProfile(profileId) {
        if (confirm('Set this as your default trading profile?')) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'set_default_profile',
                    profile_id: profileId
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error setting default profile');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error setting default profile');
            });
        }
    }

    function acknowledgeAlert(alertId) {
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'acknowledge_alert',
                alert_id: alertId
            })
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                location.reload();
            } else {
                alert('Error acknowledging alert');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error acknowledging alert');
        });
    }

    // Add CSS for animations and modal
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .field-error, .field-success {
            animation: slideIn 0.3s ease-out;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .modal-content {
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.9));
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 1rem;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            /* Hide scroll indicators */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* Internet Explorer 10+ */
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
        }

        .modal-header h3 {
            margin: 0;
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1.5rem;
            border-top: 1px solid rgba(59, 130, 246, 0.2);
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>
