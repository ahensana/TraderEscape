/* ===== PREMIUM ANIMATIONS SYSTEM - THE TRADER'S ESCAPE ===== */

// Initialize GSAP ScrollTrigger
gsap.registerPlugin(ScrollTrigger);

// Global animation variables
let tl, particlesInstance, threeScene, threeRenderer, threeCamera;
let typingAnimation, floatingShapes;

// Initialize animations when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing premium animations...');
    
    // Wait for all libraries to load
    setTimeout(() => {
        initializeAnimations();
    }, 100);
});

function initializeAnimations() {
    // Check if GSAP is available
    if (typeof gsap === 'undefined') {
        console.log('GSAP not available, skipping advanced animations');
        return;
    }
    
    // Initialize GSAP ScrollTrigger
    if (typeof ScrollTrigger !== 'undefined') {
        gsap.registerPlugin(ScrollTrigger);
    }
    
    // Initialize
    //  custom cursor
    initCustomCursor();
    
    // Initialize particles
    initParticles();
    
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
        if (typeof initCharts === 'function') {
            initCharts();
        }
    }, 2000);
};

// Fallback initialization when window loads
window.addEventListener('load', function() {
    console.log('Window loaded - animations fallback...');
    
    // Re-initialize animations if GSAP is available
    setTimeout(() => {
        if (typeof gsap !== 'undefined' && typeof ScrollTrigger !== 'undefined') {
            console.log('Re-initializing animations...');
            initializeAnimations();
        }
    }, 1000);
});

/* ===== CUSTOM CURSOR ===== */

function initCustomCursor() {
    const cursor = document.getElementById('custom-cursor');
    
    if (cursor && typeof gsap !== 'undefined') {
        let mouseX = 0, mouseY = 0;
        let cursorX = 0, cursorY = 0;
        
        document.addEventListener('mousemove', (e) => {
            mouseX = e.clientX;
            mouseY = e.clientY;
        });
        
        // Animate cursor with GSAP
        gsap.to({}, {
            duration: 0.1,
            repeat: -1,
            onUpdate: () => {
                cursorX += (mouseX - cursorX) * 0.8;
                cursorY += (mouseY - cursorY) * 0.8;
                
                cursor.style.left = cursorX + 'px';
                cursor.style.top = cursorY + 'px';
            }
        });
        
        // Add hover effects (excluding chart containers)
        const hoverElements = document.querySelectorAll('a, button, .nav-link, .highlight-card, .tool-card');
        hoverElements.forEach(element => {
            // Skip chart containers to keep cursor normal size
            if (element.querySelector('.chart-container')) {
                return;
            }
            
            element.addEventListener('mouseenter', () => {
                cursor.classList.add('hover');
            });
            element.addEventListener('mouseleave', () => {
                cursor.classList.remove('hover');
            });
        });
    } else if (cursor) {
        // Fallback cursor animation without GSAP
        document.addEventListener('mousemove', (e) => {
            cursor.style.left = e.clientX + 'px';
            cursor.style.top = e.clientY + 'px';
        });
        
        // Add hover effects (excluding chart containers)
        const hoverElements = document.querySelectorAll('a, button, .nav-link, .highlight-card, .tool-card');
        hoverElements.forEach(element => {
            // Skip chart containers to keep cursor normal size
            if (element.querySelector('.chart-container')) {
                return;
            }
            
            element.addEventListener('mouseenter', () => {
                cursor.classList.add('hover');
            });
            element.addEventListener('mouseleave', () => {
                cursor.classList.remove('hover');
            });
        });
    }
}

/* ===== PARTICLES SYSTEM ===== */

