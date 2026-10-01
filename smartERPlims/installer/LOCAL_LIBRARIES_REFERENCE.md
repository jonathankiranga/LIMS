# Smart ERP Dashboard - Local Libraries Reference

## Overview
All CSS and JavaScript libraries have been downloaded locally to ensure reliability and offline functionality. This document lists all libraries, their versions, and locations.

---

## CSS Libraries

### Bootstrap 5.3.0
- **File**: `css/bootstrap5.min.css`
- **Version**: 5.3.0
- **Source**: https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css
- **Purpose**: Responsive grid system and component styling

### Font Awesome 6.4.0
- **File**: `css/fontawesome6.4.0.all.min.css`
- **Version**: 6.4.0
- **Source**: https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css
- **Purpose**: Icon library with 2,000+ icons
- **Dependencies**: Webfonts in `css/webfonts/`

### Font Awesome Webfonts (6.4.0)
- **Location**: `css/webfonts/`
- **Files**:
  - `fa-solid-900.woff2` - Solid icon variant
  - `fa-regular-400.woff2` - Regular icon variant
  - `fa-brands-400.woff2` - Brand icon variant
- **Purpose**: Font files for Font Awesome icons

### Modern Dashboard CSS
- **File**: `css/homepage-modern.css`
- **Purpose**: Custom dashboard styling with gradients, animations, and modern UI components

---

## JavaScript Libraries

### Bootstrap 5.3.0 Bundle
- **File**: `javascripts/bootstrap5.bundle.min.js`
- **Version**: 5.3.0
- **Source**: https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js
- **Purpose**: Bootstrap JavaScript functionality (modals, dropdowns, tooltips, etc.)
- **Size**: ~70 KB (minified)

### Chart.js 3.9.1
- **File**: `javascripts/chart.min.js`
- **Version**: 3.9.1
- **Source**: https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js
- **Purpose**: Beautiful animated charts and data visualization
- **Size**: ~50 KB (minified)
- **Supported Charts**: Line, Bar, Pie, Doughnut, Scatter, Bubble, Radar, Polar Area

### jQuery 3.6.0
- **File**: `javascripts/jquery-3.6.0.min.js`
- **Version**: 3.6.0
- **Source**: https://code.jquery.com/jquery-3.6.0.min.js
- **Purpose**: DOM manipulation and compatibility with legacy code
- **Size**: ~88 KB (minified)

### Modern Dashboard JavaScript
- **File**: `javascripts/modern-dashboard.js`
- **Purpose**: Custom dashboard functionality
- **Features**:
  - Counter animations for KPI cards
  - Real-time data updates
  - Smooth interactions and transitions
  - Chart.js initialization
  - Loading state management

### Legacy Files (Preserved)
- `javascripts/MiscFunctions.min.js` - Original utility functions
- `javascripts/jquery.min.js` - Original jQuery (older version)
- `javascripts/JQueryclases.min.js` - jQuery extensions

---

## Directory Structure

```
smartERPmysql.2.2/
├── css/
│   ├── bootstrap5.min.css
│   ├── fontawesome6.4.0.all.min.css
│   ├── homepage-modern.css
│   ├── homepage.css (legacy)
│   └── webfonts/
│       ├── fa-solid-900.woff2
│       ├── fa-regular-400.woff2
│       └── fa-brands-400.woff2
├── javascripts/
│   ├── bootstrap5.bundle.min.js
│   ├── chart.min.js
│   ├── jquery-3.6.0.min.js
│   ├── jquery.min.js (legacy)
│   ├── modern-dashboard.js
│   └── MiscFunctions.min.js (legacy)
└── homepage.php
```

---

## Total Size

| Library | Size |
|---------|------|
| Bootstrap 5 CSS | ~170 KB |
| Font Awesome CSS | ~40 KB |
| Font Awesome Fonts (3 files) | ~120 KB |
| Bootstrap JS | ~70 KB |
| Chart.js | ~50 KB |
| jQuery | ~88 KB |
| Custom JavaScript | ~15 KB |
| **TOTAL** | **~553 KB** |

---

## Usage in Homepage

### CSS References
```php
<link rel="stylesheet" href="/css/bootstrap5.min.css" type="text/css"/>
<link rel="stylesheet" href="/css/fontawesome6.4.0.all.min.css" type="text/css"/>
<link rel="stylesheet" href="/css/homepage-modern.css" type="text/css"/>
```

### JavaScript References
```php
<script src="/javascripts/bootstrap5.bundle.min.js"></script>
<script src="/javascripts/chart.min.js"></script>
<script src="/javascripts/jquery-3.6.0.min.js"></script>
<script src="/javascripts/MiscFunctions.min.js"></script>
<script src="/javascripts/modern-dashboard.js"></script>
```

---

## Browser Compatibility

### Bootstrap 5
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- IE 11 (limited support)

### Chart.js 3.9.1
- All modern browsers
- Canvas-based rendering
- Responsive by default

### Font Awesome 6.4.0
- All modern browsers
- WOFF2 format for modern browsers
- Fallback to TTF for older browsers

### jQuery 3.6.0
- Chrome, Firefox, Safari, Edge
- No IE support (use jQuery 1.12 or 2.x for IE)

---

## Updates & Maintenance

To update any library:

1. Download the latest minified version from the CDN
2. Replace the file in the appropriate directory
3. Update version numbers in this document
4. Test functionality in all browsers
5. Verify CSS paths in homepage.php

### CDN Links for Checking Updates

- **Bootstrap**: https://www.bootstrapcdn.com/
- **Font Awesome**: https://fontawesome.com/docs/web/setup/get-started
- **Chart.js**: https://www.chartjs.org/docs/latest/
- **jQuery**: https://code.jquery.com/

---

## Performance Notes

- All files are minified for optimal performance
- Font Awesome: Consider lazy-loading if performance is critical
- Chart.js: Initialize charts only when needed
- Bootstrap: Full bundle included; consider splitting if size matters
- No external HTTP requests needed (fully offline-capable)

---

## Support & Documentation

- **Bootstrap 5**: https://getbootstrap.com/docs/5.3/
- **Font Awesome**: https://fontawesome.com/docs/
- **Chart.js**: https://www.chartjs.org/docs/latest/
- **jQuery**: https://api.jquery.com/

---

## License Notes

- **Bootstrap**: MIT License
- **Font Awesome**: Free License (Community Icons + Brands)
- **Chart.js**: MIT License
- **jQuery**: MIT License

All libraries are open-source and free to use.

---

**Last Updated**: March 5, 2026
**Dashboard Version**: 1.0.0
**Created By**: GitHub Copilot
