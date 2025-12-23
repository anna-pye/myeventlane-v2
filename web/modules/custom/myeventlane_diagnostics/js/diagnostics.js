/**
 * @file
 * Diagnostics widget JavaScript behavior.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Real-time diagnostics behavior for wizard steps.
   */
  Drupal.behaviors.melDiagnosticsWizard = {
    attach(context) {
      const wizards = once('mel-diagnostics-wizard', '.mel-event-form--wizard', context);
      wizards.forEach((wizard) => {
        const form = wizard.closest('form');
        if (!form) return;

        // Watch for step changes.
        const currentStepField = form.querySelector('.js-mel-wizard-current-step');
        if (!currentStepField) return;

        // Get event ID from form.
        const eventIdMatch = form.action.match(/\/node\/(\d+)\//);
        if (!eventIdMatch) return;

        const eventId = eventIdMatch[1];
        const diagnosticsWidget = wizard.querySelector('#mel-diagnostics-widget');

        if (!diagnosticsWidget) return;

        // Map wizard steps to diagnostic scopes.
        const stepScopeMap = {
          'basics': 'basics',
          'sales_visibility': 'sales_state',
          'tickets': 'tickets_rsvp',
          'capacity_waitlist': 'capacity',
          'review': null,
        };

        // Update diagnostics when step changes (watch for value changes).
        const updateOnStepChange = () => {
          const currentStep = currentStepField.value || currentStepField.getAttribute('value');
          const scope = stepScopeMap[currentStep] || null;

          if (scope) {
            updateDiagnostics(eventId, scope, diagnosticsWidget);
          }
        };

        // Watch for step changes via AJAX callbacks.
        const observer = new MutationObserver(() => {
          updateOnStepChange();
        });

        observer.observe(currentStepField, {
          attributes: true,
          attributeFilter: ['value'],
          childList: false,
          subtree: false,
        });

        // Also listen for form rebuilds via AJAX.
        const formObserver = new MutationObserver(() => {
          const newStepField = form.querySelector('.js-mel-wizard-current-step');
          if (newStepField && newStepField !== currentStepField) {
            updateOnStepChange();
          }
        });

        formObserver.observe(form, {
          childList: true,
          subtree: true,
        });

        // Initial load if on a scoped step.
        const initialStep = currentStepField.value;
        const initialScope = stepScopeMap[initialStep] || null;
        if (initialScope) {
          updateDiagnostics(eventId, initialScope, diagnosticsWidget);
        }
      });
    },
  };

  /**
   * Updates diagnostics widget via AJAX.
   */
  function updateDiagnostics(eventId, scope, container) {
    // Show loading state.
    const loadingHtml = '<div class="mel-diagnostics__loading">Loading diagnostics...</div>';
    container.innerHTML = loadingHtml;

    const url = `/vendor/events/${eventId}/diagnostics?scope=${scope}`;

    fetch(url, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/vnd.drupal-ajax',
      },
      credentials: 'same-origin',
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        // Process Drupal AJAX response.
        if (data && Array.isArray(data)) {
          data.forEach((command) => {
            if (command.command === 'insert' || command.command === 'html') {
              const selector = command.selector || '#mel-diagnostics-widget';
              const wrapper = container.querySelector(selector) || container;
              
              if (command.method === 'replace' || command.command === 'html') {
                wrapper.innerHTML = command.data;
              } else {
                wrapper.insertAdjacentHTML('beforeend', command.data);
              }
              
              // Re-attach behaviors to new content.
              Drupal.attachBehaviors(wrapper);
            }
          });
        }
      })
      .catch((error) => {
        console.error('Failed to update diagnostics:', error);
        container.innerHTML = '<div class="mel-diagnostics__error">Unable to load diagnostics. Please refresh the page.</div>';
      });
  }

})(Drupal, once);
