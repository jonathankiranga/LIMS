/**
 * Smart ERP - Page Loader JavaScript
 * Handles dynamic page loading without iframes
 */

// Global configuration
const pageLoaderConfig = {
    contentSelector: '#page-content',
    loadingHtml: '<div class="text-center py-1"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><p class="text-muted mt-1">Loading...</p></div>',
    errorHtml: '<div class="alert alert-danger" role="alert"><i class="fas fa-exclamation-circle"></i> Error loading page. Please try again.</div>',
    timeout: 10000
};

/**
 * Load a page via AJAX into the content area
 * @param {string} pageUrl - URL of the page to load
 * @param {string} pageTitle - Title for breadcrumb (optional)
 * @param {function} callback - Callback function when load completes (optional)
 */
function loadPage(pageUrl, pageTitle = null, callback = null) {
    const $content = $(pageLoaderConfig.contentSelector);
    
    // Validate URL to prevent XSS
    if (!isValidUrl(pageUrl)) {
        console.error('Invalid URL:', pageUrl);
        $content.html(pageLoaderConfig.errorHtml);
        return;
    }
    
    // Show loading state
    $content.html(pageLoaderConfig.loadingHtml);
    
    // Load the page
    $.ajax({
        url: pageUrl,
        method: 'GET',
        timeout: pageLoaderConfig.timeout,
        success: function(data) {
            // Show the loaded content
            $content.html(data);
            
            // Update breadcrumb if title provided
            if (pageTitle) {
                updateBreadcrumb(pageTitle, pageUrl);
            }
            
            // Update sidebar active item
            updateSidebarActive(pageUrl);
            
            // Trigger any page-specific initialization
            if (typeof window.onPageLoaded === 'function') {
                window.onPageLoaded();
            }
            
            // Close sidebar on mobile after loading
            if ($(window).width() < 768) {
                $('#sidebar').removeClass('show');
                $('#sidebarToggle').removeClass('active');
            }
            
            // Execute callback if provided
            if (callback && typeof callback === 'function') {
                callback();
            }
            
            // Log to console
            console.debug('Page loaded:', pageUrl);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            // Handle errors
            console.error('Page load error:', {
                url: pageUrl,
                status: textStatus,
                error: errorThrown,
                httpStatus: jqXHR.status
            });
            
            let errorMsg = pageLoaderConfig.errorHtml;
            
            if (jqXHR.status === 404) {
                errorMsg = '<div class="alert alert-warning" role="alert"><i class="fas fa-file-slash"></i> Page not found.</div>';
            } else if (jqXHR.status === 403) {
                errorMsg = '<div class="alert alert-danger" role="alert"><i class="fas fa-lock"></i> Access denied. You do not have permission.</div>';
            } else if (textStatus === 'timeout') {
                errorMsg = '<div class="alert alert-danger" role="alert"><i class="fas fa-hourglass-half"></i> Request timeout. Please try again.</div>';
            }
            
            $content.html(errorMsg);
        }
    });
}

/**
 * Validate URL to prevent XSS attacks
 * @param {string} url - URL to validate
 * @returns {boolean}
 */
function isValidUrl(url) {
    if (typeof url !== 'string') return false;
    if (url.startsWith('javascript:')) return false;
    if (url.includes('://') && !url.startsWith(rootPath)) return false;
    return true;
}

/**
 * Update breadcrumb navigation
 * @param {string} title - Current page title
 * @param {string} pageUrl - Current page URL (for potential back navigation)
 */
function updateBreadcrumb(title, pageUrl) {
    const breadcrumbHtml = `
        <li class="breadcrumb-item"><a href="#" onclick="loadDashboard(); return false;"><i class="fas fa-home"></i> Home</a></li>
        <li class="breadcrumb-item active">${escapeHtml(title)}</li>
    `;
    $('#breadcrumbs').html(breadcrumbHtml);
}

/**
 * Update sidebar active state based on loaded page
 * @param {string} pageUrl - URL of the loaded page
 */
function updateSidebarActive(pageUrl) {
    // Remove active class from all items
    $('.sidebar-menu .menu-item').removeClass('active');
    
    // Add active class to matching item
    $(`.sidebar-menu .menu-item[data-page="${pageUrl}"]`).addClass('active');
}

/**
 * Escape HTML special characters (XSS prevention)
 * @param {string} text - Text to escape
 * @returns {string}
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Load the dashboard (home page)
 */
function loadDashboard() {
    const dashboardUrl = rootPath + '/dashboard.php';
    loadPage(dashboardUrl, 'Dashboard');
}

/**
 * Show loading indicator
 */
function showLoading() {
    $(pageLoaderConfig.contentSelector).html(pageLoaderConfig.loadingHtml);
}

/**
 * Show error message
 * @param {string} message - Error message to display
 */
function showError(message = 'An error occurred. Please try again.') {
    const errorHtml = `<div class="alert alert-danger" role="alert"><i class="fas fa-exclamation-circle"></i> ${escapeHtml(message)}</div>`;
    $(pageLoaderConfig.contentSelector).html(errorHtml);
}

/**
 * Reload current page
 */
function reloadCurrentPage() {
    const currentUrl = window.location.hash || (rootPath + '/dashboard.php');
    loadPage(currentUrl);
}

/**
 * Handle window resize for sidebar responsiveness
 */
$(window).resize(function() {
    if ($(window).width() > 768) {
        $('#sidebar').removeClass('show');
        $('#sidebarToggle').removeClass('active');
    }
});

/**
 * Initialize page loader on document ready
 */
$(document).ready(function() {
    // Delegate click events for menu items
    $(document).on('click', '.sidebar-menu .menu-item', function(e) {
        e.preventDefault();
        
        const pageUrl = $(this).data('page');
        const pageTitle = $(this).text().trim();
        
        if (pageUrl) {
            loadPage(pageUrl, pageTitle);
        }
    });
    
    // Close sidebar when clicking overlay on mobile
    $(document).on('click', function(e) {
        const sidebar = $('#sidebar');
        const sidebarToggle = $('#sidebarToggle');
        
        if ($(window).width() < 768) {
            if (!sidebar.is(e.target) && 
                sidebar.has(e.target).length === 0 && 
                !sidebarToggle.is(e.target) && 
                sidebarToggle.has(e.target).length === 0) {
                sidebar.removeClass('show');
                sidebarToggle.removeClass('active');
            }
        }
    });
    
    console.debug('Page loader initialized');
});
