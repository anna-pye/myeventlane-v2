/**
 * @file
 * Event form enhancements - ensures conditional fields work correctly.
 */

(function (Drupal) {
  'use strict';

  /**
   * Ensures booking configuration conditional fields work.
   */
  Drupal.behaviors.eventFormConditionalFields = {
    attach: function (context, settings) {
      // Find the event type select field - try multiple selectors
      const selectors = [
        'select[name="field_event_type[0][value]"]',
        'select[name*="field_event_type"][name*="value"]',
        'select[name*="field_event_type"]',
      ];
      
      let eventTypeSelect = null;
      for (let i = 0; i < selectors.length; i++) {
        eventTypeSelect = context.querySelector(selectors[i]);
        if (eventTypeSelect) {
          break;
        }
      }
      
      if (!eventTypeSelect) {
        return;
      }

      // Function to toggle fields based on selection
      const toggleFields = function() {
        const value = eventTypeSelect.value;
        const rsvpFields = context.querySelector('.mel-booking-rsvp-fields');
        const paidFields = context.querySelector('.mel-booking-paid-fields');
        const externalFields = context.querySelector('.mel-booking-external-fields');

        // Hide all first
        if (rsvpFields) {
          rsvpFields.style.display = 'none';
        }
        if (paidFields) {
          paidFields.style.display = 'none';
        }
        if (externalFields) {
          externalFields.style.display = 'none';
        }

        // Show relevant fields based on selection
        if (value === 'rsvp' || value === 'both') {
          if (rsvpFields) {
            rsvpFields.style.display = 'block';
          }
        }
        if (value === 'paid' || value === 'both') {
          if (paidFields) {
            paidFields.style.display = 'block';
          }
        }
        if (value === 'external') {
          if (externalFields) {
            externalFields.style.display = 'block';
          }
        }
      };

      // Run on page load
      setTimeout(toggleFields, 100);

      // Run on change
      eventTypeSelect.addEventListener('change', toggleFields);
      
      // Also trigger Drupal states API if available
      if (typeof Drupal.states !== 'undefined') {
        setTimeout(function() {
          const changeEvent = new Event('change', { bubbles: true, cancelable: true });
          eventTypeSelect.dispatchEvent(changeEvent);
        }, 200);
      }
    }
  };

})(Drupal);
