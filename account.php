<?php
/**
 * User Account Dashboard for TraderEscape
 */

session_start();
require_once __DIR__ . '/includes/auth_functions.php';
requireAuth();

$currentUser = getCurrentUser();
$dashboardData = getUserDashboardData($currentUser['id']);
$currentPage = 'account';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - The Trader's Escape</title>
    <link rel="stylesheet" href="./assets/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main id="main-content" role="main" style="padding-top: 0;">
        <div class="container">
            <div class="hero-section" style="min-height: 40vh; display: flex; align-items: center;">
                <div class="hero-content">
                    <h1 class="hero-title">
                        <span class="title-line">Welcome Back,</span>
                        <span class="title-line highlight"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                    </h1>
                    <p class="hero-subtitle">Track your learning progress and trading tool usage</p>
        </div>
            </div>

            <!-- Account Statistics -->
            <div class="account-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
                <div class="stat-card glassmorphism">
                    <div class="stat-number"><?php echo $dashboardData['user_stats']['tools_used'] ?? 0; ?></div>
                    <div class="stat-label">Tools Used</div>
                </div>
                <div class="stat-card glassmorphism">
                    <div class="stat-number"><?php echo $dashboardData['user_stats']['content_accessed'] ?? 0; ?></div>
                    <div class="stat-label">Content Accessed</div>
                    </div>
                <div class="stat-card glassmorphism">
                    <div class="stat-number"><?php echo $dashboardData['user_stats']['content_completed'] ?? 0; ?></div>
                    <div class="stat-label">Content Completed</div>
                </div>
            </div>

            <!-- Learning Progress -->
            <div class="dashboard-section" style="margin: 3rem 0;">
                <h2 class="section-title">
                    <i class="bi bi-book"></i>
                    LEARNING PROGRESS
                </h2>
                
                <?php if (!empty($dashboardData['learning_progress'])): ?>
                    <div class="progress-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
                        <?php foreach ($dashboardData['learning_progress'] as $progress): ?>
                            <div class="progress-card glassmorphism">
                                <div class="progress-header">
                                    <div class="progress-title"><?php echo htmlspecialchars($progress['title']); ?></div>
                                    <div class="progress-percentage"><?php echo $progress['progress_percentage']; ?>%</div>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress['progress_percentage']; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No learning progress yet. Start exploring our educational content!</p>
                    <a href="./tools.php" class="btn btn-primary">Explore Tools</a>
                <?php endif; ?>
                        </div>
                        
            <!-- Recent Tool Usage -->
            <div class="dashboard-section" style="margin: 3rem 0;">
                <h2 class="section-title">
                    <i class="bi bi-tools"></i>
                    Recent Tool Usage
                </h2>
                
                <?php if (!empty($dashboardData['recent_tools'])): ?>
                    <div class="recent-activity">
                        <?php foreach ($dashboardData['recent_tools'] as $tool): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="bi bi-gear"></i>
                    </div>
                                <div class="activity-content">
                                    <div class="activity-title"><?php echo htmlspecialchars($tool['name']); ?></div>
                                    <div class="activity-time">
                                        Used <?php echo date('M j, Y', strtotime($tool['session_start'])); ?>
                            </div>
                        </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No tool usage yet. Try our trading tools to get started!</p>
                    <a href="./tools.php" class="btn btn-primary">Explore Tools</a>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
    <script src="./assets/app.js"></script>
</body>
</html>
