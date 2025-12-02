/**
 * @file
 * MyEventLane Theme - Main JavaScript Entry
 *
 * This file is the entry point for Vite and imports all theme assets.
 */

// Import SCSS (processed by Vite)
import '../scss/main.scss';

// Import components
import { initMobileNav } from './header.js';

/**
 * Initialize theme functionality.
 */
function init() {
  // Initialize mobile navigation
  initMobileNav();

  // Add loaded class for CSS transitions
  document.documentElement.classList.add('js-loaded');
}

// Run on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
