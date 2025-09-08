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
    
    <!-- Online Status Indicator -->
    <div class="status-indicator" id="online-status">Online</div>

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
                    <?php if (!empty($tradingTools)): ?>
                        <?php foreach ($tradingTools as $index => $tool): ?>
                            <div class="tool-card glassmorphism" data-aos="zoom-in" data-aos-delay="<?php echo $index * 100; ?>" role="listitem" data-tool-id="<?php echo $tool['id']; ?>">
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
                <p>&copy; 2024 The Trader's Escape. All rights reserved. | Educational content only - not financial advice.</p>
            </div>
        </div>
    </footer>

    <!-- Cookie Notice -->
    <div class="cookie-notice" id="cookie-notice" role="alert" aria-label="Cookie consent notice">
        <div class="cookie-content">
            <p>We use cookies to enhance your experience. By continuing to use this site, you agree to our use of cookies.</p>
            <div class="cookie-buttons">
                <button class="btn btn-sm btn-primary" onclick="acceptCookies()" aria-label="Accept cookies">Accept</button>
                <button class="btn btn-sm btn-outline" onclick="dismissCookies()" aria-label="Decline cookies">Decline</button>
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
            
            // Open tool based on slug
            switch(toolSlug) {
                case 'position-size-calculator':
                    showPositionCalculator();
                    break;
                case 'risk-reward-calculator':
                    showRiskRewardCalculator();
                    break;
                case 'profit-loss-calculator':
                    showProfitLossCalculator();
                    break;
                case 'margin-calculator':
                    showMarginCalculator();
                    break;
                case 'portfolio-analyzer':
                    showPortfolioAnalyzer();
                    break;
                case 'market-simulator':
                    showMarketSimulator();
                    break;
                case 'chart-analysis-tool':
                    showChartAnalysisTool();
                    break;
                default:
                    alert('Tool is coming soon!');
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
