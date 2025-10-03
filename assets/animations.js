/* ===== PREMIUM ANIMATIONS SYSTEM - THE TRADER'S ESCAPE ===== */

// Global animation variables - check if already declared
if (typeof window.animationVars === "undefined") {
  window.animationVars = {
    tl: null,
    particlesInstance: null,
    threeScene: null,
    threeRenderer: null,
    threeCamera: null,
    typingAnimation: null,
    floatingShapes: null,
  };
}

// Use window variables to avoid redeclaration
const {
  tl,
  particlesInstance,
  threeScene,
  threeRenderer,
  threeCamera,
  typingAnimation,
  floatingShapes,
} = window.animationVars;

// Initialize animations when DOM is loaded - with performance optimization
document.addEventListener("DOMContentLoaded", function () {
  console.log("Initializing premium animations...");

  // Use requestIdleCallback for non-blocking initialization
  if (window.requestIdleCallback) {
    requestIdleCallback(() => {
      waitForGSAP();
    });
  } else {
    // Fallback for browsers without requestIdleCallback
    setTimeout(() => {
      waitForGSAP();
    }, 100);
  }
});

function waitForGSAP(attempts = 0) {
  const maxAttempts = 10;

  if (typeof gsap !== "undefined") {
    console.log("GSAP loaded, initializing animations...");
    initializeAnimations();
  } else if (attempts < maxAttempts) {
    console.log(`Waiting for GSAP... attempt ${attempts + 1}/${maxAttempts}`);
    setTimeout(() => waitForGSAP(attempts + 1), 200);
  } else {
    console.log("GSAP not available after waiting, using fallback animations");
    initializeAnimations();
  }
}

function initializeAnimations() {
  // Prevent double initialization
  if (window.animationsInitialized) {
    console.log("Animations already initialized, skipping...");
    return;
  }

  // Check if GSAP is available
  if (typeof gsap === "undefined") {
    console.log("GSAP not available, skipping advanced animations");
    // Initialize basic animations without GSAP
    initBasicAnimations();
    window.animationsInitialized = true;
    return;
  }

  // Initialize GSAP ScrollTrigger
  if (typeof ScrollTrigger !== "undefined") {
    gsap.registerPlugin(ScrollTrigger);
  }

  // Initialize custom cursor
  initCustomCursor();

  // Initialize particles (disabled - using custom trading background instead)
  // initParticles();

  // Initialize Three.js
  initThreeJS();

  // Initialize GSAP animations
  initGSAPAnimations();

  // Initialize scroll animations
  initScrollAnimations();

  // Initialize floating elements
  initFloatingElements();

  // Initialize button effects
  initButtonEffects();

  // Initialize premium features
  initPremiumFeatures();

  // Start page animations
  setTimeout(() => {
    startPageAnimations();
  }, 500);

  // Initialize charts after animations
  setTimeout(() => {
    if (typeof initCharts === "function") {
      initCharts();
    }
  }, 2000);

  // Mark animations as initialized
  window.animationsInitialized = true;
}

// Basic animations without GSAP
function initBasicAnimations() {
  console.log("Initializing basic animations without GSAP...");

  // Initialize basic cursor
  initBasicCursor();

  // Initialize basic floating elements
  initBasicFloatingElements();

  // Initialize basic button effects
  initBasicButtonEffects();
}

// Basic cursor without GSAP
function initBasicCursor() {
  const cursor = document.getElementById("custom-cursor");
  if (cursor) {
    document.addEventListener("mousemove", (e) => {
      cursor.style.left = e.clientX + "px";
      cursor.style.top = e.clientY + "px";
    });

    // Add hover effects
    const hoverElements = document.querySelectorAll(
      "a, button, .nav-link, .highlight-card, .tool-card"
    );
    hoverElements.forEach((element) => {
      if (element.querySelector(".chart-container")) return;

      element.addEventListener("mouseenter", () =>
        cursor.classList.add("hover")
      );
      element.addEventListener("mouseleave", () =>
        cursor.classList.remove("hover")
      );
    });
  }
}

