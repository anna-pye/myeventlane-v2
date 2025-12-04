/**
 * @file
 * Header component - Mobile navigation toggle.
 */

/**
 * Initialize mobile navigation.
 */
export function initMobileNav() {
  const toggle = document.querySelector('.mel-nav-toggle');
  const mobileNav = document.querySelector('.mel-nav-mobile-wrapper');
  const closeBtn = document.querySelector('.mel-nav-mobile-close');

  if (!toggle || !mobileNav) {
    return;
  }

  // Create overlay if it doesn't exist
  let overlay = document.querySelector('.mel-nav-overlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.className = 'mel-nav-overlay';
    overlay.setAttribute('aria-hidden', 'true');
    document.body.appendChild(overlay);
  }

  /**
   * Open mobile nav.
   */
  function openNav() {
    mobileNav.classList.add('is-open');
    toggle.classList.add('is-active');
    overlay.classList.add('is-visible');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';

    // Focus first link in mobile nav
    const firstLink = mobileNav.querySelector('a');
    if (firstLink) {
      firstLink.focus();
    }
  }

  /**
   * Close mobile nav.
   */
  function closeNav() {
    mobileNav.classList.remove('is-open');
    toggle.classList.remove('is-active');
    overlay.classList.remove('is-visible');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';

    // Return focus to toggle
    toggle.focus();
  }

  // Toggle button click
  toggle.addEventListener('click', () => {
    const isOpen = mobileNav.classList.contains('is-open');
    if (isOpen) {
      closeNav();
    } else {
      openNav();
    }
  });

  // Close button click
  if (closeBtn) {
    closeBtn.addEventListener('click', closeNav);
  }

  // Overlay click
  overlay.addEventListener('click', closeNav);

  // Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && mobileNav.classList.contains('is-open')) {
      closeNav();
    }
  });

  // Close on resize to desktop
  const mediaQuery = window.matchMedia('(min-width: 768px)');
  mediaQuery.addEventListener('change', (e) => {
    if (e.matches && mobileNav.classList.contains('is-open')) {
      closeNav();
    }
  });
}

/**
 * Initialize account dropdown.
 */
export function initAccountDropdown() {
  console.log('initAccountDropdown called');
  
  function setupDropdowns() {
    console.log('Setting up account dropdowns...');
    const dropdowns = document.querySelectorAll('.mel-account-dropdown');
    console.log(`Found ${dropdowns.length} dropdown(s)`);
    
    if (dropdowns.length === 0) {
      console.warn('No account dropdowns found on page');
      return;
    }

    dropdowns.forEach((dropdown) => {
      const toggle = dropdown.querySelector('.mel-account-toggle');
      const menu = dropdown.querySelector('.mel-account-menu');

      if (!toggle || !menu) {
        return;
      }

      // Direct click handler on toggle
      toggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const isOpen = dropdown.classList.contains('is-open');
        
        if (isOpen) {
          // Close
          dropdown.classList.remove('is-open');
          toggle.setAttribute('aria-expanded', 'false');
          menu.setAttribute('aria-hidden', 'true');
        } else {
          // Close all other dropdowns
          document.querySelectorAll('.mel-account-dropdown.is-open').forEach((other) => {
            if (other !== dropdown) {
              other.classList.remove('is-open');
              const otherToggle = other.querySelector('.mel-account-toggle');
              const otherMenu = other.querySelector('.mel-account-menu');
              if (otherToggle) otherToggle.setAttribute('aria-expanded', 'false');
              if (otherMenu) otherMenu.setAttribute('aria-hidden', 'true');
            }
          });
          
          // Open this one
          dropdown.classList.add('is-open');
          toggle.setAttribute('aria-expanded', 'true');
          menu.setAttribute('aria-hidden', 'false');
        }
      });

      // Close on outside click - use capture phase to catch it early
      document.addEventListener('click', function closeOnOutside(e) {
        if (!dropdown.contains(e.target) && dropdown.classList.contains('is-open')) {
          dropdown.classList.remove('is-open');
          toggle.setAttribute('aria-expanded', 'false');
          menu.setAttribute('aria-hidden', 'true');
        }
      }, true);

      // Escape key
      document.addEventListener('keydown', function escapeHandler(e) {
        if (e.key === 'Escape' && dropdown.classList.contains('is-open')) {
          dropdown.classList.remove('is-open');
          toggle.setAttribute('aria-expanded', 'false');
          menu.setAttribute('aria-hidden', 'true');
          toggle.focus();
        }
      });
    });
  }

  // Run when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupDropdowns);
  } else {
    // Small delay to ensure elements are rendered
    setTimeout(setupDropdowns, 50);
  }
}

// Legacy export for backward compatibility
export function melHeaderInit() {
  initMobileNav();
  initAccountDropdown();
}
