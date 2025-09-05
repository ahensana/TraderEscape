/* ===== CHARTS.JS - THE TRADER'S ESCAPE ===== */

// Global chart variables
let equityChart = null;
let progressChart = null;

// Check if Chart.js is available
function isChartJSLoaded() {
    return typeof Chart !== 'undefined';
}

// Initialize charts when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing charts...');
    
    // Wait for Chart.js to load
    setTimeout(() => {
        if (isChartJSLoaded()) {
            initCharts();
        } else {
            console.log('Chart.js not loaded yet, waiting...');
            // Try again after a longer delay
            setTimeout(() => {
                if (isChartJSLoaded()) {
                    initCharts();
                } else {
                    console.error('Chart.js failed to load');
                }
            }, 2000);
        }
    }, 1000);
});

// Fallback initialization when window loads
window.addEventListener('load', function() {
    console.log('Window loaded - checking charts...');
    
    setTimeout(() => {
        if (isChartJSLoaded() && (!equityChart || !progressChart)) {
            console.log('Re-initializing charts on window load...');
            initCharts();
        }
    }, 1500);
});

function initCharts() {
    console.log('Initializing charts...');
    
    try {
        // Initialize equity chart
        const equityCtx = document.getElementById('equityChart');
        if (equityCtx && !equityChart) {
            console.log('Creating equity chart...');
            initEquityChart(equityCtx);
        }
        
        // Initialize progress chart
        const progressCtx = document.getElementById('progressChart');
        if (progressCtx && !progressChart) {
            console.log('Creating progress chart...');
            initProgressChart(progressCtx);
        }
        
        console.log('Charts initialization completed');
    } catch (error) {
        console.error('Error initializing charts:', error);
    }
}

function initEquityChart(ctx) {
    try {
        // Sample data for equity curve
        const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const data = [100000, 105000, 98000, 112000, 108000, 115000, 120000, 118000, 125000, 130000, 128000, 135000];
        
        // Create gradient
        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.05)');
        
        equityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Portfolio Value (Demo)',
                    data: data,
                    borderColor: '#3b82f6',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: 'rgba(255, 255, 255, 0.8)',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 12,
                                weight: '500'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: 'rgba(255, 255, 255, 0.8)',
                        borderColor: 'rgba(59, 130, 246, 0.3)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.6)',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 11
                            }
                        }
                    },
                    y: {
                        display: true,
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.6)',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 11
                            },
                            callback: function(value) {
                                return '₹' + (value / 1000) + 'K';
                            }
                        }
                    }
                },
                animation: {
                    duration: 1000
                }
            }
        });
        
        console.log('Equity chart created successfully');
        
    } catch (error) {
        console.error('Error creating equity chart:', error);
    }
}

function initProgressChart(ctx) {
    try {
        console.log('Creating progress chart...');
        
        // Sample data for study progress
        const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const data = [20, 35, 45, 60, 70, 75, 80, 85, 90, 92, 95, 98];
        
        // Destroy existing chart if it exists
        if (progressChart) {
            progressChart.destroy();
        }
        
        progressChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Study Progress (%)',
                    data: data,
                    backgroundColor: 'rgba(139, 92, 246, 0.8)',
                    borderColor: '#8b5cf6',
                    borderWidth: 2,
                    borderRadius: 4,
                    borderSkipped: false,
                    hoverBackgroundColor: 'rgba(139, 92, 246, 1)',
                    hoverBorderColor: '#a78bfa',
                    hoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: 'rgba(255, 255, 255, 0.8)',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 12,
                                weight: '500'
                            },
                            usePointStyle: true,
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        titleColor: '#ffffff',
                        bodyColor: 'rgba(255, 255, 255, 0.8)',
                        borderColor: 'rgba(139, 92, 246, 0.3)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false,
                        titleFont: {
                            family: 'Inter, sans-serif',
                            size: 14,
                            weight: '600'
                        },
                        bodyFont: {
                            family: 'Inter, sans-serif',
                            size: 12
                        },
                        callbacks: {
                            label: function(context) {
                                return `Progress: ${context.parsed.y}%`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.6)',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 11
                            }
                        }
                    },
                    y: {
                        display: true,
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.6)',
                            font: {
                                family: 'Inter, sans-serif',
                                size: 11
                            },
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart'
                }
            }
        });
        
        console.log('Progress chart created successfully');
    } catch (error) {
        console.error('Error creating progress chart:', error);
    }
}