// Basic floating elements without GSAP
function initBasicFloatingElements() {
  const floatingCards = document.querySelectorAll(".floating-card");
  floatingCards.forEach((card) => {
    card.style.animation = "float 3s ease-in-out infinite";
  });
}

// Basic button effects without GSAP
function initBasicButtonEffects() {
  const buttons = document.querySelectorAll(".btn");
  buttons.forEach((button) => {
    if (button.closest(".auth-container")) return;

    button.addEventListener("mouseenter", () => {
      button.style.transform = "scale(1.05)";
    });

    button.addEventListener("mouseleave", () => {
      button.style.transform = "scale(1)";
    });
  });
}

// Fallback initialization when window loads
window.addEventListener("load", function () {
  console.log("Window loaded - animations fallback...");

  // Only re-initialize if animations haven't been initialized yet
  if (!window.animationsInitialized) {
    setTimeout(() => {
      if (typeof gsap !== "undefined" && typeof ScrollTrigger !== "undefined") {
        console.log("Re-initializing animations...");
        initializeAnimations();
      }
    }, 1000);
  }
});

/* ===== CUSTOM CURSOR ===== */

function initCustomCursor() {
  const cursor = document.getElementById("custom-cursor");

  if (cursor && typeof gsap !== "undefined") {
    let mouseX = 0,
      mouseY = 0;
    let cursorX = 0,
      cursorY = 0;

    document.addEventListener("mousemove", (e) => {
      mouseX = e.clientX;
      mouseY = e.clientY;
    });

    // Animate cursor with GSAP
    gsap.to(
      {},
      {
        duration: 0.1,
        repeat: -1,
        onUpdate: () => {
          cursorX += (mouseX - cursorX) * 0.8;
          cursorY += (mouseY - cursorY) * 0.8;

          cursor.style.left = cursorX + "px";
          cursor.style.top = cursorY + "px";
        },
      }
    );

    // Add hover effects (excluding chart containers)
    const hoverElements = document.querySelectorAll(
      "a, button, .nav-link, .highlight-card, .tool-card"
    );
    hoverElements.forEach((element) => {
      // Skip chart containers to keep cursor normal size
      if (element.querySelector(".chart-container")) {
        return;
      }

      element.addEventListener("mouseenter", () => {
        cursor.classList.add("hover");
      });
      element.addEventListener("mouseleave", () => {
        cursor.classList.remove("hover");
      });
    });
  } else if (cursor) {
    // Fallback cursor animation without GSAP
    document.addEventListener("mousemove", (e) => {
      cursor.style.left = e.clientX + "px";
      cursor.style.top = e.clientY + "px";
    });

    // Add hover effects (excluding chart containers)
    const hoverElements = document.querySelectorAll(
      "a, button, .nav-link, .highlight-card, .tool-card"
    );
    hoverElements.forEach((element) => {
      // Skip chart containers to keep cursor normal size
      if (element.querySelector(".chart-container")) {
        return;
      }

      element.addEventListener("mouseenter", () => {
        cursor.classList.add("hover");
      });
      element.addEventListener("mouseleave", () => {
        cursor.classList.remove("hover");
      });
    });
  }
}

/* ===== PARTICLES SYSTEM ===== */