function initParticles() {
    if (typeof particlesJS !== 'undefined') {
        particlesJS('particles-js', {
            particles: {
                number: {
                    value: 80,
                    density: {
                        enable: true,
                        value_area: 800
                    }
                },
                color: {
                    value: '#3b82f6'
                },
                shape: {
                    type: 'circle',
                    stroke: {
                        width: 0,
                        color: '#000000'
                    }
                },
                opacity: {
                    value: 0.5,
                    random: false,
                    anim: {
                        enable: false,
                        speed: 1,
                        opacity_min: 0.1,
                        sync: false
                    }
                },
                size: {
                    value: 3,
                    random: true,
                    anim: {
                        enable: false,
                        speed: 40,
                        size_min: 0.1,
                        sync: false
                    }
                },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: '#3b82f6',
                    opacity: 0.4,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 6,
                    direction: 'none',
                    random: false,
                    straight: false,
                    out_mode: 'out',
                    bounce: false,
                    attract: {
                        enable: false,
                        rotateX: 600,
                        rotateY: 1200
                    }
                }
            },
            interactivity: {
                detect_on: 'canvas',
                events: {
                    onhover: {
                        enable: true,
                        mode: 'repulse'
                    },
                    onclick: {
                        enable: true,
                        mode: 'push'
                    },
                    resize: true
                },
                modes: {
                    grab: {
                        distance: 400,
                        line_linked: {
                            opacity: 1
                        }
                    },
                    bubble: {
                        distance: 400,
                        size: 40,
                        duration: 2,
                        opacity: 8,
                        speed: 3
                    },
                    repulse: {
                        distance: 200,
                        duration: 0.4
                    },
                    push: {
                        particles_nb: 4
                    },
                    remove: {
                        particles_nb: 2
                    }
                }
            },
            retina_detect: true
        });
    }
}

/* ===== THREE.JS BACKGROUND ===== */

function initThreeJS() {
    if (typeof THREE === 'undefined') return;
    
    const canvas = document.getElementById('hero-canvas');
    if (!canvas) return;
    
    // Scene setup
    threeScene = new THREE.Scene();
    
    // Camera setup
    threeCamera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    threeCamera.position.z = 5;
    
    // Renderer setup
    threeRenderer = new THREE.WebGLRenderer({ 
        canvas: canvas, 
        alpha: true,
        antialias: true 
    });
    threeRenderer.setSize(window.innerWidth, window.innerHeight);
    threeRenderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    
    // Create animated background
    createAnimatedBackground();
    
    // Animation loop
    animateThreeJS();
    
    // Handle resize
    window.addEventListener('resize', onThreeJSResize);
}

function createAnimatedBackground() {
    // Create floating geometry
    const geometry = new THREE.IcosahedronGeometry(1, 1);
    const material = new THREE.MeshBasicMaterial({
        color: 0x3b82f6,
        wireframe: true,
        transparent: true,
        opacity: 0.1
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
    threeScene.children.forEach(child => {
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
    
    // Initialize live social proof
    initLiveSocialProof();
}

function initTypingAnimation() {
    const heroTitle = document.querySelector('.hero-title .highlight');
    if (!heroTitle) return;
    
    const text = heroTitle.textContent;
    heroTitle.textContent = '';
    
    let i = 0;
    typingAnimation = setInterval(() => {
        if (i < text.length) {
            heroTitle.textContent += text.charAt(i);
            i++;
        } else {
            clearInterval(typingAnimation);
            // Add cursor blink effect
            heroTitle.classList.add('typing-complete');
        }
    }, 100);
}



function initFloatingShapes() {
    const heroSection = document.querySelector('.hero-section');
    if (!heroSection) return;
    
    // Create floating shapes
    for (let i = 0; i < 15; i++) {
        const shape = document.createElement('div');
        shape.className = 'floating-shape';
        shape.style.cssText = `
            position: absolute;
            width: ${Math.random() * 60 + 20}px;
            height: ${Math.random() * 60 + 20}px;
            background: linear-gradient(45deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: ${Math.random() > 0.5 ? '50%' : '8px'};
            left: ${Math.random() * 100}%;
            top: ${Math.random() * 100}%;
            pointer-events: none;
            z-index: 1;
        `;
        
        heroSection.appendChild(shape);
        
        // Animate shape
        gsap.to(shape, {
            duration: 10 + Math.random() * 10,
            x: (Math.random() - 0.5) * 200,
            y: (Math.random() - 0.5) * 200,
            rotation: 360,
            ease: 'none',
            repeat: -1,
            yoyo: true
        });
    }
}

function initMagneticHover() {
    const magneticElements = document.querySelectorAll('.highlight-card, .btn');
    
    magneticElements.forEach(element => {
        element.addEventListener('mousemove', (e) => {
            const rect = element.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            
            gsap.to(element, {
                duration: 0.3,
                x: x * 0.1,
                y: y * 0.1,
                ease: 'power2.out'
            });
        });
        
        element.addEventListener('mouseleave', () => {
            gsap.to(element, {
                duration: 0.3,
                x: 0,
                y: 0,
                ease: 'power2.out'
            });
        });
    });
}

function initGlassmorphism() {
    const glassElements = document.querySelectorAll('.highlight-card, .tool-card, .testimonial-card');
    
    glassElements.forEach(element => {
        element.classList.add('glassmorphism');
    });
}

function initGradientText() {
    const gradientElements = document.querySelectorAll('.section-title, .hero-title .highlight');
    
    gradientElements.forEach(element => {
        element.classList.add('gradient-text');
    });
}

function init3DCardEffects() {
    const cards = document.querySelectorAll('.highlight-card, .testimonial-card');
    
    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateX = (y - centerY) / 10;
            const rotateY = (centerX - x) / 10;
            
            gsap.to(card, {
                duration: 0.3,
                rotateX: rotateX,
                rotateY: rotateY,
                transformPerspective: 1000,
                ease: 'power2.out'
            });
        });
        
        card.addEventListener('mouseleave', () => {
            gsap.to(card, {
                duration: 0.3,
                rotateX: 0,
                rotateY: 0,
                ease: 'power2.out'
            });
        });
    });
}

