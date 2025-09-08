<?php
/**
 * Privacy Policy Page for TraderEscape
 * Privacy and data protection information
 */

session_start();
require_once __DIR__ . '/includes/db_functions.php';

// Get current page for header
$currentPage = 'privacy';

// Track page view
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
trackPageView('privacy', $userId, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, session_id());

// Log user activity if logged in
if ($userId) {
    logUserActivity($userId, 'page_view', 'Viewed privacy page', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['page' => 'privacy']));
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
                                <span class="title-line">Privacy</span>
                                <span class="title-line highlight">Policy</span>
                            </h1>
                            <p class="hero-subtitle">Your Data Protection Rights</p>
                            <p class="hero-description">We are committed to protecting your privacy and ensuring the security of your personal information. This policy explains how we collect, use, and protect your data.</p>
                            <div class="hero-disclaimer" role="alert" aria-label="Important disclaimer">
                                <span class="disclaimer-icon">⚠️</span>
                                <span>We provide educational content and do not provide any tips or calls. You are responsible for your own actions.</span>
                            </div>
                            <div class="hero-buttons">
                                <a href="#privacy-content" class="btn btn-primary">
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
                                        <i class="bi bi-shield-lock"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-person-check"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-file-earmark-lock"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-key"></i>
                                    </div>
                                </div>
                                <div class="tools-glow"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    <!-- Privacy Content Section -->
        <section class="disclaimer-section" id="privacy-content">
            <div class="container">
                <div class="disclaimer-content">
                    <div class="disclaimer-card">
                        <h2>Privacy Policy</h2>
                    <p>This Privacy Policy describes how The Trader's Escape collects, uses, and protects your personal information when you use our platform.</p>
                    <p>Last updated: <?php echo date('F j, Y'); ?></p>
                    </div>

                    <div class="disclaimer-card">
                    <h3>Information We Collect</h3>
                    <p>We collect information you provide directly to us, such as:</p>
                    <ul>
                        <li>Account registration information (name, email, username)</li>
                        <li>Profile information and preferences</li>
                        <li>Communication with our support team</li>
                        <li>Feedback and survey responses</li>
                        </ul>
                    </div>

                    <div class="disclaimer-card">
                    <h3>How We Use Your Information</h3>
                    <p>We use the collected information to:</p>
                    <ul>
                        <li>Provide and maintain our services</li>
                        <li>Personalize your experience</li>
                        <li>Send important updates and notifications</li>
                        <li>Improve our platform and services</li>
                        <li>Ensure platform security and prevent fraud</li>
                        </ul>
                    </div>

                    <div class="disclaimer-card">
                    <h3>Data Security</h3>
                    <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
                    <p>Your data is encrypted during transmission and storage using industry-standard protocols.</p>
                </div>
                
                <div class="disclaimer-card">
                    <h3>Data Sharing</h3>
                    <p>We do not sell, trade, or rent your personal information to third parties. We may share information only in these circumstances:</p>
                    <ul>
                        <li>With your explicit consent</li>
                        <li>To comply with legal obligations</li>
                        <li>To protect our rights and safety</li>
                        <li>With trusted service providers (under strict confidentiality agreements)</li>
                    </ul>
                </div>
                
                <div class="disclaimer-card">
                    <h3>Your Rights</h3>
                    <p>You have the right to:</p>
                    <ul>
                        <li>Access your personal information</li>
                        <li>Correct inaccurate data</li>
                        <li>Request deletion of your data</li>
                        <li>Opt-out of marketing communications</li>
                        <li>Data portability</li>
                    </ul>
                </div>
                
                <div class="disclaimer-card">
                    <h3>Contact Us</h3>
                    <p>If you have questions about this Privacy Policy or our data practices, please contact us at:</p>
                    <p>Email: privacy@thetradersescape.com</p>
                    <p>We will respond to your inquiry within 30 days.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