function initParticles() {
  if (typeof particlesJS !== "undefined") {
    // Check if particles container exists
    const particlesContainer = document.getElementById("particles-js");
    if (!particlesContainer) {
      console.log(
        "Particles container not found, skipping particles initialization"
      );
      return;
    }

    particlesJS("particles-js", {
      particles: {
        number: {
          value: 80,
          density: {
            enable: true,
            value_area: 800,
          },
        },
        color: {
          value: "#3b82f6",
        },
        shape: {
          type: "circle",
          stroke: {
            width: 0,
            color: "#000000",
          },
        },
        opacity: {
          value: 0.5,
          random: false,
          anim: {
            enable: false,
            speed: 1,
            opacity_min: 0.1,
            sync: false,
          },
        },
        size: {
          value: 3,
          random: true,
          anim: {
            enable: false,
            speed: 40,
            size_min: 0.1,
            sync: false,
          },
        },
        line_linked: {
          enable: true,
          distance: 150,
          color: "#3b82f6",
          opacity: 0.4,
          width: 1,
        },
        move: {
          enable: true,
          speed: 6,
          direction: "none",
          random: false,
          straight: false,
          out_mode: "out",
          bounce: false,
          attract: {
            enable: false,
            rotateX: 600,
            rotateY: 1200,
          },
        },
      },
      interactivity: {
        detect_on: "canvas",
        events: {
          onhover: {
            enable: true,
            mode: "repulse",
          },
          onclick: {
            enable: true,
            mode: "push",
          },
          resize: true,
        },
        modes: {
          grab: {
            distance: 400,
            line_linked: {
              opacity: 1,
            },
          },
          bubble: {
            distance: 400,
            size: 40,
            duration: 2,
            opacity: 8,
            speed: 3,
          },
          repulse: {
            distance: 200,
            duration: 0.4,
          },
          push: {
            particles_nb: 4,
          },
          remove: {
            particles_nb: 2,
          },
        },
      },
      retina_detect: true,
    });
  }
}

/* ===== THREE.JS BACKGROUND ===== */

function initThreeJS() {
  if (typeof THREE === "undefined") return;

  const canvas = document.getElementById("hero-canvas");
  if (!canvas) return;

  // Scene setup
  threeScene = new THREE.Scene();

  // Camera setup
  threeCamera = new THREE.PerspectiveCamera(
    75,
    window.innerWidth / window.innerHeight,
    0.1,
    1000
  );
  threeCamera.position.z = 5;

  // Renderer setup
  threeRenderer = new THREE.WebGLRenderer({
    canvas: canvas,
    alpha: true,
    antialias: true,
  });
  threeRenderer.setSize(window.innerWidth, window.innerHeight);
  threeRenderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

  // Create animated background
  createAnimatedBackground();

  // Animation loop
  animateThreeJS();

  // Handle resize
  window.addEventListener("resize", onThreeJSResize);
}

function createAnimatedBackground() {
  // Create floating geometry
  const geometry = new THREE.IcosahedronGeometry(1, 1);
  const material = new THREE.MeshBasicMaterial({
    color: 0x3b82f6,
    wireframe: true,
    transparent: true,
    opacity: 0.1,
  });

  for (let i = 0; i < 20; i++) {
    const mesh = new THREE.Mesh(geometry, material);
    mesh.position.set(
      (Math.random() - 0.5) * 20,
      (Math.random() - 0.5) * 20,
      (Math.random() - 0.5) * 20
    );
    mesh.rotation.set(
      Math.random() * Math.PI,
      Math.random() * Math.PI,
      Math.random() * Math.PI
    );
    threeScene.add(mesh);
  }
}

function animateThreeJS() {
  if (!threeScene || !threeCamera || !threeRenderer) return;

  requestAnimationFrame(animateThreeJS);

  // Rotate all meshes
  threeScene.children.forEach((child) => {
    if (child instanceof THREE.Mesh) {
      child.rotation.x += 0.005;
      child.rotation.y += 0.005;
    }
  });

  threeRenderer.render(threeScene, threeCamera);
}

function onThreeJSResize() {
  if (!threeCamera || !threeRenderer) return;

  threeCamera.aspect = window.innerWidth / window.innerHeight;
  threeCamera.updateProjectionMatrix();
  threeRenderer.setSize(window.innerWidth, window.innerHeight);
}

/* ===== PREMIUM FEATURES ===== */

