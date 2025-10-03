// Trading-themed Background for The Trader's Escape
// Check if already loaded to prevent redeclaration
if (typeof window.TradingBackground !== "undefined") {
  console.log("TradingBackground already loaded, skipping...");
  // Exit early by not executing the rest of the script
} else {
  class TradingBackground {
    constructor() {
      this.particles = [];
      this.tradingElements = [];
      this.animationId = null;
      this.init();
    }

    init() {
      this.createBackground();
      this.createParticles();
      this.createTradingElements();
      this.animate();
      this.addScrollEffect();
    }

    createBackground() {
      // Create background container
      const backgroundContainer = document.createElement("div");
      backgroundContainer.className = "trading-background";
      backgroundContainer.innerHTML = `
            <div class="bg-grid"></div>
            <div class="bg-particles"></div>
            <div class="bg-trading-elements"></div>
            <div class="bg-glow"></div>
        `;

      // Insert at the beginning of body for full page coverage
      const body = document.body;
      if (body) {
        body.insertBefore(backgroundContainer, body.firstChild);
        // Set body to relative positioning to contain the absolute background
        body.style.position = "relative";

        // Ensure background covers full page height
        this.updateBackgroundHeight();

        // Listen for window resize and scroll to update height
        window.addEventListener("resize", () => this.updateBackgroundHeight());
        window.addEventListener("scroll", () => this.updateBackgroundHeight());
      }
    }

    createParticles() {
      const particleContainer = document.querySelector(".bg-particles");
      if (!particleContainer) {
        return;
      }

      const particleCount = window.innerWidth <= 768 ? 120 : 250;

      for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement("div");
        particle.className = "trading-particle";

        // Random properties
        const size = Math.random() * 4 + 2;
        const x = Math.random() * 100;
        const y = Math.random() * 100;
        const duration = Math.random() * 20 + 10;
        const delay = Math.random() * 10;

        particle.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}%;
                top: ${y}%;
                background: ${this.getRandomColor()};
                border-radius: 50%;
                opacity: ${Math.random() * 0.6 + 0.2};
                animation: float-particle ${duration}s ease-in-out infinite;
                animation-delay: ${delay}s;
                z-index: 1;
            `;

        particleContainer.appendChild(particle);
        this.particles.push(particle);
      }
    }

    createTradingElements() {
      const elementContainer = document.querySelector(".bg-trading-elements");
      if (!elementContainer) return;

      // Create candlestick patterns
      this.createCandlesticks(elementContainer);

      // Create trend lines
      this.createTrendLines(elementContainer);

      // Create chart elements
      this.createChartElements(elementContainer);

      // Create support/resistance lines
      this.createSupportResistance(elementContainer);

      // Create volume bars
      this.createVolumeBars(elementContainer);

      // Create Fibonacci levels
      this.createFibonacciLevels(elementContainer);
    }

    createCandlesticks(container) {
      const candlestickCount = window.innerWidth <= 768 ? 40 : 70;

      for (let i = 0; i < candlestickCount; i++) {
        const candlestick = document.createElement("div");
        candlestick.className = "trading-candlestick";

        const x = Math.random() * 100;
        const y = Math.random() * 100;
        const height = Math.random() * 40 + 20;
        const isGreen = Math.random() > 0.5;
        const duration = Math.random() * 15 + 10;
        const delay = Math.random() * 10;

        candlestick.style.cssText = `
                position: absolute;
                left: ${x}%;
                top: ${y}%;
                width: 3px;
                height: ${height}px;
                background: ${isGreen ? "#10b981" : "#ef4444"};
                border-radius: 1px;
                opacity: 0.3;
                animation: float-candlestick ${duration}s ease-in-out infinite;
                animation-delay: ${delay}s;
                z-index: 1;
            `;

        container.appendChild(candlestick);
        this.tradingElements.push(candlestick);
      }
    }

    createTrendLines(container) {
      const lineCount = window.innerWidth <= 768 ? 25 : 50;

      for (let i = 0; i < lineCount; i++) {
        const line = document.createElement("div");
        line.className = "trading-trend-line";

        const x = Math.random() * 100;
        const y = Math.random() * 100;
        const length = Math.random() * 60 + 40;
        const angle = Math.random() * 360;
        const duration = Math.random() * 20 + 15;
        const delay = Math.random() * 10;

        line.style.cssText = `
                position: absolute;
                left: ${x}%;
                top: ${y}%;
                width: ${length}px;
                height: 1px;
                background: linear-gradient(90deg, transparent, #3b82f6, transparent);
                opacity: 0.2;
                transform: rotate(${angle}deg);
                animation: float-trend-line ${duration}s ease-in-out infinite;
                animation-delay: ${delay}s;
                z-index: 1;
            `;

        container.appendChild(line);
        this.tradingElements.push(line);
      }
    }

    createChartElements(container) {
      // Create moving average lines
      const maCount = window.innerWidth <= 768 ? 20 : 35;

      for (let i = 0; i < maCount; i++) {
        const maLine = document.createElement("div");
        maLine.className = "trading-ma-line";

        const x = Math.random() * 100;
        const y = Math.random() * 100;
        const length = Math.random() * 80 + 60;
        const angle = Math.random() * 30 - 15; // Slight angle variation
        const duration = Math.random() * 25 + 20;
        const delay = Math.random() * 10;

        maLine.style.cssText = `
                position: absolute;
                left: ${x}%;
                top: ${y}%;
                width: ${length}px;
                height: 2px;
                background: linear-gradient(90deg, transparent, #8b5cf6, transparent);
                opacity: 0.15;
                transform: rotate(${angle}deg);
                animation: float-ma-line ${duration}s ease-in-out infinite;
                animation-delay: ${delay}s;
                z-index: 1;
            `;

        container.appendChild(maLine);
        this.tradingElements.push(maLine);
      }
    }

    createSupportResistance(container) {
      const lineCount = window.innerWidth <= 768 ? 15 : 30;

      for (let i = 0; i < lineCount; i++) {
        const line = document.createElement("div");
        line.className = "trading-support-resistance";

        const x = Math.random() * 100;
        const y = Math.random() * 100;
        const length = Math.random() * 80 + 60;
        const angle = Math.random() * 20 - 10; // Horizontal lines
        const duration = Math.random() * 18 + 12;
        const delay = Math.random() * 10;
        const isSupport = Math.random() > 0.5;

        line.style.cssText = `
                position: absolute;
                left: ${x}%;
                top: ${y}%;
                width: ${length}px;
                height: 1px;
                background: linear-gradient(90deg, transparent, ${
                  isSupport ? "#10b981" : "#ef4444"
                }, transparent);
                opacity: 0.25;
                transform: rotate(${angle}deg);
                animation: float-support-resistance ${duration}s ease-in-out infinite;
                animation-delay: ${delay}s;
                z-index: 1;
            `;

        container.appendChild(line);
        this.tradingElements.push(line);
      }
    }

    createVolumeBars(container) {
      const barCount = window.innerWidth <= 768 ? 20 : 40;

      for (let i = 0; i < barCount; i++) {
        const bar = document.createElement("div");
        bar.className = "trading-volume-bar";

        const x = Math.random() * 100;
        const y = Math.random() * 100;
        const width = Math.random() * 4 + 2;
        const height = Math.random() * 30 + 10;
        const duration = Math.random() * 16 + 10;
        const delay = Math.random() * 10;

        bar.style.cssText = `
                position: absolute;
                left: ${x}%;
                top: ${y}%;
                width: ${width}px;
                height: ${height}px;
                background: linear-gradient(to top, #60a5fa, #3b82f6);
                opacity: 0.2;
                animation: float-volume-bar ${duration}s ease-in-out infinite;
                animation-delay: ${delay}s;
                z-index: 1;
            `;

        container.appendChild(bar);
        this.tradingElements.push(bar);
      }
    }

    createFibonacciLevels(container) {
      const levelCount = window.innerWidth <= 768 ? 10 : 20;

      for (let i = 0; i < levelCount; i++) {
        const level = document.createElement("div");
        level.className = "trading-fibonacci";

        const x = Math.random() * 100;
        const y = Math.random() * 100;
        const length = Math.random() * 70 + 50;
        const angle = Math.random() * 45 - 22.5;
        const duration = Math.random() * 22 + 15;
        const delay = Math.random() * 10;

        level.style.cssText = `
                position: absolute;
                left: ${x}%;
                top: ${y}%;
                width: ${length}px;
                height: 1px;
                background: linear-gradient(90deg, transparent, #f59e0b, transparent);
                opacity: 0.18;
                transform: rotate(${angle}deg);
                animation: float-fibonacci ${duration}s ease-in-out infinite;
                animation-delay: ${delay}s;
                z-index: 1;
            `;

        container.appendChild(level);
        this.tradingElements.push(level);
      }
    }

    getRandomColor() {
      const colors = [
        "#3b82f6", // Primary blue
        "#8b5cf6", // Purple
        "#10b981", // Green
        "#60a5fa", // Light blue
        "#f59e0b", // Amber
        "#ef4444", // Red
      ];
      return colors[Math.floor(Math.random() * colors.length)];
    }

    animate() {
      // Add subtle movement to particles
      this.particles.forEach((particle, index) => {
        const time = Date.now() * 0.001 + index;
        const x = Math.sin(time * 0.5) * 2;
        const y = Math.cos(time * 0.3) * 2;

        particle.style.transform = `translate(${x}px, ${y}px)`;
      });

      this.animationId = requestAnimationFrame(() => this.animate());
    }

    addScrollEffect() {
      let ticking = false;

      window.addEventListener("scroll", () => {
        if (!ticking) {
          requestAnimationFrame(() => {
            this.updateScrollEffect();
            ticking = false;
          });
          ticking = true;
        }
      });
    }

    updateScrollEffect() {
      const scrolled = window.pageYOffset;
      const scrollSpeed = Math.abs(scrolled - (this.lastScroll || 0));
      this.lastScroll = scrolled;

      // Get background elements for subtle effects only (no parallax movement)
      const particles = document.querySelectorAll(".trading-particle");
      const candlesticks = document.querySelectorAll(".trading-candlestick");
      const trendLines = document.querySelectorAll(".trading-trend-line");
      const maLines = document.querySelectorAll(".trading-ma-line");
      const supportResistance = document.querySelectorAll(
        ".trading-support-resistance"
      );
      const volumeBars = document.querySelectorAll(".trading-volume-bar");
      const fibonacci = document.querySelectorAll(".trading-fibonacci");

      // Subtle effects without parallax movement
      particles.forEach((particle, index) => {
        const rotation = (scrolled * 0.05 + index) % 360;
        particle.style.transform = `rotate(${rotation}deg) translateZ(0)`;

        // Add pulse effect on fast scroll
        if (scrollSpeed > 10) {
          particle.style.filter = `brightness(${1 + scrollSpeed * 0.01})`;
        }
      });

      // Candlestick subtle scaling effect
      candlesticks.forEach((candlestick, index) => {
        const scale = 1 + Math.sin(scrolled * 0.005 + index) * 0.05;
        candlestick.style.transform = `scaleY(${scale}) translateZ(0)`;
      });

      // Trend lines with subtle wave effect
      trendLines.forEach((line, index) => {
        const wave = Math.sin(scrolled * 0.003 + index) * 2;
        line.style.transform = `translateY(${wave}px) translateZ(0)`;
      });

      // Moving averages with opacity pulse
      maLines.forEach((line, index) => {
        const opacity = 0.15 + Math.sin(scrolled * 0.004 + index) * 0.08;
        line.style.opacity = opacity;
      });

      // Support/Resistance lines with subtle color shift
      supportResistance.forEach((line, index) => {
        const hueShift = (scrolled * 0.05 + index * 15) % 360;
        line.style.filter = `hue-rotate(${hueShift}deg)`;
      });

      // Volume bars with subtle height animation
      volumeBars.forEach((bar, index) => {
        const heightScale = 1 + Math.sin(scrolled * 0.006 + index) * 0.15;
        bar.style.transform = `scaleY(${heightScale}) translateZ(0)`;
      });

      // Fibonacci levels with subtle glow effect
      fibonacci.forEach((level, index) => {
        const glow = Math.sin(scrolled * 0.003 + index) * 0.05;
        level.style.filter = `drop-shadow(0 0 ${
          3 + glow * 5
        }px rgba(245, 158, 11, 0.3))`;
      });
    }

    updateBackgroundHeight() {
      const background = document.querySelector(".trading-background");
      if (background) {
        // Get the full page height (including content below viewport)
        const pageHeight = Math.max(
          document.body.scrollHeight,
          document.body.offsetHeight,
          document.documentElement.clientHeight,
          document.documentElement.scrollHeight,
          document.documentElement.offsetHeight
        );

        // Set background height to cover full page
        background.style.height = `${pageHeight}px`;
        background.style.minHeight = `${pageHeight}px`;

        // Also update all background element containers
        const bgElements = background.querySelectorAll(
          ".bg-grid, .bg-particles, .bg-trading-elements, .bg-glow"
        );
        bgElements.forEach((element) => {
          element.style.height = `${pageHeight}px`;
          element.style.minHeight = `${pageHeight}px`;
        });
      }
    }

    destroy() {
      if (this.animationId) {
        cancelAnimationFrame(this.animationId);
      }

      // Remove background elements
      const background = document.querySelector(".trading-background");
      if (background) {
        background.remove();
      }
    }
  }

  // Initialize when DOM is loaded
  document.addEventListener("DOMContentLoaded", () => {
    window.tradingBackground = new TradingBackground();

    // Update background height on scroll to ensure full coverage
    let ticking = false;

    function updateBackgroundHeight() {
      const background = document.querySelector(".trading-background");
      if (background) {
        // Get the full page height (including content below viewport)
        const pageHeight = Math.max(
          document.body.scrollHeight,
          document.body.offsetHeight,
          document.documentElement.clientHeight,
          document.documentElement.scrollHeight,
          document.documentElement.offsetHeight
        );

        // Set background height to cover full page
        background.style.height = `${pageHeight}px`;
        background.style.minHeight = `${pageHeight}px`;
      }
      ticking = false;
    }

    function requestTick() {
      if (!ticking) {
        requestAnimationFrame(updateBackgroundHeight);
        ticking = true;
      }
    }

    // Update background height on scroll for full coverage
    window.addEventListener("scroll", requestTick, { passive: true });
  });

  // Set initialization flag
  window.TradingBackground = TradingBackground;
} // Close the else block
