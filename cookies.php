<?php
/**
 * Cookies Policy Page for TraderEscape
 * Information about cookie usage
 */

session_start();
require_once __DIR__ . '/includes/db_functions.php';

// Get current page for header
$currentPage = 'cookies';

// Track page view
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
trackPageView('cookies', $userId, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, session_id());

// Log user activity if logged in
if ($userId) {
    logUserActivity($userId, 'page_view', 'Viewed cookies page', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['page' => 'cookies']));
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
                                <span class="title-line">Cookies</span>
                                <span class="title-line highlight">Policy</span>
                            </h1>
                            <p class="hero-subtitle">Understanding Our Cookie Usage</p>
                            <p class="hero-description">This policy explains how we use cookies and similar technologies to enhance your browsing experience on The Trader's Escape platform.</p>
                            <div class="hero-disclaimer" role="alert" aria-label="Important disclaimer">
                                <span class="disclaimer-icon">⚠️</span>
                                <span>We provide educational content and do not provide any tips or calls. You are responsible for your own actions.</span>
                            </div>
                            <div class="hero-buttons">
                                <a href="#cookies-content" class="btn btn-primary">
                                    <span>Read Policy</span>
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
                                        <i class="bi bi-cookie"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-gear"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-eye"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-sliders"></i>
                                    </div>
                                </div>
                                <div class="tools-glow"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    <!-- Cookies Content Section -->
    <section class="disclaimer-section" id="cookies-content">
            <div class="container">
            <div class="disclaimer-content">
                <div class="disclaimer-card">
                    <h2>Cookies Policy</h2>
                    <p>This Cookies Policy explains how The Trader's Escape uses cookies and similar technologies when you visit our platform.</p>
                    <p>Last updated: <?php echo date('F j, Y'); ?></p>
                                </div>
                                
                <div class="disclaimer-card">
                    <h3>What Are Cookies?</h3>
                    <p>Cookies are small text files that are stored on your device when you visit a website. They help websites remember information about your visit and provide a better user experience.</p>
                                </div>
                                
                <div class="disclaimer-card">
                            <h3>How We Use Cookies</h3>
                            <p>We use cookies for the following purposes:</p>
                            <ul>
                        <li><strong>Essential Cookies:</strong> Required for basic website functionality</li>
                        <li><strong>Performance Cookies:</strong> Help us understand how visitors use our site</li>
                        <li><strong>Functionality Cookies:</strong> Remember your preferences and settings</li>
                        <li><strong>Authentication Cookies:</strong> Keep you logged in during your session</li>
                            </ul>
                            </div>
                            
                <div class="disclaimer-card">
                    <h3>Types of Cookies We Use</h3>
                    <div class="cookie-types">
                        <div class="cookie-type">
                            <h4>Session Cookies</h4>
                            <p>Temporary cookies that are deleted when you close your browser. These help maintain your login status and session information.</p>
                        </div>
                        <div class="cookie-type">
                            <h4>Persistent Cookies</h4>
                            <p>Cookies that remain on your device for a set period. These remember your preferences and settings for future visits.</p>
                            </div>
                        <div class="cookie-type">
                            <h4>Third-Party Cookies</h4>
                            <p>Cookies set by third-party services we use, such as analytics tools and social media plugins.</p>
                            </div>
                        </div>
                    </div>
                    
                <div class="disclaimer-card">
                    <h3>Managing Your Cookie Preferences</h3>
                    <p>You can control and manage cookies in several ways:</p>
                    <ul>
                        <li>Browser settings: Most browsers allow you to block or delete cookies</li>
                        <li>Cookie consent: We provide options to accept or decline non-essential cookies</li>
                        <li>Third-party opt-outs: Many third-party services provide opt-out mechanisms</li>
                            </ul>
                    <p><strong>Note:</strong> Disabling certain cookies may affect website functionality.</p>
                        </div>
                        
                <div class="disclaimer-card">
                    <h3>Third-Party Services</h3>
                    <p>We use third-party services that may set cookies:</p>
                    <ul>
                        <li>Google Analytics: Website usage analytics</li>
                        <li>Social media platforms: Sharing and integration features</li>
                        <li>Payment processors: Secure payment processing</li>
                             </ul>
                    <p>These services have their own privacy policies and cookie practices.</p>
                </div>

                <div class="disclaimer-card">
                    <h3>Updates to This Policy</h3>
                    <p>We may update this Cookies Policy from time to time. Any changes will be posted on this page with an updated date.</p>
                    <p>We encourage you to review this policy periodically.</p>
                         </div>
                         
                <div class="disclaimer-card">
                    <h3>Contact Us</h3>
                    <p>If you have questions about our use of cookies, please contact us at:</p>
                    <p>Email: privacy@thetradersescape.com</p>
                         </div>
                </div>
            </div>
        </section>
    </main>

<?php include 'includes/footer.php'; ?>