function initPremiumFeatures() {
  // Initialize typing animation
  initTypingAnimation();

  // Initialize floating geometric shapes
  initFloatingShapes();

  // Initialize magnetic hover effects
  initMagneticHover();

  // Initialize glassmorphism effects
  initGlassmorphism();

  // Initialize gradient text animations
  initGradientText();

  // Initialize 3D card effects
  init3DCardEffects();

  // Initialize live social proof (disabled)
  // initLiveSocialProof();
}

function initTypingAnimation() {
  const heroTitle = document.querySelector(".hero-title .highlight");
  if (!heroTitle) return;

  const text = heroTitle.textContent;
  heroTitle.textContent = "";

  let i = 0;
  window.animationVars.typingAnimation = setInterval(() => {
    if (i < text.length) {
      heroTitle.textContent += text.charAt(i);
      i++;
    } else {
      clearInterval(window.animationVars.typingAnimation);
      // Add cursor blink effect
      heroTitle.classList.add("typing-complete");
    }
  }, 100);
}

function initFloatingShapes() {
  const heroSection = document.querySelector(".hero-section");
  if (!heroSection) return;

  // Create floating shapes
  for (let i = 0; i < 15; i++) {
    const shape = document.createElement("div");
    shape.className = "floating-shape";
    shape.style.cssText = `
            position: absolute;
            width: ${Math.random() * 60 + 20}px;
            height: ${Math.random() * 60 + 20}px;
            background: linear-gradient(45deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: ${Math.random() > 0.5 ? "50%" : "8px"};
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
            pointer-events: none;
            z-index: 1;
        `;

    heroSection.appendChild(shape);

    // Animate shape with GSAP if available
    if (typeof gsap !== "undefined") {
      gsap.to(shape, {
        duration: 10 + Math.random() * 10,
        x: (Math.random() - 0.5) * 200,
        y: (Math.random() - 0.5) * 200,
        rotation: 360,
        ease: "none",
        repeat: -1,
        yoyo: true,
      });
    } else {
      // Basic CSS animation fallback
      shape.style.animation = `float ${
        10 + Math.random() * 10
      }s ease-in-out infinite`;
    }
  }
}

function initMagneticHover() {
  const magneticElements = document.querySelectorAll(".highlight-card, .btn");

  magneticElements.forEach((element) => {
    // Skip auth-container and its children to prevent floating login form
    if (element.closest(".auth-container")) {
      return;
    }

    element.addEventListener("mousemove", (e) => {
      const rect = element.getBoundingClientRect();
      const x = e.clientX - rect.left - rect.width / 2;
      const y = e.clientY - rect.top - rect.height / 2;

      if (typeof gsap !== "undefined") {
        gsap.to(element, {
          duration: 0.3,
          x: x * 0.1,
          y: y * 0.1,
          ease: "power2.out",
        });
      } else {
        // Basic transform fallback
        element.style.transform = `translate(${x * 0.1}px, ${y * 0.1}px)`;
      }
    });

    element.addEventListener("mouseleave", () => {
      if (typeof gsap !== "undefined") {
        gsap.to(element, {
          duration: 0.3,
          x: 0,
          y: 0,
          ease: "power2.out",
        });
      } else {
        element.style.transform = "translate(0, 0)";
      }
    });
  });
}

function initGlassmorphism() {
  const glassElements = document.querySelectorAll(
    ".highlight-card, .tool-card, .testimonial-card"
  );

  glassElements.forEach((element) => {
    element.classList.add("glassmorphism");
  });
}

function initGradientText() {
  const gradientElements = document.querySelectorAll(
    ".section-title, .hero-title .highlight"
  );

  gradientElements.forEach((element) => {
    element.classList.add("gradient-text");
  });
}

