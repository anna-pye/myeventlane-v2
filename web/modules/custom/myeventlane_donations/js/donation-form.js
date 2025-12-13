/**
 * @file
 * Donation form JavaScript.
 */

(function ($, Drupal) {
  'use strict';

  /**
   * Handles preset button clicks and custom amount input.
   */
  Drupal.behaviors.donationForm = {
    attach: function (context, settings) {
      // Handle preset button clicks.
      $('.mel-donation-preset', context).once('donation-preset').on('click', function (e) {
        e.preventDefault();
        var $button = $(this);
        var amount = parseFloat($button.data('amount'));

        // Remove active class from all presets.
        $('.mel-donation-preset').removeClass('active');
        // Add active class to clicked preset.
        $button.addClass('active');
        // Clear custom amount.
        $('.mel-donation-custom-input').val('');
        // Set selected amount.
        $('input[name="selected_amount"]').val(amount);
      });

      // Handle custom amount input.
      $('.mel-donation-custom-input', context).once('donation-custom').on('input', function () {
        var $input = $(this);
        var amount = parseFloat($input.val()) || 0;

        // Remove active class from presets.
        $('.mel-donation-preset').removeClass('active');
        // Set selected amount.
        $('input[name="selected_amount"]').val(amount > 0 ? amount : '');
      });
    }
  };

})(jQuery, Drupal);
