/* ===== SIMPLIFIED SCROLL ANIMATIONS - THE TRADER'S ESCAPE ===== */

// Initialize scroll animations when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing scroll animations...');
    
    // Initialize basic animations immediately
    initBasicAnimations();
    
    // Initialize advanced animations after a delay
    setTimeout(() => {
        try {
            if (typeof gsap !== 'undefined') {
                initGSAPAnimations();
            }
        } catch (error) {
            console.log('GSAP animations not available:', error);
        }
    }, 100);
});

/* ===== BASIC ANIMATIONS ===== */

function initBasicAnimations() {
    // Add scroll-triggered animations using Intersection Observer
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    const animateElements = document.querySelectorAll('.highlight-card, .path-step, .tool-card, .testimonial-card, .faq-item');
    animateElements.forEach(el => {
        observer.observe(el);
    });
    
    // Add hover effects
    initHoverEffects();
    
    // Add scroll effects
    initScrollEffects();
}

function initHoverEffects() {
    // Card hover effects
    const cards = document.querySelectorAll('.highlight-card, .tool-card, .testimonial-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 25px rgba(59, 130, 246, 0.2)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = 'none';
        });
    });
    
    // Button hover effects
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

function initScrollEffects() {
    // Navbar scroll effect
    let lastScrollY = window.scrollY;
    
    window.addEventListener('scroll', () => {
        const navbar = document.querySelector('.navbar');
        if (navbar) {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        }
        
        // Parallax effect for hero elements
        const heroSection = document.querySelector('.hero-section');
        if (heroSection) {
            const scrolled = window.scrollY;
            const rate = scrolled * -0.5;
            
            const heroParticles = document.querySelector('.hero-particles');
            if (heroParticles) {
                heroParticles.style.transform = `translateY(${rate}px)`;
            }
            
            const logoGlow = document.querySelector('.logo-glow');
            if (logoGlow) {
                logoGlow.style.transform = `translate(-50%, -50%) scale(${1 + scrolled * 0.0001})`;
            }
        }
        
        lastScrollY = window.scrollY;
    });
}

/* ===== GSAP ANIMATIONS ===== */

function initGSAPAnimations() {
    if (typeof gsap === 'undefined') return;
    
    // Register ScrollTrigger if available
    if (typeof ScrollTrigger !== 'undefined') {
        gsap.registerPlugin(ScrollTrigger);
    }
    
    // Hero entrance animations
    initHeroAnimations();
    
    // Section reveal animations
    initSectionReveals();
    
    // Card animations
    initCardAnimations();
    
    // Parallax effects
    initParallaxEffects();
}

function initHeroAnimations() {
    // Animate hero elements on page load
    gsap.from('.hero-title .title-line', {
        duration: 1,
        y: 50,
        opacity: 0,
        stagger: 0.2,
        ease: 'power3.out'
    });
    
    gsap.from('.hero-subtitle', {
        duration: 1,
        y: 30,
        opacity: 0,
        delay: 0.5,
        ease: 'power3.out'
    });
    
    gsap.from('.hero-description', {
        duration: 1,
        y: 30,
        opacity: 0,
        delay: 0.7,
        ease: 'power3.out'
    });
    
    gsap.from('.hero-buttons', {
        duration: 1,
        y: 30,
        opacity: 0,
        delay: 0.9,
        ease: 'power3.out'
    });
    
    gsap.from('.hero-logo', {
        duration: 1.5,
        scale: 0.5,
        opacity: 0,
        delay: 0.3,
        ease: 'back.out(1.7)'
    });
}

function initSectionReveals() {
    // Animate section headers
    gsap.utils.toArray('.section-header').forEach(header => {
        gsap.from(header, {
            scrollTrigger: {
                trigger: header,
                start: 'top 80%',
                end: 'bottom 20%',
                toggleActions: 'play none none reverse'
            },
            duration: 0.8,
            y: 50,
            opacity: 0,
            ease: 'power3.out'
        });
    });
    
    // Animate cards with stagger
    gsap.utils.toArray('.highlight-card, .tool-card, .testimonial-card').forEach((card, index) => {
        gsap.from(card, {
            scrollTrigger: {
                trigger: card,
                start: 'top 85%',
                end: 'bottom 15%',
                toggleActions: 'play none none reverse'
            },
            duration: 0.8,
            y: 60,
            opacity: 0,
            delay: index * 0.1,
            ease: 'power3.out'
        });
    });
    
    // Animate learning path steps
    gsap.utils.toArray('.path-step').forEach((step, index) => {
        gsap.from(step, {
            scrollTrigger: {
                trigger: step,
                start: 'top 80%',
                end: 'bottom 20%',
                toggleActions: 'play none none reverse'
            },
            duration: 0.8,
            x: -50,
            opacity: 0,
            delay: index * 0.2,
            ease: 'power3.out'
        });
    });
}

