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

// Legacy export for backward compatibility
export function melHeaderInit() {
  initMobileNav();
}