function init3DCardEffects() {
  const cards = document.querySelectorAll(".highlight-card, .testimonial-card");

  cards.forEach((card) => {
    // Skip auth-container and its children to prevent floating login form
    if (card.closest(".auth-container")) {
      return;
    }

    card.addEventListener("mousemove", (e) => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;

      const centerX = rect.width / 2;
      const centerY = rect.height / 2;

      const rotateX = (y - centerY) / 10;
      const rotateY = (centerX - x) / 10;

      if (typeof gsap !== "undefined") {
        gsap.to(card, {
          duration: 0.3,
          rotateX: rotateX,
          rotateY: rotateY,
          transformPerspective: 1000,
          ease: "power2.out",
        });
      } else {
        // Basic transform fallback
        card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
      }
    });

    card.addEventListener("mouseleave", () => {
      if (typeof gsap !== "undefined") {
        gsap.to(card, {
          duration: 0.3,
          rotateX: 0,
          rotateY: 0,
          ease: "power2.out",
        });
      } else {
        card.style.transform =
          "perspective(1000px) rotateX(0deg) rotateY(0deg)";
      }
    });
  });
}

function initLiveSocialProof() {
  const socialProofContainer = document.createElement("div");
  socialProofContainer.className = "social-proof";
  socialProofContainer.innerHTML = `
        <div class="proof-item">
            <span class="proof-icon">👤</span>
            <span class="proof-text">Alex just completed Module 1</span>
        </div>
    `;

  const heroSection = document.querySelector(".hero-section");
  if (heroSection) {
    heroSection.appendChild(socialProofContainer);

    // Animate social proof with GSAP if available
    if (typeof gsap !== "undefined") {
      gsap.fromTo(
        socialProofContainer,
        { opacity: 0, y: 20 },
        {
          opacity: 1,
          y: 0,
          duration: 0.5,
          delay: 3,
          onComplete: () => {
            setTimeout(() => {
              gsap.to(socialProofContainer, {
                opacity: 0,
                y: -20,
                duration: 0.5,
                onComplete: () => {
                  socialProofContainer.remove();
                },
              });
            }, 3000);
          },
        }
      );
    } else {
      // Basic CSS animation fallback
      socialProofContainer.style.opacity = "0";
      socialProofContainer.style.transform = "translateY(20px)";

      setTimeout(() => {
        socialProofContainer.style.transition = "all 0.5s ease";
        socialProofContainer.style.opacity = "1";
        socialProofContainer.style.transform = "translateY(0)";

        setTimeout(() => {
          socialProofContainer.style.opacity = "0";
          socialProofContainer.style.transform = "translateY(-20px)";
          setTimeout(() => socialProofContainer.remove(), 500);
        }, 3000);
      }, 3000);
    }
  }
}

/* ===== GSAP ANIMATIONS ===== */

function initGSAPAnimations() {
  if (typeof gsap === "undefined") return;

  // Hero section animations - only animate elements that exist
  const heroTimeline = gsap.timeline();

  // Check if elements exist before animating
  const heroBadge = document.querySelector(".hero-badge");
  const heroTitle = document.querySelector(".hero-title .title-line");
  const heroSubtitle = document.querySelector(".hero-subtitle");
  const heroDescription = document.querySelector(".hero-description");
  const heroButtons = document.querySelector(".hero-buttons");
  const heroStats = document.querySelector(".hero-stats");
  const heroTradingIcons = document.querySelector(".hero-trading-icons");

  if (heroBadge) {
    heroTimeline.from(".hero-badge", {
      duration: 1,
      opacity: 0,
      y: 30,
      ease: "power3.out",
    });
  }

  if (heroTitle) {
    heroTimeline.from(
      ".hero-title .title-line",
      {
        duration: 1.2,
        opacity: 0,
        y: 50,
        stagger: 0.2,
        ease: "power3.out",
      },
      "-=0.5"
    );
  }

  if (heroSubtitle) {
    heroTimeline.from(
      ".hero-subtitle",
      {
        duration: 1,
        opacity: 0,
        y: 30,
        ease: "power3.out",
      },
      "-=0.8"
    );
  }

  if (heroDescription) {
    heroTimeline.from(
      ".hero-description",
      {
        duration: 1,
        opacity: 0,
        y: 30,
        ease: "power3.out",
      },
      "-=0.6"
    );
  }

  if (heroButtons) {
    heroTimeline.from(
      ".hero-buttons",
      {
        duration: 1,
        opacity: 0,
        y: 30,
        ease: "power3.out",
      },
      "-=0.4"
    );
  }

  if (heroStats) {
    heroTimeline.from(
      ".hero-stats",
      {
        duration: 1,
        opacity: 0,
        y: 30,
        ease: "power3.out",
      },
      "-=0.6"
    );
  }

  if (heroTradingIcons) {
    heroTimeline.from(
      ".hero-trading-icons",
      {
        duration: 1,
        opacity: 0,
        scale: 0.5,
        ease: "back.out(1.7)",
      },
      "-=0.8"
    );
  }
}

