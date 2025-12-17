/**
 * @file
 * Stripe.js Fallback Loader
 * 
 * Ensures Stripe.js loads even if library attachment fails.
 * This is a safety net for cases where the commerce_stripe/stripe library
 * doesn't load properly.
 */

(function () {
  'use strict';

  /**
   * Load Stripe.js manually if it's not already loaded.
   */
  function loadStripeFallback() {
    // Check if Stripe is already loaded
    if (typeof window.Stripe !== 'undefined') {
      return;
    }

    // Check if script tag already exists
    const existingScript = document.querySelector('script[src*="js.stripe.com"]');
    if (existingScript) {
      // Script tag exists but Stripe not loaded - wait for it
      const checkInterval = setInterval(() => {
        if (typeof window.Stripe !== 'undefined') {
          clearInterval(checkInterval);
          console.log('[Stripe Fallback] Stripe.js loaded from existing script');
          // Re-run Commerce Stripe behaviors
          if (Drupal && Drupal.behaviors) {
            if (Drupal.behaviors.commerceStripeForm) {
              Drupal.behaviors.commerceStripeForm.attach(document);
            }
            if (Drupal.behaviors.commerceStripePaymentElement) {
              Drupal.behaviors.commerceStripePaymentElement.attach(document);
            }
          }
        }
      }, 100);

      // Give up after 10 seconds
      setTimeout(() => {
        clearInterval(checkInterval);
        if (typeof window.Stripe === 'undefined') {
          console.warn('[Stripe Fallback] Existing script did not load Stripe.js, loading manually...');
          loadStripeManually();
        }
      }, 10000);
      return;
    }

    // No script tag found - load manually
    console.warn('[Stripe Fallback] Stripe.js script tag not found, loading manually...');
    loadStripeManually();
  }

  /**
   * Manually load Stripe.js from CDN.
   */
  function loadStripeManually() {
    const script = document.createElement('script');
    script.src = 'https://js.stripe.com/v3/';
    script.async = false;
    script.defer = false; // Load immediately, not deferred
    
    script.onload = function() {
      console.log('[Stripe Fallback] ✅ Stripe.js manually loaded successfully');
      console.log('[Stripe Fallback] window.Stripe type:', typeof window.Stripe);
      
      // Re-run Commerce Stripe behaviors now that Stripe is available
      if (Drupal && Drupal.behaviors) {
        // Use setTimeout to ensure Stripe is fully initialized
        setTimeout(() => {
          if (Drupal.behaviors.commerceStripeForm) {
            console.log('[Stripe Fallback] Re-running commerceStripeForm behavior');
            Drupal.behaviors.commerceStripeForm.attach(document);
          }
          if (Drupal.behaviors.commerceStripePaymentElement) {
            console.log('[Stripe Fallback] Re-running commerceStripePaymentElement behavior');
            Drupal.behaviors.commerceStripePaymentElement.attach(document);
          }
        }, 100);
      }
    };

    script.onerror = function() {
      console.error('[Stripe Fallback] ❌ Failed to load Stripe.js from CDN');
      console.error('[Stripe Fallback] This may be due to:');
      console.error('[Stripe Fallback] - Network connectivity issues');
      console.error('[Stripe Fallback] - Content Security Policy (CSP) blocking external scripts');
      console.error('[Stripe Fallback] - Firewall/proxy blocking js.stripe.com');
    };

    // Insert before other scripts to load early
    const firstScript = document.querySelector('script');
    if (firstScript && firstScript.parentNode) {
      firstScript.parentNode.insertBefore(script, firstScript);
    } else {
      document.head.appendChild(script);
    }
  }

  // Run immediately - don't wait
  console.log('[Stripe Fallback] Script loaded, checking for Stripe.js...');
  
  // Run immediately if DOM is ready
  if (document.readyState === 'complete' || document.readyState === 'interactive') {
    loadStripeFallback();
  } else {
    // Wait for DOM ready
    document.addEventListener('DOMContentLoaded', function() {
      loadStripeFallback();
    });
  }
  
  // Also run after a short delay as backup
  setTimeout(loadStripeFallback, 500);

  // Also check after Drupal behaviors run
  if (typeof Drupal !== 'undefined' && Drupal.behaviors) {
    Drupal.behaviors.myeventlaneStripeFallback = {
      attach: function(context) {
        // Only run on full page load
        if (context === document) {
          setTimeout(loadStripeFallback, 2000);
        }
      }
    };
  }
})();
