/**
 * Wizard JS (vendor form).
 *
 * Server-authoritative wizard:
 * - PHP controls which step is visible via CSS classes (.is-active, .is-hidden).
 * - JS only:
 *   - Stepper click -> set target step -> trigger hidden AJAX submit (goto).
 *   - Focus step title after rebuild for accessibility.
 *   - Rebind change/blur listeners after AJAX rebuilds.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.melEventWizard = {
    attach(context) {
      // Find wizard forms - support both old and new class names.
      const wrappers = once('mel-event-wizard', '.mel-event-form--wizard, .mel-event-wizard, form#event-wizard-form', context);
      wrappers.forEach((wrapper) => {
        const form = wrapper.closest('form') || (wrapper.tagName === 'FORM' ? wrapper : null);
        if (!form) return;

        const target = form.querySelector('.js-mel-wizard-target-step');
        const gotoBtn = form.querySelector('.js-mel-wizard-goto');
        if (!target || !gotoBtn) return;

        // Stepper click delegation.
        wrapper.addEventListener('click', (e) => {
          const btn = e.target.closest('.js-mel-stepper-button');
          if (!btn) return;

          e.preventDefault();
          e.stopPropagation();
          const step = btn.getAttribute('data-step-target');
          if (!step) return;

          target.value = step;
          gotoBtn.click();
        });
        
        // Rebind listeners after AJAX rebuilds.
        this.rebindStepListeners(form);
        
        // Ensure autocomplete is initialized on initial load.
        this.initializeAutocomplete(form);
      });

      // Focus management: after AJAX completes, focus the active step title.
      const titles = once('mel-event-wizard-focus', '.mel-wizard-step__title', context);
      titles.forEach((title) => {
        const panel = title.closest('.mel-wizard-step');
        if (panel && panel.classList.contains('is-active')) {
          title.setAttribute('tabindex', '-1');
          title.focus();
        }
      });
    },
    
    /**
     * Rebind change/blur listeners for fields in the active step.
     */
    rebindStepListeners(form) {
      // Find the active step panel.
      const activePanel = form.querySelector('.mel-wizard-step.is-active');
      if (!activePanel) {
        return;
      }

      const stepId = activePanel.getAttribute('data-step');
      if (!stepId) {
        return;
      }

      // Remove existing listeners by cloning and replacing.
      const inputs = activePanel.querySelectorAll('input, select, textarea');
      inputs.forEach((input) => {
        // Debounced update to avoid too many requests.
        let updateTimeout = null;
        
        const changeHandler = () => {
          clearTimeout(updateTimeout);
          updateTimeout = setTimeout(() => {
            this.updateDiagnostics(form, stepId);
          }, 500);
        };
        
        const blurHandler = () => {
          clearTimeout(updateTimeout);
          this.updateDiagnostics(form, stepId);
        };
        
        // Remove old listeners by cloning.
        const newInput = input.cloneNode(true);
        input.parentNode.replaceChild(newInput, input);
        
        // Add new listeners.
        newInput.addEventListener('change', changeHandler);
        newInput.addEventListener('blur', blurHandler);
      });
    },
    
    /**
     * Initialize autocomplete fields after form load or AJAX rebuild.
     */
    initializeAutocomplete(form) {
      // Find all autocomplete inputs in the form.
      const autocompleteInputs = form.querySelectorAll('input.form-autocomplete[data-autocomplete-path]');
      
      autocompleteInputs.forEach((input) => {
        // Check if autocomplete is already initialized.
        if (input.hasAttribute('data-autocomplete-processed')) {
          return;
        }
        
        // Trigger Drupal autocomplete behavior if available.
        if (typeof Drupal !== 'undefined' && Drupal.autocomplete && Drupal.autocomplete.attach) {
          try {
            Drupal.autocomplete.attach(input);
            input.setAttribute('data-autocomplete-processed', 'true');
          } catch (e) {
            console.warn('Failed to initialize autocomplete for input:', e);
          }
        }
      });
    },
    
    /**
     * Updates diagnostics widget based on current wizard step.
     */
    updateDiagnostics(form, stepId) {
      const stepScopeMap = {
        'basics': 'basics',
        'when-where': 'basics',
        'sales_visibility': 'sales_state',
        'tickets': 'tickets_rsvp',
        'capacity_waitlist': 'capacity',
        'review': null,
      };
      
      const scope = stepScopeMap[stepId] || null;
      
      const eventId = form.querySelector('input[name="nid[0][value]"], input[name="nid"]')?.value ||
                      form.querySelector('input[name="form_id"]')?.closest('form')?.dataset?.eventId;
      
      const widgetContainer = form.querySelector('#mel-diagnostics-widget, .mel-event-form__wizard-diagnostics');
      if (!widgetContainer) {
        return;
      }
      
      if (!eventId) {
        widgetContainer.innerHTML = '<div class="mel-diagnostics"><p class="mel-diagnostics__empty">Complete a step to see diagnostics.</p></div>';
        return;
      }
      
      let url = `/vendor/events/${eventId}/diagnostics/widget`;
      if (scope) {
        url += `?scope=${scope}`;
      }
      
      const currentContent = widgetContainer.innerHTML;
      if (!currentContent.includes('mel-diagnostics__loading')) {
        const loadingHtml = '<div class="mel-diagnostics"><div class="mel-diagnostics__loading">Loading checklist...</div></div>';
        widgetContainer.innerHTML = loadingHtml;
      }
      
      fetch(url, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'text/html, application/vnd.drupal-ajax',
        },
        credentials: 'same-origin',
        cache: 'no-cache',
      })
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP ${response.status}: Failed to fetch diagnostics`);
          }
          const contentType = response.headers.get('content-type');
          if (contentType && contentType.includes('application/json')) {
            return response.json().then(data => ({ type: 'json', data }));
          }
          return response.text().then(data => ({ type: 'html', data }));
        })
        .then(result => {
          if (!widgetContainer) {
            return;
          }
          
          if (result.type === 'json') {
            if (Array.isArray(result.data)) {
              result.data.forEach(command => {
                if (command.command === 'insert' || command.command === 'html' || command.command === 'replace') {
                  const selector = command.selector || '#mel-diagnostics-widget';
                  const target = document.querySelector(selector) || widgetContainer;
                  if (target) {
                    target.innerHTML = command.data;
                  }
                }
              });
            }
          } else {
            widgetContainer.innerHTML = result.data;
          }
          
          if (typeof Drupal !== 'undefined' && Drupal.attachBehaviors) {
            Drupal.attachBehaviors(widgetContainer);
          }
        })
        .catch(error => {
          console.warn('Failed to update diagnostics widget:', error);
          if (widgetContainer) {
            widgetContainer.innerHTML = '<div class="mel-diagnostics"><div class="mel-diagnostics__error">Unable to load diagnostics. Please refresh the page.</div></div>';
          }
        });
    }
  };

  // Rebind listeners after AJAX completes.
  if (typeof Drupal !== 'undefined' && Drupal.ajax) {
    const originalBeforeSerialize = Drupal.ajax.prototype.beforeSerialize;
    Drupal.ajax.prototype.beforeSerialize = function(element, options) {
      if (originalBeforeSerialize) {
        originalBeforeSerialize.call(this, element, options);
      }
    };
    
    const originalBeforeSend = Drupal.ajax.prototype.beforeSend;
    Drupal.ajax.prototype.beforeSend = function(xmlhttprequest, options) {
      if (originalBeforeSend) {
        originalBeforeSend.call(this, xmlhttprequest, options);
      }
    };
    
    const originalSuccess = Drupal.ajax.prototype.success;
    Drupal.ajax.prototype.success = function(response, status) {
      if (originalSuccess) {
        originalSuccess.call(this, response, status);
      }
      
      // After AJAX completes, rebind listeners and re-initialize autocomplete.
      setTimeout(() => {
        // Find the wizard form - try multiple selectors.
        const form = document.querySelector('form#event-wizard-form') || 
                     document.querySelector('.mel-event-wizard')?.closest('form') ||
                     document.querySelector('.mel-event-form--wizard')?.closest('form');
        
        if (form) {
          Drupal.behaviors.melEventWizard.rebindStepListeners(form);
          
          // Re-attach behaviors to re-initialize autocomplete fields.
          // This is critical for entity_autocomplete to work after AJAX rebuilds.
          if (typeof Drupal !== 'undefined' && Drupal.attachBehaviors) {
            // Attach behaviors to the entire form context to ensure autocomplete is re-initialized.
            Drupal.attachBehaviors(form, document);
          }
          
          // Explicitly re-initialize autocomplete after a short delay to ensure DOM is ready.
          setTimeout(() => {
            Drupal.behaviors.melEventWizard.initializeAutocomplete(form);
          }, 150);
        }
      }, 200);
    };
  }

})(Drupal, once);
