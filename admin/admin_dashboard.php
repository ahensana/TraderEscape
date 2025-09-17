<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$mysqli = new mysqli("localhost", "root", "", "traderescape_db");

// Get admin info
$admin_id = $_SESSION['admin_id'];
$admin_name = $_SESSION['admin_name'];

// Get statistics
$stats = [];

// Total users
$result = $mysqli->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $result->fetch_assoc()['total'];

// Active users (logged in within last 30 days)
$result = $mysqli->query("SELECT COUNT(*) as active FROM users WHERE last_login > DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stats['active_users'] = $result->fetch_assoc()['active'];

// Total page views (check if table exists)
$stats['total_views'] = 0;
$stats['recent_views'] = 0;
$result = $mysqli->query("SHOW TABLES LIKE 'page_views'");
if ($result->num_rows > 0) {
    $result = $mysqli->query("SELECT COUNT(*) as views FROM page_views");
    $stats['total_views'] = $result->fetch_assoc()['views'];
    
    // Recent page views (last 7 days)
    $result = $mysqli->query("SELECT COUNT(*) as recent FROM page_views WHERE viewed_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['recent_views'] = $result->fetch_assoc()['recent'];
}

// Get recent users
$recent_users = [];
$result = $mysqli->query("SELECT username, email, created_at, last_login FROM users ORDER BY created_at DESC LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $recent_users[] = $row;
}


$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - The Trader's Escape</title>
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 16px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(59, 130, 246, 0.4);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(59, 130, 246, 0.5),
                transparent
            );
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
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .stat-icon.users { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
        .stat-icon.active { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .stat-icon.views { background: rgba(147, 51, 234, 0.2); color: #a78bfa; }
        .stat-icon.recent { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
        }

        .stat-change {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
        }

        /* ===== CONTENT GRID ===== */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            max-width: 800px;
            margin: 0 auto;
        }

        .content-card {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 16px;
            padding: 25px;
            position: relative;
            overflow: hidden;
        }

        .content-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(59, 130, 246, 0.5),
                transparent
            );
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: white;
        }

        .card-action {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .card-action:hover {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }

        /* ===== TABLE STYLES ===== */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            text-align: left;
            padding: 12px 0;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            font-weight: 500;
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
        }

        .data-table td {
            padding: 12px 0;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            border-bottom: 1px solid rgba(59, 130, 246, 0.1);
        }

        .data-table tr:hover td {
            color: white;
            background: rgba(59, 130, 246, 0.05);
        }

        .user-avatar {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            font-size: 0.8rem;
            margin-right: 10px;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        .status-inactive {
            background: rgba(107, 114, 128, 0.2);
            color: #9ca3af;
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

            .content-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .data-table {
                font-size: 0.8rem;
            }

            .data-table th,
            .data-table td {
                padding: 8px 0;
            }
        }

        /* ===== LOADING ANIMATION ===== */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
                <h1>Admin Dashboard</h1>
            </div>
            <nav class="admin-nav">
                <div class="admin-user">
                    <div class="admin-user-avatar">
                        <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                    </div>
                    <span>Welcome, <?php echo htmlspecialchars($admin_name); ?></span>
                </div>
                <a href="community_requests.php" class="nav-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        <path d="M13 8H7"></path>
                        <path d="M17 12H7"></path>
                    </svg>
                    Community Requests
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
            <h2>Dashboard Overview</h2>
            <p>Monitor and manage your Trader's Escape platform</p>
        </section>

        <!-- Statistics Grid -->
        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Users</span>
                    <div class="stat-icon users">👥</div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-change">All registered users</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Active Users</span>
                    <div class="stat-icon active">✅</div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['active_users']); ?></div>
                <div class="stat-change">Last 30 days</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Total Page Views</span>
                    <div class="stat-icon views">📊</div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_views']); ?></div>
                <div class="stat-change">All time</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <span class="stat-title">Recent Views</span>
                    <div class="stat-icon recent">📈</div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['recent_views']); ?></div>
                <div class="stat-change">Last 7 days</div>
            </div>
        </section>

        <!-- Content Grid -->
        <section class="content-grid">
            <!-- Recent Users -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">Recent Users</h3>
                    <a href="#" class="card-action">View All</a>
                </div>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $user['last_login'] && strtotime($user['last_login']) > strtotime('-30 days') ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $user['last_login'] && strtotime($user['last_login']) > strtotime('-30 days') ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <script>
        // Logout confirmation function
        function confirmLogout() {
            // Create custom confirmation modal
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                animation: fadeIn 0.3s ease;
            `;

            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: rgba(15, 23, 42, 0.95);
                border: 1px solid rgba(59, 130, 246, 0.3);
                border-radius: 16px;
                padding: 30px;
                max-width: 400px;
                width: 90%;
                text-align: center;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                animation: slideIn 0.3s ease;
            `;

            modalContent.innerHTML = `
                <div style="margin-bottom: 20px;">
                    <div style="width: 60px; height: 60px; background: rgba(239, 68, 68, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="#fca5a5" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16,17 21,12 16,7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                    </div>
                    <h3 style="color: white; font-size: 1.3rem; font-weight: 600; margin-bottom: 10px;">Confirm Logout</h3>
                    <p style="color: rgba(255, 255, 255, 0.7); font-size: 1rem; line-height: 1.5;">Are you sure you want to logout from the admin panel?</p>
                </div>
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button onclick="closeLogoutModal()" style="
                        background: rgba(107, 114, 128, 0.2);
                        border: 1px solid rgba(107, 114, 128, 0.3);
                        color: #9ca3af;
                        padding: 10px 20px;
                        border-radius: 8px;
                        cursor: pointer;
                        font-size: 0.9rem;
                        font-weight: 500;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.background='rgba(107, 114, 128, 0.3)'" onmouseout="this.style.background='rgba(107, 114, 128, 0.2)'">
                        Cancel
                    </button>
                    <button onclick="proceedLogout()" style="
                        background: rgba(239, 68, 68, 0.2);
                        border: 1px solid rgba(239, 68, 68, 0.3);
                        color: #fca5a5;
                        padding: 10px 20px;
                        border-radius: 8px;
                        cursor: pointer;
                        font-size: 0.9rem;
                        font-weight: 500;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.background='rgba(239, 68, 68, 0.3)'" onmouseout="this.style.background='rgba(239, 68, 68, 0.2)'">
                        Logout
                    </button>
                </div>
            `;

            modal.appendChild(modalContent);
            document.body.appendChild(modal);

            // Add CSS animations
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideIn {
                    from { transform: translateY(-20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);

            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeLogoutModal();
                }
            });

            // Close modal with Escape key
            const handleEscape = function(e) {
                if (e.key === 'Escape') {
                    closeLogoutModal();
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);
        }

        function closeLogoutModal() {
            const modal = document.querySelector('div[style*="position: fixed"]');
            if (modal) {
                modal.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => {
                    modal.remove();
                }, 300);
            }
        }

        function proceedLogout() {
            // Add loading state
            const logoutBtn = document.querySelector('button[onclick="proceedLogout()"]');
            if (logoutBtn) {
                logoutBtn.innerHTML = '<div class="loading"></div>';
                logoutBtn.disabled = true;
            }
            
            // Redirect to logout page
            setTimeout(() => {
                window.location.href = 'admin_logout.php';
            }, 500);
        }

        // Auto-refresh data every 30 seconds
        setInterval(function() {
            // You can add AJAX calls here to refresh data
            console.log('Dashboard data refreshed');
        }, 30000);

        // Add smooth scrolling for better UX
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Add loading states for future AJAX calls
        function showLoading(element) {
            element.innerHTML = '<div class="loading"></div>';
        }

        function hideLoading(element, content) {
            element.innerHTML = content;
        }
    </script>
</body>
</html>
