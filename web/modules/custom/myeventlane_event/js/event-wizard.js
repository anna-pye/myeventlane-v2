/**
 * @file
 * MyEventLane Event Wizard controller.
 *
 * Responsibilities:
 * - Step navigation + persistence
 * - AJAX-safe initialization
 * - Save-per-step UX hooks
 *
 * IMPORTANT:
 * - This file MUST NOT contain address autocomplete logic.
 * - Location handling lives in myeventlane-location.js ONLY.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Wizard state helpers
   */
  function getWizardForm(context) {
    return (
      context.querySelector('form#event-wizard-form') ||
      context.querySelector('form[id*="event_wizard"]') ||
      context.querySelector('.mel-event-wizard form')
    );
  }

  function getCurrentStep(form) {
    return form.querySelector('[data-wizard-step].is-active');
  }

  function getStepIndex(stepEl) {
    return stepEl ? parseInt(stepEl.getAttribute('data-wizard-step'), 10) : null;
  }

  /**
   * Ensure Drupal detects changes before step submit
   */
  function triggerFormUpdated(form) {
    if (window.jQuery) {
      window.jQuery(form).trigger('formUpdated');
    }
  }

  /**
   * Attach handlers for wizard navigation
   */
  function initWizard(form) {
    if (!form) return;

    const steps = form.querySelectorAll('[data-wizard-step]');
    if (!steps.length) return;

    // Next / Continue buttons
    const nextButtons = form.querySelectorAll(
      'button[data-wizard-next], input[data-wizard-next]'
    );

    nextButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        triggerFormUpdated(form);
      });
    });

    // Back buttons
    const backButtons = form.querySelectorAll(
      'button[data-wizard-back], input[data-wizard-back]'
    );

    backButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        triggerFormUpdated(form);
      });
    });

    // Safety: before submit (final publish)
    form.addEventListener(
      'submit',
      () => {
        triggerFormUpdated(form);
      },
      true
    );
  }

  /**
   * Handle stepper button clicks (for EventWizardForm and EventFormAlter).
   */
  function initStepperButtons(context) {
    const buttons = once('mel-stepper-button', context.querySelectorAll('.js-mel-stepper-button'), context);
    
    // For EventWizardForm: handle clicks on step containers that trigger hidden submit buttons.
    buttons.forEach((button) => {
      // If it's a container (not a button element), find the hidden submit button inside.
      if (button.tagName !== 'BUTTON' && button.tagName !== 'INPUT') {
        const hiddenSubmit = button.querySelector('.js-mel-step-submit');
        if (hiddenSubmit) {
          button.addEventListener('click', (e) => {
            e.preventDefault();
            hiddenSubmit.click();
          });
          // Also handle keyboard navigation.
          button.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault();
              hiddenSubmit.click();
            }
          });
          return;
        }
      }
    });
    
    buttons.forEach((button) => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const targetStep = button.getAttribute('data-step-target');
        if (!targetStep) return;

        const form = button.closest('form');
        if (!form) return;

        // For EventWizardForm: find the stepper button's hidden field and click the button itself.
        // The button is already a submit button with gotoStep handler.
        const buttonStepField = button.querySelector('input[name="wizard_step"]');
        if (buttonStepField && buttonStepField.value === targetStep) {
          // Button already has the correct step value, just click it.
          button.click();
          return;
        }
        
        // Fallback: find any wizard_step field and set it, then find the matching button.
        const stepField = form.querySelector('input[name="wizard_step"]');
        if (stepField) {
          stepField.value = targetStep;
          // Find the button that matches this step and click it.
          const matchingButton = form.querySelector(`.js-mel-stepper-button[data-step-target="${targetStep}"]`);
          if (matchingButton) {
            matchingButton.click();
            return;
          }
        }
        // For EventFormAlter: use wizard_target_step mechanism.
        else {
          const targetField = form.querySelector('input[name="wizard_target_step"], .js-mel-wizard-target-step');
          if (targetField) {
            targetField.value = targetStep;
            const gotoButton = form.querySelector('.js-mel-wizard-goto, input[name*="goto"], button[name*="goto"]');
            if (gotoButton) {
              gotoButton.click();
            }
          }
        }
      });
    });
  }

  /**
   * Drupal behavior
   */
  Drupal.behaviors.myeventlaneEventWizard = {
    attach(context) {
      const forms = [];

      if (context.tagName === 'FORM') {
        forms.push(context);
      } else {
        forms.push(...context.querySelectorAll('form'));
      }

      for (const form of once('mel-event-wizard', forms, context)) {
        // Only attach to wizard forms
        if (
          form.id === 'event-wizard-form' ||
          form.classList.contains('mel-event-wizard') ||
          form.querySelector('[data-wizard-step]')
        ) {
          // Delay allows AJAX-rendered steps to exist
          setTimeout(() => initWizard(form), 50);
        }
      }

      // Initialize stepper buttons for both EventWizardForm and EventFormAlter.
      initStepperButtons(context);
    },
  };

})(window.Drupal || {}, window.once);