<?php
/**
 * Header Include for TraderEscape Email Pages
 * Custom header with correct asset paths for email folder
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database functions
require_once __DIR__ . '/../includes/db_functions.php';

// Get current page slug
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Get site settings
$siteSettings = getSiteSettings();

// Track page view (if database is available)
if (isDatabaseAvailable()) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $referrer = $_SERVER['HTTP_REFERER'] ?? null;
    $sessionId = session_id();
    
    trackPageView($currentPage, null, $ipAddress, $userAgent, $referrer, $sessionId);
}

// Get page data from database if available
$pageData = null;
if (isDatabaseAvailable()) {
    $pageData = getPageData($currentPage);
}

// Set default values if no database data
$pageTitle = $pageData['title'] ?? ucfirst($currentPage) . ' - The Trader\'s Escape';
$pageDescription = $pageData['meta_description'] ?? 'The Trader\'s Escape - Your comprehensive trading education platform';
$pageKeywords = $pageData['meta_keywords'] ?? 'trading, education, tools, stock market, investment';

// Get trading tools for navigation
$tradingTools = [];
if (isDatabaseAvailable()) {
    $tradingTools = getTradingTools();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Meta Tags -->
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords); ?>">
    <meta name="author" content="The Trader's Escape">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:site_name" content="The Trader's Escape">
    <meta property="og:image" content="../assets/logo.png">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">
    <meta property="og:image:alt" content="The Trader's Escape Logo">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="twitter:image" content="../assets/logo.png">
    
    <!-- Favicon and App Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/logo.png">
    <link rel="shortcut icon" href="../assets/logo.png">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="../assets/logo.png">
    <link rel="apple-touch-icon" sizes="57x57" href="../assets/logo.png">
    <link rel="apple-touch-icon" sizes="60x60" href="../assets/logo.png">
    <link rel="apple-touch-icon" sizes="72x72" href="../assets/logo.png">
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/logo.png">
    <link rel="apple-touch-icon" sizes="114x114" href="../assets/logo.png">
    <link rel="apple-touch-icon" sizes="120x120" href="../assets/logo.png">
    <link rel="apple-touch-icon" sizes="144x144" href="../assets/logo.png">
    <link rel="apple-touch-icon" sizes="152x152" href="../assets/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/logo.png">
    <link rel="apple-touch-icon" sizes="192x192" href="../assets/logo.png">
    <link rel="apple-touch-icon" sizes="512x512" href="../assets/logo.png">
    
    <!-- Web App Manifest -->
    <link rel="manifest" href="../manifest.json">
    
    <!-- Theme Color -->
    <meta name="theme-color" content="#0f172a">
    <meta name="msapplication-TileColor" content="#0f172a">
    <meta name="msapplication-TileImage" content="../assets/logo.png">
    
    <!-- Preload Critical Resources -->
    <link rel="preload" href="../assets/styles.css" as="style">
    <link rel="preload" href="../assets/app.js" as="script">
    <link rel="preload" href="../assets/logo.png" as="image">
    
    <!-- DNS Prefetch -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    
    <!-- External Stylesheets -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="../assets/styles.css" id="main-stylesheet">
    
    <!-- Additional Meta Tags -->
    <meta name="format-detection" content="telephone=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TraderEscape">
    
    <!-- Security Headers -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
</head>
<body>
    <!-- Cursor -->
    <div class="custom-cursor" id="custom-cursor" aria-hidden="true"></div>

    <!-- Trading Background -->
    <div class="trading-background">
        <div class="bg-grid"></div>
        <div class="bg-particles"></div>
        <div class="bg-trading-elements"></div>
        <div class="bg-glow"></div>
    </div>

    <!-- Desktop Navigation -->
    <nav class="navbar desktop-nav" id="navbar" role="navigation" aria-label="Main navigation">
        <div class="nav-container">
            <div class="nav-logo">
                <div class="logo-wrapper">
                    <img src="../assets/logo.png" alt="The Trader's Escape" class="nav-logo-img" loading="eager">
                </div>
            </div>
            <div class="nav-menu" id="nav-menu" role="menubar">
                <a href="../" class="nav-link" data-section="home" role="menuitem">
                    <span class="nav-text">Home</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="../about.php" class="nav-link" data-section="about" role="menuitem">
                    <span class="nav-text">About</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="../tools.php" class="nav-link" data-section="tools" role="menuitem">
                    <span class="nav-text">Tools</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="../disclaimer.php" class="nav-link" data-section="disclaimer" role="menuitem">
                    <span class="nav-text">Disclaimer</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="../risk.php" class="nav-link" data-section="risk" role="menuitem">
                    <span class="nav-text">Risk</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="../privacy.php" class="nav-link" data-section="privacy" role="menuitem">
                    <span class="nav-text">Privacy</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="../terms.php" class="nav-link" data-section="terms" role="menuitem">
                    <span class="nav-text">Terms</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="../contact.php" class="nav-link" data-section="contact" role="menuitem">
                    <span class="nav-text">Contact</span>
                    <span class="nav-indicator"></span>
                </a>
            </div>
            
            <!-- Profile Button - Dynamic based on login status -->
            <?php 
            $isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
            ?>
            <?php if ($isLoggedIn): ?>
                <button class="profile-btn" onclick="showProfileMenu()" aria-label="User Account">
                    <i class="bi bi-person-circle"></i>
                </button>
            <?php else: ?>
                <a href="../login.php" class="profile-btn" aria-label="Login">
                    <i class="bi bi-person-circle"></i>
                </a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Mobile Top Navigation -->
    <nav class="navbar mobile-top-nav" id="mobile-top-nav" role="navigation" aria-label="Mobile navigation">
        <div class="nav-container">
            <div class="nav-logo">
                <div class="logo-wrapper">
                    <img src="../assets/logo.png" alt="The Trader's Escape" class="nav-logo-img" loading="eager">
                </div>
            </div>
            
            <div class="mobile-nav-actions">
                <button class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="Toggle mobile menu">
                    <i class="bi bi-list"></i>
                </button>
            </div>
        </div>
    </nav>

    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav" id="mobile-bottom-nav" role="navigation" aria-label="Mobile bottom navigation">
        <a href="../" class="bottom-nav-item" data-section="home">
            <i class="bi bi-house"></i>
            <span>Home</span>
        </a>
        <a href="../about.php" class="bottom-nav-item" data-section="about">
            <i class="bi bi-info-circle"></i>
            <span>About</span>
        </a>
        <a href="../tools.php" class="bottom-nav-item" data-section="tools">
            <i class="bi bi-tools"></i>
            <span>Tools</span>
        </a>
        <a href="../contact.php" class="bottom-nav-item" data-section="contact">
            <i class="bi bi-envelope"></i>
            <span>Contact</span>
        </a>
        <?php if ($isLoggedIn): ?>
            <a href="../account.php" class="bottom-nav-item" data-section="account">
                <i class="bi bi-person-circle"></i>
                <span>Account</span>
            </a>
        <?php else: ?>
            <a href="../login.php" class="bottom-nav-item" data-section="login">
                <i class="bi bi-person-circle"></i>
                <span>Login</span>
            </a>
        <?php endif; ?>
    </nav>