// Update chart data (for future use)
function updateEquityChart(newData) {
    if (equityChart) {
        equityChart.data.datasets[0].data = newData;
        equityChart.update('active');
    }
}

function updateProgressChart(newData) {
    if (progressChart) {
        progressChart.data.datasets[0].data = newData;
        progressChart.update('active');
    }
}

// Handle window resize
window.addEventListener('resize', function() {
    if (equityChart) {
        equityChart.resize();
    }
    if (progressChart) {
        progressChart.resize();
    }
});

// Export functions for global use
window.updateEquityChart = updateEquityChart;
window.updateProgressChart = updateProgressChart;
window.initCharts = initCharts;

// Manual initialization function for debugging
window.manualInitCharts = function() {
    console.log('Manual chart initialization triggered');
    console.log('Chart.js available:', isChartJSLoaded());
    console.log('Equity canvas:', document.getElementById('equityChart'));
    console.log('Progress canvas:', document.getElementById('progressChart'));
    initCharts();
};

// Manual equity chart initialization
window.manualInitEquityChart = function() {
    console.log('Manual equity chart initialization triggered');
    const equityCtx = document.getElementById('equityChart');
    if (equityCtx && isChartJSLoaded()) {
        initEquityChart(equityCtx);
    } else {
        console.error('Cannot initialize equity chart - missing canvas or Chart.js');
    }
};

// Create a simple fallback equity chart
window.createSimpleEquityChart = function() {
    console.log('Creating simple fallback equity chart...');
    const equityCtx = document.getElementById('equityChart');
    
    if (!equityCtx || !isChartJSLoaded()) {
        console.error('Cannot create fallback chart - missing requirements');
        return;
    }
    
    try {
        // Hide the overlay temporarily
        const overlay = equityCtx.parentElement.querySelector('.chart-overlay');
        if (overlay) {
            overlay.style.display = 'none';
            console.log('Hidden chart overlay');
        }
        
        // Destroy existing chart
        if (equityChart) {
            equityChart.destroy();
            equityChart = null;
        }
        
        // Simple data
        const data = [100, 105, 98, 112, 108, 115, 120, 118, 125, 130, 128, 135];
        const labels = ['J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D'];
        
        equityChart = new Chart(equityCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Portfolio',
                    data: data,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.6)'
                        }
                    },
                    y: {
                        display: true,
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.6)'
                        }
                    }
                }
            }
        });
        
        console.log('Simple equity chart created successfully');
    } catch (error) {
        console.error('Error creating simple equity chart:', error);
    }
};

// Debug function to check chart container
window.debugEquityChart = function() {
    const equityCtx = document.getElementById('equityChart');
    const container = equityCtx ? equityCtx.parentElement : null;
    const overlay = container ? container.querySelector('.chart-overlay') : null;
    
    console.log('=== Equity Chart Debug ===');
    console.log('Canvas element:', equityCtx);
    console.log('Container element:', container);
    console.log('Overlay element:', overlay);
    console.log('Chart.js loaded:', isChartJSLoaded());
    console.log('Current equity chart instance:', equityChart);
    
    if (equityCtx) {
        console.log('Canvas dimensions:', equityCtx.width, 'x', equityCtx.height);
        console.log('Canvas style:', equityCtx.style.cssText);
        console.log('Container dimensions:', container.offsetWidth, 'x', container.offsetHeight);
    }
    
    if (overlay) {
        console.log('Overlay style:', overlay.style.cssText);
    }
};

// Fallback initialization - try to initialize charts every 2 seconds if they haven't been created
let initAttempts = 0;
const maxAttempts = 10;

function attemptChartInitialization() {
    if (initAttempts >= maxAttempts) {
        console.warn('Max chart initialization attempts reached');
        return;
    }
    
    if (!isChartJSLoaded()) {
        console.log(`Chart.js not loaded, attempt ${initAttempts + 1}/${maxAttempts}`);
        initAttempts++;
        setTimeout(attemptChartInitialization, 2000);
        return;
    }
    
    const equityCtx = document.getElementById('equityChart');
    const progressCtx = document.getElementById('progressChart');
    
    if ((equityCtx && !equityChart) || (progressCtx && !progressChart)) {
        console.log('Attempting to initialize charts...');
        initCharts();
    }
}

// Start fallback initialization after 3 seconds
setTimeout(attemptChartInitialization, 3000);
