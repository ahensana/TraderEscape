<?php
/**
 * Disclaimer Page for TraderEscape
 * Important legal information and disclaimers
 */

// Get current page for header
$currentPage = 'disclaimer';
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
                    <span style="display: block;">Disclaimer</span>
                    <span style="
                        display: block;
                        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                        background-clip: text;
                    ">Legal Information</span>
                </h1>
                <p style="
                    font-size: 1.5rem;
                    color: #e2e8f0;
                    margin-bottom: 1rem;
                ">Important Information About Our Educational Platform</p>
                <p style="
                    font-size: 1.1rem;
                    color: #cbd5e1;
                    line-height: 1.6;
                ">Please read this disclaimer carefully before using our platform. This document outlines the scope, limitations, and legal framework of The Trader's Escape.</p>
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
