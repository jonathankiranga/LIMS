/**
 * SMART ERP - Modern Dashboard JavaScript
 * Bootstrap 5 + Chart.js Integration
 * Animations, interactivity, and data visualization
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard
    initializeDashboard();
    
    // Setup Chart.js visualizations
    setupCharts();
    
    // Add smooth interactions
    setupInteractions();
    
    // Setup real-time updates
    setupRealtimeUpdates();
});

/**
 * Initialize Dashboard
 * Adds animations and event listeners
 */
function initializeDashboard() {
    const kpiCards = document.querySelectorAll('.kpi-card');
    const analyticsCards = document.querySelectorAll('.analytics-card');
    
    // Add stagger animation to KPI cards
    kpiCards.forEach((card, index) => {
        card.style.animationDelay = (index * 0.1) + 's';
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-12px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(-8px)';
        });
    });
    
    // Add stagger animation to analytics cards
    analyticsCards.forEach((card, index) => {
        card.style.animationDelay = (0.2 + index * 0.1) + 's';
    });
    
    // Add counter animation for KPI values
    animateCounters();
}

/**
 * Animate Counter Numbers
 * Creates a smooth counting effect for KPI values
 */
function animateCounters() {
    const values = document.querySelectorAll('.kpi-value');
    
    values.forEach(element => {
        const finalValue = parseInt(element.textContent);
        
        if (isNaN(finalValue)) return;
        
        let currentValue = 0;
        const increment = Math.ceil(finalValue / 30);
        
        const interval = setInterval(() => {
            currentValue += increment;
            
            if (currentValue >= finalValue) {
                element.textContent = finalValue;
                clearInterval(interval);
            } else {
                element.textContent = currentValue;
            }
        }, 20);
    });
}

/**
 * Setup Chart.js Visualizations
 * Initialize charts for data visualization
 */
function setupCharts() {
    // Chart example for pending approvals
    setupPendingApprovalsChart();
    
    // Chart example for system performance
    setupSystemMetricsChart();
    
    // Chart example for inventory
    setupInventoryChart();
}

/**
 * Pending Approvals Chart
 * Shows breakdown of pending documents
 */
function setupPendingApprovalsChart() {
    const chartContainer = document.getElementById('pendingApprovalsChart');
    
    // Only initialize if container exists (for future additions)
    if (chartContainer) {
        const ctx = chartContainer.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Purchase Orders', 'Store Requests', 'Vouchers (Finance)', 'CEO Vouchers', 'Price Lists', 'Lab Tests'],
                datasets: [{
                    data: [12, 8, 5, 3, 2, 1],
                    backgroundColor: [
                        '#667eea',
                        '#a855f7',
                        '#10b981',
                        '#f97316',
                        '#14b8a6',
                        '#ec4899'
                    ],
                    borderColor: 'white',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

/**
 * System Metrics Chart
 * Shows system performance
 */
function setupSystemMetricsChart() {
    const chartContainer = document.getElementById('systemMetricsChart');
    
    if (chartContainer) {
        const ctx = chartContainer.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'API Response Time (ms)',
                    data: [45, 38, 52, 41, 35, 48, 40],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: 'white',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

/**
 * Inventory Chart
 * Shows stock levels
 */
function setupInventoryChart() {
    const chartContainer = document.getElementById('inventoryChart');
    
    if (chartContainer) {
        const ctx = chartContainer.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['In Stock', 'Low Stock', 'Out of Stock', 'Overstock'],
                datasets: [{
                    label: 'Number of Items',
                    data: [245, 52, 18, 34],
                    backgroundColor: [
                        '#10b981',
                        '#f97316',
                        '#ef4444',
                        '#3b82f6'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

/**
 * Setup Interactions
 * Add smooth behaviors and interactions
 */
function setupInteractions() {
    // Smooth scroll for internal links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Add ripple effect to buttons
    addRippleEffect();
    
    // Add hover tooltips
    setupTooltips();
}

/**
 * Add Ripple Effect to Buttons
 * Creates a material design ripple effect
 */
function addRippleEffect() {
    const buttons = document.querySelectorAll('.btn, .kpi-card');
    
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });
}

/**
 * Setup Tooltips (Bootstrap 5)
 * Initialize Bootstrap tooltips for help text
 */
function setupTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Setup Real-time Updates
 * Simulate real-time data updates
 */
function setupRealtimeUpdates() {
    // Update KPI values periodically (example: every 30 seconds)
    setInterval(updateKPIValues, 30000);
    
    // Update time display
    updateTimeDisplay();
    setInterval(updateTimeDisplay, 1000);
}

/**
 * Update KPI Values
 * Fetch fresh data and animate updates
 */
function updateKPIValues() {
    // This would typically make an AJAX call to fetch fresh data
    // For now, just add a visual indicator
    const kpiCards = document.querySelectorAll('.kpi-card');
    
    kpiCards.forEach(card => {
        // Add a subtle pulse animation
        card.style.animation = 'none';
        setTimeout(() => {
            card.style.animation = 'fadeInUp 0.3s ease-out';
        }, 10);
    });
}

/**
 * Update Time Display
 * Keep the time display in header updated
 */
function updateTimeDisplay() {
    const timeDisplay = document.querySelector('.hero-section .text-white-50');
    
    if (timeDisplay) {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const dateStr = now.toLocaleDateString('en-US', options);
        
        timeDisplay.innerHTML = '<i class="fas fa-calendar"></i> ' + dateStr;
    }
}

/**
 * Add Loading State
 * Show loading overlay when navigating
 */
function showLoadingState() {
    document.body.classList.add('loading');
}

function hideLoadingState() {
    document.body.classList.remove('loading');
}

/**
 * Track Link Clicks
 * Show loading state when clicking navigation links
 */
document.addEventListener('click', function(e) {
    const link = e.target.closest('a[target="mainContentIFrame"]');
    
    if (link) {
        // Optional: show loading state
        // showLoadingState();
        
        // Hide after content loads
        setTimeout(hideLoadingState, 1500);
    }
});

/**
 * Utility: Format Number with Commas
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * Utility: Format Currency
 */
function formatCurrency(num, currency = 'USD') {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency
    }).format(num);
}

/**
 * Export functions for global use
 */
window.dashboardUtils = {
    formatNumber,
    formatCurrency,
    showLoadingState,
    hideLoadingState
};
