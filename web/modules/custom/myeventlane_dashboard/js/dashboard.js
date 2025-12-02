/**
 * @file
 * JavaScript behaviors for MyEventLane Dashboard.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Dashboard behaviors.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.myeventlaneDashboard = {
    attach: function (context) {
      // Add smooth transitions to stat cards on load.
      once('dashboard-stats', '.mel-stat-card', context).forEach(function (card) {
        card.style.opacity = '0';
        card.style.transform = 'translateY(10px)';

        requestAnimationFrame(function () {
          card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        });
      });
    }
  };

})(Drupal, once);