function startPageAnimations() {
  if (typeof gsap === "undefined") return;

  // Animate sections on scroll - only if elements exist
  const cards = gsap.utils.toArray(
    ".highlight-card, .tool-card, .testimonial-card"
  );
  if (cards.length > 0) {
    cards.forEach((card) => {
      gsap.from(card, {
        scrollTrigger: {
          trigger: card,
          start: "top 80%",
          end: "bottom 20%",
          toggleActions: "play none none reverse",
        },
        duration: 1,
        opacity: 0,
        y: 50,
        scale: 0.9,
        ease: "power3.out",
      });
    });
  }

  // Animate learning path steps - only if elements exist
  const pathSteps = gsap.utils.toArray(".path-step");
  if (pathSteps.length > 0) {
    pathSteps.forEach((step, index) => {
      gsap.from(step, {
        scrollTrigger: {
          trigger: step,
          start: "top 80%",
          end: "bottom 20%",
          toggleActions: "play none none reverse",
        },
        duration: 1,
        opacity: 0,
        x: -100,
        delay: index * 0.2,
        ease: "power3.out",
      });
    });
  }
}

/* ===== SCROLL ANIMATIONS ===== */

function initScrollAnimations() {
  if (typeof gsap === "undefined") return;

  // Parallax effects
  gsap.utils.toArray(".floating-card").forEach((card) => {
    const speed = parseFloat(card.dataset.speed) || 0.5;

    gsap.to(card, {
      scrollTrigger: {
        trigger: card,
        start: "top bottom",
        end: "bottom top",
        scrub: true,
      },
      y: -100 * speed,
      ease: "none",
    });
  });

  // Navbar scroll effect
  gsap.to(".navbar", {
    scrollTrigger: {
      trigger: "body",
      start: "top top",
      end: "bottom bottom",
      scrub: true,
    },
    backgroundColor: "rgba(15, 23, 42, 0.98)",
    boxShadow: "0 10px 15px -3px rgba(0, 0, 0, 0.1)",
  });
}

/* ===== FLOATING ELEMENTS ===== */

function initFloatingElements() {
  // Animate floating cards
  gsap.utils.toArray(".floating-card").forEach((card) => {
    if (typeof gsap !== "undefined") {
      gsap.to(card, {
        duration: 3,
        y: -20,
        ease: "power2.inOut",
        yoyo: true,
        repeat: -1,
      });
    } else {
      // Basic CSS animation fallback
      card.style.animation = "float 3s ease-in-out infinite";
    }
  });

  // Animate bull/bear icons
  const bullIcon = document.querySelector(".bull-icon");
  const bearIcon = document.querySelector(".bear-icon");

  if (bullIcon) {
    if (typeof gsap !== "undefined") {
      gsap.to(bullIcon, {
        duration: 2,
        y: -10,
        ease: "power2.inOut",
        yoyo: true,
        repeat: -1,
      });
    } else {
      bullIcon.style.animation = "float 2s ease-in-out infinite";
    }
  }

  if (bearIcon) {
    if (typeof gsap !== "undefined") {
      gsap.to(bearIcon, {
        duration: 2.5,
        y: -8,
        ease: "power2.inOut",
        yoyo: true,
        repeat: -1,
        delay: 0.5,
      });
    } else {
      bearIcon.style.animation = "float 2.5s ease-in-out infinite";
    }
  }
}

