<?php
// Include cookie consent handler
require_once __DIR__ . '/../cookie_consent_handler.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only show banner if user is logged in AND hasn't given consent
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

// Show banner only if user is logged in AND has not given consent
$shouldShowBanner = $isLoggedIn && !hasCookieConsent();

if ($shouldShowBanner):
?>
<div id="cookieConsentBanner" class="cookie-consent-banner">
    <div class="cookie-consent-content">
        <div class="cookie-consent-text">
            <h4><i class="bi bi-cookie"></i> Cookie Notice</h4>
            <p>We use cookies to enhance your experience. By continuing to use this site, you agree to our use of cookies. 
            <a href="./cookies.php" target="_blank">Learn more about our cookie policy</a></p>
        </div>
        <div class="cookie-consent-actions">
            <button type="button" class="cookie-btn cookie-btn-decline" onclick="declineCookies()">Decline</button>
            <button type="button" class="cookie-btn cookie-btn-accept" onclick="acceptAllCookies()">Accept All</button>
        </div>
    </div>
</div>

<style>
.cookie-consent-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.95));
    backdrop-filter: blur(20px);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding: 1.5rem;
    z-index: 9999;
    transform: translateY(100%);
    transition: transform 0.3s ease;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.3);
}

.cookie-consent-banner.show {
    transform: translateY(0);
}

.cookie-consent-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 2rem;
}

.cookie-consent-text h4 {
    margin: 0 0 0.5rem 0;
    color: #ffffff;
    font-size: 1.1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cookie-consent-text h4 i {
    color: #f59e0b;
    font-size: 1.2rem;
}

.cookie-consent-text p {
    margin: 0;
    color: #94a3b8;
    font-size: 0.9rem;
    line-height: 1.5;
}

.cookie-consent-text a {
    color: #ffffff;
    text-decoration: underline;
    transition: color 0.3s ease;
}

.cookie-consent-text a:hover {
    color: #e2e8f0;
}

.cookie-consent-actions {
    display: flex;
    gap: 1rem;
    flex-shrink: 0;
}

.cookie-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: none;
}

.cookie-btn-decline {
    background: rgba(255, 255, 255, 0.1);
    color: #94a3b8;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.cookie-btn-decline:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
}

.cookie-btn-accept {
    background: linear-gradient(135deg, #ffffff, #f8fafc);
    color: #1e293b;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.cookie-btn-accept:hover {
    background: linear-gradient(135deg, #f8fafc, #e2e8f0);
    color: #0f172a;
    transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
    .cookie-consent-content {
        flex-direction: column;
        text-align: center;
        gap: 1.5rem;
    }
    
    .cookie-consent-actions {
        width: 100%;
        justify-content: center;
    }
    
    .cookie-btn {
        flex: 1;
        max-width: 150px;
    }
}

@media (max-width: 480px) {
    .cookie-consent-banner {
        padding: 1rem;
    }
    
    .cookie-consent-text h4 {
        font-size: 1rem;
    }
    
    .cookie-consent-text p {
        font-size: 0.85rem;
    }
    
    .cookie-btn {
        padding: 0.625rem 1.25rem;
        font-size: 0.85rem;
    }
}
</style>

<script>
// Show banner after page load
document.addEventListener('DOMContentLoaded', function() {
    const banner = document.getElementById('cookieConsentBanner');
    if (banner) {
        setTimeout(() => {
            banner.classList.add('show');
        }, 1000); // Show after 1 second
    }
});

// Accept all cookies
function acceptAllCookies() {
    const consentData = {
        action: 'cookie_consent',
        consent_type: 'accepted',
        essential_cookies: true,
        analytics_cookies: true,
        marketing_cookies: true,
        functional_cookies: true
    };
    
    saveCookieConsent(consentData);
}

// Decline cookies
function declineCookies() {
    const consentData = {
        action: 'cookie_consent',
        consent_type: 'declined',
        essential_cookies: false,
        analytics_cookies: false,
        marketing_cookies: false,
        functional_cookies: false
    };
    
    saveCookieConsent(consentData);
}

// Save cookie consent
function saveCookieConsent(consentData) {
    fetch('cookie_consent_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(consentData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hide banner with animation
            const banner = document.getElementById('cookieConsentBanner');
            if (banner) {
                banner.style.transform = 'translateY(100%)';
                banner.style.transition = 'transform 0.3s ease';
                setTimeout(() => {
                    banner.style.display = 'none';
                }, 300);
            }
            
            // Show success message
            console.log('Cookie consent saved successfully');
        } else {
            console.error('Failed to save cookie consent:', data.message);
            alert('Failed to save cookie consent: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error saving cookie consent:', error);
        alert('Error saving cookie consent. Please try again.');
    });
}
</script>
<?php endif; ?>