/* ===== SIMPLIFIED JAVASCRIPT - THE TRADER'S ESCAPE ===== */

// Check if already loaded to prevent redeclaration
if (typeof window.appInitialized !== "undefined") {
  console.log("App already initialized, skipping...");
  // Exit early by not executing the rest of the script
} else {
  // Initialize everything when DOM is loaded
  document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM loaded, initializing...");

    // Set initialization flag
    window.appInitialized = true;

    // Basic functionality
    initNavigation();
    initEducationalNotice();
    initLearningProgress();
    initFAQ();
    initSmoothScroll();
    initAuthentication();

    // PWA functionality
    initPWA();
  });

  /* ===== BASIC FUNCTIONALITY ===== */

  function initNavigation() {
    const navToggle = document.querySelector(".nav-toggle");
    const navMenu = document.querySelector(".nav-menu");
    const navLinks = document.querySelectorAll(".nav-link");

    if (navToggle && navMenu) {
      navToggle.addEventListener("click", () => {
        navMenu.classList.toggle("active");
        navToggle.classList.toggle("active");
      });

      // Close menu when clicking outside
      document.addEventListener("click", (e) => {
        if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
          navMenu.classList.remove("active");
          navToggle.classList.remove("active");
        }
      });
    }

    // Active link highlighting
    navLinks.forEach((link) => {
      link.addEventListener("click", () => {
        navLinks.forEach((l) => l.classList.remove("active"));
        link.classList.add("active");
      });
    });

    // Add scroll effect to navbar
    window.addEventListener("scroll", () => {
      const navbar = document.querySelector(".navbar");
      if (navbar) {
        if (window.scrollY > 50) {
          navbar.classList.add("scrolled");
        } else {
          navbar.classList.remove("scrolled");
        }
      }
    });
  }

  function initEducationalNotice() {
    const notice = document.getElementById("educational-notice");
    if (notice) {
      // Show notice after a delay
      setTimeout(() => {
        notice.style.display = "flex";
        notice.style.opacity = "1";
      }, 1000);
    }
  }

  function dismissNotice() {
    const notice = document.getElementById("educational-notice");
    if (notice) {
      notice.style.opacity = "0";
      setTimeout(() => {
        notice.style.display = "none";
      }, 300);
    }
  }

  function initLearningProgress() {
    const checkboxes = document.querySelectorAll(".learning-checkbox");

    checkboxes.forEach((checkbox) => {
      // Load saved state
      const saved = localStorage.getItem(checkbox.id);
      if (saved === "true") {
        checkbox.checked = true;
        checkbox.closest(".path-step").classList.add("completed");
      }

      // Save state on change
      checkbox.addEventListener("change", () => {
        localStorage.setItem(checkbox.id, checkbox.checked);
        if (checkbox.checked) {
          checkbox.closest(".path-step").classList.add("completed");
        } else {
          checkbox.closest(".path-step").classList.remove("completed");
        }
      });
    });
  }

  function initFAQ() {
    const faqItems = document.querySelectorAll(".faq-item");

    faqItems.forEach((item) => {
      const question = item.querySelector(".faq-question");
      const answer = item.querySelector(".faq-answer");
      const toggle = item.querySelector(".faq-toggle");

      if (question && answer && toggle) {
        question.addEventListener("click", () => {
          const isOpen = item.classList.contains("active");

          // Close all other items
          faqItems.forEach((otherItem) => {
            if (otherItem !== item) {
              otherItem.classList.remove("active");
              const otherAnswer = otherItem.querySelector(".faq-answer");
              const otherToggle = otherItem.querySelector(".faq-toggle");
              if (otherAnswer) otherAnswer.style.maxHeight = "0px";
              if (otherToggle) otherToggle.textContent = "+";
            }
          });

          // Toggle current item
          if (isOpen) {
            item.classList.remove("active");
            answer.style.maxHeight = "0px";
            toggle.textContent = "+";
          } else {
            item.classList.add("active");
            answer.style.maxHeight = answer.scrollHeight + "px";
            toggle.textContent = "−";
          }
        });
      }
    });
  }

  function initSmoothScroll() {
    const links = document.querySelectorAll('a[href^="#"]');

    links.forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();
        const targetId = link.getAttribute("href");
        const targetElement = document.querySelector(targetId);

        if (targetElement) {
          const offsetTop = targetElement.offsetTop - 100; // Account for fixed navbar
          window.scrollTo({
            top: offsetTop,
            behavior: "smooth",
          });
        }
      });
    });

    // Enable native smooth scrolling
    document.documentElement.style.scrollBehavior = "smooth";
  }

  // Export functions to global scope
  window.dismissNotice = dismissNotice;

  /* ===== PWA FUNCTIONALITY ===== */

  // Check if deferredPrompt already exists
  if (typeof window.deferredPrompt === "undefined") {
    window.deferredPrompt = null;
  }

  function initPWA() {
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);

    // Register service worker first
    // Service Worker Registration - DISABLED
    // if ('serviceWorker' in navigator) {
    //     window.addEventListener('load', () => {
    //         navigator.serviceWorker.register('./sw.js')
    //             .then(registration => {
    //                     console.log('ServiceWorker registration successful with scope: ', registration.scope);
    //         });
    //     });
    // }

    // Check if already installed
    if (window.matchMedia("(display-mode: standalone)").matches) {
      console.log("App is already installed");
      return;
    }

    // Handle install prompt (Android/Chrome)
    if (!isIOS) {
      window.addEventListener("beforeinstallprompt", (e) => {
        console.log("Install prompt triggered");
        e.preventDefault();
        window.deferredPrompt = e;

        // Check if user has dismissed the prompt recently
        const dismissedTime = null; // localStorage DISABLED
        const sevenDays = 7 * 24 * 60 * 60 * 1000; // 7 days in milliseconds

        if (
          !dismissedTime ||
          Date.now() - parseInt(dismissedTime) > sevenDays
        ) {
          // Show install button in navigation
          showInstallButton();
        }
      });
    } else {
      // For iOS, show the banner only
      console.log("iOS device detected - showing banner");
      setTimeout(() => {
        showIOSBanner();
      }, 2000); // Show after 2 seconds
    }

    // Handle app installed
    window.addEventListener("appinstalled", (evt) => {
      console.log("App was installed");
      window.deferredPrompt = null;
      hideInstallPrompt();
    });

    // Handle online/offline status
    window.addEventListener("online", () => {
      console.log("App is online");
      showOnlineStatus();
    });

    window.addEventListener("offline", () => {
      console.log("App is offline");
      showOfflineStatus();
    });

    // Check initial online status
    if (!navigator.onLine) {
      showOfflineStatus();
    }

    // Request notification permission
    requestNotificationPermission();

    // Log PWA status for debugging
    logPWAStatus();

    // Install button text update removed - buttons no longer exist
  }

  function showInstallButton() {
    // This function is kept for compatibility but no longer needed
    // since we removed the install buttons
    console.log("Install button function called - buttons removed");
  }

  function showInstallPrompt() {
    // Check if it's iPhone
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const isSafari =
      /Safari/.test(navigator.userAgent) && !/Chrome/.test(navigator.userAgent);

    // Create install prompt element
    const installPrompt = document.createElement("div");
    installPrompt.id = "install-prompt";
    installPrompt.className = "install-prompt";

    if (isIOS) {
      let browserMessage = "";
      if (!isSafari) {
        browserMessage =
          '<p style="color: #ef4444; font-size: 0.9rem; margin-top: 0.5rem;"><i class="bi bi-exclamation-triangle"></i> For best experience, use Safari browser</p>';
      }

      installPrompt.innerHTML = `
            <div class="install-content">
                <div class="install-icon">
                    <i class="bi bi-phone"></i>
                </div>
                <div class="install-text">
                    <h4>Add to Home Screen</h4>
                    <p>Tap the share button <i class="bi bi-share"></i> then "Add to Home Screen"</p>
                    ${browserMessage}
                </div>
                <div class="install-buttons">
                    <button class="btn btn-primary" onclick="showIOSInstructions()">Show Instructions</button>
                    <button class="btn btn-outline" onclick="dismissInstallPrompt()">Later</button>
                </div>
            </div>
        `;
    } else {
      installPrompt.innerHTML = `
            <div class="install-content">
                <div class="install-icon">
                    <i class="bi bi-download"></i>
                </div>
                <div class="install-text">
                    <h4>Install The Trader's Escape</h4>
                    <p>Get quick access to trading education on your device</p>
                </div>
                <div class="install-buttons">
                    <button class="btn btn-primary" onclick="installApp()">Install</button>
                    <button class="btn btn-outline" onclick="dismissInstallPrompt()">Later</button>
                </div>
            </div>
        `;
    }

    document.body.appendChild(installPrompt);

    // Show with animation
    setTimeout(() => {
      installPrompt.style.opacity = "1";
      installPrompt.style.transform = "translateY(0)";
    }, 100);
  }

  function hideInstallPrompt() {
    const installPrompt = document.getElementById("install-prompt");
    if (installPrompt) {
      installPrompt.style.opacity = "0";
      installPrompt.style.transform = "translateY(100%)";
      setTimeout(() => {
        installPrompt.remove();
      }, 300);
    }
  }

  function dismissInstallPrompt() {
    hideInstallPrompt();
    // Don't show again for 7 days
    // localStorage.setItem('install-prompt-dismissed', Date.now()); // DISABLED
  }

  function installApp() {
    if (window.deferredPrompt) {
      window.deferredPrompt.prompt();
      window.deferredPrompt.userChoice.then((choiceResult) => {
        if (choiceResult.outcome === "accepted") {
          console.log("User accepted the install prompt");
        } else {
          console.log("User dismissed the install prompt");
        }
        window.deferredPrompt = null;
      });
    }
    hideInstallPrompt();
  }

  function showOnlineStatus() {
    const statusElement = document.getElementById("online-status");
    if (statusElement) {
      statusElement.textContent = "Online";
      statusElement.className = "status-indicator online";
    }
  }

  function showOfflineStatus() {
    const statusElement = document.getElementById("online-status");
    if (statusElement) {
      statusElement.textContent = "Offline";
      statusElement.className = "status-indicator offline";
    }
  }

  function requestNotificationPermission() {
    if ("Notification" in window && Notification.permission === "default") {
      // Request permission after user interaction
      document.addEventListener(
        "click",
        function requestPermission() {
          Notification.requestPermission();
          document.removeEventListener("click", requestPermission);
        },
        { once: true }
      );
    }
  }

  // Export PWA functions to global scope
  window.installApp = installApp;
  window.dismissInstallPrompt = dismissInstallPrompt;
  window.showIOSInstructions = showIOSInstructions;
  window.closeIOSInstructions = closeIOSInstructions;
  window.closeIOSBanner = closeIOSBanner;
  window.showProfileMenu = showProfileMenu;
  window.closeProfileMenu = closeProfileMenu;

  function showIOSInstructions() {
    // Create iOS instructions modal
    const modal = document.createElement("div");
    modal.className = "ios-instructions-modal";
    modal.innerHTML = `
        <div class="ios-instructions-content">
            <div class="ios-instructions-header">
                <h3>How to Add to Home Screen</h3>
                <button onclick="closeIOSInstructions()" class="close-btn">&times;</button>
            </div>
            <div class="ios-instructions-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-text">Tap the share button <i class="bi bi-share"></i> at the bottom of your browser</div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-text">Scroll down and tap "Add to Home Screen"</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-text">Tap "Add" to confirm</div>
                </div>
            </div>
            <div class="ios-instructions-footer">
                <button class="btn btn-primary" onclick="closeIOSInstructions()">Got it!</button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Show with animation
    setTimeout(() => {
      modal.style.opacity = "1";
    }, 100);
  }

  function closeIOSInstructions() {
    const modal = document.querySelector(".ios-instructions-modal");
    if (modal) {
      modal.style.opacity = "0";
      setTimeout(() => {
        modal.remove();
      }, 300);
    }
  }

  function showIOSBanner() {
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const banner = document.getElementById("ios-install-banner");

    if (isIOS && banner) {
      // Check if user has dismissed the banner recently
      const dismissedTime = null; // localStorage DISABLED
      const oneDay = 24 * 60 * 60 * 1000; // 1 day in milliseconds

      if (!dismissedTime || Date.now() - parseInt(dismissedTime) > oneDay) {
        banner.style.display = "block";
      }
    }
  }

  function closeIOSBanner() {
    const banner = document.getElementById("ios-install-banner");
    if (banner) {
      banner.style.display = "none";
      // Don't show again for 1 day
      // localStorage.setItem('ios-banner-dismissed', Date.now()); // DISABLED
    }
  }

  function showProfileMenu() {
    console.log("Profile menu clicked - checking authentication...");
    // Check authentication status via AJAX
    fetch("./check_auth.php")
      .then((response) => response.json())
      .then((data) => {
        console.log("Auth check response:", data);
        if (data.authenticated) {
          // User is logged in, show profile menu
          console.log("User is authenticated, showing profile menu");
          showProfileMenuContent(data.user);
        } else {
          // User is not logged in, redirect to login
          console.log("User is not authenticated, redirecting to login");
          window.location.href = "./login.php";
        }
      })
      .catch((error) => {
        console.error("Error checking auth:", error);
        // Fallback: redirect to login
        window.location.href = "./login.php";
      });
  }

  function showProfileMenuContent(userData) {
    console.log("Creating profile menu with user data:", userData);
    const userName = userData.full_name || userData.username || "User";
    const userEmail = userData.email || "";

    // Create profile menu modal
    const modal = document.createElement("div");
    modal.className = "profile-menu-modal";
    modal.innerHTML = `
        <div class="profile-menu-content">
            <div class="profile-menu-header">
                <div class="profile-avatar">
                    <div class="avatar-ring"></div>
                    <i class="bi bi-person-circle"></i>
                </div>
                <div class="profile-info">
                    <h3>Welcome, ${userName}!</h3>
                    <p>${userEmail}</p>
                    <div class="profile-stats">
                        <span class="stat-item">
                            <i class="bi bi-star-fill"></i>
                            <span>Premium Member</span>
                        </span>
                    </div>
                </div>
                <button onclick="closeProfileMenu()" class="close-btn">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="profile-menu-body">
                <div class="profile-option" data-action="account">
                    <div class="option-icon">
                        <i class="bi bi-person"></i>
                        <div class="icon-glow"></div>
                    </div>
                    <div class="option-content">
                        <span class="option-title">My Account</span>
                        <span class="option-subtitle">View and edit profile</span>
                    </div>
                    <div class="option-badge">New</div>
                    <i class="bi bi-chevron-right"></i>
                </div>

                <div class="profile-option" data-action="help">
                    <div class="option-icon">
                        <i class="bi bi-question-circle"></i>
                        <div class="icon-glow"></div>
                    </div>
                    <div class="option-content">
                        <span class="option-title">Help & Support</span>
                        <span class="option-subtitle">Get assistance</span>
                    </div>
                    <i class="bi bi-chevron-right"></i>
                </div>
                <div class="profile-divider"></div>
                <div class="profile-option logout-option" data-action="logout">
                    <div class="option-icon">
                        <i class="bi bi-box-arrow-right"></i>
                        <div class="icon-glow"></div>
                    </div>
                    <div class="option-content">
                        <span class="option-title">Logout</span>
                        <span class="option-subtitle">Sign out of your account</span>
                    </div>
                    <i class="bi bi-chevron-right"></i>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Add click handlers
    const options = modal.querySelectorAll(".profile-option");
    options.forEach((option) => {
      option.addEventListener("click", function () {
        const action = this.getAttribute("data-action");
        handleProfileAction(action);
      });
    });

    // Show with enhanced animation
    setTimeout(() => {
      modal.style.opacity = "1";
      const content = modal.querySelector(".profile-menu-content");
      content.style.transform = "scale(1) rotateY(0deg)";

      // Stagger animation for menu items
      const menuItems = modal.querySelectorAll(".profile-option");
      menuItems.forEach((item, index) => {
        setTimeout(() => {
          item.style.opacity = "1";
          item.style.transform = "translateX(0)";
        }, 150 + index * 50);
      });
    }, 100);
  }

  function handleProfileAction(action) {
    console.log("Profile action:", action);
    // Add your action handling logic here
    switch (action) {
      case "account":
        // Handle account action - redirect to account page
        window.location.href = "./account.php";
        break;
      case "help":
        // Handle help action - redirect to contact page
        window.location.href = "./contact.php";
        break;
      case "logout":
        // Handle logout action
        logout();
        break;
    }
  }

  // Mobile Menu Functions
  function toggleMobileMenu() {
    const overlay = document.getElementById("mobile-menu-overlay");
    const isOpen = overlay.classList.contains("active");

    if (isOpen) {
      overlay.classList.remove("active");
      document.body.style.overflow = "";
    } else {
      overlay.classList.add("active");
      document.body.style.overflow = "hidden";
    }
  }

  // Close mobile menu when clicking outside
  document.addEventListener("click", function (event) {
    const overlay = document.getElementById("mobile-menu-overlay");
    const menuBtn = document.querySelector(".mobile-menu-btn");

    if (
      overlay.classList.contains("active") &&
      !overlay.contains(event.target) &&
      !menuBtn.contains(event.target)
    ) {
      toggleMobileMenu();
    }
  });

  // Handle bottom navigation active states
  function setActiveBottomNav(section) {
    const bottomNavItems = document.querySelectorAll(".bottom-nav-item");
    bottomNavItems.forEach((item) => {
      item.classList.remove("active");
    });

    const activeItem = document.querySelector(`[data-section="${section}"]`);
    if (activeItem) {
      activeItem.classList.add("active");
    }
  }

  function closeProfileMenu() {
    const modal = document.querySelector(".profile-menu-modal");
    if (modal) {
      modal.style.opacity = "0";
      setTimeout(() => {
        modal.remove();
      }, 300);
    }
  }

  function logPWAStatus() {
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);

    console.log("=== PWA Status Check ===");
    console.log("Service Worker Support:", "serviceWorker" in navigator);
    console.log(
      "Display Mode:",
      window.matchMedia("(display-mode: standalone)").matches
        ? "standalone"
        : "browser"
    );
    console.log(
      "Manifest:",
      document.querySelector('link[rel="manifest"]')?.href
    );
    console.log("HTTPS:", window.location.protocol === "https:");
    console.log("Online:", navigator.onLine);
    console.log("iOS Device:", isIOS);
    console.log("User Agent:", navigator.userAgent);

    // Check PWA installability
    checkPWAInstallability();

    if (isIOS) {
      console.log("📱 iPhone Installation:");
      console.log("1. Tap the share button (square with arrow)");
      console.log('2. Scroll down and tap "Add to Home Screen"');
      console.log('3. Tap "Add" to confirm');
      console.log("4. The app will appear on your home screen!");
      console.log("");
      console.log("💡 Tip: Make sure you're using Safari browser on iPhone");
    }

    console.log("========================");
  }

  function checkPWAInstallability() {
    const manifest = document.querySelector('link[rel="manifest"]');
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const isSafari =
      /Safari/.test(navigator.userAgent) && !/Chrome/.test(navigator.userAgent);

    console.log("PWA Installability Check:");
    console.log("- Manifest present:", !!manifest);
    console.log("- iOS device:", isIOS);
    console.log("- Safari browser:", isSafari);
    console.log("- HTTPS/HTTP:", window.location.protocol);

    // Check manifest content
    if (manifest) {
      fetch(manifest.href)
        .then((response) => response.json())
        .then((data) => {
          console.log("- Manifest validation:", {
            name: data.name,
            short_name: data.short_name,
            start_url: data.start_url,
            display: data.display,
            icons: data.icons?.length || 0,
          });
        })
        .catch((error) => {
          console.log("- Manifest error:", error.message);
        });
    }

    if (isIOS && !isSafari) {
      console.log("⚠️  Warning: Use Safari browser for best iOS PWA support");
    }

    if (
      window.location.protocol !== "https:" &&
      window.location.hostname !== "localhost"
    ) {
      console.log("⚠️  Warning: PWA requires HTTPS (except for localhost)");
    }

    // iOS specific checks
    if (isIOS) {
      console.log("📱 iOS PWA Requirements:");
      console.log("- ✅ apple-touch-icon present");
      console.log("- ✅ apple-mobile-web-app-capable present");
      console.log("- ✅ apple-mobile-web-app-status-bar-style present");
      console.log("- ✅ apple-mobile-web-app-title present");
      console.log("- ✅ mobile-web-app-capable present");
    }
  }

  /* ===== AUTHENTICATION FUNCTIONS ===== */

  function initAuthentication() {
    // Add click event listeners to all tools links
    const toolsLinks = document.querySelectorAll('a[href="/tools.php"]');
    toolsLinks.forEach((link) => {
      link.addEventListener("click", function (e) {
        e.preventDefault();
        checkAuthAndRedirect();
      });
    });

    // Also handle any dynamically added tools links
    const observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (node.nodeType === 1) {
            // Element node
            const newToolsLinks = node.querySelectorAll
              ? node.querySelectorAll('a[href="/tools.php"]')
              : [];
            newToolsLinks.forEach((link) => {
              link.addEventListener("click", function (e) {
                e.preventDefault();
                checkAuthAndRedirect();
              });
            });
          }
        });
      });
    });

    observer.observe(document.body, {
      childList: true,
      subtree: true,
    });
  }

  function checkAuthAndRedirect() {
    // Check authentication status via AJAX
    fetch("./check_auth.php")
      .then((response) => response.json())
      .then((data) => {
        if (data.authenticated) {
          // User is logged in, redirect to tools
          window.location.href = "./tools.php";
        } else {
          // User is not logged in, redirect to login page
          window.location.href = "./login.php";
        }
      })
      .catch((error) => {
        console.error("Error checking auth:", error);
        // Fallback: redirect to login
        window.location.href = "./login.php";
      });
  }

  function logout() {
    console.log("Logout initiated...");

    // Call logout endpoint
    fetch("./logout.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("Logout response:", data);
        if (data.success) {
          console.log("Logout successful, redirecting to login...");
          // Close profile menu
          closeProfileMenu();
          // Redirect to login page
          window.location.href = "./login.php";
        } else {
          console.error("Logout failed:", data.message);
          // Fallback: redirect anyway
          window.location.href = "./login.php";
        }
      })
      .catch((error) => {
        console.error("Logout error:", error);
        // Fallback: redirect anyway
        window.location.href = "./login.php";
      });
  }

  // Global function to access tools (can be called from anywhere)
  function accessTools() {
    checkAuthAndRedirect();
  }
} // Close the else block