/* ===== COUNTER ANIMATIONS ===== */

function initCounterAnimations() {
  const counters = document.querySelectorAll(".stat-number");

  counters.forEach((counter) => {
    const target = parseInt(counter.dataset.target);
    const suffix = counter.dataset.suffix || "";

    if (typeof gsap !== "undefined") {
      gsap.to(counter, {
        scrollTrigger: {
          trigger: counter,
          start: "top 80%",
          toggleActions: "play none none reverse",
        },
        duration: 2,
        innerHTML: target,
        ease: "power2.out",
        snap: { innerHTML: 1 },
        onUpdate: function () {
          counter.textContent = Math.floor(counter.innerHTML) + suffix;
        },
      });
    } else {
      // Basic counter animation fallback
      let current = 0;
      const increment = target / 100;
      const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
          current = target;
          clearInterval(timer);
        }
        counter.textContent = Math.floor(current) + suffix;
      }, 20);
    }
  });
}

/* ===== BUTTON EFFECTS ===== */

function initButtonEffects() {
  const buttons = document.querySelectorAll(".btn");

  buttons.forEach((button) => {
    // Skip auth-container buttons to prevent floating login form
    if (button.closest(".auth-container")) {
      return;
    }

    button.addEventListener("mouseenter", () => {
      if (typeof gsap !== "undefined") {
        gsap.to(button, {
          duration: 0.3,
          scale: 1.05,
          ease: "power2.out",
        });
      } else {
        button.style.transform = "scale(1.05)";
      }
    });

    button.addEventListener("mouseleave", () => {
      if (typeof gsap !== "undefined") {
        gsap.to(button, {
          duration: 0.3,
          scale: 1,
          ease: "power2.out",
        });
      } else {
        button.style.transform = "scale(1)";
      }
    });

    button.addEventListener("click", () => {
      if (typeof gsap !== "undefined") {
        gsap.to(button, {
          duration: 0.1,
          scale: 0.95,
          ease: "power2.out",
          yoyo: true,
          repeat: 1,
        });
      } else {
        button.style.transform = "scale(0.95)";
        setTimeout(() => {
          button.style.transform = "scale(1)";
        }, 100);
      }
    });
  });
}

/* ===== UTILITY FUNCTIONS ===== */

// Smooth scroll to section
function smoothScrollTo(target) {
  const element = document.querySelector(target);
  if (element) {
    if (typeof gsap !== "undefined") {
      gsap.to(window, {
        duration: 1.5,
        scrollTo: {
          y: element,
          offsetY: 100,
        },
        ease: "power3.inOut",
      });
    } else {
      // Basic smooth scroll fallback
      element.scrollIntoView({ behavior: "smooth" });
    }
  }
}

// Add ripple effect to buttons
function addRippleEffect(event) {
  const button = event.currentTarget;
  const ripple = document.createElement("span");
  const rect = button.getBoundingClientRect();
  const size = Math.max(rect.width, rect.height);
  const x = event.clientX - rect.left - size / 2;
  const y = event.clientY - rect.top - size / 2;

  ripple.style.cssText = `
        position: absolute;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
    `;

  button.appendChild(ripple);

  setTimeout(() => {
    ripple.remove();
  }, 600);
}

// Add ripple animation to CSS
const style = document.createElement("style");
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-20px); }
    }
`;
document.head.appendChild(style);

// Export functions for global use
window.smoothScrollTo = smoothScrollTo;
window.addRippleEffect = addRippleEffect;

// Scroll to top function
function scrollToTop() {
  if (typeof gsap !== "undefined") {
    gsap.to(window, {
      duration: 1.5,
      scrollTo: { y: 0 },
      ease: "power3.inOut",
    });
  } else {
    // Basic scroll to top fallback
    window.scrollTo({ top: 0, behavior: "smooth" });
  }
}

window.scrollToTop = scrollToTop;
