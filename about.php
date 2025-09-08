<?php
/**
 * About Us Page for TraderEscape
 * Learn about our platform and mission
 */

session_start();
require_once __DIR__ . '/includes/db_functions.php';

// Get current page for header
$currentPage = 'about';

// Track page view
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
trackPageView('about', $userId, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, $_SERVER['HTTP_REFERER'] ?? null, session_id());

// Log user activity if logged in
if ($userId) {
    logUserActivity($userId, 'page_view', 'Viewed about page', $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, json_encode(['page' => 'about']));
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
                                <span class="title-line">About</span>
                                <span class="title-line highlight">The Trader's Escape</span>
                            </h1>
                            <p class="hero-subtitle">Empowering Traders Through Education</p>
                            <p class="hero-description">Discover our mission to democratize trading education and provide comprehensive tools for market success. Learn about our journey and commitment to trader empowerment.</p>
                            <div class="hero-disclaimer" role="alert" aria-label="Important disclaimer">
                                <span class="disclaimer-icon">⚠️</span>
                                <span>We provide educational content and do not provide any tips or calls. You are responsible for your own actions.</span>
                            </div>
                            <div class="hero-buttons">
                                <a href="#about-content" class="btn btn-primary">
                                    <span>Learn More</span>
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
                                        <i class="bi bi-book"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-lightbulb"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-trophy"></i>
                                    </div>
                                </div>
                                <div class="tools-glow"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- About Content Section -->
        <section class="disclaimer-section" id="about-content">
            <div class="container">
                <div class="disclaimer-content">
                    <div class="disclaimer-card">
                        <h2>Our Mission</h2>
                        <p>The Trader's Escape was founded with a simple yet powerful mission: to democratize trading education and empower individuals with the knowledge, tools, and confidence needed to navigate the complex world of financial markets.</p>
                        <p>We believe that financial literacy and trading education should be accessible to everyone, regardless of their background or experience level. Our platform serves as a comprehensive resource for traders at all stages of their journey.</p>
                    </div>

                    <div class="disclaimer-card">
                        <h3>What We Do</h3>
                        <p>At The Trader's Escape, we provide:</p>
                        <ul>
                            <li><strong>Comprehensive Educational Content:</strong> From basic concepts to advanced strategies</li>
                            <li><strong>Interactive Learning Tools:</strong> Practical tools to enhance your trading skills</li>
                            <li><strong>Market Analysis Resources:</strong> Insights and analysis to inform your decisions</li>
                            <li><strong>Community Support:</strong> A network of like-minded traders and learners</li>
                            <li><strong>Risk Management Education:</strong> Essential knowledge for protecting your capital</li>
                        </ul>
                    </div>

                    <div class="disclaimer-card">
                        <h3>Our Values</h3>
                        <div class="values-grid">
                            <div class="value-item">
                                <h4><i class="bi bi-lightbulb"></i> Education First</h4>
                                <p>We prioritize education over speculation, focusing on building solid foundations and understanding market mechanics.</p>
                            </div>
                            <div class="value-item">
                                <h4><i class="bi bi-shield-check"></i> Risk Awareness</h4>
                                <p>We emphasize the importance of risk management and responsible trading practices.</p>
                            </div>
                            <div class="value-item">
                                <h4><i class="bi bi-people"></i> Community</h4>
                                <p>We foster a supportive community where traders can learn, share, and grow together.</p>
                            </div>
                            <div class="value-item">
                                <h4><i class="bi bi-graph-up"></i> Continuous Improvement</h4>
                                <p>We constantly evolve our content and tools based on market changes and user feedback.</p>
                            </div>
                        </div>
                    </div>

                    <div class="disclaimer-card">
                        <h3>Our Approach</h3>
                        <p>Unlike traditional trading education platforms, we take a holistic approach that combines:</p>
                        <ul>
                            <li><strong>Theoretical Knowledge:</strong> Understanding market fundamentals and trading principles</li>
                            <li><strong>Practical Application:</strong> Real-world examples and case studies</li>
                            <li><strong>Tool Integration:</strong> Hands-on experience with trading tools and platforms</li>
                            <li><strong>Risk Management:</strong> Comprehensive coverage of risk mitigation strategies</li>
                            <li><strong>Psychological Aspects:</strong> Mental discipline and emotional control in trading</li>
                        </ul>
                    </div>

                    <div class="disclaimer-card">
                        <h3>Educational Philosophy</h3>
                        <p>Our educational philosophy is built on three core principles:</p>
                        <div class="philosophy-grid">
                            <div class="philosophy-item">
                                <h4>1. Progressive Learning</h4>
                                <p>We structure our content to build knowledge progressively, from basic concepts to advanced strategies, ensuring a solid foundation at each step.</p>
                            </div>
                            <div class="philosophy-item">
                                <h4>2. Practical Focus</h4>
                                <p>Every concept is accompanied by practical examples and real-world applications, making learning relevant and actionable.</p>
                            </div>
                            <div class="philosophy-item">
                                <h4>3. Continuous Support</h4>
                                <p>Learning doesn't end with content consumption. We provide ongoing support through tools, community, and updated resources.</p>
                            </div>
                        </div>
                    </div>

                    <div class="disclaimer-card">
                        <h3>Our Commitment</h3>
                        <p>We are committed to:</p>
                        <ul>
                            <li>Providing accurate, up-to-date, and comprehensive educational content</li>
                            <li>Maintaining transparency about the risks involved in trading</li>
                            <li>Never providing specific investment advice or stock recommendations</li>
                            <li>Continuously improving our platform based on user feedback</li>
                            <li>Fostering a supportive and inclusive trading community</li>
                            <li>Promoting responsible trading practices and risk management</li>
                        </ul>
                    </div>

                    <div class="disclaimer-card">
                        <h3>Join Our Community</h3>
                        <p>Whether you're a complete beginner or an experienced trader looking to refine your skills, The Trader's Escape welcomes you to join our community of learners and traders.</p>
                        <p>Start your journey with us and discover the tools, knowledge, and support you need to navigate the markets with confidence.</p>
                        <div class="cta-buttons">
                            <a href="#" class="btn btn-primary" onclick="accessTools()">Explore Our Tools</a>
                            <a href="./contact.php" class="btn btn-secondary">Get in Touch</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

<?php include 'includes/footer.php'; ?>

    <script>
    // Access tools function
    function accessTools() {
        // Check if user is logged in
        fetch('./check_auth.php')
            .then(response => response.json())
            .then(data => {
                if (data.authenticated) {
                    window.location.href = './tools.php';
                } else {
                    window.location.href = './login.php';
                }
            })
            .catch(error => {
                console.error('Auth check failed:', error);
                window.location.href = './login.php';
            });
        }
    </script>
