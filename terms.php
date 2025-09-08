<?php
/**
 * Terms of Service Page for TraderEscape
 * Terms and conditions of use
 */

session_start();
require_once __DIR__ . '/includes/db_functions.php';

// Get current page for header
$currentPage = 'terms';

// Track page view
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
trackPageView('terms', $userId, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, session_id());

// Log user activity if logged in
if ($userId) {
    logUserActivity($userId, 'page_view', 'Viewed terms page', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['page' => 'terms']));
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
                                <span class="title-line">Terms of</span>
                                <span class="title-line highlight">Service</span>
                            </h1>
                            <p class="hero-subtitle">Platform Usage Terms</p>
                            <p class="hero-description">By using The Trader's Escape platform, you agree to these terms and conditions. Please read them carefully before proceeding.</p>
                            <div class="hero-disclaimer" role="alert" aria-label="Important disclaimer">
                                <span class="disclaimer-icon">⚠️</span>
                                <span>We provide educational content and do not provide any tips or calls. You are responsible for your own actions.</span>
                            </div>
                            <div class="hero-buttons">
                                <a href="#terms-content" class="btn btn-primary">
                                    <span>Read Terms</span>
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
                                        <i class="bi bi-file-text"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-shield-check"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-person-check"></i>
                                    </div>
                                </div>
                                <div class="tools-glow"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    <!-- Terms Content Section -->
        <section class="disclaimer-section" id="terms-content">
            <div class="container">
                <div class="disclaimer-content">
                    <div class="disclaimer-card">
                    <h2>Terms of Service</h2>
                    <p>These Terms of Service govern your use of The Trader's Escape platform and services.</p>
                    <p>Last updated: <?php echo date('F j, Y'); ?></p>
                    </div>

                    <div class="disclaimer-card">
                    <h3>Acceptance of Terms</h3>
                    <p>By accessing or using our platform, you agree to be bound by these Terms of Service and all applicable laws and regulations.</p>
                    <p>If you do not agree with any of these terms, you are prohibited from using our services.</p>
                    </div>

                    <div class="disclaimer-card">
                    <h3>Use License</h3>
                    <p>We grant you a limited, non-exclusive, non-transferable license to access and use our platform for educational purposes only.</p>
                    <p>You may not:</p>
                    <ul>
                        <li>Modify or copy our content without permission</li>
                        <li>Use our content for commercial purposes</li>
                        <li>Attempt to reverse engineer our platform</li>
                        <li>Interfere with platform security</li>
                        <li>Share your account credentials</li>
                        </ul>
                    </div>

                    <div class="disclaimer-card">
                    <h3>User Responsibilities</h3>
                    <p>As a user of our platform, you are responsible for:</p>
                    <ul>
                        <li>Maintaining the confidentiality of your account</li>
                        <li>All activities that occur under your account</li>
                        <li>Providing accurate and truthful information</li>
                        <li>Complying with all applicable laws and regulations</li>
                        <li>Respecting other users and platform guidelines</li>
                        </ul>
                    </div>

                    <div class="disclaimer-card">
                    <h3>Educational Content</h3>
                    <p>All content provided on our platform is for educational purposes only. We do not:</p>
                        <ul>
                        <li>Provide financial advice or recommendations</li>
                        <li>Guarantee investment returns</li>
                        <li>Endorse specific trading strategies</li>
                        <li>Take responsibility for trading decisions</li>
                        </ul>
                    </div>

                    <div class="disclaimer-card">
                    <h3>Intellectual Property</h3>
                    <p>The content, design, and functionality of our platform are protected by copyright, trademark, and other intellectual property laws.</p>
                    <p>You may not reproduce, distribute, or create derivative works without our written permission.</p>
                    </div>

                    <div class="disclaimer-card">
                    <h3>Limitation of Liability</h3>
                    <p>We shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of our platform.</p>
                    <p>Our total liability shall not exceed the amount you paid for our services, if any.</p>
                </div>
                
                <div class="disclaimer-card">
                    <h3>Termination</h3>
                    <p>We may terminate or suspend your access to our platform at any time, without prior notice, for conduct that we believe violates these Terms of Service.</p>
                </div>
                
                <div class="disclaimer-card">
                    <h3>Changes to Terms</h3>
                    <p>We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting on our platform.</p>
                    <p>Your continued use of our platform constitutes acceptance of the modified terms.</p>
                </div>
                
                <div class="disclaimer-card">
                    <h3>Contact Information</h3>
                    <p>If you have questions about these Terms of Service, please contact us at:</p>
                    <p>Email: legal@thetradersescape.com</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
