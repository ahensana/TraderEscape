<?php
/**
 * Header Include for TraderEscape
 * Common header with database integration
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database functions
require_once __DIR__ . '/db_functions.php';

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($pageKeywords); ?>">
    <meta name="author" content="The Trader's Escape">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#3b82f6">
    <meta name="msapplication-TileColor" content="#3b82f6">
    
    <!-- PWA Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TraderEscape">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="The Trader's Escape">
    <meta name="msapplication-config" content="./browserconfig.xml">
    
    <!-- iPhone PWA Meta Tags -->
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-touch-fullscreen" content="yes">
    <meta name="apple-mobile-web-app-orientations" content="portrait">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TraderEscape">
    <meta name="msapplication-TileImage" content="./assets/logo.png">
    <meta name="msapplication-TileColor" content="#3b82f6">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:image" content="./assets/logo.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="The Trader's Escape">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="twitter:image" content="./assets/logo.png">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="./assets/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./assets/logo.png">
    
    <!-- iPhone Icons -->
    <link rel="apple-touch-icon" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="57x57" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="60x60" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="72x72" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="76x76" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="114x114" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="120x120" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="144x144" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="152x152" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="180x180" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="192x192" href="./assets/logo.png">
    <link rel="apple-touch-icon" sizes="512x512" href="./assets/logo.png">
    
    <!-- Web App Manifest -->
    <link rel="manifest" href="./manifest.json">
    
    <!-- Preconnect to CDNs for faster loading -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://unpkg.com">
    
    <!-- DNS Prefetch for external resources -->
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="//unpkg.com">
    
    <!-- Resource hints for better performance -->
    <link rel="preload" href="./assets/styles.css" as="style">
    <link rel="preload" href="./assets/app.js" as="script">
    <link rel="preload" href="./assets/logo.png" as="image">
    
    <!-- Google Fonts with display=swap for better performance -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
     
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="./assets/styles.css" id="main-stylesheet">
</head>
<body>


    <!-- Cursor -->
    <div class="custom-cursor" id="custom-cursor" aria-hidden="true"></div>

    <!-- iOS Install Banner -->
    <div id="ios-install-banner" class="ios-install-banner" style="display: none;">
        <div class="ios-banner-content">
            <div class="ios-banner-text">
                <i class="bi bi-phone"></i>
                <span>Add this app to your home screen for quick access</span>
            </div>
            <button class="ios-banner-btn" onclick="showInstallPrompt()">Add to Home</button>
            <button class="ios-banner-close" onclick="closeIOSBanner()">&times;</button>
        </div>
    </div>

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
                    <img src="./assets/logo.png" alt="The Trader's Escape" class="nav-logo-img" loading="eager">
                </div>
            </div>
            <div class="nav-menu" id="nav-menu" role="menubar">
                <a href="./" class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>" data-section="home" role="menuitem" <?php echo $currentPage === 'index' ? 'aria-current="page"' : ''; ?>>
                    <span class="nav-text">Home</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="./about.php" class="nav-link <?php echo $currentPage === 'about' ? 'active' : ''; ?>" data-section="about" role="menuitem">
                    <span class="nav-text">About</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="./tools.php" class="nav-link <?php echo $currentPage === 'tools' ? 'active' : ''; ?>" data-section="tools" role="menuitem">
                    <span class="nav-text">Tools</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="./disclaimer.php" class="nav-link <?php echo $currentPage === 'disclaimer' ? 'active' : ''; ?>" data-section="disclaimer" role="menuitem">
                    <span class="nav-text">Disclaimer</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="./risk.php" class="nav-link <?php echo $currentPage === 'risk' ? 'active' : ''; ?>" data-section="risk" role="menuitem">
                    <span class="nav-text">Risk</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="./privacy.php" class="nav-link <?php echo $currentPage === 'privacy' ? 'active' : ''; ?>" data-section="privacy" role="menuitem">
                    <span class="nav-text">Privacy</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="./terms.php" class="nav-link <?php echo $currentPage === 'terms' ? 'active' : ''; ?>" data-section="terms" role="menuitem">
                    <span class="nav-text">Terms</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="./contact.php" class="nav-link <?php echo $currentPage === 'contact' ? 'active' : ''; ?>" data-section="contact" role="menuitem">
                    <span class="nav-text">Contact</span>
                    <span class="nav-indicator"></span>
                </a>
            </div>
            
            <!-- Profile Button - Dynamic based on login status -->
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id']): ?>
                <button class="profile-btn" onclick="showProfileMenu()" aria-label="User Account">
                    <i class="bi bi-person-circle"></i>
                </button>
            <?php else: ?>
                <a href="./login.php" class="profile-btn" aria-label="Login">
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
                    <img src="./assets/logo.png" alt="The Trader's Escape" class="nav-logo-img" loading="eager">
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
        <a href="./" class="bottom-nav-item <?php echo $currentPage === 'index' ? 'active' : ''; ?>" data-section="home">
            <i class="bi bi-house"></i>
            <span>Home</span>
        </a>
        <a href="./tools.php" class="bottom-nav-item <?php echo $currentPage === 'tools' ? 'active' : ''; ?>" data-section="tools">
            <i class="bi bi-tools"></i>
            <span>Tools</span>
        </a>
        <a href="./about.php" class="bottom-nav-item <?php echo $currentPage === 'about' ? 'active' : ''; ?>" data-section="about">
            <i class="bi bi-info-circle"></i>
            <span>About</span>
        </a>
        <a href="./contact.php" class="bottom-nav-item <?php echo $currentPage === 'contact' ? 'active' : ''; ?>" data-section="contact">
            <i class="bi bi-envelope"></i>
            <span>Contact</span>
        </a>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id']): ?>
            <button class="bottom-nav-item" onclick="showProfileMenu()" data-section="profile">
                <i class="bi bi-person"></i>
                <span>Account</span>
            </button>
        <?php else: ?>
            <a href="./login.php" class="bottom-nav-item" data-section="profile">
                <i class="bi bi-person"></i>
                <span>Login</span>
            </a>
        <?php endif; ?>
    </nav>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobile-menu-overlay">
        <div class="mobile-menu-content">
            <div class="mobile-menu-header">
                <h3>Menu</h3>
                <button class="mobile-menu-close" onclick="toggleMobileMenu()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="mobile-menu-items">
                <a href="./disclaimer.php" class="mobile-menu-item">
                    <i class="bi bi-shield-exclamation"></i>
                    <span>Disclaimer</span>
                </a>
                <a href="./risk.php" class="mobile-menu-item">
                    <i class="bi bi-exclamation-triangle"></i>
                    <span>Risk</span>
                </a>
                <a href="./privacy.php" class="mobile-menu-item">
                    <i class="bi bi-shield-lock"></i>
                    <span>Privacy</span>
                </a>
                <a href="./terms.php" class="mobile-menu-item">
                    <i class="bi bi-file-text"></i>
                    <span>Terms</span>
                </a>
            </div>
        </div>
    </div>


