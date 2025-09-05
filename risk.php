<?php
/**
 * Risk Disclosure Page for TraderEscape
 * Important risk information for traders
 */

// Get current page for header
$currentPage = 'risk';
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
                <span style="display: block;">Risk</span>
                    <span style="
                        display: block;
                        background: linear-gradient(135deg, #3b82f6, #8b5cf6);
                        -webkit-background-clip: text;
                        -webkit-text-fill-color: transparent;
                        background-clip: text;
                ">Disclosure</span>
                </h1>
                <p style="
                    font-size: 1.5rem;
                    color: #e2e8f0;
                    margin-bottom: 1rem;
            ">Understanding Trading Risks</p>
                <p style="
                    font-size: 1.1rem;
                    color: #cbd5e1;
                    line-height: 1.6;
            ">Trading in financial markets involves substantial risk. This page outlines the key risks you should understand before engaging in any trading activities.</p>
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
