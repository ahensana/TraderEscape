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

    // Add CSS for animations
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
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>
