/**
 * Smart ERP - Menu Handler
 * Converts menu structure from data-clear-2.js to sidebar navigation
 * 
 * Menu format from data-clear-2.js:
 * ["Menu Item", "page.php", "icon", ...]
 * ["|Submenu", "", "", ...] 
 * ["||Sub-submenu", "page.php", "", ...]
 */

$(document).ready(function() {
    // Build and render the menu
    buildMenuFromData();
});

/**
 * Build menu HTML from the menuItems array defined in data-clear-2.js
 */
function buildMenuFromData() {
    if (typeof menuItems === 'undefined') {
        console.error('Menu items not defined. Make sure data-clear-2.js is loaded.');
        return false;
    }
    
    const $sidebarMenu = $('#sidebarMenu');
    let menuHtml = '';
    let submenuStack = [];
    
    menuItems.forEach((item, index) => {
        const itemText = item[0];
        const itemPage = item[1];
        const itemIcon = item[2];
        
        // Determine menu level based on || and | prefixes
        const level = getMenuLevel(itemText);
        const cleanText = itemText.replace(/^\|+/, '').trim();
        
        // Adjust submenu stack for current level
        while (submenuStack.length >= level) {
            submenuStack.pop();
            menuHtml += '</ul></li>';
        }
        
        if (level === 1) {
            // Top-level menu item
            const iconClass = itemIcon ? itemIcon.replace('images/', '') : 'fa-folder';
            menuHtml += `
                <li class="menu-group">
                    <a class="menu-item d-flex align-items-center" data-page="${itemPage}">
                        <i class="fas ${iconClass} me-2"></i>
                        ${escapeHtml(cleanText)}
                    </a>
            `;
            submenuStack.push(1);
        } else if (level === 2) {
            // Submenu level
            if (!menuHtml.includes('<ul class="submenu')) {
                menuHtml += '<ul class="submenu">';
                submenuStack.push(2);
            }
            
            const iconClass = itemIcon ? itemIcon.replace('images/', '') : 'fa-file';
            menuHtml += `
                <li>
                    <a class="menu-item d-flex align-items-center" data-page="${itemPage}">
                        <i class="fas ${iconClass} me-2"></i>
                        ${escapeHtml(cleanText)}
                    </a>
                </li>
            `;
        } else if (level === 3) {
            // Sub-submenu level
            const iconClass = itemIcon ? itemIcon.replace('images/', '') : 'fa-chevron-right';
            menuHtml += `
                <li style="padding-left: 1rem;">
                    <a class="menu-item d-flex align-items-center" data-page="${itemPage}">
                        <i class="fas ${iconClass} me-2"></i>
                        <small>${escapeHtml(cleanText)}</small>
                    </a>
                </li>
            `;
        }
    });
    
    // Close remaining open tags
    while (submenuStack.length > 0) {
        submenuStack.pop();
        if (submenuStack.length > 0) {
            menuHtml += '</ul></li>';
        }
    }
    
    // Set the menu HTML
    $sidebarMenu.html(menuHtml);
    
    console.debug('Menu built from data-clear-2.js');
    return true;
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
 * Determine menu level based on prefix count
 * | = level 2 (submenu)
 * || = level 3 (sub-submenu)
 * (no prefix) = level 1 (main menu)
 * 
 * @param {string} itemText - Menu item text with prefixes
 * @returns {number} Menu level (1, 2, or 3)
 */
function getMenuLevel(itemText) {
    let level = 1;
    for (let char of itemText) {
        if (char === '|') level++;
        else break;
    }
    return level;
}

/**
 * Toggle menu group (submenu) visibility and handle page loads
 */
$(document).on('click', '.menu-item', function(e) {
    const pageUrl = $(this).data('page');
    const pageText = $(this).text().trim();
    const $submenu = $(this).next('.submenu');
    
    // If this item has a submenu, toggle it
    if ($submenu.length) {
        e.preventDefault();
        $submenu.slideToggle(200);
        $(this).toggleClass('expanded');
        return false;
    }
    
    // If this item has a page URL, load that page
    if (pageUrl) {
        e.preventDefault();
        
        // Build full URL if relative path
        const fullUrl = pageUrl.startsWith('/') ? pageUrl : rootPath + '/' + pageUrl;
        
        // Call to page-loader.js function
        if (typeof loadPage === 'function') {
            loadPage(fullUrl, pageText);
        } else {
            console.error('loadPage function not available. Loading directly...');
            window.location.href = fullUrl;
        }
        
        return false;
    }
});

console.debug('Menu handler loaded');
