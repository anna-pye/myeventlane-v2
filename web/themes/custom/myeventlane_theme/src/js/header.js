/**
 * @file
 * Header component - Mobile navigation toggle and account dropdown.
 */

// Store references globally to avoid re-querying
let mobileNavState = {
  toggle: null,
  mobileNav: null,
  closeBtn: null,
  overlay: null,
  initialized: false,
};

/**
 * Initialize mobile navigation with enhanced accessibility.
 * Uses event delegation for reliability.
 */
export function initMobileNav() {
  // If already initialized, don't re-initialize
  if (mobileNavState.initialized) {
    return;
  }

  // Try to find elements
  mobileNavState.toggle = document.querySelector('.mel-nav-toggle');
  mobileNavState.mobileNav = document.querySelector('.mel-nav-mobile-wrapper');
  mobileNavState.closeBtn = document.querySelector('.mel-nav-mobile-close');
  mobileNavState.overlay = document.querySelector('.mel-nav-overlay');

  // If elements not found, retry
  if (!mobileNavState.toggle || !mobileNavState.mobileNav) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initMobileNav);
    } else {
      setTimeout(initMobileNav, 100);
    }
    return;
  }

  // Create overlay if it doesn't exist
  if (!mobileNavState.overlay) {
    mobileNavState.overlay = document.createElement('div');
    mobileNavState.overlay.className = 'mel-nav-overlay';
    mobileNavState.overlay.setAttribute('aria-hidden', 'true');
    mobileNavState.overlay.setAttribute('tabindex', '-1');
    document.body.appendChild(mobileNavState.overlay);
  }

  // Mark as initialized
  mobileNavState.initialized = true;

  // Get all focusable elements in mobile nav
  const getFocusableElements = () => {
    const focusableSelectors = [
      'a[href]',
      'button:not([disabled])',
      'input:not([disabled])',
      'select:not([disabled])',
      'textarea:not([disabled])',
      '[tabindex]:not([tabindex="-1"])',
    ].join(', ');
    return Array.from(mobileNavState.mobileNav.querySelectorAll(focusableSelectors));
  };

  // Store element that had focus before menu opened
  let previousActiveElement = null;

  /**
   * Trap focus within mobile nav.
   */
  function trapFocus(e) {
    if (!mobileNavState.mobileNav.classList.contains('is-open')) {
      return;
    }

    const focusableElements = getFocusableElements();
    if (focusableElements.length === 0) {
      return;
    }

    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    // If Tab is pressed
    if (e.key === 'Tab') {
      if (e.shiftKey) {
        // Shift + Tab
        if (document.activeElement === firstElement) {
          e.preventDefault();
          lastElement.focus();
        }
      } else {
        // Tab
        if (document.activeElement === lastElement) {
          e.preventDefault();
          firstElement.focus();
        }
      }
    }
  }

  /**
   * Open mobile nav.
   */
  function openNav() {
    // Store current focus
    previousActiveElement = document.activeElement;

    mobileNavState.mobileNav.classList.add('is-open');
    mobileNavState.toggle.classList.add('is-active');
    mobileNavState.overlay.classList.add('is-visible');
    mobileNavState.toggle.setAttribute('aria-expanded', 'true');
    mobileNavState.mobileNav.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    // Focus first link in mobile nav
    const firstLink = mobileNavState.mobileNav.querySelector('a, button');
    if (firstLink) {
      setTimeout(() => firstLink.focus(), 100);
    }

    // Add focus trap
    document.addEventListener('keydown', trapFocus);
  }

  /**
   * Close mobile nav.
   */
  function closeNav() {
    mobileNavState.mobileNav.classList.remove('is-open');
    mobileNavState.toggle.classList.remove('is-active');
    mobileNavState.overlay.classList.remove('is-visible');
    mobileNavState.toggle.setAttribute('aria-expanded', 'false');
    mobileNavState.mobileNav.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';

    // Remove focus trap
    document.removeEventListener('keydown', trapFocus);

    // Return focus to previous element or toggle
    if (previousActiveElement && typeof previousActiveElement.focus === 'function') {
      setTimeout(() => previousActiveElement.focus(), 100);
    } else {
      mobileNavState.toggle.focus();
    }
  }

  // Use event delegation on document for maximum reliability
  document.addEventListener('click', function(e) {
    // Toggle button click
    if (e.target.closest('.mel-nav-toggle')) {
      e.preventDefault();
      e.stopPropagation();
      const isOpen = mobileNavState.mobileNav.classList.contains('is-open');
      if (isOpen) {
        closeNav();
      } else {
        openNav();
      }
      return;
    }

    // Close button click (by class or data attribute)
    if (e.target.closest('.mel-nav-mobile-close') || e.target.closest('[data-nav-close]')) {
      e.preventDefault();
      e.stopPropagation();
      closeNav();
      return;
    }

    // Overlay click
    if (e.target === mobileNavState.overlay || e.target.classList.contains('mel-nav-overlay')) {
      e.preventDefault();
      e.stopPropagation();
      closeNav();
      return;
    }
  }, true);

  // Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && mobileNavState.mobileNav.classList.contains('is-open')) {
      closeNav();
    }
  });

  // Close on resize to desktop
  const mediaQuery = window.matchMedia('(min-width: 768px)');
  if (mediaQuery.addEventListener) {
    mediaQuery.addEventListener('change', function(e) {
      if (e.matches && mobileNavState.mobileNav.classList.contains('is-open')) {
        closeNav();
      }
    });
  } else {
    // Fallback for older browsers
    mediaQuery.addListener(function(e) {
      if (e.matches && mobileNavState.mobileNav.classList.contains('is-open')) {
        closeNav();
      }
    });
  }
}

/**
 * Initialize account dropdown.
 * Note: Dropdown now works CSS-only using :focus-within.
 * This function is kept for backward compatibility but does nothing.
 */
export function initAccountDropdown() {
  // CSS-only dropdown using :focus-within - no JS needed!
  return;
}

// Legacy export for backward compatibility
export function melHeaderInit() {
  initMobileNav();
  initAccountDropdown();
}
