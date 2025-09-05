<?php
/**
 * Terms of Service Page for TraderEscape
 * Terms and conditions of use
 */

// Get current page for header
$currentPage = 'terms';
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
                <span style="display: block;">Terms of</span>
                    <span style="
                        display: block;
                        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                        background-clip: text;
                ">Service</span>
                </h1>
                <p style="
                    font-size: 1.5rem;
                    color: #e2e8f0;
                    margin-bottom: 1rem;
            ">Platform Usage Terms</p>
                <p style="
                    font-size: 1.1rem;
                    color: #cbd5e1;
                    line-height: 1.6;
            ">By using The Trader's Escape platform, you agree to these terms and conditions. Please read them carefully before proceeding.</p>
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