function initLiveSocialProof() {
    const socialProofContainer = document.createElement('div');
    socialProofContainer.className = 'social-proof';
    socialProofContainer.innerHTML = `
        <div class="proof-item">
            <span class="proof-icon">👤</span>
            <span class="proof-text">Alex just completed Module 1</span>
        </div>
    `;
    
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        heroSection.appendChild(socialProofContainer);
        
        // Animate social proof
        gsap.fromTo(socialProofContainer, 
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
                            }
                        });
                    }, 3000);
                }
            }
        );
    }
}

/* ===== GSAP ANIMATIONS ===== */

function initGSAPAnimations() {
    // Hero section animations
    const heroTimeline = gsap.timeline();
    
    heroTimeline
        .from('.hero-badge', {
            duration: 1,
            opacity: 0,
            y: 30,
            ease: 'power3.out'
        })
        .from('.hero-title .title-line', {
            duration: 1.2,
            opacity: 0,
            y: 50,
            stagger: 0.2,
            ease: 'power3.out'
        }, '-=0.5')
        .from('.hero-subtitle', {
            duration: 1,
            opacity: 0,
            y: 30,
            ease: 'power3.out'
        }, '-=0.8')
        .from('.hero-description', {
            duration: 1,
            opacity: 0,
            y: 30,
            ease: 'power3.out'
        }, '-=0.6')
        .from('.hero-buttons', {
            duration: 1,
            opacity: 0,
            y: 30,
            ease: 'power3.out'
        }, '-=0.4')
        .from('.hero-stats', {
            duration: 1,
            opacity: 0,
            y: 30,
            ease: 'power3.out'
        }, '-=0.6')
        .from('.hero-trading-icons', {
            duration: 1,
            opacity: 0,
            scale: 0.5,
            ease: 'back.out(1.7)'
        }, '-=0.8');
}

function startPageAnimations() {
    // Animate sections on scroll
    gsap.utils.toArray('.highlight-card, .tool-card, .testimonial-card').forEach(card => {
        gsap.from(card, {
            scrollTrigger: {
                trigger: card,
                start: 'top 80%',
                end: 'bottom 20%',
                toggleActions: 'play none none reverse'
            },
            duration: 1,
            opacity: 0,
            y: 50,
            scale: 0.9,
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
            duration: 1,
            opacity: 0,
            x: -100,
            delay: index * 0.2,
            ease: 'power3.out'
        });
    });
}

