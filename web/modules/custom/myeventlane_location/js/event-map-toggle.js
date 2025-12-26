/**
 * @file
 * Map toggle behavior for event node display.
 *
 * Provides a simple toggle to show/hide the map on event pages.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Initialize map toggle for event pages.
   */
  function initMapToggle(context) {
    const toggles = once('mel-map-toggle', '.mel-event-map-toggle', context);
    
    toggles.forEach((toggle) => {
      const mapContainer = toggle.closest('.mel-event-location').querySelector('.mel-event-map-container');
      if (!mapContainer) {
        return;
      }

      // Set initial state (hidden).
      mapContainer.style.display = 'none';
      toggle.setAttribute('aria-expanded', 'false');

      toggle.addEventListener('click', (e) => {
        e.preventDefault();
        
        const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
        
        if (isExpanded) {
          // Hide map.
          mapContainer.style.display = 'none';
          toggle.setAttribute('aria-expanded', 'false');
          toggle.textContent = Drupal.t('Show map');
        } else {
          // Show map and lazy-initialize if needed.
          mapContainer.style.display = 'block';
          toggle.setAttribute('aria-expanded', 'true');
          toggle.textContent = Drupal.t('Hide map');
          
          // Lazy-initialize map only on first open.
          if (!mapContainer.dataset.initialized) {
            mapContainer.dataset.initialized = '1';
            // Trigger map initialization if available.
            if (window.Drupal && Drupal.behaviors.myeventlaneLocationMap) {
              Drupal.behaviors.myeventlaneLocationMap.attach(mapContainer);
            }
          }
        }
      });
    });
  }

  /**
   * Drupal behavior.
   */
  Drupal.behaviors.myeventlaneLocationMapToggle = {
    attach(context) {
      initMapToggle(context);
    }
  };

})(window.Drupal, window.once);
