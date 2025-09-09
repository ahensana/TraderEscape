<?php
/**
 * Trading Tools Page for TraderEscape
 * Protected page - requires user authentication
 */

session_start();
require_once __DIR__ . '/includes/auth_functions.php';
require_once __DIR__ . '/includes/db_functions.php';

// Require user to be logged in to access tools
requireAuth();

$currentUser = getCurrentUser();
$currentPage = 'tools';

// Get trading tools from database
$tradingTools = getTradingTools();

// Track page view
trackPageView('tools', $currentUser['id'], $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, session_id());

// Log user activity
logUserActivity($currentUser['id'], 'page_view', 'Viewed trading tools page', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['page' => 'tools']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />
    <title>Trading Tools - The Trader's Escape</title>
    <meta name="description" content="Access advanced trading tools and analytics at The Trader's Escape. Educational tools for technical analysis, risk management, and market research.">
    <meta name="keywords" content="trading tools, technical analysis, risk management, market analytics, trading education">
    <meta name="author" content="The Trader's Escape">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#3b82f6">
    <meta name="msapplication-TileColor" content="#3b82f6">
    
    <!-- PWA Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TraderEscape">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="The Trader's Escape">
    <meta name="msapplication-config" content="/browserconfig.xml">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://thetradersescape.com/tools.html">
    <meta property="og:title" content="Trading Tools - The Trader's Escape">
    <meta property="og:description" content="Access advanced trading tools and analytics for educational purposes. Technical analysis and risk management tools.">
    <meta property="og:image" content="/assets/logo.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="The Trader's Escape">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://thetradersescape.com/tools.html">
    <meta property="twitter:title" content="Trading Tools - The Trader's Escape">
    <meta property="twitter:description" content="Access advanced trading tools and analytics for educational purposes.">
    <meta property="twitter:image" content="./assets/logo.png">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="./assets/logo.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./assets/logo.png">
    <link rel="apple-touch-icon" href="./assets/logo.png">
    
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
    
    <!-- Critical CSS inline for faster rendering -->
    <style>
        /* Critical CSS for above-the-fold content */
        .skip-link {
            position: absolute;
            top: -40px;
            left: 6px;
            background: #3b82f6;
            color: white;
            padding: 8px;
            text-decoration: none;
            border-radius: 4px;
            z-index: 10000;
            transition: top 0.3s;
        }
        
        .skip-link:focus {
            top: 6px;
        }
        
        
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(20px);
            transition: all 0.3s ease;
        }
        
        .hero-section {
            min-height: 60vh;
            display: flex;
            align-items: center;
            position: relative;
            padding-top: 80px;
            padding-bottom: 1rem;
            background: transparent;
            color: white;
            text-align: center;
        }
        
        /* Reduce spacing for content sections after hero */
        .hero-section + .disclaimer-section,
        .hero-section + .tools-section {
            padding-top: 2rem;
            padding-bottom: 4rem;
        }
        
        .hero-content {
            width: 100%;
            text-align: center;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #ffffff;
        }
        
        .hero-title .highlight {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            color: #e2e8f0;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        
        .hero-description {
            font-size: 1.1rem;
            color: #cbd5e1;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .hero-section {
                padding-top: 100px;
                min-height: 80vh;
            }
            
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .hero-description {
                font-size: 1rem;
                padding: 0 1rem;
            }
        }
        
        /* Performance optimizations */
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0f172a;
            color: #ffffff;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Tool Features Styling */
        .tool-features {
            display: flex;
            gap: 12px;
            margin: 16px 0;
            flex-wrap: wrap;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: #94a3b8;
        }
        
        .feature-item i {
            color: #3b82f6;
            font-size: 16px;
        }
        
        /* Premium Tool Styling */
        .premium-tool {
            border: 2px solid rgba(16, 185, 129, 0.3);
            background: linear-gradient(145deg, rgba(16, 185, 129, 0.05), rgba(16, 185, 129, 0.02));
            position: relative;
        }
        
        .premium-tool::before {
            content: 'Premium';
            position: absolute;
            top: -10px;
            right: 20px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            z-index: 10;
        }
        
        .premium-tool .tool-header h3 {
            color: #10b981;
        }
        
        .premium-tool .btn-primary {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
        }
        
        .premium-tool .btn-primary:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
        }
        
        /* Tool Modal System */
        .tool-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .tool-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .tool-modal-container {
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.9));
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 20px;
            width: 95%;
            max-width: 1200px;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            transform: scale(0.9) translateY(50px);
            transition: all 0.3s ease;
        }
        
        .tool-modal-overlay.active .tool-modal-container {
            transform: scale(1) translateY(0);
        }
        
        .tool-modal-header {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.05));
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .tool-modal-header h2 {
            margin: 0;
            color: #ffffff;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .tool-modal-close {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tool-modal-close:hover {
            background: rgba(239, 68, 68, 0.2);
            transform: scale(1.1);
        }
        
        .tool-modal-content {
            padding: 2rem;
            max-height: calc(90vh - 100px);
            overflow-y: auto;
            color: #ffffff;
        }
        
        /* Unified Tool Design */
        .unified-tool-container {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(20px);
        }
        
        .unified-tool-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .unified-tool-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .unified-tool-subtitle {
            color: #94a3b8;
            font-size: 1.1rem;
        }
        
        .unified-form-group {
            margin-bottom: 1.5rem;
        }
        
        .unified-form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: #e2e8f0;
            font-weight: 500;
        }
        
        .unified-form-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 12px 16px;
            color: #ffffff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .unified-form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .unified-btn {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .unified-btn:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
        }
        
        .unified-btn-secondary {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .unified-btn-secondary:hover {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0.1));
            color: #ffffff;
        }
        
        .unified-result-card {
            background: linear-gradient(145deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .unified-result-title {
            color: #10b981;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .unified-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        
        .unified-stat {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
        
        .unified-stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 0.25rem;
        }
        
        .unified-stat-label {
            color: #94a3b8;
            font-size: 0.9rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .tool-modal-container {
                width: 98%;
                max-height: 95vh;
            }
            
            .tool-modal-header {
                padding: 1rem 1.5rem;
            }
            
            .tool-modal-content {
                padding: 1.5rem;
            }
            
            .unified-tool-title {
                font-size: 1.5rem;
            }
            
            .unified-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .tool-content p {
            margin: 0 0 16px 0;
            color: #94a3b8;
            font-size: 14px;
        }
    </style>
    
    <!-- Premium Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r158/three.min.js"></script>
    <script src="https://unpkg.com/animejs@3.2.1/lib/anime.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/particles.js/2.0.0/particles.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="./assets/styles.css?v=<?php echo time(); ?>">
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
    

    <!-- Desktop Navigation -->
    <nav class="navbar desktop-nav" id="navbar" role="navigation" aria-label="Main navigation">
        <div class="nav-container">
            <div class="nav-logo">
                <div class="logo-wrapper">
                    <img src="./assets/logo.png" alt="The Trader's Escape" class="nav-logo-img" loading="eager">
                </div>
            </div>
            <div class="nav-menu" id="nav-menu" role="menubar">
                <a href="./" class="nav-link" data-section="home" role="menuitem">
                    <span class="nav-text">Home</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="./about.php" class="nav-link" data-section="about" role="menuitem">
                    <span class="nav-text">About</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="./tools.php" class="nav-link active" data-section="tools" role="menuitem" aria-current="page">
                    <span class="nav-text">Tools</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="./disclaimer.php" class="nav-link" data-section="disclaimer" role="menuitem">
                    <span class="nav-text">Disclaimer</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="./risk.php" class="nav-link" data-section="risk" role="menuitem">
                    <span class="nav-text">Risk</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="./privacy.php" class="nav-link" data-section="privacy" role="menuitem">
                    <span class="nav-text">Privacy</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="./terms.php" class="nav-link" data-section="terms" role="menuitem">
                    <span class="nav-text">Terms</span>
                    <span class="nav-indicator"></span>
                </a>
                <a href="./contact.php" class="nav-link" data-section="contact" role="menuitem">
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
        <a href="./" class="bottom-nav-item" data-section="home">
            <i class="bi bi-house"></i>
            <span>Home</span>
        </a>
        <a href="./tools.php" class="bottom-nav-item active" data-section="tools">
            <i class="bi bi-tools"></i>
            <span>Tools</span>
        </a>
        <a href="./about.php" class="bottom-nav-item" data-section="about">
            <i class="bi bi-info-circle"></i>
            <span>About</span>
        </a>
        <a href="./contact.php" class="bottom-nav-item" data-section="contact">
            <i class="bi bi-envelope"></i>
            <span>Contact</span>
        </a>
        <?php if ($isLoggedIn): ?>
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

    <!-- Main Content -->
    <main id="main-content" role="main">
        <!-- Hero Section -->
        <section class="hero-section" id="hero" aria-labelledby="hero-title">
            <div class="hero-content">
                <div class="container">
                    <div class="hero-grid">
                        <div class="hero-text">
                            <h1 class="hero-title" id="hero-title">
                                <span class="title-line">Trading</span>
                                <span class="title-line highlight">Tools & Analytics</span>
                            </h1>
                            <p class="hero-subtitle">Advanced Tools for Educational Trading Analysis</p>
                            <p class="hero-description">Access professional-grade tools designed to enhance your learning and understanding of market dynamics.</p>
                            <div class="hero-disclaimer" role="alert" aria-label="Important disclaimer">
                                <span class="disclaimer-icon">⚠️</span>
                                <span>These tools are for educational purposes only. We do not provide financial advice or trading recommendations.</span>
                            </div>
                            <div class="hero-buttons">
                                <a href="#tools-section" class="btn btn-primary">
                                    <span>Explore Tools</span>
                                </a>
                                <a href="./risk.php" class="btn btn-secondary">
                                    <span>Risk Disclosure</span>
                                </a>
                            </div>
                        </div>
                        <div class="hero-visual">
                            <div class="tools-hero-container">
                                <div class="tools-icon-grid">
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-graph-up"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-calculator"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-bar-chart"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-speedometer2"></i>
                                    </div>
                                </div>
                                <div class="tools-glow"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tools Section -->
        <section class="tools-section" id="tools-section" aria-labelledby="tools-title">
            <div class="container">
                <div class="section-header">
                    <div class="section-badge">
                        <span class="badge-icon" aria-hidden="true"><i class="bi bi-gear"></i></span>
                        <span>Trading Tools</span>
                    </div>
                    <h2 class="section-title gradient-text" id="tools-title">Interactive Trading Tools</h2>
                    <p class="section-subtitle">Educational tools to enhance your market analysis skills</p>
                </div>
                <div class="tools-grid" role="list">
                    <!-- Risk Management Tool - First Position -->
                    <div class="tool-card glassmorphism premium-tool" data-aos="zoom-in" data-aos-delay="0" role="listitem" data-tool-id="risk-management">
                        <div class="tool-header">
                            <h3>Advanced Risk Management</h3>
                            <span class="tool-badge">Analyzer</span>
                        </div>
                        <div class="tool-content">
                            <p>Comprehensive risk management tool with position sizing, trade journal, and analytics.</p>
                            <div class="tool-features">
                                <div class="feature-item">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Risk Management</span>
                                </div>
                                <div class="feature-item">
                                    <i class="bi bi-shield-check"></i>
                                    <span>Protected</span>
                                </div>
                                </div>
                            <div class="tool-actions">
                                <button class="btn btn-primary" onclick="openRiskManagementTool()">
                                    <i class="bi bi-shield-check"></i> Use Tool
                                </button>
                                <button class="btn btn-secondary" onclick="viewToolDetails('risk-management')">
                                    <i class="bi bi-info-circle"></i> Details
                                </button>
                            </div>
                                </div>
                                </div>
                    
                    <?php if (!empty($tradingTools)): ?>
                        <?php foreach ($tradingTools as $index => $tool): ?>
                            <div class="tool-card glassmorphism" data-aos="zoom-in" data-aos-delay="<?php echo ($index + 1) * 100; ?>" role="listitem" data-tool-id="<?php echo $tool['id']; ?>">
                        <div class="tool-header">
                                    <h3><?php echo htmlspecialchars($tool['name']); ?></h3>
                                    <span class="tool-badge"><?php echo ucfirst($tool['tool_type']); ?></span>
                        </div>
                        <div class="tool-content">
                                    <p><?php echo htmlspecialchars($tool['description']); ?></p>
                            <div class="tool-features">
                                <div class="feature-item">
                                            <i class="bi bi-<?php echo $tool['tool_type'] === 'calculator' ? 'calculator' : ($tool['tool_type'] === 'analyzer' ? 'graph-up' : ($tool['tool_type'] === 'simulator' ? 'play-circle' : ($tool['tool_type'] === 'chart' ? 'bar-chart' : 'gear'))); ?>"></i>
                                            <span><?php echo ucfirst($tool['tool_type']); ?></span>
                                </div>
                                <div class="feature-item">
                                            <i class="bi bi-<?php echo $tool['requires_auth'] ? 'shield-check' : 'unlock'; ?>"></i>
                                            <span><?php echo $tool['requires_auth'] ? 'Protected' : 'Public'; ?></span>
                                </div>
                                </div>
                                    <div class="tool-actions">
                                        <button class="btn btn-primary" onclick="openTool(<?php echo $tool['id']; ?>, '<?php echo htmlspecialchars($tool['slug']); ?>')">
                                            <i class="bi bi-play-circle"></i> Use Tool
                                        </button>
                                        <button class="btn btn-secondary" onclick="viewToolDetails(<?php echo $tool['id']; ?>)">
                                            <i class="bi bi-info-circle"></i> Details
                            </button>
                            </div>
                                </div>
                                </div>
                        <?php endforeach; ?>
                        
                    <?php else: ?>
                        <div class="tool-card glassmorphism" role="listitem">
                        <div class="tool-header">
                                <h3>No Tools Available</h3>
                                <span class="tool-badge">Coming Soon</span>
                        </div>
                        <div class="tool-content">
                                <p>We're working on adding more trading tools. Check back soon!</p>
                                </div>
                                </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Educational Notice Section -->
        <section class="community-section" id="educational-notice" aria-labelledby="educational-notice-title">
            <div class="container">
                <div class="community-content">
                    <div class="community-text">
                        <div class="section-badge">
                            <span class="badge-icon" aria-hidden="true"><i class="bi bi-info-circle"></i></span>
                            <span>Important Notice</span>
                        </div>
                        <h2 id="educational-notice-title">Educational Purpose Only</h2>
                        <p>All tools and analytics provided on this platform are strictly for educational purposes. They are designed to help you understand trading concepts, risk management principles, and market analysis techniques. These tools do not constitute financial advice, and any calculations or results should not be used for actual trading decisions.</p>
                        <a href="/disclaimer.html" class="btn btn-outline">
                            <span class="btn-text">Read Full Disclaimer</span>
                            <div class="btn-background"></div>
                        </a>
                    </div>
                    <div class="community-visual">
                        <div class="conduct-icon" aria-hidden="true">
                            <i class="bi bi-shield-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <img src="./assets/logo.png" alt="The Trader's Escape">
                        <span>The Trader's Escape</span>
                    </div>
                    <p>Empowering traders with comprehensive educational content and advanced tools for stock market success.</p>
                    <div class="social-links">
                        <a href="#" class="social-link" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="social-link" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-link" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="social-link" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="social-link" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                        <a href="#" class="social-link" aria-label="Discord"><i class="bi bi-discord"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="./">Home</a></li>
                        <li><a href="./about.php">About</a></li>
                        <li><a href="./tools.php">Tools</a></li>
                        <li><a href="./disclaimer.php">Disclaimer</a></li>
                        <li><a href="./risk.php">Risk Disclosure</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="./privacy.php">Privacy Policy</a></li>
                        <li><a href="./terms.php">Terms & Conditions</a></li>
                        <li><a href="./cookies.php">Cookies Policy</a></li>
                        <li><a href="./contact.php">Contact Us</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Educational Notice</h4>
                    <p>All content and tools are for educational purposes only. We do not provide financial advice or stock recommendations.</p>
                    <div class="footer-badge">Educational Content Only</div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 The Trader's Escape. All rights reserved. | Educational content only - not financial advice.</p>
            </div>
        </div>
    </footer>


    <!-- Tool Modal Container -->
    <div id="tool-modal" class="tool-modal-overlay" style="display: none;">
        <div class="tool-modal-container">
            <div class="tool-modal-header">
                <h2 id="modal-tool-title">Tool Title</h2>
                <button class="tool-modal-close" onclick="closeToolModal()" aria-label="Close tool">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="tool-modal-content" id="modal-tool-content">
                <!-- Tool content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <div class="fab-container">
        <button class="fab-button" onclick="scrollToTop()" aria-label="Scroll to top of page">
            <i class="bi bi-arrow-up" aria-hidden="true"></i>
        </button>
    </div>
    

    <!-- Authentication is now handled server-side -->
    
    <!-- Scripts with defer for better performance -->
    <script src="./assets/app.js?v=<?php echo time(); ?>" defer></script>
    <script src="./assets/charts.js?v=<?php echo time(); ?>" defer></script>
    <script src="./assets/animations.js?v=<?php echo time(); ?>" defer></script>
    <script src="./assets/trading-background.js?v=<?php echo time(); ?>" defer></script>
    
    <!-- Tools-specific JavaScript -->
    <script>
        // Position Size Calculator
        function calculatePosition() {
            const accountSize = parseFloat(document.getElementById('account-size').value) || 0;
            const riskPercentage = parseFloat(document.getElementById('risk-percentage').value) || 0;
            const stopLoss = parseFloat(document.getElementById('stop-loss').value) || 0;
            
            if (accountSize > 0 && riskPercentage > 0 && stopLoss > 0) {
                const riskAmount = (accountSize * riskPercentage) / 100;
                const positionSize = Math.floor(riskAmount / stopLoss);
                
                document.getElementById('position-size').textContent = positionSize.toLocaleString();
                document.getElementById('risk-amount').textContent = '₹' + riskAmount.toLocaleString();
            }
        }
        
        // Risk-Reward Calculator
        function calculateRiskReward() {
            const entryPrice = parseFloat(document.getElementById('entry-price').value) || 0;
            const targetPrice = parseFloat(document.getElementById('target-price').value) || 0;
            const stopLossPrice = parseFloat(document.getElementById('stop-loss-price').value) || 0;
            
            if (entryPrice > 0 && targetPrice > 0 && stopLossPrice > 0) {
                const potentialProfit = targetPrice - entryPrice;
                const potentialLoss = entryPrice - stopLossPrice;
                const riskRewardRatio = (potentialProfit / potentialLoss).toFixed(2);
                
                document.getElementById('risk-reward-ratio').textContent = riskRewardRatio + ':1';
                document.getElementById('potential-profit').textContent = '₹' + potentialProfit.toFixed(2);
            }
        }
        
        // Risk Management Tool
        function openRiskManagement() {
            window.open('/riskmanagement.php', '_blank', 'width=1400,height=800,scrollbars=yes,resizable=yes');
        }
        
        // Tool interaction functions
        function openTool(toolId, toolSlug) {
            // Log tool usage
            fetch('./track_tool_usage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    tool_id: toolId,
                    action: 'open'
                })
            }).catch(error => console.log('Tool usage tracking failed:', error));
            
            // Open tool in modal based on slug
            const toolContentMap = {
                'position-size-calculator': {
                    title: 'Position Size Calculator',
                    content: getPositionSizeCalculatorContent()
                },
                'risk-reward-calculator': {
                    title: 'Risk Reward Calculator',
                    content: getRiskRewardCalculatorContent()
                },
                'profit-loss-calculator': {
                    title: 'Profit Loss Calculator',
                    content: getProfitLossCalculatorContent()
                },
                'margin-calculator': {
                    title: 'Margin Calculator',
                    content: getMarginCalculatorContent()
                },
                'portfolio-analyzer': {
                    title: 'Portfolio Analyzer',
                    content: getPortfolioAnalyzerContent()
                },
                'market-simulator': {
                    title: 'Market Simulator',
                    content: getMarketSimulatorContent()
                },
                'chart-analysis-tool': {
                    title: 'Chart Analysis Tool',
                    content: getChartAnalysisToolContent()
                }
            };
            
            const tool = toolContentMap[toolSlug];
            if (tool) {
                openToolModal(tool.title, tool.content);
            } else {
                alert('Tool "' + toolSlug + '" is not yet available.\n\nThis feature is coming soon!');
            }
        }
        
        function viewToolDetails(toolId) {
            // Log tool details view
            fetch('./track_tool_usage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    tool_id: toolId,
                    action: 'view_details'
                })
            }).catch(error => console.log('Tool usage tracking failed:', error));
            
            alert('Tool details coming soon!');
        }
        
        // Tool implementations
        function showPositionCalculator() {
            alert('Position Size Calculator - Coming Soon!');
        }
        
        function showRiskRewardCalculator() {
            alert('Risk-Reward Calculator - Coming Soon!');
        }
        
        function showProfitLossCalculator() {
            alert('Profit-Loss Calculator - Coming Soon!');
        }
        
        function showMarginCalculator() {
            alert('Margin Calculator - Coming Soon!');
        }
        
        function showPortfolioAnalyzer() {
            alert('Portfolio Analyzer - Coming Soon!');
        }
        
        function showMarketSimulator() {
            alert('Market Simulator - Coming Soon!');
        }
        
        function showChartAnalysisTool() {
            alert('Chart Analysis Tool - Coming Soon!');
        }
        
        // Risk Management Tool function
        function openRiskManagementTool() {
            // Log tool usage for risk management
            fetch('./track_tool_usage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    tool_id: 'risk-management',
                    action: 'open'
                })
            }).catch(error => console.log('Risk management tool usage tracking failed:', error));
            
            // Open risk management tool in modal
            openToolModal('Advanced Risk Management', getRiskManagementToolContent());
        }
        
        // Modal System Functions
        function openToolModal(title, content) {
            document.getElementById('modal-tool-title').textContent = title;
            document.getElementById('modal-tool-content').innerHTML = content;
            document.getElementById('tool-modal').style.display = 'flex';
            setTimeout(() => {
                document.getElementById('tool-modal').classList.add('active');
                // Initialize trade journal if it's the Risk Management tool
                if (title === 'Advanced Risk Management') {
                    setTimeout(initializeTradeJournal, 100);
                }
            }, 10);
        }
        
        function closeToolModal() {
            document.getElementById('tool-modal').classList.remove('active');
            setTimeout(() => {
                document.getElementById('tool-modal').style.display = 'none';
            }, 300);
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.id === 'tool-modal') {
                closeToolModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeToolModal();
            }
        });
        
        // Tool Content Generators
        function getRiskManagementToolContent() {
            return `
                <div class="unified-tool-container">
                    <div class="unified-tool-header">
                        <h1 class="unified-tool-title">Advanced Risk Management</h1>
                        <p class="unified-tool-subtitle">Comprehensive risk management with position sizing, trade journal, and analytics</p>
                    </div>
                    
                    <!-- Advanced Position Size Calculator -->
                    <div class="unified-tool-container" style="margin-bottom: 2rem;">
                        <h3 style="color: #3b82f6; margin-bottom: 1.5rem;">
                            <i class="bi bi-calculator"></i> Advanced Position Size Calculator
                        </h3>
                        
                        <div class="unified-grid">
                            <div class="unified-form-group">
                                <label class="unified-form-label">Account Equity (₹)</label>
                                <input type="number" class="unified-form-input" id="equity" value="1000000" placeholder="1000000">
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Risk Percentage (%)</label>
                                <input type="number" class="unified-form-input" id="riskPct" value="1" placeholder="1" step="0.1">
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Fixed Risk Amount (₹)</label>
                                <input type="number" class="unified-form-input" id="riskFixed" value="" placeholder="Leave empty for %">
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Daily Loss Limit (%)</label>
                                <input type="number" class="unified-form-input" id="dailyLossPct" value="3" placeholder="3" step="0.1">
                            </div>
                        </div>
                        
                        <div class="unified-grid">
                            <div class="unified-form-group">
                                <label class="unified-form-label">Segment</label>
                                <select class="unified-form-input" id="segment">
                                    <option value="Equity Cash">Equity Cash</option>
                                    <option value="Futures">Futures</option>
                                    <option value="Options">Options</option>
                                </select>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Side</label>
                                <select class="unified-form-input" id="side">
                                    <option value="Long">Long</option>
                                    <option value="Short">Short</option>
                                </select>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Symbol</label>
                                <input type="text" class="unified-form-input" id="symbol" placeholder="AAPL">
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Preset</label>
                                <select class="unified-form-input" id="preset">
                                    <option value="">Custom</option>
                                    <option value="SPY">SPY</option>
                                    <option value="QQQ">QQQ</option>
                                    <option value="IWM">IWM</option>
                                    <option value="ES">ES Futures</option>
                                    <option value="NQ">NQ Futures</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="unified-grid">
                            <div class="unified-form-group">
                                <label class="unified-form-label">Entry Price (₹)</label>
                                <input type="number" class="unified-form-input" id="entry" value="100" placeholder="100" step="0.01">
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Stop Loss (₹)</label>
                                <input type="number" class="unified-form-input" id="stop" value="95" placeholder="95" step="0.01">
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Target Price (₹)</label>
                                <input type="number" class="unified-form-input" id="target" value="" placeholder="Optional" step="0.01">
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">R Multiple</label>
                                <input type="number" class="unified-form-input" id="rMultiple" value="" placeholder="Optional" step="0.1">
                            </div>
                        </div>
                        
                        <div class="unified-grid">
                            <div class="unified-form-group">
                                <label class="unified-form-label">Lot Size</label>
                                <input type="number" class="unified-form-input" id="lotSize" value="100" placeholder="100">
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Point Value</label>
                                <input type="number" class="unified-form-input" id="pointValue" value="1" placeholder="1">
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Fees (₹)</label>
                                <input type="number" class="unified-form-input" id="fees" value="0" placeholder="0" step="0.01">
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Rounding Rule</label>
                                <select class="unified-form-input" id="roundRule">
                                    <option value="down">Down</option>
                                    <option value="up">Up</option>
                                    <option value="nearest">Nearest</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="unified-grid">
                            <div class="unified-form-group">
                                <label class="unified-form-label">
                                    <input type="checkbox" id="useAtr"> Use ATR for Stop Loss
                                </label>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">ATR</label>
                                <input type="number" class="unified-form-input" id="atr" value="" placeholder="ATR value" step="0.01">
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">ATR Multiplier</label>
                                <input type="number" class="unified-form-input" id="atrMult" value="2" placeholder="2" step="0.1">
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Max Open Risk (%)</label>
                                <input type="number" class="unified-form-input" id="maxOpenRiskPct" value="2" placeholder="2" step="0.1">
                            </div>
                        </div>
                        
                        <div class="unified-grid">
                            <div class="unified-form-group">
                                <label class="unified-form-label">Tags</label>
                                <input type="text" class="unified-form-input" id="sizerTags" placeholder="swing, momentum">
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Notes</label>
                                <input type="text" class="unified-form-input" id="sizerNotes" placeholder="Trade notes">
                            </div>
                        </div>
                        
                        <!-- KPI Display -->
                        <div class="unified-grid" style="margin: 2rem 0;">
                            <div class="unified-stat">
                                <div class="unified-stat-label">Stop Distance (₹)</div>
                                <div class="unified-stat-value" id="kStop">–</div>
                            </div>
                            <div class="unified-stat">
                                <div class="unified-stat-label">Position Size</div>
                                <div class="unified-stat-value" id="kQty">–</div>
                            </div>
                            <div class="unified-stat">
                                <div class="unified-stat-label">Risk Amount (₹)</div>
                                <div class="unified-stat-value" id="kRisk">–</div>
                            </div>
                            <div class="unified-stat">
                                <div class="unified-stat-label">Potential Reward (₹)</div>
                                <div class="unified-stat-value" id="kReward">–</div>
                            </div>
                        </div>
                        
                        <div class="unified-grid">
                            <div class="unified-stat">
                                <div class="unified-stat-label">Risk Budget</div>
                                <div class="unified-stat-value" id="budgetBadge">–</div>
                            </div>
                        </div>
                        
                        <div style="color: #ef4444; font-size: 0.9rem; margin: 1rem 0;" id="guardMsg"></div>
                        
                        <div style="text-align: center; margin: 2rem 0;">
                            <button class="unified-btn" onclick="addJournal()">
                                <i class="bi bi-plus-circle"></i> Add to Journal
                            </button>
                            <button class="unified-btn-secondary" onclick="clearSizer()" style="margin-left: 1rem;">
                                <i class="bi bi-arrow-clockwise"></i> Clear
        </button>
                        </div>
    </div>

                    <!-- Trade Journal & Analytics -->
                    <div class="unified-tool-container" style="background: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.2);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                            <h3 style="color: #8b5cf6; margin: 0;">
                                <i class="bi bi-journal-text"></i> Trade Journal & Analytics
                            </h3>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <input type="text" class="unified-form-input" id="searchBox" placeholder="Search notes…" style="min-width: 200px; max-width: 200px;">
                                <input type="number" class="unified-form-input" id="monthlyTarget" value="100000" placeholder="Monthly Target (₹)" style="max-width: 150px;">
                                <button class="unified-btn-secondary" onclick="exportCsv()">Export CSV</button>
                                <button class="unified-btn-secondary" onclick="printReport()">Print</button>
                                <button class="unified-btn-secondary" onclick="resetJournal()">Reset</button>
                            </div>
                        </div>
                        
                        <!-- Stats Display -->
                        <div class="unified-grid" style="margin-bottom: 2rem;">
                            <div class="unified-stat">
                                <div class="unified-stat-label">Net P&L (₹)</div>
                                <div class="unified-stat-value" id="statPnl">–</div>
                            </div>
                            <div class="unified-stat">
                                <div class="unified-stat-label">Cumulative R</div>
                                <div class="unified-stat-value" id="statR">–</div>
                            </div>
                            <div class="unified-stat">
                                <div class="unified-stat-label">Win rate</div>
                                <div class="unified-stat-value" id="statWin">–</div>
                            </div>
                            <div class="unified-stat">
                                <div class="unified-stat-label">Expectancy / trade (R)</div>
                                <div class="unified-stat-value" id="statExp">–</div>
                            </div>
                        </div>
                        
                        <!-- Charts -->
                        <div class="unified-grid" style="margin-bottom: 2rem;">
                            <div class="unified-tool-container" style="padding: 1rem;">
                                <h4 style="color: #3b82f6; margin-bottom: 1rem;">Equity Curve</h4>
                                <canvas id="equityCanvas" width="600" height="200" style="width: 100%; height: 200px; background: rgba(255, 255, 255, 0.05); border-radius: 8px;"></canvas>
                            </div>
                            <div class="unified-tool-container" style="padding: 1rem;">
                                <h4 style="color: #3b82f6; margin-bottom: 1rem;">R-Multiple Distribution</h4>
                                <canvas id="histCanvas" width="600" height="200" style="width: 100%; height: 200px; background: rgba(255, 255, 255, 0.05); border-radius: 8px;"></canvas>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div style="margin-bottom: 2rem;">
                            <label style="color: #94a3b8; font-size: 0.9rem;">Progress vs monthly target</label>
                            <div style="height: 12px; background: rgba(255, 255, 255, 0.1); border-radius: 6px; overflow: hidden; margin: 0.5rem 0;">
                                <div id="progressBar" style="height: 100%; background: linear-gradient(135deg, #3b82f6, #1d4ed8); width: 0%; transition: width 0.5s ease;"></div>
                            </div>
                            <div style="color: #94a3b8; font-size: 0.8rem;" id="progressHint">0% of ₹ 0 target</div>
                        </div>
                        
                        <!-- Journal Table -->
                        <div style="overflow-x: auto; margin-top: 1rem;">
                            <table id="journalTable" style="width: 100%; border-collapse: collapse; font-size: 0.8rem;">
                                <thead>
                                    <tr style="background: rgba(59, 130, 246, 0.1);">
                                        <th style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); text-align: left;">Date</th>
                                        <th style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); text-align: left;">Segment</th>
                                        <th style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); text-align: left;">Symbol</th>
                                        <th style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); text-align: left;">Side</th>
                                        <th style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); text-align: left;">Entry</th>
                                        <th style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); text-align: left;">Stop</th>
                                        <th style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); text-align: left;">Exit</th>
                                        <th style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); text-align: left;">Qty/Lots</th>
                                        <th style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); text-align: left;">R</th>
                                        <th style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); text-align: left;">P&L (₹)</th>
                                        <th style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); text-align: left;">Tags</th>
                                        <th style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); text-align: left;">Notes</th>
                                        <th style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1); text-align: left;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        
                        <div style="color: #94a3b8; font-size: 0.8rem; margin-top: 1rem;">
                            Disclaimer: Taxes/fees/lot sizes can change. This tool is educational and not financial advice.
                        </div>
                    </div>
                </div>
            `;
        }
        
        function getPositionSizeCalculatorContent() {
            return `
                <div class="unified-tool-container">
                    <div class="unified-tool-header">
                        <h1 class="unified-tool-title">Position Size Calculator</h1>
                        <p class="unified-tool-subtitle">Calculate the optimal position size based on your risk tolerance and account size</p>
                    </div>
                    
                        <div class="unified-grid">
                            <div class="unified-form-group">
                                <label class="unified-form-label">Account Size (₹)</label>
                                <input type="number" class="unified-form-input" id="accountSize" placeholder="100000" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Risk Percentage (%)</label>
                                <input type="number" class="unified-form-input" id="riskPercentage" placeholder="2" step="0.1" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Entry Price (₹)</label>
                                <input type="number" class="unified-form-input" id="entryPrice" placeholder="100" step="0.01" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Stop Loss (₹)</label>
                                <input type="number" class="unified-form-input" id="stopLoss" placeholder="95" step="0.01" required>
                            </div>
                        </div>
                    
                    <div style="text-align: center; margin: 2rem 0;">
                        <button class="unified-btn" onclick="calculatePositionSize()">
                            <i class="bi bi-calculator"></i> Calculate Position Size
                        </button>
                    </div>
                    
                    <div id="position-result" class="unified-result-card" style="display: none;">
                        <div class="unified-result-title">
                            <i class="bi bi-check-circle"></i> Position Size Calculation
                        </div>
                        <div id="position-result-content"></div>
                    </div>
                    
                    <div class="unified-tool-container" style="margin-top: 2rem; background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2);">
                        <h3 style="color: #10b981; margin-bottom: 1rem;">
                            <i class="bi bi-lightbulb"></i> How Position Sizing Works
                        </h3>
                        <div style="color: #94a3b8; line-height: 1.6;">
                            <p><strong>Risk Management:</strong> This calculator helps you determine how many shares to buy based on your risk tolerance.</p>
                            <p><strong>Formula:</strong> Position Size = (Account Size × Risk %) ÷ (Entry Price - Stop Loss)</p>
                            <p><strong>Example:</strong> With $10,000 account, 2% risk, $100 entry, $95 stop = 40 shares maximum.</p>
                            <p><strong>Benefits:</strong> Protects your capital and ensures consistent risk across all trades.</p>
                        </div>
                    </div>
                </div>
            `;
        }
        
        function getRiskRewardCalculatorContent() {
            return `
                <div class="unified-tool-container">
                    <div class="unified-tool-header">
                        <h1 class="unified-tool-title">Risk Reward Calculator</h1>
                        <p class="unified-tool-subtitle">Analyze the risk-to-reward ratio of your trading strategies</p>
                    </div>
                    
                        <div class="unified-grid">
                            <div class="unified-form-group">
                                <label class="unified-form-label">Entry Price (₹)</label>
                                <input type="number" class="unified-form-input" id="entryPrice" placeholder="100" step="0.01" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Stop Loss (₹)</label>
                                <input type="number" class="unified-form-input" id="stopLoss" placeholder="95" step="0.01" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Take Profit 1 (₹)</label>
                                <input type="number" class="unified-form-input" id="takeProfit1" placeholder="110" step="0.01" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Take Profit 2 (₹)</label>
                                <input type="number" class="unified-form-input" id="takeProfit2" placeholder="120" step="0.01">
                            </div>
                        </div>
                    
                    <div style="text-align: center; margin: 2rem 0;">
                        <button class="unified-btn" onclick="calculateRiskReward()">
                            <i class="bi bi-graph-up"></i> Calculate Risk Reward
                        </button>
                    </div>
                    
                    <div id="risk-reward-result" class="unified-result-card" style="display: none;">
                        <div class="unified-result-title">
                            <i class="bi bi-check-circle"></i> Risk Reward Analysis
                        </div>
                        <div id="risk-reward-result-content"></div>
                    </div>
                    
                    <div class="unified-tool-container" style="margin-top: 2rem; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2);">
                        <h3 style="color: #3b82f6; margin-bottom: 1rem;">
                            <i class="bi bi-info-circle"></i> Risk-Reward Ratio Guide
                        </h3>
                        <div style="color: #94a3b8; line-height: 1.6;">
                            <p><strong>Excellent (3:1+):</strong> Very favorable setup, high probability of success.</p>
                            <p><strong>Good (2:1 to 3:1):</strong> Solid trading opportunity with reasonable risk.</p>
                            <p><strong>Fair (1:1 to 2:1):</strong> Consider if setup is strong enough.</p>
                            <p><strong>Poor (Below 1:1):</strong> Avoid or adjust your targets.</p>
                            <p><strong>Win Rate Needed:</strong> Shows minimum win rate required to be profitable.</p>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Complete Risk Management System from riskmanagement.php
        const F2 = new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 });
        const $ = id => document.getElementById(id);

        // Storage keys
        const KEY_SETTINGS = 'tte_risk_settings', KEY_JOURNAL = 'tte_trades_journal', KEY_TARGET = 'tte_monthly_target';

        // Tick rounding for precision
        const TICK = 0.01;
        const roundTick = (x) => Math.round(x / TICK) * TICK;

        // Presets for different instruments
        const PRESETS = {
            "SPY": { lot: 100, point: 1 },
            "QQQ": { lot: 100, point: 1 },
            "IWM": { lot: 100, point: 1 },
            "ES": { lot: 1, point: 50 },
            "NQ": { lot: 1, point: 20 }
        };

        // Settings management
        function saveSettings() {
            const payload = {
                equity: +$('equity').value || 0,
                riskPct: +$('riskPct').value || 0,
                riskFixed: $('riskFixed').value || '',
                dailyLossPct: +$('dailyLossPct').value || 0,
                maxOpenRiskPct: +$('maxOpenRiskPct').value || 0
            };
            localStorage.setItem(KEY_SETTINGS, JSON.stringify(payload));
        }

        function loadSettings() {
            try {
                const s = JSON.parse(localStorage.getItem(KEY_SETTINGS) || '{}');
                $('equity').value = s.equity ?? 1000000;
                $('riskPct').value = s.riskPct ?? 1;
                $('riskFixed').value = s.riskFixed ?? '';
                $('dailyLossPct').value = s.dailyLossPct ?? 3;
                $('maxOpenRiskPct').value = s.maxOpenRiskPct ?? 2;
            } catch (e) {}
        }

        // Journal management
        function saveJournal(t) {
            localStorage.setItem(KEY_JOURNAL, JSON.stringify(t));
        }

        function loadJournal() {
            try {
                return JSON.parse(localStorage.getItem(KEY_JOURNAL) || '[]');
            } catch (e) {
                return [];
            }
        }

        function saveTarget(v) {
            localStorage.setItem(KEY_TARGET, String(v || 0));
        }

        function loadTarget() {
            return +(localStorage.getItem(KEY_TARGET) || 0);
        }

        // Risk budget calculation
        function riskBudget() {
            const eq = +$('equity').value || 0;
            const fixed = +$('riskFixed').value || 0;
            const pct = +$('riskPct').value || 0;
            const b = fixed > 0 ? fixed : eq * (pct / 100);
            $('budgetBadge').textContent = 'Risk budget: ₹' + F2.format(Math.round(b));
            return Math.max(b, 0);
        }

        // Rounding rules
        function roundByRule(x, rule) {
            if (rule === 'up') return Math.ceil(x);
            if (rule === 'nearest') return Math.round(x);
            return Math.floor(x);
        }

        // Preset handling
        function onPreset() {
            const p = PRESETS[$('preset').value];
            if (p) {
                $('symbol').value = $('preset').value;
                $('lotSize').value = p.lot;
                $('pointValue').value = p.point;
            }
        }

        // ATR-based stop loss
        function maybeApplyATR(entry, side) {
            if (!$('useAtr').checked) return +$('stop').value || 0;
            const atr = +$('atr').value || 0;
            const mult = +$('atrMult').value || 0;
            if (!(atr > 0 && mult > 0)) return +$('stop').value || 0;
            const sd = atr * mult;
            let stop = side === 'Long' ? (entry - sd) : (entry + sd);
            stop = roundTick(stop);
            $('stop').value = stop.toFixed(2);
            return stop;
        }

        // Position size calculator
        function computeSizer() {
            const segment = $('segment').value;
            const side = $('side').value;
            let entry = +$('entry').value || 0;
            let stop = +$('stop').value || 0;
            
            entry = roundTick(entry);
            $('entry').value = entry.toFixed(2);
            stop = maybeApplyATR(entry, side) || roundTick(stop);
            $('stop').value = stop.toFixed(2);
            
            let target = $('target').value ? (+$('target').value || 0) : null;
            if (target) {
                target = roundTick(target);
                $('target').value = target.toFixed(2);
            }
            
            const rMultiple = $('rMultiple').value ? (+$('rMultiple').value || 0) : null;
            const lotSize = +$('lotSize').value || 1;
            const pointValue = +$('pointValue').value || 1;
            const feesExtra = +$('fees').value || 0;
            const roundRule = $('roundRule').value;

            if (!target && rMultiple) {
                if (side === 'Long') {
                    target = entry + rMultiple * (entry - stop);
                } else {
                    target = entry - rMultiple * (stop - entry);
                }
                target = roundTick(target);
                $('target').value = target.toFixed(2);
            }

            if (entry <= 0 || stop <= 0) {
                $('kStop').textContent = '–';
                $('kQty').textContent = '–';
                $('kRisk').textContent = '–';
                $('kReward').textContent = '–';
                return null;
            }

            const sd = Math.abs(entry - stop);
            if (sd === 0) {
                $('kStop').textContent = '0';
                $('kQty').textContent = '–';
                $('kRisk').textContent = '–';
                $('kReward').textContent = '–';
                return null;
            }

            const budget = riskBudget();
            let qty = 0, riskUsed = 0, exposure = 0, reward = 0;

            if (segment === 'Equity Cash') {
                const riskPerUnit = sd;
                qty = roundByRule((budget) / riskPerUnit, roundRule);
                qty = Math.max(qty, 0);
                riskUsed = qty * riskPerUnit;
                exposure = qty * entry;
                if (target) {
                    reward = qty * Math.abs(target - entry);
                }
                $('kQty').textContent = qty + ' sh';
            } else {
                const riskPerLot = sd * lotSize * pointValue;
                qty = roundByRule((budget) / riskPerLot, roundRule);
                qty = Math.max(qty, 0);
                riskUsed = qty * riskPerLot;
                exposure = qty * lotSize * entry * pointValue;
                if (target) {
                    reward = qty * Math.abs(target - entry) * lotSize * pointValue;
                }
                $('kQty').textContent = qty + ' lot(s)';
            }

            riskUsed += feesExtra;
            if (target) {
                reward = Math.max(0, reward - feesExtra);
            }

            $('kStop').textContent = sd.toFixed(2) + ' pts';
            $('kRisk').textContent = '₹' + F2.format(Math.round(riskUsed));
            $('kReward').textContent = target ? ('₹' + F2.format(Math.round(reward))) : '–';

            const guard = [];
            if (qty <= 0) guard.push('Position size rounds to 0 under current budget/fees.');
            $('guardMsg').textContent = guard.join(' ');

            return {
                segment, side, entry, stop, target, rMultiple, lotSize, pointValue,
                qty, sd, riskUsed, exposure, feesExtra,
                tags: $('sizerTags').value.trim(),
                notes: $('sizerNotes').value.trim()
            };
        }
        
        // Chart drawing functions
        function drawEquityCurve(id, rows) {
            const c = $(id);
            if (!c) return;
            const ctx = c.getContext('2d');
            ctx.clearRect(0, 0, c.width, c.height);
            
            const cum = [];
            let s = 0;
            rows.forEach(r => {
                s += (+r.pnl || 0);
                cum.push(s);
            });
            
            if (cum.length === 0) {
                ctx.fillStyle = '#94a3b8';
                ctx.fillText('No data', 10, 20);
                return;
            }
            
            const minV = Math.min(...cum);
            const maxV = Math.max(...cum);
            const pad = 30;
            const W = c.width;
            const H = c.height;
            
            // Grid
            ctx.strokeStyle = 'rgba(255,255,255,0.1)';
            ctx.beginPath();
            ctx.moveTo(pad, pad);
            ctx.lineTo(pad, H - pad);
            ctx.lineTo(W - pad, H - pad);
            ctx.stroke();
            
            // Scale functions
            const toX = i => pad + (W - 2 * pad) * (i / (cum.length - 1 || 1));
            const toY = v => H - pad - (H - 2 * pad) * ((v - minV) / ((maxV - minV) || 1));
            
            // Equity curve
            ctx.strokeStyle = '#3b82f6';
            ctx.lineWidth = 2;
            ctx.beginPath();
            ctx.moveTo(toX(0), toY(cum[0]));
            for (let i = 1; i < cum.length; i++) {
                ctx.lineTo(toX(i), toY(cum[i]));
            }
            ctx.stroke();
        }

        function drawHistogram(id, arr) {
            const c = $(id);
            if (!c) return;
            const ctx = c.getContext('2d');
            ctx.clearRect(0, 0, c.width, c.height);
            
            if (arr.length === 0) {
                ctx.fillStyle = '#94a3b8';
                ctx.fillText('No data', 10, 20);
                return;
            }
            
            const bins = 11;
            const minB = -5;
            const maxB = 5;
            const step = (maxB - minB) / bins;
            const counts = new Array(bins).fill(0);
            
            arr.forEach(v => {
                let idx = Math.floor((v - minB) / step);
                idx = Math.max(0, Math.min(bins - 1, idx));
                counts[idx]++;
            });
            
            const pad = 30;
            const W = c.width;
            const H = c.height;
            const barW = (W - 2 * pad) / bins;
            const maxC = Math.max(...counts) || 1;
            
            // Grid
            ctx.strokeStyle = 'rgba(255,255,255,0.1)';
            ctx.beginPath();
            ctx.moveTo(pad, pad);
            ctx.lineTo(pad, H - pad);
            ctx.lineTo(W - pad, H - pad);
            ctx.stroke();
            
            // Bars
            for (let i = 0; i < bins; i++) {
                const h = (H - 2 * pad) * (counts[i] / maxC);
                const x = pad + i * barW;
                const y = H - pad - h;
                ctx.fillStyle = '#1d4ed8';
                ctx.fillRect(x + 2, y, barW - 4, h);
            }
        }

        // Journal rendering
        function renderJournal() {
            const rows = loadJournal();
            const tbody = $('journalTable')?.querySelector('tbody');
            if (!tbody) return;
            tbody.innerHTML = '';
            
            rows.forEach((t, i) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1);">${t.date || ''}</td>
                    <td style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1);">${t.segment || ''}</td>
                    <td style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1);">${t.symbol || ''}</td>
                    <td style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1);">${t.side || ''}</td>
                    <td style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1);">${(+t.entry || 0).toFixed(2)}</td>
                    <td style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1);">${(+t.stop || 0).toFixed(2)}</td>
                    <td style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1);">${(+t.exit || 0).toFixed(2)}</td>
                    <td style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1);">${t.qty || 0} ${(t.segment === 'Equity Cash') ? 'sh' : 'lot(s)'}</td>
                    <td style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1);">${(t.r ?? 0).toFixed(2)}</td>
                    <td style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1);">₹${F2.format(t.pnl ?? 0)}</td>
                    <td style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1);">${t.tags || ''}</td>
                    <td style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1);">${t.notes || ''}</td>
                    <td style="padding: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1);">
                        <button class="unified-btn-secondary" onclick="editJournal(${i})" style="margin-right: 0.25rem; padding: 0.25rem 0.5rem; font-size: 0.7rem;">Edit</button>
                        <button class="unified-btn-secondary" onclick="delJournal(${i})" style="padding: 0.25rem 0.5rem; font-size: 0.7rem; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.3);">Delete</button>
                    </td>`;
                tbody.appendChild(tr);
            });

            const pnl = rows.reduce((a, b) => a + (+b.pnl || 0), 0);
            const rsum = rows.reduce((a, b) => a + (+b.r || 0), 0);
            const wins = rows.filter(t => (+t.pnl || 0) > 0).length;
            const trades = rows.length;
            const winRate = trades ? (wins / trades * 100) : 0;
            const expectancyR = trades ? (rsum / trades) : 0;

            if ($('statPnl')) $('statPnl').textContent = '₹' + F2.format(pnl);
            if ($('statR')) $('statR').textContent = F2.format(rsum);
            if ($('statWin')) $('statWin').textContent = F2.format(winRate) + ' %';
            if ($('statExp')) $('statExp').textContent = F2.format(expectancyR);

            drawEquityCurve('equityCanvas', rows);
            drawHistogram('histCanvas', rows.map(t => +t.r || 0));
            updateProgress();
        }

        // Journal management functions
        function addJournal() {
            const s = computeSizer();
            if (!s) return;
            
            const symbol = $('symbol').value.trim() || '—';
            const exit = prompt('Exit price?');
            if (exit === null) return;
            
            let exitPx = +exit;
            if (!(exitPx > 0)) return alert('Invalid exit price');
            
            exitPx = roundTick(exitPx);
            const dir = s.side === 'Long' ? 1 : -1;
            const riskPerUnit = Math.abs(s.entry - s.stop);
            const pnlPerUnit = (exitPx - s.entry) * dir;
            
            let qtyUnits = s.qty;
            let factor = 1;
            if (s.segment !== 'Equity Cash') {
                qtyUnits = s.qty * s.lotSize;
                factor = s.pointValue;
            }
            
            let pnl = qtyUnits * pnlPerUnit * factor;
            pnl -= s.feesExtra;
            
            const R = riskPerUnit > 0 ? (pnlPerUnit / riskPerUnit) : 0;
            
            const entry = {
                date: new Date().toISOString().slice(0, 10),
                segment: s.segment,
                symbol: symbol,
                side: s.side,
                entry: s.entry,
                stop: s.stop,
                exit: exitPx,
                qty: s.qty,
                lotSize: s.lotSize || null,
                pointValue: s.pointValue || 1,
                r: +(R.toFixed(2)),
                pnl: Math.round(pnl),
                tags: s.tags || '',
                notes: s.notes || ''
            };
            
            const j = loadJournal();
            j.push(entry);
            saveJournal(j);
            renderJournal();
        }

        function editJournal(i) {
            const rows = loadJournal();
            const t = rows[i];
            if (!t) return;
            
            const nd = prompt('Date (YYYY-MM-DD)', t.date) || t.date;
            const nx = +prompt('Exit price', t.exit) || t.exit;
            const np = +prompt('P&L ($)', t.pnl) || t.pnl;
            const nr = +prompt('R multiple', t.r) || t.r;
            const ntags = prompt('Tags', t.tags || '') || t.tags || '';
            const nnotes = prompt('Notes', t.notes || '') || t.notes || '';
            
            rows[i] = { ...t, date: nd, exit: nx, pnl: np, r: nr, tags: ntags, notes: nnotes };
            saveJournal(rows);
            renderJournal();
        }

        function delJournal(i) {
            const rows = loadJournal();
            const t = rows[i];
            if (!t) return;
            
            if (!confirm('Delete this trade?')) return;
            
            rows.splice(i, 1);
            saveJournal(rows);
            renderJournal();
        }

        // Export/Import functions
        function exportCsv() {
            const rows = loadJournal();
            const header = ['Date', 'Segment', 'Symbol', 'Side', 'Entry', 'Stop', 'Exit', 'Qty/Lots', 'LotSize', 'PointValue', 'R', 'PnL', 'Tags', 'Notes'];
            const lines = [header.join(',')];
            
            rows.forEach(t => {
                const vals = [
                    t.date, t.segment, t.symbol, t.side, t.entry, t.stop, t.exit, t.qty,
                    (t.lotSize || ''), (t.pointValue || ''), t.r, t.pnl,
                    (t.tags || ''), (t.notes || '').replace(/\n/g, ' ')
                ];
                lines.push(vals.map(v => `"` + String(v).replace(/"/g, '""') + `"`).join(','));
            });
            
            const blob = new Blob([lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a');
            const today = new Date().toISOString().slice(0, 10);
            a.href = URL.createObjectURL(blob);
            a.download = `risk_management_journal_${today}.csv`;
            a.click();
            URL.revokeObjectURL(a.href);
        }

        function printReport() {
            window.print();
        }

        function resetJournal() {
            if (confirm('Are you sure you want to reset all journal data?')) {
                localStorage.removeItem(KEY_JOURNAL);
                renderJournal();
            }
        }

        // Progress tracking
        function updateProgress() {
            const target = +$('monthlyTarget')?.value || 0;
            const rows = loadJournal();
            const currentMonth = new Date().toISOString().slice(0, 7);
            const monthPnL = rows
                .filter(t => t.date.startsWith(currentMonth))
                .reduce((sum, t) => sum + (+t.pnl || 0), 0);
            
            const progress = target > 0 ? Math.min(100, (monthPnL / target) * 100) : 0;
            if ($('progressBar')) $('progressBar').style.width = progress + '%';
            if ($('progressHint')) $('progressHint').textContent = `${progress.toFixed(1)}% of ₹${target.toLocaleString()} target`;
        }

        // Clear sizer function
        function clearSizer() {
            ['symbol', 'entry', 'stop', 'target', 'rMultiple', 'fees', 'atr', 'atrMult', 'sizerTags', 'sizerNotes'].forEach(id => {
                if ($(id)) $(id).value = '';
            });
            if ($('entry')) $('entry').value = '100';
            if ($('stop')) $('stop').value = '95';
            if ($('useAtr')) $('useAtr').checked = false;
            computeSizer();
        }

        // Add demo journal entries
        function addDemoData() {
            const demoData = [
                {
                    date: '2025-01-15',
                    segment: 'Equity Cash',
                    symbol: 'RELIANCE',
                    side: 'Long',
                    entry: 2500.00,
                    stop: 2450.00,
                    exit: 2580.00,
                    qty: 20,
                    r: 1.6,
                    pnl: 1600,
                    tags: 'swing, momentum',
                    notes: 'Breakout trade on earnings'
                },
                {
                    date: '2025-01-18',
                    segment: 'Equity Cash',
                    symbol: 'TCS',
                    side: 'Short',
                    entry: 3800.00,
                    stop: 3850.00,
                    exit: 3720.00,
                    qty: 15,
                    r: 2.0,
                    pnl: 1200,
                    tags: 'short, reversal',
                    notes: 'Failed breakout short'
                },
                {
                    date: '2025-01-22',
                    segment: 'Futures',
                    symbol: 'NIFTY',
                    side: 'Long',
                    entry: 22000,
                    stop: 21800,
                    exit: 22250,
                    qty: 1,
                    r: 1.25,
                    pnl: 2500,
                    tags: 'futures, breakout',
                    notes: 'NIFTY breakout trade'
                }
            ];
            
            const existingData = loadJournal();
            if (existingData.length === 0) {
                saveJournal(demoData);
                renderJournal();
            }
        }

        // Initialize trade journal display when modal opens
        function initializeTradeJournal() {
            loadSettings();
            riskBudget();
            computeSizer();
            renderJournal();
            addDemoData();
            
            // Add event listeners
            ['equity', 'riskPct', 'riskFixed', 'dailyLossPct', 'maxOpenRiskPct'].forEach(id => {
                if ($(id)) {
                    $(id).addEventListener('input', () => {
                        saveSettings();
                        riskBudget();
                        renderJournal();
                    });
                }
            });

            ['segment', 'side', 'entry', 'stop', 'target', 'rMultiple', 'lotSize', 'pointValue', 'fees', 'roundRule', 'atr', 'atrMult', 'sizerTags', 'sizerNotes'].forEach(id => {
                if ($(id)) {
                    $(id).addEventListener('input', () => {
                        computeSizer();
                    });
                }
            });

            if ($('useAtr')) {
                $('useAtr').addEventListener('change', () => {
                    computeSizer();
                });
            }

            if ($('preset')) {
                $('preset').addEventListener('change', onPreset);
            }

            if ($('searchBox')) {
                $('searchBox').addEventListener('input', renderJournal);
            }

            if ($('monthlyTarget')) {
                $('monthlyTarget').addEventListener('input', updateProgress);
            }
        }
        
        // Legacy function for compatibility
        function calculateRiskManagement() {
            computeSizer();
        }
        
        function calculatePositionSize() {
            const accountSize = parseFloat(document.getElementById('accountSize').value) || 0;
            const riskPercentage = parseFloat(document.getElementById('riskPercentage').value) || 0;
            const entryPrice = parseFloat(document.getElementById('entryPrice').value) || 0;
            const stopLoss = parseFloat(document.getElementById('stopLoss').value) || 0;
            
            if (accountSize > 0 && riskPercentage > 0 && entryPrice > 0 && stopLoss > 0) {
                const riskAmount = accountSize * (riskPercentage / 100);
                const priceDifference = Math.abs(entryPrice - stopLoss);
                const positionSize = Math.floor(riskAmount / priceDifference);
                const positionValue = positionSize * entryPrice;
                const actualRisk = positionSize * priceDifference;
                const actualRiskPercentage = (actualRisk / accountSize) * 100;
                
                const resultContent = `
                    <div class="unified-grid">
                        <div class="unified-stat">
                            <div class="unified-stat-value">${positionSize.toLocaleString()}</div>
                            <div class="unified-stat-label">Position Size (Shares)</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">₹${positionValue.toLocaleString()}</div>
                            <div class="unified-stat-label">Position Value</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">₹${riskAmount.toFixed(2)}</div>
                            <div class="unified-stat-label">Risk Amount</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">${actualRiskPercentage.toFixed(2)}%</div>
                            <div class="unified-stat-label">Actual Risk</div>
                        </div>
                    </div>
                `;
                
                document.getElementById('position-result-content').innerHTML = resultContent;
                document.getElementById('position-result').style.display = 'block';
            }
        }
        
        function calculateRiskReward() {
            const entryPrice = parseFloat(document.getElementById('entryPrice').value) || 0;
            const stopLoss = parseFloat(document.getElementById('stopLoss').value) || 0;
            const takeProfit1 = parseFloat(document.getElementById('takeProfit1').value) || 0;
            const takeProfit2 = parseFloat(document.getElementById('takeProfit2').value) || 0;
            
            if (entryPrice > 0 && stopLoss > 0 && takeProfit1 > 0) {
                const risk = Math.abs(entryPrice - stopLoss);
                const reward1 = Math.abs(takeProfit1 - entryPrice);
                const ratio1 = reward1 / risk;
                
                let resultContent = `
                    <div class="unified-grid">
                        <div class="unified-stat">
                            <div class="unified-stat-value">$${risk.toFixed(2)}</div>
                            <div class="unified-stat-label">Risk per Share</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">$${reward1.toFixed(2)}</div>
                            <div class="unified-stat-label">Reward per Share (TP1)</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">1:${ratio1.toFixed(2)}</div>
                            <div class="unified-stat-label">Risk:Reward Ratio</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">${((1/(1+ratio1))*100).toFixed(1)}%</div>
                            <div class="unified-stat-label">Win Rate Needed</div>
                        </div>
                    </div>
                `;
                
                if (takeProfit2 > 0) {
                    const reward2 = Math.abs(takeProfit2 - entryPrice);
                    const ratio2 = reward2 / risk;
                    resultContent += `
                        <div class="unified-grid" style="margin-top: 1rem;">
                            <div class="unified-stat">
                                <div class="unified-stat-value">$${reward2.toFixed(2)}</div>
                                <div class="unified-stat-label">Reward per Share (TP2)</div>
                            </div>
                            <div class="unified-stat">
                                <div class="unified-stat-value">1:${ratio2.toFixed(2)}</div>
                                <div class="unified-stat-label">Risk:Reward Ratio (TP2)</div>
                            </div>
                        </div>
                    `;
                }
                
                document.getElementById('risk-reward-result-content').innerHTML = resultContent;
                document.getElementById('risk-reward-result').style.display = 'block';
            }
        }
        
        // Additional Tool Content Generators
        function getProfitLossCalculatorContent() {
            return `
                <div class="unified-tool-container">
                    <div class="unified-tool-header">
                        <h1 class="unified-tool-title">Profit Loss Calculator</h1>
                        <p class="unified-tool-subtitle">Calculate potential profits and losses for your trades</p>
                    </div>
                    
                        <div class="unified-grid">
                            <div class="unified-form-group">
                                <label class="unified-form-label">Entry Price (₹)</label>
                                <input type="number" class="unified-form-input" id="entryPrice" placeholder="100" step="0.01" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Quantity (Shares)</label>
                                <input type="number" class="unified-form-input" id="quantity" placeholder="100" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Exit Price (₹)</label>
                                <input type="number" class="unified-form-input" id="exitPrice" placeholder="110" step="0.01" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Commission (₹)</label>
                                <input type="number" class="unified-form-input" id="commission" placeholder="20" step="0.01" value="20">
                            </div>
                        </div>
                    
                    <div style="text-align: center; margin: 2rem 0;">
                        <button class="unified-btn" onclick="calculateProfitLoss()">
                            <i class="bi bi-cash-stack"></i> Calculate P&L
                        </button>
                    </div>
                    
                    <div id="profit-loss-result" class="unified-result-card" style="display: none;">
                        <div class="unified-result-title">
                            <i class="bi bi-check-circle"></i> Profit & Loss Analysis
                        </div>
                        <div id="profit-loss-result-content"></div>
                    </div>
                </div>
            `;
        }
        
        function getMarginCalculatorContent() {
            return `
                <div class="unified-tool-container">
                    <div class="unified-tool-header">
                        <h1 class="unified-tool-title">Margin Calculator</h1>
                        <p class="unified-tool-subtitle">Determine margin requirements for your trading positions</p>
                    </div>
                    
                        <div class="unified-grid">
                            <div class="unified-form-group">
                                <label class="unified-form-label">Account Value (₹)</label>
                                <input type="number" class="unified-form-input" id="accountValue" placeholder="250000" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Stock Price (₹)</label>
                                <input type="number" class="unified-form-input" id="stockPrice" placeholder="100" step="0.01" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Number of Shares</label>
                                <input type="number" class="unified-form-input" id="shares" placeholder="100" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Margin Requirement (%)</label>
                                <select class="unified-form-input" id="marginRequirement" required>
                                    <option value="50">50% (Day Trading)</option>
                                    <option value="25">25% (Pattern Day Trader)</option>
                                    <option value="100">100% (Cash Account)</option>
                                </select>
                            </div>
                        </div>
                    
                    <div style="text-align: center; margin: 2rem 0;">
                        <button class="unified-btn" onclick="calculateMargin()">
                            <i class="bi bi-shield-check"></i> Calculate Margin
                        </button>
                    </div>
                    
                    <div id="margin-result" class="unified-result-card" style="display: none;">
                        <div class="unified-result-title">
                            <i class="bi bi-check-circle"></i> Margin Analysis
                        </div>
                        <div id="margin-result-content"></div>
                    </div>
                </div>
            `;
        }
        
        function getPortfolioAnalyzerContent() {
            return `
                <div class="unified-tool-container">
                    <div class="unified-tool-header">
                        <h1 class="unified-tool-title">Portfolio Analyzer</h1>
                        <p class="unified-tool-subtitle">Analyze your portfolio performance and risk metrics</p>
                    </div>
                    
                        <div class="unified-grid">
                            <div class="unified-form-group">
                                <label class="unified-form-label">Total Portfolio Value (₹)</label>
                                <input type="number" class="unified-form-input" id="totalPortfolioValue" placeholder="1000000" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Initial Investment (₹)</label>
                                <input type="number" class="unified-form-input" id="initialInvestment" placeholder="800000" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Time Period (Months)</label>
                                <input type="number" class="unified-form-input" id="timePeriod" placeholder="12" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Risk Tolerance</label>
                                <select class="unified-form-input" id="riskTolerance" required>
                                    <option value="conservative">Conservative</option>
                                    <option value="moderate">Moderate</option>
                                    <option value="aggressive">Aggressive</option>
                                </select>
                            </div>
                        </div>
                    
                    <div style="text-align: center; margin: 2rem 0;">
                        <button class="unified-btn" onclick="analyzePortfolio()">
                            <i class="bi bi-graph-up-arrow"></i> Analyze Portfolio
                        </button>
                    </div>
                    
                    <div id="portfolio-result" class="unified-result-card" style="display: none;">
                        <div class="unified-result-title">
                            <i class="bi bi-check-circle"></i> Portfolio Analysis
                        </div>
                        <div id="portfolio-result-content"></div>
                    </div>
                </div>
            `;
        }
        
        function getMarketSimulatorContent() {
            return `
                <div class="unified-tool-container">
                    <div class="unified-tool-header">
                        <h1 class="unified-tool-title">Market Simulator</h1>
                        <p class="unified-tool-subtitle">Practice trading with virtual money in a risk-free environment</p>
                    </div>
                    
                        <div class="unified-grid">
                            <div class="unified-form-group">
                                <label class="unified-form-label">Initial Capital (₹)</label>
                                <input type="number" class="unified-form-input" id="initialCapital" placeholder="100000" value="100000" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Stock Symbol</label>
                                <input type="text" class="unified-form-input" id="stockSymbol" placeholder="RELIANCE" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Current Price (₹)</label>
                                <input type="number" class="unified-form-input" id="currentPrice" placeholder="2500" step="0.01" required>
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Number of Shares</label>
                                <input type="number" class="unified-form-input" id="shares" placeholder="10" required>
                            </div>
                        </div>
                    
                    <div style="text-align: center; margin: 2rem 0;">
                        <button class="unified-btn" onclick="executeTrade()">
                            <i class="bi bi-controller"></i> Execute Trade
                        </button>
                    </div>
                    
                    <div id="simulator-result" class="unified-result-card" style="display: none;">
                        <div class="unified-result-title">
                            <i class="bi bi-wallet2"></i> Portfolio Status
                        </div>
                        <div id="simulator-result-content"></div>
                    </div>
                </div>
            `;
        }
        
        function getChartAnalysisToolContent() {
            return `
                <div class="unified-tool-container">
                    <div class="unified-tool-header">
                        <h1 class="unified-tool-title">Chart Analysis Tool</h1>
                        <p class="unified-tool-subtitle">Advanced charting and technical analysis tools</p>
                    </div>
                    
                        <div class="unified-grid">
                            <div class="unified-form-group">
                                <label class="unified-form-label">Stock Symbol</label>
                                <input type="text" class="unified-form-input" id="symbol" placeholder="RELIANCE" required>
                            </div>
                        <div class="unified-form-group">
                            <label class="unified-form-label">Timeframe</label>
                            <select class="unified-form-input" id="timeframe" required>
                                <option value="1D">1 Day</option>
                                <option value="1W">1 Week</option>
                                <option value="1M">1 Month</option>
                                <option value="3M">3 Months</option>
                                <option value="6M">6 Months</option>
                                <option value="1Y">1 Year</option>
                            </select>
                        </div>
                        <div class="unified-form-group">
                            <label class="unified-form-label">Chart Type</label>
                            <select class="unified-form-input" id="chartType" required>
                                <option value="candlestick">Candlestick</option>
                                <option value="line">Line</option>
                                <option value="bar">Bar</option>
                            </select>
                        </div>
                        <div class="unified-form-group">
                            <label class="unified-form-label">&nbsp;</label>
                            <button class="unified-btn" onclick="analyzeChart()">
                                <i class="bi bi-graph-up"></i> Analyze
                            </button>
                        </div>
                    </div>
                    
                    <div id="chart-result" class="unified-result-card" style="display: none;">
                        <div class="unified-result-title">
                            <i class="bi bi-bar-chart"></i> Technical Analysis
                        </div>
                        <div id="chart-result-content"></div>
                    </div>
                </div>
            `;
        }
        
        // Additional Calculation Functions
        function calculateProfitLoss() {
            const entryPrice = parseFloat(document.getElementById('entryPrice').value) || 0;
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const exitPrice = parseFloat(document.getElementById('exitPrice').value) || 0;
            const commission = parseFloat(document.getElementById('commission').value) || 0;
            
            if (entryPrice > 0 && quantity > 0 && exitPrice > 0) {
                const grossProfit = (exitPrice - entryPrice) * quantity;
                const totalCommission = commission * 2; // Entry + Exit
                const netProfit = grossProfit - totalCommission;
                const totalInvestment = entryPrice * quantity;
                const returnPercentage = (netProfit / totalInvestment) * 100;
                
                const resultContent = `
                    <div class="unified-grid">
                        <div class="unified-stat">
                            <div class="unified-stat-value">₹${grossProfit.toFixed(2)}</div>
                            <div class="unified-stat-label">Gross P&L</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">₹${totalCommission.toFixed(2)}</div>
                            <div class="unified-stat-label">Commission</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">₹${netProfit.toFixed(2)}</div>
                            <div class="unified-stat-label">Net P&L</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">${returnPercentage.toFixed(2)}%</div>
                            <div class="unified-stat-label">Return %</div>
                        </div>
                    </div>
                `;
                
                document.getElementById('profit-loss-result-content').innerHTML = resultContent;
                document.getElementById('profit-loss-result').style.display = 'block';
            }
        }
        
        function calculateMargin() {
            const accountValue = parseFloat(document.getElementById('accountValue').value) || 0;
            const stockPrice = parseFloat(document.getElementById('stockPrice').value) || 0;
            const shares = parseInt(document.getElementById('shares').value) || 0;
            const marginRequirement = parseFloat(document.getElementById('marginRequirement').value) || 0;
            
            if (accountValue > 0 && stockPrice > 0 && shares > 0) {
                const totalPositionValue = stockPrice * shares;
                const requiredMargin = totalPositionValue * (marginRequirement / 100);
                const availableMargin = accountValue * 0.95; // 95% of account value for safety
                const canAfford = requiredMargin <= availableMargin;
                const buyingPower = (accountValue * 0.95) / (marginRequirement / 100);
                
                const resultContent = `
                    <div class="unified-grid">
                        <div class="unified-stat">
                            <div class="unified-stat-value">₹${totalPositionValue.toLocaleString()}</div>
                            <div class="unified-stat-label">Total Position Value</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">₹${requiredMargin.toLocaleString()}</div>
                            <div class="unified-stat-label">Required Margin</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">₹${availableMargin.toLocaleString()}</div>
                            <div class="unified-stat-label">Available Margin</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">${canAfford ? 'Yes' : 'No'}</div>
                            <div class="unified-stat-label">Can Afford</div>
                        </div>
                    </div>
                `;
                
                document.getElementById('margin-result-content').innerHTML = resultContent;
                document.getElementById('margin-result').style.display = 'block';
            }
        }
        
        function analyzePortfolio() {
            const totalValue = parseFloat(document.getElementById('totalPortfolioValue').value) || 0;
            const initialInvestment = parseFloat(document.getElementById('initialInvestment').value) || 0;
            const timePeriod = parseFloat(document.getElementById('timePeriod').value) || 0;
            const riskTolerance = document.getElementById('riskTolerance').value;
            
            if (totalValue > 0 && initialInvestment > 0 && timePeriod > 0) {
                const totalReturn = totalValue - initialInvestment;
                const returnPercentage = (totalReturn / initialInvestment) * 100;
                const annualizedReturn = (Math.pow(totalValue / initialInvestment, 12 / timePeriod) - 1) * 100;
                const monthlyReturn = returnPercentage / timePeriod;
                
                const resultContent = `
                    <div class="unified-grid">
                        <div class="unified-stat">
                            <div class="unified-stat-value">₹${totalReturn.toLocaleString()}</div>
                            <div class="unified-stat-label">Total Return</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">${returnPercentage.toFixed(2)}%</div>
                            <div class="unified-stat-label">Return %</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">${annualizedReturn.toFixed(2)}%</div>
                            <div class="unified-stat-label">Annualized Return</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">${monthlyReturn.toFixed(2)}%</div>
                            <div class="unified-stat-label">Monthly Return</div>
                        </div>
                    </div>
                `;
                
                document.getElementById('portfolio-result-content').innerHTML = resultContent;
                document.getElementById('portfolio-result').style.display = 'block';
            }
        }
        
        function executeTrade() {
            const symbol = document.getElementById('stockSymbol').value.toUpperCase();
            const currentPrice = parseFloat(document.getElementById('currentPrice').value) || 0;
            const shares = parseInt(document.getElementById('shares').value) || 0;
            
            if (symbol && currentPrice > 0 && shares > 0) {
                const tradeValue = currentPrice * shares;
                const resultContent = `
                    <div class="unified-grid">
                        <div class="unified-stat">
                            <div class="unified-stat-value">${symbol}</div>
                            <div class="unified-stat-label">Symbol</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">₹${currentPrice.toFixed(2)}</div>
                            <div class="unified-stat-label">Current Price</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">${shares}</div>
                            <div class="unified-stat-label">Shares</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">₹${tradeValue.toFixed(2)}</div>
                            <div class="unified-stat-label">Trade Value</div>
                        </div>
                    </div>
                `;
                
                document.getElementById('simulator-result-content').innerHTML = resultContent;
                document.getElementById('simulator-result').style.display = 'block';
            }
        }
        
        function analyzeChart() {
            const symbol = document.getElementById('symbol').value.toUpperCase();
            const timeframe = document.getElementById('timeframe').value;
            const chartType = document.getElementById('chartType').value;
            
            if (symbol) {
                const resultContent = `
                    <div class="unified-grid">
                        <div class="unified-stat">
                            <div class="unified-stat-value">${symbol}</div>
                            <div class="unified-stat-label">Symbol</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">${timeframe}</div>
                            <div class="unified-stat-label">Timeframe</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">${chartType}</div>
                            <div class="unified-stat-label">Chart Type</div>
                        </div>
                        <div class="unified-stat">
                            <div class="unified-stat-value">$${(150 + Math.random() * 20).toFixed(2)}</div>
                            <div class="unified-stat-label">Current Price</div>
                        </div>
                    </div>
                `;
                
                document.getElementById('chart-result-content').innerHTML = resultContent;
                document.getElementById('chart-result').style.display = 'block';
            }
        }
    </script>
    
    <!-- Cache Busting Script -->
    <script>
        // Force reload if page is cached
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
        
        // Add cache-busting parameter to all internal links
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a[href^="/"], a[href^="./"], a[href^="../"]');
            links.forEach(link => {
                if (!link.href.includes('?v=')) {
                    link.href += (link.href.includes('?') ? '&' : '?') + 'v=' + Date.now();
                }
            });
        });
    </script>
    
    <!-- Include app.js for profile menu functionality -->
    <script src="./assets/app.js"></script>
</body>
</html>

    </div>

    


    <!-- Authentication is now handled server-side -->
    
    
    <!-- Scripts with defer for better performance -->

    <script src="./assets/app.js?v=<?php echo time(); ?>" defer></script>

    <script src="./assets/charts.js?v=<?php echo time(); ?>" defer></script>

    <script src="./assets/animations.js?v=<?php echo time(); ?>" defer></script>

    <script src="./assets/trading-background.js?v=<?php echo time(); ?>" defer></script>

    
    
    <!-- Tools-specific JavaScript -->

    <script>

        // Position Size Calculator

        function calculatePosition() {

            const accountSize = parseFloat(document.getElementById('account-size').value) || 0;

            const riskPercentage = parseFloat(document.getElementById('risk-percentage').value) || 0;

            const stopLoss = parseFloat(document.getElementById('stop-loss').value) || 0;

            
            
            if (accountSize > 0 && riskPercentage > 0 && stopLoss > 0) {

                const riskAmount = (accountSize * riskPercentage) / 100;

                const positionSize = Math.floor(riskAmount / stopLoss);

                
                
                document.getElementById('position-size').textContent = positionSize.toLocaleString();

                document.getElementById('risk-amount').textContent = '₹' + riskAmount.toLocaleString();

            }

        }

        
        
        // Risk-Reward Calculator

        function calculateRiskReward() {

            const entryPrice = parseFloat(document.getElementById('entry-price').value) || 0;

            const targetPrice = parseFloat(document.getElementById('target-price').value) || 0;

            const stopLossPrice = parseFloat(document.getElementById('stop-loss-price').value) || 0;

            
            
            if (entryPrice > 0 && targetPrice > 0 && stopLossPrice > 0) {

                const potentialProfit = targetPrice - entryPrice;

                const potentialLoss = entryPrice - stopLossPrice;

                const riskRewardRatio = (potentialProfit / potentialLoss).toFixed(2);

                
                
                document.getElementById('risk-reward-ratio').textContent = riskRewardRatio + ':1';

                document.getElementById('potential-profit').textContent = '₹' + potentialProfit.toFixed(2);

            }

        }

        
        
        // Risk Management Tool

        function openRiskManagement() {

            window.open('/riskmanagement.php', '_blank', 'width=1400,height=800,scrollbars=yes,resizable=yes');
        }
        
        // Tool interaction functions
        function openTool(toolId, toolSlug) {
            // Log tool usage
            fetch('./track_tool_usage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    tool_id: toolId,
                    action: 'open'
                })
            }).catch(error => console.log('Tool usage tracking failed:', error));
            
            // Open tool in modal based on slug
            const toolContentMap = {
                'position-size-calculator': {
                    title: 'Position Size Calculator',
                    content: getPositionSizeCalculatorContent()
                },
                'risk-reward-calculator': {
                    title: 'Risk Reward Calculator',
                    content: getRiskRewardCalculatorContent()
                },
                'profit-loss-calculator': {
                    title: 'Profit Loss Calculator',
                    content: getProfitLossCalculatorContent()
                },
                'margin-calculator': {
                    title: 'Margin Calculator',
                    content: getMarginCalculatorContent()
                },
                'portfolio-analyzer': {
                    title: 'Portfolio Analyzer',
                    content: getPortfolioAnalyzerContent()
                },
                'market-simulator': {
                    title: 'Market Simulator',
                    content: getMarketSimulatorContent()
                },
                'chart-analysis-tool': {
                    title: 'Chart Analysis Tool',
                    content: getChartAnalysisToolContent()
                }
            };
            
            const tool = toolContentMap[toolSlug];
            if (tool) {
                openToolModal(tool.title, tool.content);
            } else {
                alert('Tool "' + toolSlug + '" is not yet available.\n\nThis feature is coming soon!');
            }
        }
        
        function viewToolDetails(toolId) {
            // Log tool details view
            fetch('./track_tool_usage.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    tool_id: toolId,
                    action: 'view_details'
                })
            }).catch(error => console.log('Tool usage tracking failed:', error));
            
            alert('Tool details coming soon!');
        }
        
        // Tool implementations
        function showPositionCalculator() {
            alert('Position Size Calculator - Coming Soon!');
        }
        
        function showRiskRewardCalculator() {
            alert('Risk-Reward Calculator - Coming Soon!');
        }
        
        function showProfitLossCalculator() {
            alert('Profit-Loss Calculator - Coming Soon!');
        }
        
        function showMarginCalculator() {
            alert('Margin Calculator - Coming Soon!');
        }
        
        function showPortfolioAnalyzer() {
            alert('Portfolio Analyzer - Coming Soon!');
        }
        
        function showMarketSimulator() {
            alert('Market Simulator - Coming Soon!');
        }
        
        function showChartAnalysisTool() {
            alert('Chart Analysis Tool - Coming Soon!');
        }

    </script>

    
    
    <!-- Cache Busting Script -->

    <script>

        // Force reload if page is cached

        window.addEventListener('pageshow', function(event) {

            if (event.persisted) {

                window.location.reload();

            }

        });

        
        
        // Add cache-busting parameter to all internal links

        document.addEventListener('DOMContentLoaded', function() {

            const links = document.querySelectorAll('a[href^="/"], a[href^="./"], a[href^="../"]');

            links.forEach(link => {

                if (!link.href.includes('?v=')) {

                    link.href += (link.href.includes('?') ? '&' : '?') + 'v=' + Date.now();

                }

            });

        });

    </script>

    
    <!-- Include app.js for profile menu functionality -->
    <script src="./assets/app.js"></script>
</body>
</html>