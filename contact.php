<?php
/**
 * Contact Page for TraderEscape
 * Handles contact form submissions and displays contact information
 */

// Start session
session_start();

// Include database functions
require_once __DIR__ . '/includes/db_functions.php';

// Handle form submission
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message_text = trim($_POST['message'] ?? '');
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message_text)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            // Store contact form submission in database
            $pdo = getDB();
            
            // Create contact_submissions table if it doesn't exist
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS contact_submissions (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    subject VARCHAR(200) NOT NULL,
                    message TEXT NOT NULL,
                    ip_address VARCHAR(45),
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");
            
            $stmt = $pdo->prepare("
                INSERT INTO contact_submissions (name, email, subject, message, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $name, 
                $email, 
                $subject, 
                $message_text,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            
            $message = 'Thank you for your message! We will get back to you soon.';
            
            // Clear form data
            $name = $email = $subject = $message_text = '';
            
        } catch (Exception $e) {
            error_log("Contact form error: " . $e->getMessage());
            $error = 'Sorry, there was an error sending your message. Please try again.';
        }
    }
}

// Get current page for header
$currentPage = 'contact';
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
                                <span class="title-line">Get In</span>
                                <span class="title-line highlight">Touch</span>
                            </h1>
                            <p class="hero-subtitle">We're Here to Help</p>
                            <p class="hero-description">Have questions about our platform, tools, or educational content? Reach out to our team and we'll get back to you as soon as possible.</p>
                            <div class="hero-disclaimer" role="alert" aria-label="Important disclaimer">
                                <span class="disclaimer-icon">⚠️</span>
                                <span>We provide educational content and do not provide any tips or calls. You are responsible for your own actions.</span>
                            </div>
                            <div class="hero-buttons">
                                <a href="#contact-form" class="btn btn-primary">
                                    <span>Send Message</span>
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
                                        <i class="bi bi-envelope"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-chat-dots"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-headset"></i>
                                    </div>
                                    <div class="tool-icon" aria-hidden="true">
                                        <i class="bi bi-question-circle"></i>
                                    </div>
                                </div>
                                <div class="tools-glow"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    <!-- Contact Form Section -->
    <section class="disclaimer-section" id="contact-form">
            <div class="container">
                <div class="disclaimer-content">
                <?php if ($message): ?>
                    <div class="alert alert-success" style="
                        background: rgba(34, 197, 94, 0.1);
                        border: 1px solid rgba(34, 197, 94, 0.3);
                        color: #22c55e;
                        padding: 1rem;
                        border-radius: 8px;
                        margin-bottom: 2rem;
                        text-align: center;
                    ">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error" style="
                        background: rgba(239, 68, 68, 0.1);
                        border: 1px solid rgba(239, 68, 68, 0.3);
                        color: #ef4444;
                        padding: 1rem;
                        border-radius: 8px;
                        margin-bottom: 2rem;
                        text-align: center;
                    ">
                        <?php echo htmlspecialchars($error); ?>
                                </div>
                <?php endif; ?>

                <div class="disclaimer-card">
                    <h2>Send Us a Message</h2>
                    <p>Fill out the form below and we'll get back to you within 24 hours.</p>
                    
                    <form method="POST" action="" style="margin-top: 2rem;">
                        <div style="margin-bottom: 1.5rem;">
                            <label for="name" style="
                                display: block;
                                margin-bottom: 0.5rem;
                                color: #e2e8f0;
                                font-weight: 600;
                            ">Full Name *</label>
                            <input type="text" id="name" name="name" required style="
                                width: 100%;
                                padding: 0.75rem;
                                border: 1px solid rgba(59, 130, 246, 0.3);
                                border-radius: 8px;
                                background: rgba(15, 23, 42, 0.8);
                                color: white;
                                font-size: 1rem;
                                transition: all 0.3s ease;
                            " value="<?php echo htmlspecialchars($name ?? ''); ?>">
                                </div>
                                
                        <div style="margin-bottom: 1.5rem;">
                            <label for="email" style="
                                display: block;
                                margin-bottom: 0.5rem;
                                color: #e2e8f0;
                                font-weight: 600;
                            ">Email Address *</label>
                            <input type="email" id="email" name="email" required style="
                                width: 100%;
                                padding: 0.75rem;
                                border: 1px solid rgba(59, 130, 246, 0.3);
                                border-radius: 8px;
                                background: rgba(15, 23, 42, 0.8);
                                color: white;
                                font-size: 1rem;
                                transition: all 0.3s ease;
                            " value="<?php echo htmlspecialchars($email ?? ''); ?>">
                                </div>
                                
                        <div style="margin-bottom: 1.5rem;">
                            <label for="subject" style="
                                display: block;
                                margin-bottom: 0.5rem;
                                color: #e2e8f0;
                                font-weight: 600;
                            ">Subject *</label>
                            <input type="text" id="subject" name="subject" required style="
                                width: 100%;
                                padding: 0.75rem;
                                border: 1px solid rgba(59, 130, 246, 0.3);
                                border-radius: 8px;
                                background: rgba(15, 23, 42, 0.8);
                                color: white;
                                font-size: 1rem;
                                transition: all 0.3s ease;
                            " value="<?php echo htmlspecialchars($subject ?? ''); ?>">
                                </div>
                                
                        <div style="margin-bottom: 2rem;">
                            <label for="message" style="
                                display: block;
                                margin-bottom: 0.5rem;
                                color: #e2e8f0;
                                font-weight: 600;
                            ">Message *</label>
                            <textarea id="message" name="message" rows="6" required style="
                                width: 100%;
                                padding: 0.75rem;
                                border: 1px solid rgba(59, 130, 246, 0.3);
                                border-radius: 8px;
                                background: rgba(15, 23, 42, 0.8);
                                color: white;
                                font-size: 1rem;
                                resize: vertical;
                                transition: all 0.3s ease;
                            "><?php echo htmlspecialchars($message_text ?? ''); ?></textarea>
                        </div>

                        <button type="submit" style="
                            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
                            color: white;
                            border: none;
                            padding: 1rem 2rem;
                            border-radius: 8px;
                            font-size: 1.1rem;
                            font-weight: 600;
                            cursor: pointer;
                            transition: all 0.3s ease;
                            width: 100%;
                        " onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                            Send Message
                        </button>
                    </form>
                                </div>
                                
                <div class="disclaimer-card">
                    <h3>Other Ways to Reach Us</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 1.5rem;">
                        <div style="text-align: center;">
                            <i class="bi bi-envelope" style="font-size: 2rem; color: #3b82f6; margin-bottom: 1rem;"></i>
                            <h4>Email Support</h4>
                            <p>support@thetradersescape.com</p>
                            <p style="color: #94a3b8; font-size: 0.9rem;">Response within 24 hours</p>
                                </div>
                                
                        <div style="text-align: center;">
                            <i class="bi bi-clock" style="font-size: 2rem; color: #3b82f6; margin-bottom: 1rem;"></i>
                            <h4>Business Hours</h4>
                            <p>Monday - Friday</p>
                            <p style="color: #94a3b8; font-size: 0.9rem;">9:00 AM - 6:00 PM EST</p>
                            </div>
                            
                        <div style="text-align: center;">
                            <i class="bi bi-chat-dots" style="font-size: 2rem; color: #3b82f6; margin-bottom: 1rem;"></i>
                            <h4>Live Chat</h4>
                            <p>Available during business hours</p>
                            <p style="color: #94a3b8; font-size: 0.9rem;">Click the chat icon below</p>
                            </div>
                        </div>
                    </div>

                    <div class="disclaimer-card">
                        <h3>Frequently Asked Questions</h3>
                    <div style="margin-top: 1.5rem;">
                        <details style="margin-bottom: 1rem; border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 8px; overflow: hidden;">
                            <summary style="
                                padding: 1rem;
                                background: rgba(59, 130, 246, 0.1);
                                cursor: pointer;
                                font-weight: 600;
                                color: #e2e8f0;
                            ">How quickly do you respond to inquiries?</summary>
                            <div style="padding: 1rem; background: rgba(15, 23, 42, 0.5);">
                                <p>We typically respond to all inquiries within 24 hours during business days. For urgent matters, please include "URGENT" in your subject line.</p>
                            </div>
                        </details>
                        
                        <details style="margin-bottom: 1rem; border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 8px; overflow: hidden;">
                            <summary style="
                                padding: 1rem;
                                background: rgba(59, 130, 246, 0.1);
                                cursor: pointer;
                                font-weight: 600;
                                color: #e2e8f0;
                            ">Do you provide technical support for the trading tools?</summary>
                            <div style="padding: 1rem; background: rgba(15, 23, 42, 0.5);">
                                <p>Yes, we provide comprehensive technical support for all our trading tools and educational resources. Our team is here to help you get the most out of our platform.</p>
                            </div>
                        </details>
                        
                        <details style="margin-bottom: 1rem; border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 8px; overflow: hidden;">
                            <summary style="
                                padding: 1rem;
                                background: rgba(59, 130, 246, 0.1);
                                color: #e2e8f0;
                                cursor: pointer;
                                font-weight: 600;
                            ">Can I request specific educational content?</summary>
                            <div style="padding: 1rem; background: rgba(15, 23, 42, 0.5);">
                                <p>Absolutely! We welcome suggestions for new educational content. If there's a specific topic or strategy you'd like us to cover, please let us know through this contact form.</p>
                            </div>
                        </details>
                    </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

<?php include 'includes/footer.php'; ?>