function initCardAnimations() {
    // Enhanced hover animations for cards
    gsap.utils.toArray('.highlight-card, .tool-card, .testimonial-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            gsap.to(card, {
                scale: 1.02,
                duration: 0.3,
                ease: 'power2.out'
            });
        });
        
        card.addEventListener('mouseleave', () => {
            gsap.to(card, {
                scale: 1,
                duration: 0.3,
                ease: 'power2.out'
            });
        });
    });
    
    // Icon animations
    gsap.utils.toArray('.card-icon').forEach(icon => {
        icon.addEventListener('mouseenter', () => {
            gsap.to(icon, {
                rotation: 360,
                duration: 0.6,
                ease: 'power2.out'
            });
        });
    });
}

function initParallaxEffects() {
    // Hero background parallax
    gsap.to('.hero-particles', {
        scrollTrigger: {
            trigger: '.hero-section',
            start: 'top top',
            end: 'bottom top',
            scrub: 1
        },
        y: -200,
        ease: 'none'
    });
    
    // Logo glow parallax
    gsap.to('.logo-glow', {
        scrollTrigger: {
            trigger: '.hero-section',
            start: 'top top',
            end: 'bottom top',
            scrub: 1
        },
        scale: 1.3,
        ease: 'none'
    });
    
    // Background elements parallax
    gsap.to('.hero-background', {
        scrollTrigger: {
            trigger: '.hero-section',
            start: 'top top',
            end: 'bottom top',
            scrub: 1
        },
        y: -100,
        ease: 'none'
    });
}

/* ===== UTILITY FUNCTIONS ===== */

function createFloatingAnimation(element) {
    if (typeof gsap === 'undefined') return;
    
    gsap.to(element, {
        y: -10,
        duration: 2,
        ease: 'power2.inOut',
        yoyo: true,
        repeat: -1
    });
}

function createPulseAnimation(element) {
    if (typeof gsap === 'undefined') return;
    
    gsap.to(element, {
        scale: 1.1,
        duration: 1,
        ease: 'power2.inOut',
        yoyo: true,
        repeat: -1
    });
}

function createGlowAnimation(element) {
    if (typeof gsap === 'undefined') return;
    
    gsap.to(element, {
        boxShadow: '0 0 20px rgba(59, 130, 246, 0.5)',
        duration: 1.5,
        ease: 'power2.inOut',
        yoyo: true,
        repeat: -1
    });
}

/* ===== PERFORMANCE OPTIMIZATION ===== */

function pauseAllAnimations() {
    if (typeof gsap !== 'undefined') {
        gsap.globalTimeline.pause();
    }
}

function resumeAllAnimations() {
    if (typeof gsap !== 'undefined') {
        gsap.globalTimeline.resume();
    }
}

function refreshAnimations() {
    if (typeof ScrollTrigger !== 'undefined') {
        ScrollTrigger.refresh();
    }
}

/* ===== EVENT LISTENERS ===== */

// Handle window resize
window.addEventListener('resize', () => {
    refreshAnimations();
});

// Handle orientation change
window.addEventListener('orientationchange', () => {
    setTimeout(() => {
        refreshAnimations();
    }, 500);
});

// Handle visibility change (pause animations when tab is not visible)
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        pauseAllAnimations();
    } else {
        resumeAllAnimations();
    }
});

// Handle reduced motion preference
if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    // Disable all animations for users who prefer reduced motion
    const style = document.createElement('style');
    style.textContent = `
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    `;
    document.head.appendChild(style);
}

/* ===== EXPORT FUNCTIONS ===== */

// Export utility functions for global use
window.createFloatingAnimation = createFloatingAnimation;
window.createPulseAnimation = createPulseAnimation;
window.createGlowAnimation = createGlowAnimation;
window.pauseAllAnimations = pauseAllAnimations;
window.resumeAllAnimations = resumeAllAnimations;
window.refreshAnimations = refreshAnimations;