/* ===== SCROLL ANIMATIONS ===== */

function initScrollAnimations() {
    // Parallax effects
    gsap.utils.toArray('.floating-card').forEach(card => {
        const speed = parseFloat(card.dataset.speed) || 0.5;
        
        gsap.to(card, {
            scrollTrigger: {
                trigger: card,
                start: 'top bottom',
                end: 'bottom top',
                scrub: true
            },
            y: -100 * speed,
            ease: 'none'
        });
    });
    
    // Navbar scroll effect
    gsap.to('.navbar', {
        scrollTrigger: {
            trigger: 'body',
            start: 'top top',
            end: 'bottom bottom',
            scrub: true
        },
        backgroundColor: 'rgba(15, 23, 42, 0.98)',
        boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1)'
    });
}

/* ===== FLOATING ELEMENTS ===== */

function initFloatingElements() {
    // Animate floating cards
    gsap.utils.toArray('.floating-card').forEach(card => {
        gsap.to(card, {
            duration: 3,
            y: -20,
            ease: 'power2.inOut',
            yoyo: true,
            repeat: -1
        });
    });
    
    // Animate bull/bear icons
    const bullIcon = document.querySelector('.bull-icon');
    const bearIcon = document.querySelector('.bear-icon');
    
    if (bullIcon) {
        gsap.to(bullIcon, {
            duration: 2,
            y: -10,
            ease: 'power2.inOut',
            yoyo: true,
            repeat: -1
        });
    }
    
    if (bearIcon) {
        gsap.to(bearIcon, {
            duration: 2.5,
            y: -8,
            ease: 'power2.inOut',
            yoyo: true,
            repeat: -1,
            delay: 0.5
        });
    }
}

/* ===== COUNTER ANIMATIONS ===== */

function initCounterAnimations() {
    const counters = document.querySelectorAll('.stat-number');
    
    counters.forEach(counter => {
        const target = parseInt(counter.dataset.target);
        const suffix = counter.dataset.suffix || '';
        
        gsap.to(counter, {
            scrollTrigger: {
                trigger: counter,
                start: 'top 80%',
                toggleActions: 'play none none reverse'
            },
            duration: 2,
            innerHTML: target,
            ease: 'power2.out',
            snap: { innerHTML: 1 },
            onUpdate: function() {
                counter.textContent = Math.floor(counter.innerHTML) + suffix;
            }
        });
    });
}

/* ===== BUTTON EFFECTS ===== */

function initButtonEffects() {
    const buttons = document.querySelectorAll('.btn');
    
    buttons.forEach(button => {
        button.addEventListener('mouseenter', () => {
            gsap.to(button, {
                duration: 0.3,
                scale: 1.05,
                ease: 'power2.out'
            });
        });
        
        button.addEventListener('mouseleave', () => {
            gsap.to(button, {
                duration: 0.3,
                scale: 1,
                ease: 'power2.out'
            });
        });
        
        button.addEventListener('click', () => {
            gsap.to(button, {
                duration: 0.1,
                scale: 0.95,
                ease: 'power2.out',
                yoyo: true,
                repeat: 1
            });
        });
    });
}

/* ===== UTILITY FUNCTIONS ===== */

// Smooth scroll to section
function smoothScrollTo(target) {
    const element = document.querySelector(target);
    if (element) {
        gsap.to(window, {
            duration: 1.5,
            scrollTo: {
                y: element,
                offsetY: 100
            },
            ease: 'power3.inOut'
        });
    }
}

// Add ripple effect to buttons
function addRippleEffect(event) {
    const button = event.currentTarget;
    const ripple = document.createElement('span');
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
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Export functions for global use
window.smoothScrollTo = smoothScrollTo;
window.addRippleEffect = addRippleEffect;

// Scroll to top function
function scrollToTop() {
    gsap.to(window, {
        duration: 1.5,
        scrollTo: { y: 0 },
        ease: 'power3.inOut'
    });
}

window.scrollToTop = scrollToTop;
