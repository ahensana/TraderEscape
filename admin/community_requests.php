<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

require_once __DIR__ . '/../includes/community_functions.php';

$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];

$message = "";
$error = "";

// Get messages from session (for redirects)
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    unset($_SESSION['admin_message']);
}
if (isset($_SESSION['admin_error'])) {
    $error = $_SESSION['admin_error'];
    unset($_SESSION['admin_error']);
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $requestId = (int)$_POST['request_id'];
        $adminNotes = trim($_POST['admin_notes'] ?? '');
        
        if ($_POST['action'] == 'approve') {
            $result = approveCommunityRequest($requestId, $admin_id, $adminNotes);
            if ($result['success']) {
                $_SESSION['admin_message'] = $result['message'];
            } else {
                $_SESSION['admin_error'] = $result['message'];
            }
            // Redirect to prevent browser refresh warning
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } elseif ($_POST['action'] == 'reject') {
            $result = rejectCommunityRequest($requestId, $admin_id, $adminNotes);
            if ($result['success']) {
                $_SESSION['admin_message'] = $result['message'];
            } else {
                $_SESSION['admin_error'] = $result['message'];
            }
            // Redirect to prevent browser refresh warning
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } elseif ($_POST['action'] == 'remove_user') {
            $userId = (int)$_POST['user_id'];
            $result = removeUserFromCommunity($userId, $admin_id, $adminNotes);
            if ($result['success']) {
                $_SESSION['admin_message'] = $result['message'];
            } else {
                $_SESSION['admin_error'] = $result['message'];
            }
            // Redirect to prevent browser refresh warning
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Get community statistics
$stats = getCommunityStats();

// Get all community requests
$requests = getAllCommunityRequests(100);

// Get all community users
$communityUsers = getAllCommunityUsers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Requests - The Trader's Escape</title>
    <link rel="icon" type="image/png" href="../assets/logo.png">
    
    <!-- Trading Background Script -->
    <script src="../assets/trading-background.js" defer></script>
    
    <style>
        /* ===== ESSENTIAL STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        *::-webkit-scrollbar {
            display: none;
        }

        html, body {
            margin: 0 !important;
            padding: 0 !important;
            top: 0 !important;
            height: 100%;
        }

        body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0f172a;
            color: #ffffff;
            line-height: 1.6;
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* ===== TRADING BACKGROUND ===== */
        .trading-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            min-height: 100%;
            z-index: -1;
            pointer-events: none;
            overflow: hidden;
            will-change: transform;
            transform: translateZ(0);
            backface-visibility: hidden;
            perspective: 1000px;
        }

        .bg-grid {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            min-height: 100vh;
            background-image: linear-gradient(
                rgba(59, 130, 246, 0.1) 1px,
                transparent 1px
            ),
            linear-gradient(90deg, rgba(59, 130, 246, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            background-repeat: repeat;
            opacity: 0.3;
        }

        .bg-particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            min-height: 100vh;
        }

        .bg-glow {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            min-height: 100vh;
            background: radial-gradient(
                circle at 20% 80%,
                rgba(59, 130, 246, 0.1) 0%,
                transparent 50%
            ),
            radial-gradient(
                circle at 80% 20%,
                rgba(147, 51, 234, 0.1) 0%,
                transparent 50%
            ),
            radial-gradient(
                circle at 40% 40%,
                rgba(16, 185, 129, 0.05) 0%,
                transparent 50%
            );
        }


        /* ===== ADMIN HEADER ===== */
        .admin-header {
            background: rgba(15, 23, 42, 0.95);
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .admin-header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-logo img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .admin-logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .admin-nav {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, 0.8);
        }

        .admin-user-avatar {
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
        }

        .logout-btn {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.5);
            color: #fca5a5;
        }

        .nav-btn {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .nav-btn:hover {
            background: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.5);
            color: #60a5fa;
            transform: translateY(-1px);
        }

        /* ===== MAIN CONTENT ===== */
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .admin-welcome {
            text-align: center;
            margin-bottom: 40px;
        }

        .admin-welcome h2 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6, #06b6d4);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .admin-welcome p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.1rem;
        }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 16px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(59, 130, 246, 0.4);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-title {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stat-icon {
            width: 35px;
            height: 35px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
        }

        .stat-icon.requests { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .stat-icon.pending { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .stat-icon.approved { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .stat-icon.community { background: rgba(147, 51, 234, 0.2); color: #a78bfa; }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
        }

        /* ===== REQUESTS TABLE ===== */
        .requests-container {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 16px;
            padding: 25px;
            position: relative;
            overflow: hidden;
        }

        .requests-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .requests-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: white;
        }

        .requests-table {
            width: 100%;
            border-collapse: collapse;
        }

        .requests-table th {
            text-align: left;
            padding: 15px 0;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            font-weight: 500;
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
        }

        .requests-table td {
            padding: 15px 0;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(59, 130, 246, 0.1);
        }

        .requests-table tr:hover td {
            color: white;
            background: rgba(59, 130, 246, 0.05);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }

        .status-approved {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-approve {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-approve:hover {
            background: rgba(16, 185, 129, 0.3);
            color: #34d399;
        }

        .btn-reject {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-reject:hover {
            background: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        /* ===== MESSAGES ===== */
        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .message-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .message-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .admin-header-content {
                flex-direction: column;
                gap: 15px;
            }

            .admin-nav {
                flex-direction: column;
                gap: 10px;
            }

            .admin-welcome h2 {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .requests-table {
                font-size: 0.8rem;
            }

            .requests-table th,
            .requests-table td {
                padding: 10px 0;
            }
        }

        /* ===== MODAL STYLES ===== */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .modal-content {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(59, 130, 246, 0.2);
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow: hidden;
            position: relative;
            z-index: 1001;
        }

        .modal-header {
            padding: 24px 24px 16px;
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header h3 {
            color: #ffffff;
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.7);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        .modal-body {
            padding: 24px;
            max-height: calc(80vh - 120px);
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #ffffff;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            color: #ffffff;
            font-size: 0.9rem;
            resize: vertical;
            min-height: 80px;
            font-family: inherit;
            transition: all 0.2s ease;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid rgba(59, 130, 246, 0.2);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border: none;
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
            transform: translateY(-1px);
        }

        .btn-reject {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: none;
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-reject:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
            transform: translateY(-1px);
        }

        .btn-remove {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            border: none;
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .btn-remove:hover {
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            box-shadow: 0 6px 16px rgba(245, 158, 11, 0.4);
            transform: translateY(-1px);
        }

        /* Mobile responsive for modal */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 20px;
                max-height: 90vh;
            }

            .modal-header {
                padding: 20px 20px 12px;
            }

            .modal-body {
                padding: 20px;
                max-height: calc(90vh - 100px);
            }

            .form-actions {
                flex-direction: column;
                gap: 8px;
            }

            .btn-secondary,
            .btn-primary,
            .btn-reject {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Trading Background -->
    <div class="trading-background">
        <div class="bg-grid"></div>
        <div class="bg-particles"></div>
        <div class="bg-glow"></div>
    </div>

    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-logo">
                <img src="../assets/logo.png" alt="The Trader's Escape Logo">
                <h1>Community Requests</h1>
            </div>
            <nav class="admin-nav">
                <div class="admin-user">
                    <div class="admin-user-avatar">
                        <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                    </div>
                    <span>Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
                </div>
                <a href="admin_dashboard.php" class="nav-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    </svg>
                    Dashboard
                </a>
                <a href="admin_management.php" class="nav-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    Manage Admins
                </a>
                <a href="#" class="logout-btn" onclick="confirmLogout()">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="admin-container">
        <!-- Welcome Section -->
        <section class="admin-welcome">
            <h2>Community Requests Management</h2>
            <p>Review and manage community join requests</p>
        </section>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="message message-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message message-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Statistics Grid -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Requests</span>
                    <div class="stat-icon requests">📝</div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_requests'] ?? 0); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Pending</span>
                    <div class="stat-icon pending">⏳</div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['pending_requests'] ?? 0); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Approved</span>
                    <div class="stat-icon approved">✅</div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['approved_requests'] ?? 0); ?></div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Community Members</span>
                    <div class="stat-icon community">👥</div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['community_users'] ?? 0); ?></div>
            </div>
        </section>

        <!-- Requests Table -->
        <section class="requests-container">
            <div class="requests-header">
                <h3 class="requests-title">All Community Requests</h3>
            </div>
            
            <?php if (empty($requests)): ?>
                <p style="text-align: center; color: rgba(255, 255, 255, 0.6); padding: 40px 0;">
                    No community requests found.
                </p>
            <?php else: ?>
                <table class="requests-table">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Name</th>
                            <th>Message</th>
                            <th>Status</th>
                            <th>Requested</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['email']); ?></td>
                            <td>
                                <?php 
                                $name = $request['full_name'] ?: $request['user_full_name'] ?: $request['username'] ?: $request['user_username'] ?: 'N/A';
                                echo htmlspecialchars($name);
                                ?>
                            </td>
                            <td>
                                <?php if ($request['request_message']): ?>
                                    <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($request['request_message']); ?>">
                                        <?php echo htmlspecialchars($request['request_message']); ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: rgba(255, 255, 255, 0.5);">No message</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $request['status']; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($request['requested_at'])); ?></td>
                            <td>
                                <?php if ($request['status'] === 'pending'): ?>
                                    <div class="action-buttons">
                                        <button class="btn-approve" onclick="approveRequest(<?php echo $request['id']; ?>)">Approve</button>
                                        <button class="btn-reject" onclick="rejectRequest(<?php echo $request['id']; ?>)">Reject</button>
                                    </div>
                                <?php elseif ($request['status'] === 'approved' && $request['user_id']): ?>
                                    <div class="action-buttons">
                                        <button class="btn-remove" onclick="removeUser(<?php echo $request['user_id']; ?>, '<?php echo htmlspecialchars($request['email']); ?>', '<?php echo htmlspecialchars($name); ?>')">Remove</button>
                                    </div>
                                <?php else: ?>
                                    <span style="color: rgba(255, 255, 255, 0.5); font-size: 0.8rem;">
                                        Processed by <?php echo htmlspecialchars($request['processed_by_username'] ?? 'Admin'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

    </main>

    <!-- Approval/Rejection Modal -->
    <div id="actionModal" class="modal" style="display: none;">
        <div class="modal-backdrop" onclick="closeModal()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Action Required</h3>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <form id="actionForm" method="POST">
                    <input type="hidden" name="action" id="actionType">
                    <input type="hidden" name="request_id" id="requestId">
                    <input type="hidden" name="user_id" id="userId">
                    
                    <div class="form-group">
                        <label for="adminNotes">Admin Notes (Optional)</label>
                        <textarea id="adminNotes" name="admin_notes" rows="3" placeholder="Add any notes about this decision..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="submit" id="submitAction" class="btn-primary">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function approveRequest(requestId) {
            document.getElementById('modalTitle').textContent = 'Approve Community Request';
            document.getElementById('actionType').value = 'approve';
            document.getElementById('requestId').value = requestId;
            document.getElementById('userId').value = '';
            document.getElementById('submitAction').textContent = 'Approve Request';
            document.getElementById('submitAction').className = 'btn-primary';
            document.getElementById('actionModal').style.display = 'flex';
        }

        function rejectRequest(requestId) {
            document.getElementById('modalTitle').textContent = 'Reject Community Request';
            document.getElementById('actionType').value = 'reject';
            document.getElementById('requestId').value = requestId;
            document.getElementById('userId').value = '';
            document.getElementById('submitAction').textContent = 'Reject Request';
            document.getElementById('submitAction').className = 'btn-reject';
            document.getElementById('actionModal').style.display = 'flex';
        }

        function removeUser(userId, userEmail, userName) {
            document.getElementById('modalTitle').textContent = 'Remove User from Community';
            document.getElementById('actionType').value = 'remove_user';
            document.getElementById('requestId').value = '';
            document.getElementById('userId').value = userId;
            document.getElementById('submitAction').textContent = 'Remove User';
            document.getElementById('submitAction').className = 'btn-remove';
            document.getElementById('actionModal').style.display = 'flex';
            
            // Update the form label to show which user is being removed
            const notesLabel = document.querySelector('label[for="adminNotes"]');
            notesLabel.textContent = `Remove ${userName} (${userEmail}) from community - Admin Notes (Optional)`;
        }

        function closeModal() {
            document.getElementById('actionModal').style.display = 'none';
            document.getElementById('adminNotes').value = '';
            
            // Reset the form label
            const notesLabel = document.querySelector('label[for="adminNotes"]');
            notesLabel.textContent = 'Admin Notes (Optional)';
        }

        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
