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
        
        /* Tools Hero Container and Grid */
        .tools-hero-container {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 300px;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .tools-icon-grid {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            max-width: 200px;
            position: relative;
            align-items: center;
        }

        .tool-icon {
            position: relative;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .tool-icon:hover {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        .tools-glow {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.3; transform: translate(-50%, -50%) scale(1); }
            50% { opacity: 0.6; transform: translate(-50%, -50%) scale(1.1); }
        }

        /* Icon Description Styling */
        .icon-description {
            position: absolute;
            font-size: 1rem;
            font-weight: 600;
            color: #ffffff;
            text-transform: none;
            letter-spacing: 0.5px;
            white-space: normal;
            width: 300px !important;
            max-width: 300px !important;
            line-height: 1.6;
            z-index: 10;
            pointer-events: none;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0.9;
            text-align: left;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 12px 16px;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.2);
        }

        .icon-description.icon-right {
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 25px;
        }

        .icon-description.icon-left {
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-right: 25px;
            text-align: right;
        }

        .tool-icon:hover .icon-description {
            color: #ffffff;
            text-shadow: 0 0 20px rgba(59, 130, 246, 0.8);
            opacity: 1;
            transform: translateY(-50%) scale(1.05);
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(139, 92, 246, 0.2));
            border: 1px solid rgba(59, 130, 246, 0.3);
            box-shadow: 0 8px 30px rgba(59, 130, 246, 0.4);
        }

        /* Responsive adjustments for icon descriptions */
        @media (max-width: 1200px) {
            .tools-hero-container {
                max-width: 1200px;
            }
            
            .icon-description {
                width: 250px !important;
                max-width: 250px !important;
                font-size: 0.9rem;
                padding: 10px 14px;
            }
        }

        @media (max-width: 1024px) {
            .tools-hero-container {
                max-width: 1000px;
            }
            
            .icon-description {
                width: 220px !important;
                max-width: 220px !important;
                font-size: 0.85rem;
                padding: 10px 12px;
            }
        }

        @media (max-width: 768px) {
            .tools-icon-grid {
                gap: 1.5rem;
                max-width: 200px;
            }
            
            .tool-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
            }
            
            .icon-description {
                font-size: 0.8rem;
                width: 180px !important;
                max-width: 180px !important;
                line-height: 1.4;
                padding: 8px 10px;
            }
            
            .icon-description.icon-right {
                margin-left: 15px;
            }
            
            .icon-description.icon-left {
                margin-right: 15px;
            }
        }

        @media (max-width: 480px) {
            .icon-description {
                font-size: 0.75rem;
                width: 150px !important;
                max-width: 150px !important;
                line-height: 1.3;
                padding: 6px 8px;
            }
            
            .icon-description.icon-right {
                margin-left: 12px;
            }
            
            .icon-description.icon-left {
                margin-right: 12px;
            }
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
            border: 2px solid rgba(239, 68, 68, 0.3);
            background: linear-gradient(145deg, rgba(239, 68, 68, 0.05), rgba(239, 68, 68, 0.02));
            position: relative;
        }
        
        .premium-tool::before {
            content: 'Special';
            position: absolute;
            top: -10px;
            right: 20px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            z-index: 10;
        }
        
        .premium-tool .tool-header h3 {
            color: #ef4444;
        }
        
        .premium-tool .btn-primary {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none;
        }
        
        .premium-tool .btn-primary:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
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
                                        <span class="icon-description icon-right">Advanced Market Analysis Tools for Educational Trading Research & Technical Analysis</span>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-calculator"></i>
                                        <span class="icon-description icon-left">Professional Risk Management Calculators for Position Sizing & Portfolio Protection</span>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-bar-chart"></i>
                                        <span class="icon-description icon-right">Comprehensive Portfolio Analytics & Performance Tracking for Educational Purposes</span>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-speedometer2"></i>
                                        <span class="icon-description icon-left">Real-Time Trading Performance Monitoring & Educational Market Simulation Tools</span>
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
            
            // Get tool details based on ID
            const toolDetails = getToolDetails(toolId);
            if (toolDetails) {
                showToolDetailsModal(toolDetails);
            } else {
                showError('Tool details not available', 'error');
            }
        }
        
        // Tool implementations
        function showPositionCalculator() {
            alert('Position Size Calculator - Coming Soon!');
        }
        
        function showRiskRewardCalculator() {
            alert('Risk-Reward Calculator - Coming Soon!');
        }
        
        // Enhanced Profit-Loss Calculator
        function showProfitLossCalculator() {
            const content = getProfitLossCalculatorContent();
            showToolModal('Profit-Loss Calculator', content);
        }
        
        // Enhanced Margin Calculator
        function showMarginCalculator() {
            const content = getMarginCalculatorContent();
            showToolModal('Margin Calculator', content);
        }
        
        // Enhanced Portfolio Analyzer
        function showPortfolioAnalyzer() {
            const content = getPortfolioAnalyzerContent();
            showToolModal('Portfolio Analyzer', content);
        }
        
        // Enhanced Market Simulator
        function showMarketSimulator() {
            const content = getMarketSimulatorContent();
            showToolModal('Market Simulator', content);
        }
        
        // Enhanced Chart Analysis Tool
        function showChartAnalysisTool() {
            const content = getChartAnalysisToolContent();
            showToolModal('Chart Analysis Tool', content);
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
            
            // Show loading state
            showLoadingState();
            
            // Fetch real user data
            fetch('./test_api_simple.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                hideLoadingState();
                if (data.success) {
                    const content = getRiskManagementToolContent(data.data);
                    openToolModal('Advanced Risk Management', content);
                } else {
                    showError(`Failed to load data: ${data.message || 'Unknown error'}`, 'error');
                }
            })
            .catch(error => {
                hideLoadingState();
                console.error('Error loading risk management data:', error);
                showError(`Error loading data: ${error.message}. Please check if you're logged in.`, 'error');
            });
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

        // Add loading states for better UX
        function showLoadingState(element) {
            if (element) {
                const originalText = element.textContent;
                element.textContent = 'Loading...';
                element.disabled = true;
                return () => {
                    element.textContent = originalText;
                    element.disabled = false;
                };
            } else {
                // Global loading state
                const loadingDiv = document.createElement('div');
                loadingDiv.id = 'global-loading';
                loadingDiv.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.8);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    color: white;
                    font-size: 1.2rem;
                `;
                loadingDiv.innerHTML = '<div style="text-align: center;"><div style="border: 3px solid #3b82f6; border-top: 3px solid transparent; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>Loading...</div>';
                document.body.appendChild(loadingDiv);
            }
        }

        function hideLoadingState() {
            const loadingDiv = document.getElementById('global-loading');
            if (loadingDiv) {
                loadingDiv.remove();
            }
        }

        // Enhanced error handling with user-friendly messages
        function showError(message, type = 'error') {
            const errorDiv = document.createElement('div');
            errorDiv.className = `alert alert-${type}`;
            errorDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'error' ? '#dc2626' : '#059669'};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 10000;
                animation: slideIn 0.3s ease;
            `;
            errorDiv.textContent = message;
            document.body.appendChild(errorDiv);
            
            setTimeout(() => {
                errorDiv.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => errorDiv.remove(), 300);
            }, 3000);
        }

        // Add CSS for animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);

        // Keyboard shortcuts for better accessibility
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + 1-4 for quick tool access
            if ((e.ctrlKey || e.metaKey) && e.key >= '1' && e.key <= '4') {
                e.preventDefault();
                const toolIndex = parseInt(e.key) - 1;
                const toolCards = document.querySelectorAll('.tool-card');
                if (toolCards[toolIndex]) {
                    const button = toolCards[toolIndex].querySelector('.btn-primary');
                    if (button) button.click();
                }
            }
            
            // Ctrl/Cmd + K for search/focus
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                const searchInput = document.querySelector('input[type="search"], input[placeholder*="search" i]');
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
        });

        // Add tooltip system for better UX
        function addTooltip(element, text) {
            element.setAttribute('title', text);
            element.style.cursor = 'help';
        }

        // Initialize tooltips for form inputs
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[type="number"], input[type="text"]');
            inputs.forEach(input => {
                if (input.placeholder) {
                    addTooltip(input, input.placeholder);
                }
            });

            // Performance monitoring
            if ('performance' in window) {
                window.addEventListener('load', function() {
                    setTimeout(() => {
                        const perfData = performance.getEntriesByType('navigation')[0];
                        if (perfData) {
                            console.log('Page Load Performance:', {
                                loadTime: Math.round(perfData.loadEventEnd - perfData.loadEventStart),
                                domContentLoaded: Math.round(perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart),
                                totalTime: Math.round(perfData.loadEventEnd - perfData.fetchStart)
                            });
                        }
                    }, 0);
                });
            }

            // Add smooth scroll behavior
            document.documentElement.style.scrollBehavior = 'smooth';
        });

        // Tool Details System
        function getToolDetails(toolId) {
            const toolDetailsMap = {
                'risk-management': {
                    title: 'Advanced Risk Management',
                    category: 'Risk Analysis',
                    description: 'Comprehensive risk management tool with position sizing, trade journal, and analytics.',
                    features: [
                        'Position Size Calculator',
                        'Risk-Reward Analysis',
                        'Trade Journal & Analytics',
                        'Portfolio Risk Assessment',
                        'Real-time Risk Monitoring',
                        'Performance Tracking'
                    ],
                    benefits: [
                        'Protect your capital with proper position sizing',
                        'Maintain consistent risk across all trades',
                        'Track and analyze your trading performance',
                        'Identify risk patterns and improve strategies',
                        'Optimize portfolio allocation',
                        'Reduce emotional trading decisions'
                    ],
                    useCases: [
                        'Calculate optimal position size for each trade',
                        'Set appropriate stop-loss levels',
                        'Track daily/weekly/monthly performance',
                        'Analyze win rate and profit factors',
                        'Monitor portfolio drawdown',
                        'Plan risk budget allocation'
                    ],
                    difficulty: 'Intermediate',
                    timeToLearn: '15-30 minutes',
                    prerequisites: 'Basic understanding of trading concepts'
                },
                'position-calculator': {
                    title: 'Position Size Calculator',
                    category: 'Risk Management',
                    description: 'Calculate the optimal position size based on your risk tolerance and account size.',
                    features: [
                        'Account Size Input',
                        'Risk Percentage Calculator',
                        'Stop Loss Integration',
                        'Position Size Formula',
                        'Risk Amount Display',
                        'Multiple Asset Support'
                    ],
                    benefits: [
                        'Prevents over-leveraging',
                        'Maintains consistent risk per trade',
                        'Protects account from large losses',
                        'Improves long-term profitability',
                        'Reduces emotional stress',
                        'Enables systematic trading'
                    ],
                    useCases: [
                        'Stock trading position sizing',
                        'Forex position calculation',
                        'Options position management',
                        'Crypto trading risk control',
                        'Portfolio rebalancing',
                        'Risk budget planning'
                    ],
                    difficulty: 'Beginner',
                    timeToLearn: '5-10 minutes',
                    prerequisites: 'Understanding of risk management basics'
                },
                'risk-reward': {
                    title: 'Risk-Reward Calculator',
                    category: 'Analysis',
                    description: 'Analyze the risk-reward ratio of your trades to ensure profitable setups.',
                    features: [
                        'Entry Price Analysis',
                        'Target Price Calculation',
                        'Stop Loss Assessment',
                        'Risk-Reward Ratio Display',
                        'Profit/Loss Projection',
                        'Multiple Timeframe Support'
                    ],
                    benefits: [
                        'Identifies high-probability setups',
                        'Improves trade selection',
                        'Increases win rate',
                        'Maximizes profit potential',
                        'Reduces losing trades',
                        'Enhances trading discipline'
                    ],
                    useCases: [
                        'Pre-trade analysis',
                        'Setup validation',
                        'Trade planning',
                        'Strategy backtesting',
                        'Performance optimization',
                        'Risk assessment'
                    ],
                    difficulty: 'Beginner',
                    timeToLearn: '5-10 minutes',
                    prerequisites: 'Basic understanding of entry/exit points'
                },
                'portfolio-analyzer': {
                    title: 'Portfolio Analyzer',
                    category: 'Portfolio Management',
                    description: 'Comprehensive portfolio analysis with performance metrics and risk assessment.',
                    features: [
                        'Portfolio Performance Tracking',
                        'Risk Metrics Calculation',
                        'Correlation Analysis',
                        'Diversification Assessment',
                        'Performance Attribution',
                        'Benchmark Comparison'
                    ],
                    benefits: [
                        'Optimizes portfolio allocation',
                        'Identifies risk concentrations',
                        'Improves diversification',
                        'Tracks performance over time',
                        'Enables data-driven decisions',
                        'Reduces portfolio volatility'
                    ],
                    useCases: [
                        'Portfolio rebalancing',
                        'Risk assessment',
                        'Performance evaluation',
                        'Asset allocation optimization',
                        'Diversification analysis',
                        'Benchmark tracking'
                    ],
                    difficulty: 'Advanced',
                    timeToLearn: '30-45 minutes',
                    prerequisites: 'Understanding of portfolio theory and risk metrics'
                },
                'volatility-calculator': {
                    title: 'Volatility Calculator',
                    category: 'Market Analysis',
                    description: 'Calculate and analyze market volatility for better trading decisions.',
                    features: [
                        'Historical Volatility Calculation',
                        'Implied Volatility Analysis',
                        'Volatility Percentiles',
                        'Volatility Forecasting',
                        'Options Pricing Integration',
                        'Risk Assessment Tools'
                    ],
                    benefits: [
                        'Better timing of entries/exits',
                        'Improved options strategies',
                        'Enhanced risk management',
                        'Market condition awareness',
                        'Volatility-based position sizing',
                        'Strategy optimization'
                    ],
                    useCases: [
                        'Options trading strategies',
                        'Volatility-based entries',
                        'Risk assessment',
                        'Market timing',
                        'Strategy selection',
                        'Portfolio hedging'
                    ],
                    difficulty: 'Intermediate',
                    timeToLearn: '20-30 minutes',
                    prerequisites: 'Understanding of volatility concepts and options'
                },
                'correlation-analyzer': {
                    title: 'Correlation Analyzer',
                    category: 'Market Analysis',
                    description: 'Analyze correlations between different assets and markets.',
                    features: [
                        'Asset Correlation Matrix',
                        'Time Period Analysis',
                        'Correlation Trends',
                        'Diversification Insights',
                        'Risk Assessment',
                        'Portfolio Optimization'
                    ],
                    benefits: [
                        'Improves diversification',
                        'Reduces portfolio risk',
                        'Identifies market relationships',
                        'Optimizes asset allocation',
                        'Enhances risk management',
                        'Better strategy selection'
                    ],
                    useCases: [
                        'Portfolio diversification',
                        'Risk management',
                        'Asset allocation',
                        'Strategy development',
                        'Market analysis',
                        'Hedging strategies'
                    ],
                    difficulty: 'Intermediate',
                    timeToLearn: '15-25 minutes',
                    prerequisites: 'Understanding of correlation and diversification'
                }
            };

            return toolDetailsMap[toolId] || null;
        }

        function showToolDetailsModal(toolDetails) {
            const modalContent = `
                <div class="tool-details-container">
                    <div class="tool-details-header">
                        <div class="tool-details-title-section">
                            <h2 class="tool-details-title">${toolDetails.title}</h2>
                            <div class="tool-details-meta">
                                <span class="tool-category">${toolDetails.category}</span>
                                <span class="tool-difficulty">${toolDetails.difficulty}</span>
                                <span class="tool-time">${toolDetails.timeToLearn}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tool-details-content">
                        <div class="tool-description">
                            <h3>Description</h3>
                            <p>${toolDetails.description}</p>
                        </div>
                        
                        <div class="tool-prerequisites">
                            <h3>Prerequisites</h3>
                            <p>${toolDetails.prerequisites}</p>
                        </div>
                        
                        <div class="tool-features">
                            <h3>Key Features</h3>
                            <ul>
                                ${toolDetails.features.map(feature => `<li>${feature}</li>`).join('')}
                            </ul>
                        </div>
                        
                        <div class="tool-benefits">
                            <h3>Benefits</h3>
                            <ul>
                                ${toolDetails.benefits.map(benefit => `<li>${benefit}</li>`).join('')}
                            </ul>
                        </div>
                        
                        <div class="tool-use-cases">
                            <h3>Use Cases</h3>
                            <ul>
                                ${toolDetails.useCases.map(useCase => `<li>${useCase}</li>`).join('')}
                            </ul>
                        </div>
                    </div>
                    
                    <div class="tool-details-actions">
                        <button class="btn btn-primary" onclick="closeToolDetailsModal()">
                            <i class="bi bi-check-circle"></i> Got it
                        </button>
                    </div>
                </div>
            `;

            // Create modal if it doesn't exist
            let detailsModal = document.getElementById('tool-details-modal');
            if (!detailsModal) {
                detailsModal = document.createElement('div');
                detailsModal.id = 'tool-details-modal';
                detailsModal.className = 'tool-details-modal';
                detailsModal.innerHTML = `
                    <div class="tool-details-modal-content">
                        <button class="tool-details-close" onclick="closeToolDetailsModal()">
                            <i class="bi bi-x-lg"></i>
                        </button>
                        <div id="tool-details-content"></div>
                    </div>
                `;
                document.body.appendChild(detailsModal);
            }

            // Add styles if not already added
            if (!document.getElementById('tool-details-styles')) {
                const styles = document.createElement('style');
                styles.id = 'tool-details-styles';
                styles.textContent = `
                    .tool-details-modal {
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
                    
                    .tool-details-modal.active {
                        opacity: 1;
                        visibility: visible;
                    }
                    
                    .tool-details-modal-content {
                        background: linear-gradient(145deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.9));
                        border: 1px solid rgba(59, 130, 246, 0.2);
                        border-radius: 16px;
                        max-width: 600px;
                        width: 90%;
                        max-height: 80vh;
                        overflow-y: auto;
                        position: relative;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
                    }
                    
                    .tool-details-close {
                        position: absolute;
                        top: 15px;
                        right: 15px;
                        background: rgba(255, 255, 255, 0.1);
                        border: none;
                        color: #ffffff;
                        width: 35px;
                        height: 35px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        cursor: pointer;
                        transition: all 0.3s ease;
                        z-index: 10;
                    }
                    
                    .tool-details-close:hover {
                        background: rgba(255, 255, 255, 0.2);
                        transform: scale(1.1);
                    }
                    
                    .tool-details-container {
                        padding: 2rem;
                        color: #ffffff;
                    }
                    
                    .tool-details-header {
                        margin-bottom: 2rem;
                        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                        padding-bottom: 1rem;
                    }
                    
                    .tool-details-title {
                        font-size: 1.8rem;
                        font-weight: 700;
                        margin: 0 0 1rem 0;
                        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                        background-clip: text;
                    }
                    
                    .tool-details-meta {
                        display: flex;
                        gap: 1rem;
                        flex-wrap: wrap;
                    }
                    
                    .tool-category, .tool-difficulty, .tool-time {
                        background: rgba(59, 130, 246, 0.2);
                        padding: 0.5rem 1rem;
                        border-radius: 20px;
                        font-size: 0.85rem;
                        font-weight: 500;
                        border: 1px solid rgba(59, 130, 246, 0.3);
                    }
                    
                    .tool-details-content h3 {
                        color: #3b82f6;
                        font-size: 1.2rem;
                        margin: 1.5rem 0 0.5rem 0;
                        font-weight: 600;
                    }
                    
                    .tool-details-content p {
                        color: #cbd5e1;
                        line-height: 1.6;
                        margin-bottom: 1rem;
                    }
                    
                    .tool-details-content ul {
                        color: #e2e8f0;
                        padding-left: 1.5rem;
                        margin-bottom: 1rem;
                    }
                    
                    .tool-details-content li {
                        margin-bottom: 0.5rem;
                        line-height: 1.5;
                    }
                    
                    .tool-details-actions {
                        margin-top: 2rem;
                        text-align: center;
                        border-top: 1px solid rgba(255, 255, 255, 0.1);
                        padding-top: 1rem;
                    }
                    
                    @media (max-width: 768px) {
                        .tool-details-modal-content {
                            width: 95%;
                            max-height: 90vh;
                        }
                        
                        .tool-details-container {
                            padding: 1.5rem;
                        }
                        
                        .tool-details-title {
                            font-size: 1.5rem;
                        }
                        
                        .tool-details-meta {
                            gap: 0.5rem;
                        }
                        
                        .tool-category, .tool-difficulty, .tool-time {
                            font-size: 0.8rem;
                            padding: 0.4rem 0.8rem;
                        }
                    }
                `;
                document.head.appendChild(styles);
            }

            document.getElementById('tool-details-content').innerHTML = modalContent;
            detailsModal.classList.add('active');
        }

        function closeToolDetailsModal() {
            const modal = document.getElementById('tool-details-modal');
            if (modal) {
                modal.classList.remove('active');
            }
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.id === 'tool-details-modal') {
                closeToolDetailsModal();
            }
        });
        
        // Tool Content Generators
        function getRiskManagementToolContent(userData = null) {
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
                                <input type="number" class="unified-form-input" id="equity" value="${userData?.account_size || 1000000}" placeholder="1000000">
                            </div>
                            <div class="unified-form-group">
                                <label class="unified-form-label">Risk Percentage (%)</label>
                                <input type="number" class="unified-form-input" id="riskPct" value="${userData?.risk_per_trade || 1}" placeholder="1" step="0.1">
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
                                <input type="number" class="unified-form-input" id="monthlyTarget" value="${userData?.monthly_target || 100000}" placeholder="Monthly Target (₹)" style="max-width: 150px;">
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
                        
                        <!-- Trading Stats -->
                        <div id="trading-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                            <div style="background: rgba(59, 130, 246, 0.1); padding: 1rem; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.2);">
                                <h4 style="color: #3b82f6; margin: 0 0 0.5rem 0;">Win Rate</h4>
                                <p style="font-size: 1.5rem; font-weight: bold; margin: 0; color: #10b981;">${userData?.metrics?.win_rate || 0}%</p>
                            </div>
                            <div style="background: rgba(59, 130, 246, 0.1); padding: 1rem; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.2);">
                                <h4 style="color: #3b82f6; margin: 0 0 0.5rem 0;">Profit Factor</h4>
                                <p style="font-size: 1.5rem; font-weight: bold; margin: 0; color: #10b981;">${userData?.metrics?.profit_factor || 0}</p>
                            </div>
                            <div style="background: rgba(59, 130, 246, 0.1); padding: 1rem; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.2);">
                                <h4 style="color: #3b82f6; margin: 0 0 0.5rem 0;">Total Trades</h4>
                                <p style="font-size: 1.5rem; font-weight: bold; margin: 0; color: #ffffff;">${userData?.metrics?.total_trades || 0}</p>
                            </div>
                            <div style="background: rgba(59, 130, 246, 0.1); padding: 1rem; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.2);">
                                <h4 style="color: #3b82f6; margin: 0 0 0.5rem 0;">Total P&L</h4>
                                <p style="font-size: 1.5rem; font-weight: bold; margin: 0; color: ${(userData?.metrics?.total_profit || 0) >= 0 ? '#10b981' : '#ef4444'};">₹${(userData?.metrics?.total_profit || 0).toLocaleString()}</p>
                            </div>
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
            
            // Save to database
            fetch('./save_journal_entry.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(entry)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showError('Journal entry saved successfully!', 'success');
                    // Also save to localStorage for immediate display
            const j = loadJournal();
            j.push(entry);
            saveJournal(j);
            renderJournal();
                } else {
                    showError(`Failed to save journal entry: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                console.error('Error saving journal entry:', error);
                showError('Error saving journal entry. Please try again.', 'error');
            });
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
            const entryPrice = parseFloat(document.getElementById('entryPrice').value) || 0;
            const stopLoss = parseFloat(document.getElementById('stopLoss').value) || 0;
            const takeProfit1 = parseFloat(document.getElementById('takeProfit1').value) || 0;
            const takeProfit2 = parseFloat(document.getElementById('takeProfit2').value) || 0;
            
            if (entryPrice > 0 && stopLoss > 0 && takeProfit1 > 0) {
                const potentialLoss = entryPrice - stopLoss;
                const potentialProfit1 = takeProfit1 - entryPrice;
                const riskRewardRatio1 = (potentialProfit1 / potentialLoss).toFixed(2);
                
                let resultContent = `
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <div style="background: rgba(59, 130, 246, 0.1); padding: 1rem; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.2);">
                            <h4 style="color: #3b82f6; margin: 0 0 0.5rem 0;">Risk Amount</h4>
                            <p style="font-size: 1.2rem; font-weight: bold; margin: 0; color: #ef4444;">₹${potentialLoss.toFixed(2)}</p>
                        </div>
                        <div style="background: rgba(59, 130, 246, 0.1); padding: 1rem; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.2);">
                            <h4 style="color: #3b82f6; margin: 0 0 0.5rem 0;">Profit Target 1</h4>
                            <p style="font-size: 1.2rem; font-weight: bold; margin: 0; color: #10b981;">₹${potentialProfit1.toFixed(2)}</p>
                        </div>
                        <div style="background: rgba(59, 130, 246, 0.1); padding: 1rem; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.2);">
                            <h4 style="color: #3b82f6; margin: 0 0 0.5rem 0;">Risk:Reward Ratio</h4>
                            <p style="font-size: 1.2rem; font-weight: bold; margin: 0; color: #ffffff;">1:${riskRewardRatio1}</p>
                        </div>
                    </div>
                `;
                
                if (takeProfit2 > 0) {
                    const potentialProfit2 = takeProfit2 - entryPrice;
                    const riskRewardRatio2 = (potentialProfit2 / potentialLoss).toFixed(2);
                    
                    resultContent += `
                        <div style="background: rgba(139, 92, 246, 0.1); padding: 1rem; border-radius: 8px; border: 1px solid rgba(139, 92, 246, 0.2); margin-top: 1rem;">
                            <h4 style="color: #8b5cf6; margin: 0 0 0.5rem 0;">Profit Target 2</h4>
                            <p style="font-size: 1.2rem; font-weight: bold; margin: 0; color: #10b981;">₹${potentialProfit2.toFixed(2)}</p>
                            <p style="color: #8b5cf6; margin: 0.5rem 0 0 0;">Risk:Reward Ratio: 1:${riskRewardRatio2}</p>
                        </div>
                    `;
                }
                
                // Show result
                document.getElementById('risk-reward-result-content').innerHTML = resultContent;
                document.getElementById('risk-reward-result').style.display = 'block';
                
                // Scroll to result
                document.getElementById('risk-reward-result').scrollIntoView({ behavior: 'smooth' });
            } else {
                showError('Please fill in all required fields (Entry Price, Stop Loss, and Take Profit 1)', 'error');
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
            
            // Get tool details based on ID
            const toolDetails = getToolDetails(toolId);
            if (toolDetails) {
                showToolDetailsModal(toolDetails);
            } else {
                showError('Tool details not available', 'error');
            }
        }
        
        // Tool implementations
        function showPositionCalculator() {
            alert('Position Size Calculator - Coming Soon!');
        }
        
        function showRiskRewardCalculator() {
            alert('Risk-Reward Calculator - Coming Soon!');
        }
        
        // Enhanced Profit-Loss Calculator
        function showProfitLossCalculator() {
            const content = getProfitLossCalculatorContent();
            showToolModal('Profit-Loss Calculator', content);
        }
        
        // Enhanced Margin Calculator
        function showMarginCalculator() {
            const content = getMarginCalculatorContent();
            showToolModal('Margin Calculator', content);
        }
        
        // Enhanced Portfolio Analyzer
        function showPortfolioAnalyzer() {
            const content = getPortfolioAnalyzerContent();
            showToolModal('Portfolio Analyzer', content);
        }
        
        // Enhanced Market Simulator
        function showMarketSimulator() {
            const content = getMarketSimulatorContent();
            showToolModal('Market Simulator', content);
        }
        
        // Enhanced Chart Analysis Tool
        function showChartAnalysisTool() {
            const content = getChartAnalysisToolContent();
            showToolModal('Chart Analysis Tool', content);
        }

        // Enhanced Tool Content Generators
        function getMarginCalculatorContent() {
            return `
                <div class="unified-tool-container">
                    <div class="unified-tool-header">
                        <h1 class="unified-tool-title">Margin Calculator</h1>
                        <p class="unified-tool-subtitle">Calculate margin requirements and leverage for your trades</p>
                    </div>
                    
                    <div class="unified-grid">
                        <div class="unified-input-group">
                            <label for="margin-symbol">Symbol</label>
                            <input type="text" id="margin-symbol" placeholder="e.g., RELIANCE" value="RELIANCE">
                        </div>
                        <div class="unified-input-group">
                            <label for="margin-price">Current Price</label>
                            <input type="number" id="margin-price" placeholder="2500" step="0.01">
                        </div>
                        <div class="unified-input-group">
                            <label for="margin-quantity">Quantity</label>
                            <input type="number" id="margin-quantity" placeholder="100" step="1">
                        </div>
                        <div class="unified-input-group">
                            <label for="margin-type">Margin Type</label>
                            <select id="margin-type">
                                <option value="equity">Equity (20%)</option>
                                <option value="futures">Futures (15%)</option>
                                <option value="options">Options (Premium)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="unified-button-group">
                        <button class="btn btn-primary" onclick="calculateMargin()">Calculate Margin</button>
                        <button class="btn btn-secondary" onclick="clearMarginForm()">Clear</button>
                    </div>
                    
                    <div id="margin-result" class="unified-result" style="display: none;">
                        <h3>Margin Analysis</h3>
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
                        <div class="unified-input-group">
                            <label for="portfolio-total-value">Total Portfolio Value</label>
                            <input type="number" id="portfolio-total-value" placeholder="100000" step="0.01">
                        </div>
                        <div class="unified-input-group">
                            <label for="portfolio-invested">Total Invested</label>
                            <input type="number" id="portfolio-invested" placeholder="80000" step="0.01">
                        </div>
                        <div class="unified-input-group">
                            <label for="portfolio-time-period">Time Period (Months)</label>
                            <input type="number" id="portfolio-time-period" placeholder="12" step="1">
                        </div>
                        <div class="unified-input-group">
                            <label for="portfolio-risk-free-rate">Risk-Free Rate (%)</label>
                            <input type="number" id="portfolio-risk-free-rate" placeholder="6" step="0.01">
                        </div>
                    </div>
                    
                    <div class="unified-button-group">
                        <button class="btn btn-primary" onclick="analyzePortfolio()">Analyze Portfolio</button>
                        <button class="btn btn-secondary" onclick="clearPortfolioForm()">Clear</button>
                    </div>
                    
                    <div id="portfolio-result" class="unified-result" style="display: none;">
                        <h3>Portfolio Analysis</h3>
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
                        <div class="unified-input-group">
                            <label for="sim-account-size">Virtual Account Size</label>
                            <input type="number" id="sim-account-size" placeholder="100000" step="0.01">
                        </div>
                        <div class="unified-input-group">
                            <label for="sim-strategy">Trading Strategy</label>
                            <select id="sim-strategy">
                                <option value="conservative">Conservative</option>
                                <option value="moderate">Moderate</option>
                                <option value="aggressive">Aggressive</option>
                            </select>
                        </div>
                        <div class="unified-input-group">
                            <label for="sim-timeframe">Simulation Period</label>
                            <select id="sim-timeframe">
                                <option value="1">1 Month</option>
                                <option value="3">3 Months</option>
                                <option value="6" selected>6 Months</option>
                                <option value="12">1 Year</option>
                            </select>
                        </div>
                        <div class="unified-input-group">
                            <label for="sim-market-condition">Market Condition</label>
                            <select id="sim-market-condition">
                                <option value="bull">Bull Market</option>
                                <option value="bear">Bear Market</option>
                                <option value="sideways">Sideways Market</option>
                                <option value="volatile">Volatile Market</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="unified-button-group">
                        <button class="btn btn-primary" onclick="startSimulation()">Start Simulation</button>
                        <button class="btn btn-secondary" onclick="clearSimulationForm()">Clear</button>
                    </div>
                    
                    <div id="simulation-result" class="unified-result" style="display: none;">
                        <h3>Simulation Results</h3>
                        <div id="simulation-result-content"></div>
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
                        <div class="unified-input-group">
                            <label for="chart-symbol">Symbol</label>
                            <input type="text" id="chart-symbol" placeholder="e.g., RELIANCE" value="RELIANCE">
                        </div>
                        <div class="unified-input-group">
                            <label for="chart-timeframe">Timeframe</label>
                            <select id="chart-timeframe">
                                <option value="1m">1 Minute</option>
                                <option value="5m">5 Minutes</option>
                                <option value="15m">15 Minutes</option>
                                <option value="1h">1 Hour</option>
                                <option value="1d" selected>1 Day</option>
                                <option value="1w">1 Week</option>
                            </select>
                        </div>
                        <div class="unified-input-group">
                            <label for="chart-indicators">Technical Indicators</label>
                            <select id="chart-indicators" multiple>
                                <option value="sma">Simple Moving Average</option>
                                <option value="ema">Exponential Moving Average</option>
                                <option value="rsi">RSI</option>
                                <option value="macd">MACD</option>
                                <option value="bollinger">Bollinger Bands</option>
                            </select>
                        </div>
                        <div class="unified-input-group">
                            <label for="chart-period">Analysis Period</label>
                            <select id="chart-period">
                                <option value="30">30 Days</option>
                                <option value="90" selected>90 Days</option>
                                <option value="180">180 Days</option>
                                <option value="365">1 Year</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="unified-button-group">
                        <button class="btn btn-primary" onclick="generateChart()">Generate Chart</button>
                        <button class="btn btn-secondary" onclick="clearChartForm()">Clear</button>
                    </div>
                    
                    <div id="chart-result" class="unified-result" style="display: none;">
                        <h3>Chart Analysis</h3>
                        <div id="chart-result-content"></div>
                    </div>
                </div>
            `;
        }
        
        function getVolatilityCalculatorContent() {
            return `
                <div class="unified-tool-container">
                    <div class="unified-tool-header">
                        <h1 class="unified-tool-title">Volatility Calculator</h1>
                        <p class="unified-tool-subtitle">Calculate historical and implied volatility for better risk assessment</p>
                    </div>
                    
                    <div class="unified-grid">
                        <div class="unified-input-group">
                            <label for="vol-symbol">Symbol</label>
                            <input type="text" id="vol-symbol" placeholder="e.g., RELIANCE" value="RELIANCE">
                        </div>
                        <div class="unified-input-group">
                            <label for="vol-period">Period (Days)</label>
                            <select id="vol-period">
                                <option value="30">30 Days</option>
                                <option value="60">60 Days</option>
                                <option value="90" selected>90 Days</option>
                                <option value="180">180 Days</option>
                                <option value="365">1 Year</option>
                            </select>
                        </div>
                        <div class="unified-input-group">
                            <label for="vol-current-price">Current Price</label>
                            <input type="number" id="vol-current-price" placeholder="2500" step="0.01">
                        </div>
                        <div class="unified-input-group">
                            <label for="vol-strike-price">Strike Price (for IV)</label>
                            <input type="number" id="vol-strike-price" placeholder="2500" step="0.01">
                        </div>
                    </div>
                    
                    <div class="unified-button-group">
                        <button class="btn btn-primary" onclick="calculateVolatility()">Calculate Volatility</button>
                        <button class="btn btn-secondary" onclick="clearVolatilityForm()">Clear</button>
                    </div>
                    
                    <div id="volatility-result" class="unified-result" style="display: none;">
                        <h3>Volatility Analysis</h3>
                        <div id="volatility-result-content"></div>
                    </div>
                </div>
            `;
        }
        
        function getCorrelationAnalyzerContent() {
            return `
                <div class="unified-tool-container">
                    <div class="unified-tool-header">
                        <h1 class="unified-tool-title">Correlation Analyzer</h1>
                        <p class="unified-tool-subtitle">Analyze correlation between assets for portfolio diversification</p>
                    </div>
                    
                    <div class="unified-grid">
                        <div class="unified-input-group">
                            <label for="corr-symbol1">Symbol 1</label>
                            <input type="text" id="corr-symbol1" placeholder="e.g., RELIANCE" value="RELIANCE">
                        </div>
                        <div class="unified-input-group">
                            <label for="corr-symbol2">Symbol 2</label>
                            <input type="text" id="corr-symbol2" placeholder="e.g., TCS" value="TCS">
                        </div>
                        <div class="unified-input-group">
                            <label for="corr-period">Analysis Period</label>
                            <select id="corr-period">
                                <option value="30">30 Days</option>
                                <option value="60">60 Days</option>
                                <option value="90" selected>90 Days</option>
                                <option value="180">180 Days</option>
                                <option value="365">1 Year</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="unified-button-group">
                        <button class="btn btn-primary" onclick="calculateCorrelation()">Analyze Correlation</button>
                        <button class="btn btn-secondary" onclick="clearCorrelationForm()">Clear</button>
                    </div>
                    
                    <div id="correlation-result" class="unified-result" style="display: none;">
                        <h3>Correlation Analysis</h3>
                        <div id="correlation-result-content"></div>
                    </div>
                </div>
            `;
        }
        
        function getDrawdownCalculatorContent() {
            return `
                <div class="unified-tool-container">
                    <div class="unified-tool-header">
                        <h1 class="unified-tool-title">Drawdown Calculator</h1>
                        <p class="unified-tool-subtitle">Calculate maximum drawdown and recovery time</p>
                    </div>
                    
                    <div class="unified-grid">
                        <div class="unified-input-group">
                            <label for="dd-account-size">Account Size</label>
                            <input type="number" id="dd-account-size" placeholder="100000" step="0.01">
                        </div>
                        <div class="unified-input-group">
                            <label for="dd-current-value">Current Portfolio Value</label>
                            <input type="number" id="dd-current-value" placeholder="95000" step="0.01">
                        </div>
                        <div class="unified-input-group">
                            <label for="dd-peak-value">Peak Portfolio Value</label>
                            <input type="number" id="dd-peak-value" placeholder="110000" step="0.01">
                        </div>
                        <div class="unified-input-group">
                            <label for="dd-monthly-return">Expected Monthly Return (%)</label>
                            <input type="number" id="dd-monthly-return" placeholder="5" step="0.01">
                        </div>
                    </div>
                    
                    <div class="unified-button-group">
                        <button class="btn btn-primary" onclick="calculateDrawdown()">Calculate Drawdown</button>
                        <button class="btn btn-secondary" onclick="clearDrawdownForm()">Clear</button>
                    </div>
                    
                    <div id="drawdown-result" class="unified-result" style="display: none;">
                        <h3>Drawdown Analysis</h3>
                        <div id="drawdown-result-content"></div>
                    </div>
                </div>
            `;
        }
        
        function getKellyCriterionCalculatorContent() {
            return `
                <div class="unified-tool-container">
                    <div class="unified-tool-header">
                        <h1 class="unified-tool-title">Kelly Criterion Calculator</h1>
                        <p class="unified-tool-subtitle">Calculate optimal position size using Kelly Criterion</p>
                    </div>
                    
                    <div class="unified-grid">
                        <div class="unified-input-group">
                            <label for="kelly-win-rate">Win Rate (%)</label>
                            <input type="number" id="kelly-win-rate" placeholder="60" step="0.01">
                        </div>
                        <div class="unified-input-group">
                            <label for="kelly-avg-win">Average Win Amount</label>
                            <input type="number" id="kelly-avg-win" placeholder="1000" step="0.01">
                        </div>
                        <div class="unified-input-group">
                            <label for="kelly-avg-loss">Average Loss Amount</label>
                            <input type="number" id="kelly-avg-loss" placeholder="500" step="0.01">
                        </div>
                        <div class="unified-input-group">
                            <label for="kelly-account-size">Account Size</label>
                            <input type="number" id="kelly-account-size" placeholder="100000" step="0.01">
                        </div>
                    </div>
                    
                    <div class="unified-button-group">
                        <button class="btn btn-primary" onclick="calculateKellyCriterion()">Calculate Kelly %</button>
                        <button class="btn btn-secondary" onclick="clearKellyForm()">Clear</button>
                    </div>
                    
                    <div id="kelly-result" class="unified-result" style="display: none;">
                        <h3>Kelly Criterion Analysis</h3>
                        <div id="kelly-result-content"></div>
                    </div>
                </div>
            `;
        }
        
        function getVaRCalculatorContent() {
            return `
                <div class="unified-tool-container">
                    <div class="unified-tool-header">
                        <h1 class="unified-tool-title">Value at Risk (VaR) Calculator</h1>
                        <p class="unified-tool-subtitle">Calculate portfolio Value at Risk for risk assessment</p>
                    </div>
                    
                    <div class="unified-grid">
                        <div class="unified-input-group">
                            <label for="var-portfolio-value">Portfolio Value</label>
                            <input type="number" id="var-portfolio-value" placeholder="100000" step="0.01">
                        </div>
                        <div class="unified-input-group">
                            <label for="var-confidence-level">Confidence Level (%)</label>
                            <select id="var-confidence-level">
                                <option value="90">90%</option>
                                <option value="95" selected>95%</option>
                                <option value="99">99%</option>
                            </select>
                        </div>
                        <div class="unified-input-group">
                            <label for="var-time-horizon">Time Horizon (Days)</label>
                            <select id="var-time-horizon">
                                <option value="1" selected>1 Day</option>
                                <option value="5">5 Days</option>
                                <option value="10">10 Days</option>
                                <option value="30">30 Days</option>
                            </select>
                        </div>
                        <div class="unified-input-group">
                            <label for="var-volatility">Portfolio Volatility (%)</label>
                            <input type="number" id="var-volatility" placeholder="20" step="0.01">
                        </div>
                    </div>
                    
                    <div class="unified-button-group">
                        <button class="btn btn-primary" onclick="calculateVaR()">Calculate VaR</button>
                        <button class="btn btn-secondary" onclick="clearVaRForm()">Clear</button>
                    </div>
                    
                    <div id="var-result" class="unified-result" style="display: none;">
                        <h3>Value at Risk Analysis</h3>
                        <div id="var-result-content"></div>
                    </div>
                </div>
            `;
        }
        
        function getStressTestSimulatorContent() {
            return `
                <div class="unified-tool-container">
                    <div class="unified-tool-header">
                        <h1 class="unified-tool-title">Stress Test Simulator</h1>
                        <p class="unified-tool-subtitle">Simulate portfolio performance under stress scenarios</p>
                    </div>
                    
                    <div class="unified-grid">
                        <div class="unified-input-group">
                            <label for="stress-portfolio-value">Portfolio Value</label>
                            <input type="number" id="stress-portfolio-value" placeholder="100000" step="0.01">
                        </div>
                        <div class="unified-input-group">
                            <label for="stress-scenario">Stress Scenario</label>
                            <select id="stress-scenario">
                                <option value="market_crash">Market Crash (-20%)</option>
                                <option value="recession">Recession (-15%)</option>
                                <option value="volatility_spike">Volatility Spike (-10%)</option>
                                <option value="sector_rotation">Sector Rotation (-8%)</option>
                                <option value="custom">Custom Scenario</option>
                            </select>
                        </div>
                        <div class="unified-input-group">
                            <label for="stress-custom-loss">Custom Loss (%)</label>
                            <input type="number" id="stress-custom-loss" placeholder="10" step="0.01" style="display: none;">
                        </div>
                        <div class="unified-input-group">
                            <label for="stress-recovery-time">Recovery Time (Months)</label>
                            <input type="number" id="stress-recovery-time" placeholder="6" step="1">
                        </div>
                    </div>
                    
                    <div class="unified-button-group">
                        <button class="btn btn-primary" onclick="runStressTest()">Run Stress Test</button>
                        <button class="btn btn-secondary" onclick="clearStressTestForm()">Clear</button>
                    </div>
                    
                    <div id="stress-test-result" class="unified-result" style="display: none;">
                        <h3>Stress Test Results</h3>
                        <div id="stress-test-result-content"></div>
                    </div>
                </div>
            `;
        }
        
        function getRiskBudgetManagerContent() {
            return `
                <div class="unified-tool-container">
                    <div class="unified-tool-header">
                        <h1 class="unified-tool-title">Risk Budget Manager</h1>
                        <p class="unified-tool-subtitle">Allocate and manage risk budget across strategies</p>
                    </div>
                    
                    <div class="unified-grid">
                        <div class="unified-input-group">
                            <label for="rb-total-risk">Total Risk Budget (%)</label>
                            <input type="number" id="rb-total-risk" placeholder="10" step="0.01">
                        </div>
                        <div class="unified-input-group">
                            <label for="rb-strategy1">Strategy 1 Risk (%)</label>
                            <input type="number" id="rb-strategy1" placeholder="4" step="0.01">
                        </div>
                        <div class="unified-input-group">
                            <label for="rb-strategy2">Strategy 2 Risk (%)</label>
                            <input type="number" id="rb-strategy2" placeholder="3" step="0.01">
                        </div>
                        <div class="unified-input-group">
                            <label for="rb-strategy3">Strategy 3 Risk (%)</label>
                            <input type="number" id="rb-strategy3" placeholder="3" step="0.01">
                        </div>
                    </div>
                    
                    <div class="unified-button-group">
                        <button class="btn btn-primary" onclick="calculateRiskBudget()">Calculate Risk Budget</button>
                        <button class="btn btn-secondary" onclick="clearRiskBudgetForm()">Clear</button>
                    </div>
                    
                    <div id="risk-budget-result" class="unified-result" style="display: none;">
                        <h3>Risk Budget Analysis</h3>
                        <div id="risk-budget-result-content"></div>
                    </div>
                </div>
            `;
        }
        
        // Enhanced calculation functions
        function calculateVolatility() {
            const symbol = document.getElementById('vol-symbol').value;
            const period = parseInt(document.getElementById('vol-period').value);
            const currentPrice = parseFloat(document.getElementById('vol-current-price').value) || 0;
            const strikePrice = parseFloat(document.getElementById('vol-strike-price').value) || 0;
            
            if (!symbol || currentPrice <= 0) {
                alert('Please enter valid symbol and current price');
                return;
            }
            
            // Simulate volatility calculation (in real implementation, this would fetch historical data)
            const historicalVol = 25 + Math.random() * 15; // 25-40% range
            const impliedVol = 20 + Math.random() * 20; // 20-40% range
            
            const resultContent = `
                <div class="unified-grid">
                    <div class="unified-stat">
                        <div class="unified-stat-value">${historicalVol.toFixed(2)}%</div>
                        <div class="unified-stat-label">Historical Volatility (${period}D)</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${impliedVol.toFixed(2)}%</div>
                        <div class="unified-stat-label">Implied Volatility</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${(currentPrice * historicalVol / 100).toFixed(2)}</div>
                        <div class="unified-stat-label">Daily Volatility (₹)</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${(historicalVol > impliedVol ? 'High' : 'Low')}</div>
                        <div class="unified-stat-label">Volatility Status</div>
                    </div>
                </div>
                <div class="tool-content">
                    <p><strong>Analysis:</strong> ${symbol} shows ${historicalVol > 30 ? 'high' : historicalVol > 20 ? 'moderate' : 'low'} volatility over the past ${period} days.</p>
                    <p><strong>Recommendation:</strong> ${historicalVol > 30 ? 'Consider reducing position size due to high volatility.' : 'Volatility levels are manageable for normal position sizing.'}</p>
                </div>
            `;
            
            document.getElementById('volatility-result-content').innerHTML = resultContent;
            document.getElementById('volatility-result').style.display = 'block';
        }
        
        function calculateCorrelation() {
            const symbol1 = document.getElementById('corr-symbol1').value;
            const symbol2 = document.getElementById('corr-symbol2').value;
            const period = parseInt(document.getElementById('corr-period').value);
            
            if (!symbol1 || !symbol2) {
                alert('Please enter both symbols');
                return;
            }
            
            // Simulate correlation calculation
            const correlation = -0.5 + Math.random() * 1.5; // -0.5 to 1.0 range
            const strength = Math.abs(correlation);
            
            let strengthText = '';
            let recommendation = '';
            
            if (strength > 0.7) {
                strengthText = 'Strong';
                recommendation = 'High correlation - consider reducing exposure to both assets for better diversification.';
            } else if (strength > 0.3) {
                strengthText = 'Moderate';
                recommendation = 'Moderate correlation - monitor both positions closely.';
            } else {
                strengthText = 'Weak';
                recommendation = 'Low correlation - good for portfolio diversification.';
            }
            
            const resultContent = `
                <div class="unified-grid">
                    <div class="unified-stat">
                        <div class="unified-stat-value">${correlation.toFixed(3)}</div>
                        <div class="unified-stat-label">Correlation Coefficient</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${strengthText}</div>
                        <div class="unified-stat-label">Correlation Strength</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${(strength * 100).toFixed(1)}%</div>
                        <div class="unified-stat-label">Correlation %</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${correlation > 0 ? 'Positive' : 'Negative'}</div>
                        <div class="unified-stat-label">Direction</div>
                    </div>
                </div>
                <div class="tool-content">
                    <p><strong>Analysis:</strong> ${symbol1} and ${symbol2} show ${strengthText.toLowerCase()} ${correlation > 0 ? 'positive' : 'negative'} correlation over the past ${period} days.</p>
                    <p><strong>Recommendation:</strong> ${recommendation}</p>
                </div>
            `;
            
            document.getElementById('correlation-result-content').innerHTML = resultContent;
            document.getElementById('correlation-result').style.display = 'block';
        }
        
        function calculateDrawdown() {
            const accountSize = parseFloat(document.getElementById('dd-account-size').value) || 0;
            const currentValue = parseFloat(document.getElementById('dd-current-value').value) || 0;
            const peakValue = parseFloat(document.getElementById('dd-peak-value').value) || 0;
            const monthlyReturn = parseFloat(document.getElementById('dd-monthly-return').value) || 0;
            
            if (accountSize <= 0 || currentValue <= 0 || peakValue <= 0) {
                alert('Please enter valid values');
                return;
            }
            
            const currentDrawdown = ((peakValue - currentValue) / peakValue) * 100;
            const maxDrawdown = ((accountSize - currentValue) / accountSize) * 100;
            const recoveryAmount = peakValue - currentValue;
            const recoveryTime = monthlyReturn > 0 ? (recoveryAmount / (currentValue * monthlyReturn / 100)) : 0;
            
            const resultContent = `
                <div class="unified-grid">
                    <div class="unified-stat">
                        <div class="unified-stat-value">${currentDrawdown.toFixed(2)}%</div>
                        <div class="unified-stat-label">Current Drawdown</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${maxDrawdown.toFixed(2)}%</div>
                        <div class="unified-stat-label">Max Drawdown</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">₹${recoveryAmount.toFixed(2)}</div>
                        <div class="unified-stat-label">Recovery Amount</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${recoveryTime.toFixed(1)} months</div>
                        <div class="unified-stat-label">Recovery Time</div>
                    </div>
                </div>
                <div class="tool-content">
                    <p><strong>Analysis:</strong> Your portfolio is currently ${currentDrawdown.toFixed(2)}% below its peak value.</p>
                    <p><strong>Risk Assessment:</strong> ${maxDrawdown > 20 ? 'High risk - consider reducing position sizes.' : maxDrawdown > 10 ? 'Moderate risk - monitor closely.' : 'Low risk - within acceptable limits.'}</p>
                </div>
            `;
            
            document.getElementById('drawdown-result-content').innerHTML = resultContent;
            document.getElementById('drawdown-result').style.display = 'block';
        }
        
        function calculateKellyCriterion() {
            const winRate = parseFloat(document.getElementById('kelly-win-rate').value) || 0;
            const avgWin = parseFloat(document.getElementById('kelly-avg-win').value) || 0;
            const avgLoss = parseFloat(document.getElementById('kelly-avg-loss').value) || 0;
            const accountSize = parseFloat(document.getElementById('kelly-account-size').value) || 0;
            
            if (winRate <= 0 || avgWin <= 0 || avgLoss <= 0 || accountSize <= 0) {
                alert('Please enter valid values');
                return;
            }
            
            const winRateDecimal = winRate / 100;
            const kellyPercent = ((winRateDecimal * avgWin) - ((1 - winRateDecimal) * avgLoss)) / avgWin;
            const kellyPercentSafe = kellyPercent * 0.25; // Conservative Kelly (25% of full Kelly)
            const positionSize = (kellyPercentSafe * accountSize) / avgWin;
            
            const resultContent = `
                <div class="unified-grid">
                    <div class="unified-stat">
                        <div class="unified-stat-value">${(kellyPercent * 100).toFixed(2)}%</div>
                        <div class="unified-stat-label">Full Kelly %</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${(kellyPercentSafe * 100).toFixed(2)}%</div>
                        <div class="unified-stat-label">Conservative Kelly %</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${positionSize.toFixed(0)}</div>
                        <div class="unified-stat-label">Recommended Position Size</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">₹${(positionSize * avgWin).toFixed(2)}</div>
                        <div class="unified-stat-label">Position Value</div>
                    </div>
                </div>
                <div class="tool-content">
                    <p><strong>Analysis:</strong> Based on your win rate of ${winRate}% and risk-reward ratio, the Kelly Criterion suggests optimal position sizing.</p>
                    <p><strong>Recommendation:</strong> ${kellyPercent > 0.1 ? 'High Kelly % - consider reducing position size for safety.' : 'Kelly % is within reasonable limits.'}</p>
                </div>
            `;
            
            document.getElementById('kelly-result-content').innerHTML = resultContent;
            document.getElementById('kelly-result').style.display = 'block';
        }
        
        function calculateVaR() {
            const portfolioValue = parseFloat(document.getElementById('var-portfolio-value').value) || 0;
            const confidenceLevel = parseInt(document.getElementById('var-confidence-level').value);
            const timeHorizon = parseInt(document.getElementById('var-time-horizon').value);
            const volatility = parseFloat(document.getElementById('var-volatility').value) || 0;
            
            if (portfolioValue <= 0 || volatility <= 0) {
                alert('Please enter valid portfolio value and volatility');
                return;
            }
            
            // VaR calculation using normal distribution approximation
            const zScore = confidenceLevel === 90 ? 1.28 : confidenceLevel === 95 ? 1.65 : 2.33;
            const dailyVolatility = volatility / Math.sqrt(252); // Annual to daily
            const timeAdjustedVol = dailyVolatility * Math.sqrt(timeHorizon);
            const varAmount = portfolioValue * zScore * timeAdjustedVol;
            const varPercentage = (varAmount / portfolioValue) * 100;
            
            const resultContent = `
                <div class="unified-grid">
                    <div class="unified-stat">
                        <div class="unified-stat-value">₹${varAmount.toFixed(2)}</div>
                        <div class="unified-stat-label">VaR Amount</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${varPercentage.toFixed(2)}%</div>
                        <div class="unified-stat-label">VaR Percentage</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${confidenceLevel}%</div>
                        <div class="unified-stat-label">Confidence Level</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${timeHorizon} day${timeHorizon > 1 ? 's' : ''}</div>
                        <div class="unified-stat-label">Time Horizon</div>
                    </div>
                </div>
                <div class="tool-content">
                    <p><strong>Analysis:</strong> There is a ${100 - confidenceLevel}% chance that your portfolio will lose more than ₹${varAmount.toFixed(2)} over the next ${timeHorizon} day${timeHorizon > 1 ? 's' : ''}.</p>
                    <p><strong>Risk Assessment:</strong> ${varPercentage > 10 ? 'High risk - consider reducing portfolio volatility.' : varPercentage > 5 ? 'Moderate risk - monitor closely.' : 'Low risk - within acceptable limits.'}</p>
                </div>
            `;
            
            document.getElementById('var-result-content').innerHTML = resultContent;
            document.getElementById('var-result').style.display = 'block';
        }
        
        function runStressTest() {
            const portfolioValue = parseFloat(document.getElementById('stress-portfolio-value').value) || 0;
            const scenario = document.getElementById('stress-scenario').value;
            const customLoss = parseFloat(document.getElementById('stress-custom-loss').value) || 0;
            const recoveryTime = parseFloat(document.getElementById('stress-recovery-time').value) || 0;
            
            if (portfolioValue <= 0) {
                alert('Please enter valid portfolio value');
                return;
            }
            
            let lossPercentage = 0;
            let scenarioName = '';
            
            switch (scenario) {
                case 'market_crash':
                    lossPercentage = 20;
                    scenarioName = 'Market Crash';
                    break;
                case 'recession':
                    lossPercentage = 15;
                    scenarioName = 'Recession';
                    break;
                case 'volatility_spike':
                    lossPercentage = 10;
                    scenarioName = 'Volatility Spike';
                    break;
                case 'sector_rotation':
                    lossPercentage = 8;
                    scenarioName = 'Sector Rotation';
                    break;
                case 'custom':
                    lossPercentage = customLoss;
                    scenarioName = 'Custom Scenario';
                    break;
            }
            
            const lossAmount = portfolioValue * (lossPercentage / 100);
            const remainingValue = portfolioValue - lossAmount;
            const monthlyRecovery = recoveryTime > 0 ? lossAmount / recoveryTime : 0;
            
            const resultContent = `
                <div class="unified-grid">
                    <div class="unified-stat">
                        <div class="unified-stat-value">₹${lossAmount.toFixed(2)}</div>
                        <div class="unified-stat-label">Potential Loss</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${lossPercentage.toFixed(1)}%</div>
                        <div class="unified-stat-label">Loss Percentage</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">₹${remainingValue.toFixed(2)}</div>
                        <div class="unified-stat-label">Remaining Value</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">₹${monthlyRecovery.toFixed(2)}</div>
                        <div class="unified-stat-label">Monthly Recovery Needed</div>
                    </div>
                </div>
                <div class="tool-content">
                    <p><strong>Scenario:</strong> ${scenarioName} stress test</p>
                    <p><strong>Impact:</strong> Your portfolio could lose ₹${lossAmount.toFixed(2)} (${lossPercentage.toFixed(1)}%) in this scenario.</p>
                    <p><strong>Recovery:</strong> ${recoveryTime > 0 ? `To recover in ${recoveryTime} months, you would need ${((monthlyRecovery/remainingValue)*100).toFixed(1)}% monthly returns.` : 'Recovery time not specified.'}</p>
                </div>
            `;
            
            document.getElementById('stress-test-result-content').innerHTML = resultContent;
            document.getElementById('stress-test-result').style.display = 'block';
        }
        
        function calculateRiskBudget() {
            const totalRisk = parseFloat(document.getElementById('rb-total-risk').value) || 0;
            const strategy1Risk = parseFloat(document.getElementById('rb-strategy1').value) || 0;
            const strategy2Risk = parseFloat(document.getElementById('rb-strategy2').value) || 0;
            const strategy3Risk = parseFloat(document.getElementById('rb-strategy3').value) || 0;
            
            if (totalRisk <= 0) {
                alert('Please enter total risk budget');
                return;
            }
            
            const allocatedRisk = strategy1Risk + strategy2Risk + strategy3Risk;
            const remainingRisk = totalRisk - allocatedRisk;
            const allocationPercentage = (allocatedRisk / totalRisk) * 100;
            
            const resultContent = `
                <div class="unified-grid">
                    <div class="unified-stat">
                        <div class="unified-stat-value">${totalRisk.toFixed(2)}%</div>
                        <div class="unified-stat-label">Total Risk Budget</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${allocatedRisk.toFixed(2)}%</div>
                        <div class="unified-stat-label">Allocated Risk</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${remainingRisk.toFixed(2)}%</div>
                        <div class="unified-stat-label">Remaining Risk</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${allocationPercentage.toFixed(1)}%</div>
                        <div class="unified-stat-label">Allocation %</div>
                    </div>
                </div>
                <div class="tool-content">
                    <h4>Risk Allocation Breakdown:</h4>
                    <ul>
                        <li>Strategy 1: ${strategy1Risk.toFixed(2)}% (${((strategy1Risk/totalRisk)*100).toFixed(1)}% of total)</li>
                        <li>Strategy 2: ${strategy2Risk.toFixed(2)}% (${((strategy2Risk/totalRisk)*100).toFixed(1)}% of total)</li>
                        <li>Strategy 3: ${strategy3Risk.toFixed(2)}% (${((strategy3Risk/totalRisk)*100).toFixed(1)}% of total)</li>
                    </ul>
                    <p><strong>Analysis:</strong> ${remainingRisk > 0 ? `You have ${remainingRisk.toFixed(2)}% risk budget remaining.` : remainingRisk < 0 ? `You are over-allocated by ${Math.abs(remainingRisk).toFixed(2)}%.` : 'Your risk budget is fully allocated.'}</p>
                </div>
            `;
            
            document.getElementById('risk-budget-result-content').innerHTML = resultContent;
            document.getElementById('risk-budget-result').style.display = 'block';
        }
        
        // Calculation functions for existing tools
        function calculateMargin() {
            const symbol = document.getElementById('margin-symbol').value;
            const price = parseFloat(document.getElementById('margin-price').value) || 0;
            const quantity = parseInt(document.getElementById('margin-quantity').value) || 0;
            const marginType = document.getElementById('margin-type').value;
            
            if (!symbol || price <= 0 || quantity <= 0) {
                alert('Please enter valid values');
                return;
            }
            
            const totalValue = price * quantity;
            let marginPercentage = 0;
            let marginAmount = 0;
            
            switch (marginType) {
                case 'equity':
                    marginPercentage = 20;
                    break;
                case 'futures':
                    marginPercentage = 15;
                    break;
                case 'options':
                    marginPercentage = 100; // Premium paid upfront
                    break;
            }
            
            marginAmount = totalValue * (marginPercentage / 100);
            const leverage = marginPercentage < 100 ? (100 / marginPercentage) : 1;
            
            const resultContent = `
                <div class="unified-grid">
                    <div class="unified-stat">
                        <div class="unified-stat-value">₹${totalValue.toFixed(2)}</div>
                        <div class="unified-stat-label">Total Value</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">₹${marginAmount.toFixed(2)}</div>
                        <div class="unified-stat-label">Margin Required</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${marginPercentage}%</div>
                        <div class="unified-stat-label">Margin %</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${leverage.toFixed(1)}x</div>
                        <div class="unified-stat-label">Leverage</div>
                    </div>
                </div>
                <div class="tool-content">
                    <p><strong>Analysis:</strong> For ${quantity} shares of ${symbol} at ₹${price}, you need ₹${marginAmount.toFixed(2)} margin.</p>
                    <p><strong>Risk:</strong> ${leverage > 3 ? 'High leverage - monitor position closely.' : 'Leverage is within reasonable limits.'}</p>
                </div>
            `;
            
            document.getElementById('margin-result-content').innerHTML = resultContent;
            document.getElementById('margin-result').style.display = 'block';
        }
        
        function analyzePortfolio() {
            const totalValue = parseFloat(document.getElementById('portfolio-total-value').value) || 0;
            const invested = parseFloat(document.getElementById('portfolio-invested').value) || 0;
            const timePeriod = parseFloat(document.getElementById('portfolio-time-period').value) || 0;
            const riskFreeRate = parseFloat(document.getElementById('portfolio-risk-free-rate').value) || 0;
            
            if (totalValue <= 0 || invested <= 0 || timePeriod <= 0) {
                alert('Please enter valid values');
                return;
            }
            
            const totalReturn = totalValue - invested;
            const totalReturnPercentage = (totalReturn / invested) * 100;
            const annualizedReturn = Math.pow((totalValue / invested), (12 / timePeriod)) - 1;
            const annualizedReturnPercentage = annualizedReturn * 100;
            const excessReturn = annualizedReturnPercentage - riskFreeRate;
            
            // Simulate volatility calculation
            const volatility = 15 + Math.random() * 10; // 15-25% range
            const sharpeRatio = excessReturn / volatility;
            
            const resultContent = `
                <div class="unified-grid">
                    <div class="unified-stat">
                        <div class="unified-stat-value">₹${totalReturn.toFixed(2)}</div>
                        <div class="unified-stat-label">Total Return</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${totalReturnPercentage.toFixed(2)}%</div>
                        <div class="unified-stat-label">Total Return %</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${annualizedReturnPercentage.toFixed(2)}%</div>
                        <div class="unified-stat-label">Annualized Return</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${sharpeRatio.toFixed(2)}</div>
                        <div class="unified-stat-label">Sharpe Ratio</div>
                    </div>
                </div>
                <div class="tool-content">
                    <p><strong>Performance:</strong> Your portfolio has generated ${totalReturnPercentage.toFixed(2)}% returns over ${timePeriod} months.</p>
                    <p><strong>Risk-Adjusted Return:</strong> ${sharpeRatio > 1 ? 'Excellent risk-adjusted performance.' : sharpeRatio > 0.5 ? 'Good risk-adjusted performance.' : 'Consider improving risk management.'}</p>
                </div>
            `;
            
            document.getElementById('portfolio-result-content').innerHTML = resultContent;
            document.getElementById('portfolio-result').style.display = 'block';
        }
        
        function startSimulation() {
            const accountSize = parseFloat(document.getElementById('sim-account-size').value) || 0;
            const strategy = document.getElementById('sim-strategy').value;
            const timeframe = parseInt(document.getElementById('sim-timeframe').value);
            const marketCondition = document.getElementById('sim-market-condition').value;
            
            if (accountSize <= 0) {
                alert('Please enter valid account size');
                return;
            }
            
            // Simulate trading results based on strategy and market conditions
            let baseReturn = 0;
            let volatility = 0;
            
            switch (strategy) {
                case 'conservative':
                    baseReturn = 8;
                    volatility = 12;
                    break;
                case 'moderate':
                    baseReturn = 15;
                    volatility = 20;
                    break;
                case 'aggressive':
                    baseReturn = 25;
                    volatility = 35;
                    break;
            }
            
            // Adjust for market conditions
            switch (marketCondition) {
                case 'bull':
                    baseReturn *= 1.5;
                    break;
                case 'bear':
                    baseReturn *= -0.8;
                    break;
                case 'sideways':
                    baseReturn *= 0.3;
                    break;
                case 'volatile':
                    volatility *= 1.5;
                    break;
            }
            
            const finalValue = accountSize * (1 + (baseReturn / 100));
            const totalReturn = finalValue - accountSize;
            const totalReturnPercentage = (totalReturn / accountSize) * 100;
            
            const resultContent = `
                <div class="unified-grid">
                    <div class="unified-stat">
                        <div class="unified-stat-value">₹${finalValue.toFixed(2)}</div>
                        <div class="unified-stat-label">Final Value</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">₹${totalReturn.toFixed(2)}</div>
                        <div class="unified-stat-label">Total Return</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${totalReturnPercentage.toFixed(2)}%</div>
                        <div class="unified-stat-label">Return %</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${volatility.toFixed(1)}%</div>
                        <div class="unified-stat-label">Volatility</div>
                    </div>
                </div>
                <div class="tool-content">
                    <p><strong>Simulation Results:</strong> ${strategy} strategy in ${marketCondition} market over ${timeframe} months.</p>
                    <p><strong>Performance:</strong> ${totalReturnPercentage > 0 ? 'Profitable simulation.' : 'Loss in simulation - review strategy.'}</p>
                </div>
            `;
            
            document.getElementById('simulation-result-content').innerHTML = resultContent;
            document.getElementById('simulation-result').style.display = 'block';
        }
        
        function generateChart() {
            const symbol = document.getElementById('chart-symbol').value;
            const timeframe = document.getElementById('chart-timeframe').value;
            const indicators = Array.from(document.getElementById('chart-indicators').selectedOptions).map(option => option.value);
            const period = parseInt(document.getElementById('chart-period').value);
            
            if (!symbol) {
                alert('Please enter a symbol');
                return;
            }
            
            // Simulate chart analysis
            const trend = Math.random() > 0.5 ? 'Bullish' : 'Bearish';
            const strength = Math.random() * 100;
            const support = 100 + Math.random() * 200;
            const resistance = 300 + Math.random() * 200;
            
            let indicatorAnalysis = '';
            indicators.forEach(indicator => {
                switch (indicator) {
                    case 'sma':
                        indicatorAnalysis += '<li>SMA: Trend confirmation</li>';
                        break;
                    case 'ema':
                        indicatorAnalysis += '<li>EMA: Momentum indicator</li>';
                        break;
                    case 'rsi':
                        indicatorAnalysis += '<li>RSI: ' + (Math.random() > 0.5 ? 'Overbought' : 'Oversold') + '</li>';
                        break;
                    case 'macd':
                        indicatorAnalysis += '<li>MACD: ' + (Math.random() > 0.5 ? 'Bullish crossover' : 'Bearish crossover') + '</li>';
                        break;
                    case 'bollinger':
                        indicatorAnalysis += '<li>Bollinger Bands: ' + (Math.random() > 0.5 ? 'Price near upper band' : 'Price near lower band') + '</li>';
                        break;
                }
            });
            
            const resultContent = `
                <div class="unified-grid">
                    <div class="unified-stat">
                        <div class="unified-stat-value">${trend}</div>
                        <div class="unified-stat-label">Trend</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">${strength.toFixed(1)}%</div>
                        <div class="unified-stat-label">Trend Strength</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">₹${support.toFixed(2)}</div>
                        <div class="unified-stat-label">Support Level</div>
                    </div>
                    <div class="unified-stat">
                        <div class="unified-stat-value">₹${resistance.toFixed(2)}</div>
                        <div class="unified-stat-label">Resistance Level</div>
                    </div>
                </div>
                <div class="tool-content">
                    <p><strong>Chart Analysis for ${symbol}:</strong></p>
                    <p><strong>Timeframe:</strong> ${timeframe} | <strong>Period:</strong> ${period} days</p>
                    <p><strong>Technical Indicators:</strong></p>
                    <ul>${indicatorAnalysis}</ul>
                    <p><strong>Recommendation:</strong> ${trend === 'Bullish' ? 'Consider long positions with stop-loss at support.' : 'Consider short positions with stop-loss at resistance.'}</p>
                </div>
            `;
            
            document.getElementById('chart-result-content').innerHTML = resultContent;
            document.getElementById('chart-result').style.display = 'block';
        }
        
        // Clear functions for existing tools
        function clearMarginForm() {
            document.getElementById('margin-symbol').value = '';
            document.getElementById('margin-price').value = '';
            document.getElementById('margin-quantity').value = '';
            document.getElementById('margin-result').style.display = 'none';
        }
        
        function clearPortfolioForm() {
            document.getElementById('portfolio-total-value').value = '';
            document.getElementById('portfolio-invested').value = '';
            document.getElementById('portfolio-time-period').value = '';
            document.getElementById('portfolio-risk-free-rate').value = '';
            document.getElementById('portfolio-result').style.display = 'none';
        }
        
        function clearSimulationForm() {
            document.getElementById('sim-account-size').value = '';
            document.getElementById('simulation-result').style.display = 'none';
        }
        
        function clearChartForm() {
            document.getElementById('chart-symbol').value = '';
            document.getElementById('chart-result').style.display = 'none';
        }
        
        // Clear functions for new tools
        function clearVolatilityForm() {
            document.getElementById('vol-symbol').value = '';
            document.getElementById('vol-current-price').value = '';
            document.getElementById('vol-strike-price').value = '';
            document.getElementById('volatility-result').style.display = 'none';
        }
        
        function clearCorrelationForm() {
            document.getElementById('corr-symbol1').value = '';
            document.getElementById('corr-symbol2').value = '';
            document.getElementById('correlation-result').style.display = 'none';
        }
        
        function clearDrawdownForm() {
            document.getElementById('dd-account-size').value = '';
            document.getElementById('dd-current-value').value = '';
            document.getElementById('dd-peak-value').value = '';
            document.getElementById('dd-monthly-return').value = '';
            document.getElementById('drawdown-result').style.display = 'none';
        }
        
        function clearKellyForm() {
            document.getElementById('kelly-win-rate').value = '';
            document.getElementById('kelly-avg-win').value = '';
            document.getElementById('kelly-avg-loss').value = '';
            document.getElementById('kelly-account-size').value = '';
            document.getElementById('kelly-result').style.display = 'none';
        }
        
        function clearVaRForm() {
            document.getElementById('var-portfolio-value').value = '';
            document.getElementById('var-volatility').value = '';
            document.getElementById('var-result').style.display = 'none';
        }
        
        function clearStressTestForm() {
            document.getElementById('stress-portfolio-value').value = '';
            document.getElementById('stress-custom-loss').value = '';
            document.getElementById('stress-recovery-time').value = '';
            document.getElementById('stress-test-result').style.display = 'none';
        }
        
        function clearRiskBudgetForm() {
            document.getElementById('rb-total-risk').value = '';
            document.getElementById('rb-strategy1').value = '';
            document.getElementById('rb-strategy2').value = '';
            document.getElementById('rb-strategy3').value = '';
            document.getElementById('risk-budget-result').style.display = 'none';
        }
        
        // Show/hide custom loss input based on scenario selection
        document.addEventListener('DOMContentLoaded', function() {
            const scenarioSelect = document.getElementById('stress-scenario');
            const customLossInput = document.getElementById('stress-custom-loss');
            
            if (scenarioSelect && customLossInput) {
                scenarioSelect.addEventListener('change', function() {
                    if (this.value === 'custom') {
                        customLossInput.style.display = 'block';
                    } else {
                        customLossInput.style.display = 'none';
                    }
                });
            }
        });

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