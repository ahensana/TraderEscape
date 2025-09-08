<?php 
session_start();
require_once __DIR__ . '/includes/db_functions.php';

// Track page view
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
trackPageView('home', $userId, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, session_id());

// Log user activity if logged in
if ($userId) {
    logUserActivity($userId, 'page_view', 'Viewed home page', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['page' => 'home']));
}

include 'includes/header.php'; 
?>

    <!-- Critical CSS inline for faster rendering -->
    <style>
        

        
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
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
    </style>
    

    

    
    <!-- Structured Data for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "EducationalOrganization",
        "name": "The Trader's Escape",
        "description": "Premium trading education platform providing educational content for stock market learning",
        "url": "https://thetradersescape.com",
        "logo": "./assets/logo.png",
        "sameAs": [
            "https://thetradersescape.com"
        ],
        "educationalLevel": "Beginner to Advanced",
        "teaches": [
            "Stock Market Fundamentals",
            "Technical Analysis",
            "Risk Management",
            "Trading Psychology"
        ],
        "disambiguatingDescription": "Educational platform focused on trading education, not financial advice"
    }
    </script>











    <!-- Main Content -->
    <main id="main-content" role="main">
        <!-- Hero Section -->
        <section class="hero-section" id="hero" aria-labelledby="hero-title">
            <div class="hero-content">
                <div class="container">
                    <div class="hero-grid">
                        <div class="hero-text">
                            <h1 class="hero-title" id="hero-title">
                                <span class="title-line">Welcome To</span>
                                <span class="title-line highlight">The Trader's Escape</span>
                            </h1>
                            <p class="hero-subtitle">Power Up Your Trading with Our Enhanced Tools</p>
                            <p class="hero-description">New To Stock Market? Learn the basics and understand concepts faster!</p>
                            <div class="hero-disclaimer" role="alert" aria-label="Important disclaimer">
                                <span class="disclaimer-icon">⚠️</span>
                                <span>We provide educational content and do not provide any tips or calls. You are responsible for your own actions.</span>
                            </div>
                            <div class="hero-buttons">
                                <a href="#" class="btn btn-primary" onclick="accessTools()">
                                    <span>Start Learning</span>
                                </a>
                                <a href="./risk.php" class="btn btn-secondary">
                                    <span>Read Risk Disclosure</span>
                                </a>
                            </div>
                        </div>
                        <div class="hero-visual">
                            <div class="hero-logo-container">
                                <img src="./assets/bullbear.png" alt="The Trader's Escape Logo" class="hero-logo" id="bullbear-logo">
                                <div class="logo-glow-container">
                                    <div class="bull-glow"></div>
                                    <div class="bear-glow"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </section>

        <!-- Highlights Section -->
        <section class="highlights-section" id="highlights" aria-labelledby="highlights-title">
            <div class="container">
                <div class="section-header">
                    <div class="section-badge">
                        <span class="badge-icon" aria-hidden="true"><i class="bi bi-star-fill"></i></span>
                        <span>Why Choose Us</span>
                    </div>
                    <h2 class="section-title gradient-text" id="highlights-title">Why Choose The Trader's Escape</h2>
                    <p class="section-subtitle">Premium features designed for serious traders</p>
                </div>
                <div class="highlights-grid" role="list">
                    <div class="highlight-card glassmorphism" data-aos="fade-up" role="listitem">
                        <div class="card-icon">
                            <div class="icon-bg"></div>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                            </svg>
                        </div>
                        <h3>Beginner-Friendly Learning</h3>
                        <p>Structured learning paths designed for newcomers to understand complex trading concepts easily.</p>
                        <div class="card-hover-effect"></div>
                    </div>
                    <div class="highlight-card glassmorphism" data-aos="fade-up" data-aos-delay="100" role="listitem">
                        <div class="card-icon">
                            <div class="icon-bg"></div>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                            </svg>
                        </div>
                        <h3>Enhanced Tools</h3>
                        <p>Advanced charting tools and analytics to help you make informed trading decisions.</p>
                        <div class="card-hover-effect"></div>
                    </div>
                    <div class="highlight-card glassmorphism" data-aos="fade-up" data-aos-delay="200" role="listitem">
                        <div class="card-icon">
                            <div class="icon-bg"></div>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                                <path d="M2 17l10 5 10-5"/>
                                <path d="M2 12l10 5 10-5"/>
                            </svg>
                        </div>
                        <h3>No Tips, Just Education</h3>
                        <p>Pure educational content focused on building your knowledge and understanding of the markets.</p>
                        <div class="card-hover-effect"></div>
                    </div>
                    <div class="highlight-card glassmorphism" data-aos="fade-up" data-aos-delay="300" role="listitem">
                        <div class="card-icon">
                            <div class="icon-bg"></div>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </div>
                        <h3>Community Conduct</h3>
                        <p>Join a community of learners focused on education, not speculation or unauthorized advice.</p>
                        <div class="card-hover-effect"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Start Learning Section -->
        <section class="learning-section" id="start-learning" aria-labelledby="learning-title">
            <div class="container">
                <div class="section-header">
                    <div class="section-badge">
                        <span class="badge-icon" aria-hidden="true"><i class="bi bi-bullseye"></i></span>
                        <span>Learning Path</span>
                    </div>
                    <h2 class="section-title gradient-text" id="learning-title">Your Learning Journey</h2>
                    <p class="section-subtitle">Follow this structured path to master trading fundamentals</p>
                </div>
                <div class="learning-path" role="list">
                    <div class="path-step" data-aos="slide-right" role="listitem">
                        <div class="step-number">
                            <span>01</span>
                            <div class="step-progress-ring"></div>
                        </div>
                        <div class="step-content">
                            <h3>Foundation Basics</h3>
                            <p>Learn the fundamental concepts of stock markets, trading terminology, and basic analysis techniques.</p>
                            <div class="step-progress">
                                <label class="checkbox-container">
                                    <input type="checkbox" id="step-1" class="learning-checkbox" aria-label="Mark Foundation Basics as completed">
                                    <span class="checkmark"></span>
                                    Mark as completed
                                </label>
                            </div>
                        </div>
                        <div class="step-visual">
                            <div class="step-icon" aria-hidden="true"><i class="bi bi-book"></i></div>
                        </div>
                    </div>
                    <div class="path-step" data-aos="slide-right" data-aos-delay="100" role="listitem">
                        <div class="step-number">
                            <span>02</span>
                            <div class="step-progress-ring"></div>
                        </div>
                        <div class="step-content">
                            <h3>Technical Analysis</h3>
                            <p>Master chart patterns, indicators, and technical analysis tools for better market understanding.</p>
                            <div class="step-progress">
                                <label class="checkbox-container">
                                    <input type="checkbox" id="step-2" class="learning-checkbox" aria-label="Mark Technical Analysis as completed">
                                    <span class="checkmark"></span>
                                    Mark as completed
                                </label>
                            </div>
                        </div>
                        <div class="step-visual">
                            <div class="step-icon" aria-hidden="true"><i class="bi bi-graph-up"></i></div>
                        </div>
                    </div>
                    <div class="path-step" data-aos="slide-right" data-aos-delay="200" role="listitem">
                        <div class="step-number">
                            <span>03</span>
                            <div class="step-progress-ring"></div>
                        </div>
                        <div class="step-content">
                            <h3>Risk Management</h3>
                            <p>Understand position sizing, stop losses, and risk management strategies to protect your capital.</p>
                            <div class="step-progress">
                                <label class="checkbox-container">
                                    <input type="checkbox" id="step-3" class="learning-checkbox" aria-label="Mark Risk Management as completed">
                                    <span class="checkmark"></span>
                                    Mark as completed
                                </label>
                            </div>
                        </div>
                        <div class="step-visual">
                            <div class="step-icon" aria-hidden="true"><i class="bi bi-shield-check"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tools Section -->
        <section class="tools-section" id="tools" aria-labelledby="tools-title">
            <div class="container">
                <div class="section-header">
                    <div class="section-badge">
                        <span class="badge-icon" aria-hidden="true"><i class="bi bi-gear"></i></span>
                        <span>Trading Tools</span>
                    </div>
                    <h2 class="section-title gradient-text" id="tools-title">Trading Tools & Analytics</h2>
                    <p class="section-subtitle">Advanced tools to enhance your trading analysis</p>
                </div>
                <div class="tools-grid" role="list">
                    <div class="tool-card glassmorphism" data-aos="zoom-in" role="listitem">
                        <div class="tool-header">
                            <h3>Sample Equity Curve</h3>
                            <span class="tool-badge">Demo Data</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="equityChart" aria-label="Sample equity curve chart showing educational data"></canvas>
                        </div>
                        <div class="tool-stats">
                            <div class="stat">
                                <span class="stat-label">Total Return</span>
                                <span class="stat-value">+45.2%</span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Max Drawdown</span>
                                <span class="stat-value">-12.8%</span>
                            </div>
                        </div>
                    </div>
                    <div class="tool-card glassmorphism" data-aos="zoom-in" data-aos-delay="100" role="listitem">
                        <div class="tool-header">
                            <h3>Monthly Study Progress</h3>
                            <span class="tool-badge">Demo Data</span>
                        </div>
                        <div class="chart-container">
                            <canvas id="progressChart" aria-label="Monthly study progress chart showing educational data"></canvas>
                        </div>
                        <div class="tool-stats">
                            <div class="stat">
                                <span class="stat-label">Completion Rate</span>
                                <span class="stat-value">87%</span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Avg Score</span>
                                <span class="stat-value">92%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section class="testimonials-section" id="testimonials" aria-labelledby="testimonials-title">
            <div class="container">
                <div class="section-header">
                    <div class="section-badge">
                        <span class="badge-icon" aria-hidden="true"><i class="bi bi-chat-quote"></i></span>
                        <span>Testimonials</span>
                    </div>
                    <h2 class="section-title gradient-text" id="testimonials-title">What Learners Say</h2>
                    <p class="section-subtitle">Illustrative testimonials from our educational community</p>
                </div>
                <div class="testimonials-grid" role="list">
                    <div class="testimonial-card glassmorphism" data-aos="fade-up" role="listitem">
                        <div class="testimonial-content">
                            <div class="quote-icon" aria-hidden="true">"</div>
                            <blockquote>
                                <p>"The educational content helped me understand complex trading concepts that I struggled with before."</p>
                            </blockquote>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar" aria-hidden="true">A</div>
                            <div class="author-info">
                                <h4>Alex M.</h4>
                                <span>Beginner Trader</span>
                            </div>
                        </div>
                        <div class="testimonial-badge">Illustrative Only</div>
                    </div>
                    <div class="testimonial-card glassmorphism" data-aos="fade-up" data-aos-delay="100" role="listitem">
                        <div class="testimonial-content">
                            <div class="quote-icon" aria-hidden="true">"</div>
                            <blockquote>
                                <p>"The tools and analytics provided valuable insights for my learning journey in the stock market."</p>
                            </blockquote>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar" aria-hidden="true">S</div>
                            <div class="author-info">
                                <h4>Sarah K.</h4>
                                <span>Intermediate Learner</span>
                            </div>
                        </div>
                        <div class="testimonial-badge">Illustrative Only</div>
                    </div>
                    <div class="testimonial-card glassmorphism" data-aos="fade-up" data-aos-delay="200" role="listitem">
                        <div class="testimonial-content">
                            <div class="quote-icon" aria-hidden="true">"</div>
                            <blockquote>
                                <p>"Focus on education rather than tips helped me develop a more disciplined approach to trading."</p>
                            </blockquote>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar" aria-hidden="true">M</div>
                            <div class="author-info">
                                <h4>Mike R.</h4>
                                <span>Advanced Student</span>
                            </div>
                        </div>
                        <div class="testimonial-badge">Illustrative Only</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="faq-section" id="faq" aria-labelledby="faq-title">
            <div class="container">
                <div class="section-header">
                    <div class="section-badge">
                        <span class="badge-icon" aria-hidden="true"><i class="bi bi-question-circle"></i></span>
                        <span>FAQ</span>
                    </div>
                    <h2 class="section-title gradient-text" id="faq-title">Frequently Asked Questions</h2>
                    <p class="section-subtitle">Common questions about our educational platform</p>
                </div>
                <div class="faq-container" role="list">
                    <div class="faq-item" data-aos="fade-up" role="listitem">
                        <div class="faq-question" role="button" tabindex="0" aria-expanded="false" aria-controls="faq-answer-1">
                            <h3>Are you SEBI registered?</h3>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </div>
                        <div class="faq-answer" id="faq-answer-1" role="region" aria-labelledby="faq-question-1">
                            <p>No. We are education only. The Trader's Escape, its founders, and contributors are not SEBI-registered investment advisors or research analysts unless specifically stated.</p>
                        </div>
                    </div>
                    <div class="faq-item" data-aos="fade-up" data-aos-delay="100" role="listitem">
                        <div class="faq-question" role="button" tabindex="0" aria-expanded="false" aria-controls="faq-answer-2">
                            <h3>Do you give trading tips or calls?</h3>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </div>
                        <div class="faq-answer" id="faq-answer-2" role="region" aria-labelledby="faq-question-2">
                            <p>No. We provide educational content and do not provide any tips or calls. All content is intended to help users understand stock market concepts, not to serve as financial advice.</p>
                        </div>
                    </div>
                    <div class="faq-item" data-aos="fade-up" data-aos-delay="200" role="listitem">
                        <div class="faq-question" role="button" tabindex="0" aria-expanded="false" aria-controls="faq-answer-3">
                            <h3>What kind of content do you provide?</h3>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </div>
                        <div class="faq-answer" id="faq-answer-3" role="region" aria-labelledby="faq-question-3">
                            <p>We provide educational content including courses, videos, tools, strategies, and articles focused on helping users understand stock market concepts and develop their trading knowledge.</p>
                        </div>
                    </div>
                    <div class="faq-item" data-aos="fade-up" data-aos-delay="300" role="listitem">
                        <div class="faq-question" role="button" tabindex="0" aria-expanded="false" aria-controls="faq-answer-4">
                            <h3>Is there a community I can join?</h3>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </div>
                        <div class="faq-answer" id="faq-answer-4" role="region" aria-labelledby="faq-question-4">
                            <p>Yes, we have learning communities. However, we do not allow users to post or promote unauthorized advisory services, tips, or calls. Violators will be removed and blocked.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Community Conduct Section -->
        <section class="community-section" id="community" aria-labelledby="community-title">
            <div class="container">
                <div class="community-content">
                    <div class="community-text">
                        <div class="section-badge">
                            <span class="badge-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                            <span>Community</span>
                        </div>
                        <h2 id="community-title">Community Conduct</h2>
                        <p>We maintain a strict educational environment. No tips, calls, or unauthorized advisory services are allowed in our communities. Violators will be removed and blocked to maintain the integrity of our educational platform.</p>
                        <a href="./disclaimer.php" class="btn btn-outline">
                            <span class="btn-text">Read Full Disclaimer</span>
                            <div class="btn-background"></div>
                        </a>
                    </div>
                    <div class="community-visual">
                        <div class="conduct-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Chat Icon -->
    <button onclick="toggleChatOptions()" class="chat-icon-btn" id="chatIconBtn">
        <svg class="chat-icon-svg" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
    </button>

    <?php include 'includes/footer.php'; ?>

    <!-- Scripts with defer for better performance -->
    <script src="./assets/app.js" defer></script>
    <script src="./assets/charts.js" defer></script>
    <script src="./assets/animations.js" defer></script>
    <script src="./assets/trading-background.js" defer></script>
    
    <script>
        // Enhanced bullbear glow effect
        function enhanceBullbearGlow() {
            const logo = document.getElementById('bullbear-logo');
            const bullGlow = document.querySelector('.bull-glow');
            const bearGlow = document.querySelector('.bear-glow');
            
            if (logo && bullGlow && bearGlow) {
                // Create canvas to analyze image colors
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                logo.addEventListener('load', function() {
                    canvas.width = logo.naturalWidth;
                    canvas.height = logo.naturalHeight;
                    ctx.drawImage(logo, 0, 0);
                    
                    // Sample colors from bull area (left side)
                    const bullData = ctx.getImageData(0, 0, canvas.width * 0.4, canvas.height).data;
                    let bullRed = 0, bullGreen = 0, bullBlue = 0, bullCount = 0;
                    
                    // Sample colors from bear area (right side)
                    const bearData = ctx.getImageData(canvas.width * 0.6, 0, canvas.width * 0.4, canvas.height).data;
                    let bearRed = 0, bearGreen = 0, bearBlue = 0, bearCount = 0;
                    
                    // Calculate average colors for bull area
                    for (let i = 0; i < bullData.length; i += 4) {
                        if (bullData[i + 3] > 0) { // If pixel is not transparent
                            bullRed += bullData[i];
                            bullGreen += bullData[i + 1];
                            bullBlue += bullData[i + 2];
                            bullCount++;
                        }
                    }
                    
                    // Calculate average colors for bear area
                    for (let i = 0; i < bearData.length; i += 4) {
                        if (bearData[i + 3] > 0) { // If pixel is not transparent
                            bearRed += bearData[i];
                            bearGreen += bearData[i + 1];
                            bearBlue += bearData[i + 2];
                            bearCount++;
                        }
                    }
                    
                    if (bullCount > 0) {
                        const avgBullRed = Math.round(bullRed / bullCount);
                        const avgBullGreen = Math.round(bullGreen / bullCount);
                        const avgBullBlue = Math.round(bullBlue / bullCount);
                        
                        // Apply bull glow with detected colors
                        bullGlow.style.background = `radial-gradient(circle at 30% 50%, rgba(${avgBullRed}, ${avgBullGreen}, ${avgBullBlue}, 0.4) 0%, transparent 50%)`;
                    }
                    
                    if (bearCount > 0) {
                        const avgBearRed = Math.round(bearRed / bearCount);
                        const avgBearGreen = Math.round(bearGreen / bearCount);
                        const avgBearBlue = Math.round(bearBlue / bearCount);
                        
                        // Apply bear glow with detected colors
                        bearGlow.style.background = `radial-gradient(circle at 70% 50%, rgba(${avgBearRed}, ${avgBearGreen}, ${avgBearBlue}, 0.4) 0%, transparent 50%)`;
                    }
                });
                
                // If image is already loaded
                if (logo.complete) {
                    logo.dispatchEvent(new Event('load'));
                }
            }
        }
        
        // Initialize enhanced glow effect
        document.addEventListener('DOMContentLoaded', enhanceBullbearGlow);
        window.addEventListener('load', enhanceBullbearGlow);
        
        // Chat icon functionality
        function toggleChatOptions() {
            // For now, this will show an alert. You can replace this with actual chat functionality
            alert('Chat feature coming soon! This will connect you with our support team.');
        }
        
        // Add keyboard support for chat icon
        document.addEventListener('DOMContentLoaded', function() {
            const chatIconBtn = document.getElementById('chatIconBtn');
            if (chatIconBtn) {
                // Make sure the chat icon is visible and round
                chatIconBtn.style.display = 'flex';
                chatIconBtn.style.visibility = 'visible';
                chatIconBtn.style.opacity = '1';
                chatIconBtn.style.position = 'fixed';
                chatIconBtn.style.bottom = '30px';
                chatIconBtn.style.right = '30px';
                chatIconBtn.style.zIndex = '99999';
                chatIconBtn.style.backgroundColor = '#2563eb';
                chatIconBtn.style.width = '80px';
                chatIconBtn.style.height = '80px';
                chatIconBtn.style.borderRadius = '50%';
                chatIconBtn.style.minWidth = '80px';
                chatIconBtn.style.minHeight = '80px';
                chatIconBtn.style.maxWidth = '80px';
                chatIconBtn.style.maxHeight = '80px';
                chatIconBtn.style.aspectRatio = '1/1';
                
                console.log('Chat icon found and made visible!');
                
                chatIconBtn.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        toggleChatOptions();
                    }
                });
            } else {
                console.error('Chat icon button not found!');
                // Try to create it manually
                const newChatBtn = document.createElement('button');
                newChatBtn.id = 'chatIconBtn';
                newChatBtn.className = 'chat-icon-btn';
                newChatBtn.innerHTML = '<svg class="chat-icon-svg" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>';
                newChatBtn.onclick = toggleChatOptions;
                document.body.appendChild(newChatBtn);
                console.log('Chat icon created manually!');
            }
        });
    </script>
</body>
</html>