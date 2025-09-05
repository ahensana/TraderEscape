<?php
/**
 * Privacy Policy Page for TraderEscape
 * Privacy and data protection information
 */

// Get current page for header
$currentPage = 'privacy';
?>
<?php include 'includes/header.php'; ?>

<main id="main-content" role="main" style="padding-top: 0;">
        <!-- Hero Section -->
        <section style="
        min-height: 40vh;
            display: flex;
            align-items: center;
            justify-content: center;
        padding-top: 0;
        padding-bottom: 1rem;
            background: transparent;
            color: white;
            text-align: center;
        " id="hero">
            <div style="max-width: 800px; padding: 1rem;">
                <h1 style="
                    font-size: 3rem;
                    font-weight: bold;
                    margin-bottom: 1rem;
                    color: white;
                ">
                <span style="display: block;">Privacy</span>
                    <span style="
                        display: block;
                        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                        background-clip: text;
                ">Policy</span>
                </h1>
                <p style="
                    font-size: 1.5rem;
                    color: #e2e8f0;
                    margin-bottom: 1rem;
            ">Your Data Protection Rights</p>
                <p style="
                    font-size: 1.1rem;
                    color: #cbd5e1;
                    line-height: 1.6;
            ">We are committed to protecting your privacy and ensuring the security of your personal information. This policy explains how we collect, use, and protect your data.</p>
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
