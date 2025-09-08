<?php
/**
 * Disclaimer Page for TraderEscape
 * Important legal information and disclaimers
 */

session_start();
require_once __DIR__ . '/includes/db_functions.php';

// Get current page for header
$currentPage = 'disclaimer';

// Track page view
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
trackPageView('disclaimer', $userId, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, session_id());

// Log user activity if logged in
if ($userId) {
    logUserActivity($userId, 'page_view', 'Viewed disclaimer page', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['page' => 'disclaimer']));
}
?>
<?php include 'includes/header.php'; ?>

<style>
/* Reduce spacing for content sections after hero */
.hero-section {
    padding-bottom: 1rem;
}

.hero-section + .disclaimer-section {
    padding-top: 2rem;
    padding-bottom: 4rem;
}
</style>

<main id="main-content" role="main" style="padding-top: 0;">
        <!-- Hero Section -->
        <section class="hero-section" id="hero" aria-labelledby="hero-title">
            <div class="hero-content">
                <div class="container">
                    <div class="hero-grid">
                        <div class="hero-text">
                            <h1 class="hero-title" id="hero-title">
                                <span class="title-line">Disclaimer</span>
                                <span class="title-line highlight">Legal Information</span>
                            </h1>
                            <p class="hero-subtitle">Important Information About Our Educational Platform</p>
                            <p class="hero-description">Please read this disclaimer carefully before using our platform. This document outlines the scope, limitations, and legal framework of The Trader's Escape.</p>
                            <div class="hero-disclaimer" role="alert" aria-label="Important disclaimer">
                                <span class="disclaimer-icon">⚠️</span>
                                <span>We provide educational content and do not provide any tips or calls. You are responsible for your own actions.</span>
                            </div>
                            <div class="hero-buttons">
                                <a href="#disclaimer-content" class="btn btn-primary">
                                    <span>Read Disclaimer</span>
                                </a>
                                <a href="./contact.php" class="btn btn-secondary">
                                    <span>Contact Us</span>
                                </a>
                            </div>
                        </div>
                        <div class="hero-visual">
                            <div class="tools-hero-container">
                                <div class="tools-icon-grid">
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-info-circle"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-shield-exclamation"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </div>
                                </div>
                                <div class="tools-glow"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Disclaimer Content Section -->
        <section class="disclaimer-section" id="disclaimer-content">
            <div class="container">
                <div class="disclaimer-content">
                    <div class="disclaimer-card">
                        <h2>Disclaimer</h2>
                        <p>The information provided on The Trader's Escape (www.thetradersescape.com) is strictly for educational and informational purposes only.</p>
                        
                        <h3>We do not provide:</h3>
                        <ul>
                            <li>Stock tips or buy/sell recommendations</li>
                            <li>Portfolio management services</li>
                            <li>Personalized investment advice</li>
                            <li>Intraday or positional trading calls</li>
                        </ul>
                        
                        <p>All content — including courses, videos, tools, strategies, and articles — is intended to help users understand stock market concepts, not to serve as financial advice.</p>
                    </div>

                    <div class="disclaimer-card">
                        <h3>No SEBI Registration</h3>
                        <p>The Trader's Escape, its founders, and contributors are not SEBI-registered investment advisors or research analysts unless specifically stated.</p>
                    </div>

                    <div class="disclaimer-card">
                        <h3>No Guarantees or Assured Returns</h3>
                        <p>Trading and investing in the stock market involve risk, including the loss of capital.</p>
                        <p>Past performance is not indicative of future results. Any strategies or tools discussed are for academic understanding and may not suit all individuals or situations.</p>
                    </div>

                    <div class="disclaimer-card">
                        <h3>User Responsibility</h3>
                        <p>Users are solely responsible for:</p>
                        <ul>
                            <li>Evaluating the information provided</li>
                            <li>Making independent financial decisions</li>
                            <li>Consulting with a SEBI-registered financial advisor before taking market positions</li>
                        </ul>
                        <p>By using this website, you agree that The Trader's Escape and its team shall not be held liable for any direct or indirect losses incurred from the use of our content or services.</p>
                    </div>

                    <div class="disclaimer-card">
                        <h3>Affiliate Disclosure</h3>
                        <p>Some links on our platform may be affiliate links. This means we may earn a small commission at no extra cost to you if you sign up or purchase through these links. These affiliations do not influence our educational content.</p>
                    </div>

                    <div class="disclaimer-card">
                        <h3>Community Conduct</h3>
                        <p>We do not allow users to post or promote unauthorized advisory services, tips, or calls in any of our learning communities (e.g., Telegram, Discord, Forums, or Comments). Violators will be removed and blocked.</p>
                    </div>

                    <div class="disclaimer-card">
                        <p><strong>By continuing to access this platform, you acknowledge and agree to all parts of this disclaimer.</strong></p>
                    </div>
                </div>
            </div>
        </section>
    </main>

<?php include 'includes/footer.php'; ?>
