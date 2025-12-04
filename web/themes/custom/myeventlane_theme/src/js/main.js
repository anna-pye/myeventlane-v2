/**
 * @file
 * MyEventLane Theme - Main JavaScript Entry
 *
 * This file is the entry point for Vite and imports all theme assets.
 */

// Import SCSS (processed by Vite)
import '../scss/main.scss';

// Import components
import { initMobileNav, initAccountDropdown } from './header.js';

/**
 * Initialize theme functionality.
 */
function init() {
  console.log('MyEventLane theme JS initializing...');
  
  // Initialize mobile navigation
  initMobileNav();

  // Initialize account dropdown
  initAccountDropdown();

  // Add loaded class for CSS transitions
  document.documentElement.classList.add('js-loaded');
  
  console.log('MyEventLane theme JS initialized');
}

// Run on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
