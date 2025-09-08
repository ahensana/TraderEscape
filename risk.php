<?php
/**
 * Risk Disclosure Page for TraderEscape
 * Important risk information for traders
 */

session_start();
require_once __DIR__ . '/includes/db_functions.php';

// Get current page for header
$currentPage = 'risk';

// Track page view
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
trackPageView('risk', $userId, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, session_id());

// Log user activity if logged in
if ($userId) {
    logUserActivity($userId, 'page_view', 'Viewed risk page', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['page' => 'risk']));
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
                                <span class="title-line">Risk</span>
                                <span class="title-line highlight">Disclosure</span>
                            </h1>
                            <p class="hero-subtitle">Understanding Trading Risks</p>
                            <p class="hero-description">Trading in financial markets involves substantial risk. This page outlines the key risks you should understand before engaging in any trading activities.</p>
                            <div class="hero-disclaimer" role="alert" aria-label="Important disclaimer">
                                <span class="disclaimer-icon">⚠️</span>
                                <span>We provide educational content and do not provide any tips or calls. You are responsible for your own actions.</span>
                            </div>
                            <div class="hero-buttons">
                                <a href="#risk-content" class="btn btn-primary">
                                    <span>Read Risks</span>
                                </a>
                                <a href="./tools.php" class="btn btn-secondary">
                                    <span>Explore Tools</span>
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
                                        <i class="bi bi-graph-down"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-shield-exclamation"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-lightning"></i>
                                    </div>
                                </div>
                                <div class="tools-glow"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    <!-- Risk Content Section -->
        <section class="disclaimer-section" id="risk-content">
            <div class="container">
                <div class="disclaimer-content">
                    <div class="disclaimer-card">
                        <h2>Risk Disclosure Statement</h2>
                    <p>Trading in stocks, options, futures, and other financial instruments involves substantial risk and is not suitable for all investors.</p>
                    
                    <h3>Key Risks Include:</h3>
                    <ul>
                        <li><strong>Market Risk:</strong> The value of investments can go down as well as up</li>
                        <li><strong>Liquidity Risk:</strong> Some investments may be difficult to sell quickly</li>
                        <li><strong>Volatility Risk:</strong> Prices can change rapidly and unpredictably</li>
                        <li><strong>Leverage Risk:</strong> Using borrowed money can amplify losses</li>
                        <li><strong>Currency Risk:</strong> Exchange rate fluctuations can affect returns</li>
                        </ul>
                    </div>

                    <div class="disclaimer-card">
                    <h3>Capital Loss Risk</h3>
                    <p>You can lose some or all of your invested capital. Past performance is not indicative of future results.</p>
                    <p>Never invest more than you can afford to lose.</p>
                    </div>

                    <div class="disclaimer-card">
                    <h3>Educational Purpose Only</h3>
                    <p>The content on this platform is for educational purposes only and should not be considered as investment advice.</p>
                    <p>Always consult with qualified financial professionals before making investment decisions.</p>
                </div>
                
                <div class="disclaimer-card">
                    <h3>Risk Management</h3>
                    <p>Successful trading requires:</p>
                    <ul>
                        <li>Proper risk management strategies</li>
                        <li>Diversification of investments</li>
                        <li>Setting stop-loss orders</li>
                        <li>Understanding position sizing</li>
                        <li>Emotional discipline</li>
                    </ul>
                </div>
                
                <div class="disclaimer-card">
                    <h3>Regulatory Considerations</h3>
                    <p>Trading activities are subject to various regulations and tax implications that vary by jurisdiction.</p>
                    <p>Ensure compliance with all applicable laws and regulations in your area.</p>
                </div>
                
                <div class="disclaimer-card">
                    <p><strong>By using this platform, you acknowledge that you understand these risks and accept full responsibility for your trading decisions.</strong></p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
