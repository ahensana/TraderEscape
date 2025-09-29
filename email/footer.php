<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <div class="footer-logo">
                    <img src="../assets/logo.png" alt="The Trader's Escape">
                    <span>The Trader's Escape</span>
                </div>
                <p>Empowering traders with comprehensive educational content and advanced tools for stock market success.</p>
                <div class="social-links">
                    <a href="#" class="social-link" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="social-link" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="social-link" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="social-link" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    <a href="#" class="social-link" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    <a href="#" class="social-link" aria-label="Discord"><i class="bi bi-discord"></i></a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="../">Home</a></li>
                    <li><a href="../about.php">About</a></li>
                    <li><a href="../tools.php">Tools</a></li>
                    <li><a href="../disclaimer.php">Disclaimer</a></li>
                    <li><a href="../risk.php">Risk Disclosure</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Legal</h4>
                <ul>
                    <li><a href="../privacy.php">Privacy Policy</a></li>
                    <li><a href="../terms.php">Terms & Conditions</a></li>
                    <li><a href="../cookies.php">Cookies Policy</a></li>
                    <li><a href="../contact.php">Contact Us</a></li>
                </ul>
            </div>
            
            <div class="footer-section">
                <h4>Educational Notice</h4>
                <p>All content is for educational purposes only. We do not provide financial advice or stock recommendations.</p>
                <div class="footer-badge">Educational Content Only</div>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2024 The Trader's Escape. All rights reserved. | Educational content only - not financial advice.</p>
        </div>
    </div>
</footer>

<!-- Floating Action Button -->
<div class="fab-container">
    <button class="fab-button" onclick="scrollToTop()" aria-label="Scroll to top of page">
        <i class="bi bi-arrow-up" aria-hidden="true"></i>
    </button>
</div>

<!-- Scripts -->
<script>
// Minimal functionality for OTP page
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Auto-hide alerts after 5 seconds
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 300);
    });
}, 5000);
</script>
