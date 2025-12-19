/**
 * @file
 * Event wizard JavaScript.
 *
 * The wizard is server-authoritative (PHP controls visibility and validation).
 * JS only provides:
 * - Stepper click -> sets target step and triggers hidden AJAX submit.
 * - Focus management after AJAX rebuild.
 */

(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.melEventWizard = {
    attach: function (context) {
      const $wrapper = $(once('mel-event-wizard', '#mel-event-wizard-wrapper', context));
      if (!$wrapper.length) {
        return;
      }

      const $form = $wrapper.closest('form');
      const $target = $form.find('.js-mel-wizard-target-step');
      const $goto = $form.find('.js-mel-wizard-goto');

      // Stepper navigation.
      $form.on('click', '.mel-wizard-stepper__link', function (e) {
        e.preventDefault();

        const step = $(this).attr('data-step-target');
        if (!step || !$target.length || !$goto.length) {
          return;
        }

        $target.val(step);
        $goto.trigger('click');
      });

      // After AJAX replacement, focus the step title for accessibility.
      const $activeTitle = $wrapper.find('.mel-wizard-step__title').first();
      if ($activeTitle.length) {
        if (!$activeTitle.attr('tabindex')) {
          $activeTitle.attr('tabindex', '-1');
        }
        $activeTitle.trigger('focus');
      }
    }
  };

})(jQuery, Drupal, once);
