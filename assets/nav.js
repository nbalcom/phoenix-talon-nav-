document.addEventListener('DOMContentLoaded', function() {
    // RESOLVE SELECTOR TARGET DYNAMICALLY: Pull the selector string from the admin options panel array data layer
    const targetSelector = (typeof phxTalonConfig !== 'undefined' && phxTalonConfig.hamburgerSelector) 
        ? phxTalonConfig.hamburgerSelector 
        : '.phx-menu-toggle';

    const themeHamburgers = document.querySelectorAll(targetSelector);
    const portal = document.getElementById('phx-mobile-portal');
    const overlay = document.getElementById('phx-drawer-overlay');
    const closeBtn = document.getElementById('phx-drawer-close');

    if (!portal) return;

    // Initialize Root Canvas ARIA States across matching elements discovered inside active viewports
    themeHamburgers.forEach(hamburger => {
        hamburger.setAttribute('aria-expanded', 'false');
        hamburger.setAttribute('aria-controls', 'phx-mobile-portal');
    });
    portal.setAttribute('aria-hidden', 'true');

    // Centralized state controllers
    const openPortal = (e) => {
        if (window.innerWidth <= 768) {
            e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation();
            portal.classList.add('phx-active');
            
            themeHamburgers.forEach(hamburger => hamburger.setAttribute('aria-expanded', 'true'));
            portal.setAttribute('aria-hidden', 'false');
        }
    };

    const closePortal = () => {
        portal.classList.remove('phx-active');
        themeHamburgers.forEach(hamburger => hamburger.setAttribute('aria-expanded', 'false'));
        portal.setAttribute('aria-hidden', 'true');
    };

    // 1. Intercept Theme Hamburger Click via Dynamic Selector Node Mapping loops
    themeHamburgers.forEach(hamburger => {
        hamburger.addEventListener('click', openPortal, true); 
    });

    // 2. Close Menu Actions
    if(closeBtn) closeBtn.addEventListener('click', closePortal);
    if(overlay) overlay.addEventListener('click', closePortal);
    
    // Add Keyboard Accessibility (Escape Key to Close)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && portal.classList.contains('phx-active')) {
            closePortal();
        }
    });

    // 3. Drill-Down Menu Logic & Sub-Menu ARIA Mapping
    const parentLinks = portal.querySelectorAll('.menu-item-has-children > a');
    
    parentLinks.forEach(link => {
        const subMenu = link.nextElementSibling;
        if (!subMenu || !subMenu.classList.contains('sub-menu')) return;

        // Initialize sub-menu ARIA states
        link.setAttribute('aria-expanded', 'false');
        link.setAttribute('role', 'button');
        subMenu.setAttribute('aria-hidden', 'true');

        // Inject ARIA-compliant "Back" button
        const backLi = document.createElement('li');
        backLi.className = 'phx-back-btn';
        backLi.innerHTML = `<a href="#" aria-label="Go back to previous menu">← Back</a>`;
        subMenu.insertBefore(backLi, subMenu.firstChild);

        // Slide Sub-Menu IN (Toggle ARIA Open)
        link.addEventListener('click', function(e) {
            if (window.innerWidth > 768) return;
            
            e.preventDefault();
            subMenu.classList.add('phx-submenu-active');
            link.setAttribute('aria-expanded', 'true');
            subMenu.setAttribute('aria-hidden', 'false');
        });

        // Slide Sub-Menu OUT (Toggle ARIA Closed)
        backLi.querySelector('a').addEventListener('click', function(e) {
            e.preventDefault();
            subMenu.classList.remove('phx-submenu-active');
            link.setAttribute('aria-expanded', 'false');
            subMenu.setAttribute('aria-hidden', 'true');
        });
    });
});