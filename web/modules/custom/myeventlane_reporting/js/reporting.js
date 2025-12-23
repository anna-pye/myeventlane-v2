/**
 * @file
 * Reporting module JavaScript.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Initialize Chart.js charts for reporting.
   */
  Drupal.behaviors.melReportingCharts = {
    attach: function (context, settings) {
      once('mel-reporting-charts', '.mel-chart-container', context).forEach(function (container) {
        const chartId = container.dataset.chartId;
        const chartType = container.dataset.chartType || 'line';
        const chartUrl = container.dataset.chartUrl;

        if (!chartUrl) {
          return;
        }

        // Check if Chart.js is loaded.
        if (typeof Chart === 'undefined') {
          console.warn('Chart.js not loaded');
          return;
        }

        // Fetch chart data.
        fetch(chartUrl)
          .then(response => response.json())
          .then(data => {
            const ctx = container.querySelector('canvas').getContext('2d');

            const config = {
              type: chartType,
              data: data,
              options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                  legend: {
                    display: true,
                  },
                },
                scales: chartType === 'line' ? {
                  y: {
                    beginAtZero: true,
                  },
                  y1: data.datasets && data.datasets.length > 1 ? {
                    type: 'linear',
                    display: true,
                    position: 'right',
                  } : undefined,
                } : undefined,
              },
            };

            new Chart(ctx, config);
          })
          .catch(error => {
            console.error('Error loading chart data:', error);
          });
      });
    },
  };

  /**
   * Initialize tabs.
   */
  Drupal.behaviors.melReportingTabs = {
    attach: function (context, settings) {
      once('mel-reporting-tabs', '.mel-reporting-tabs', context).forEach(function (tabsContainer) {
        const tabs = tabsContainer.querySelectorAll('.mel-reporting-tab');
        tabs.forEach(tab => {
          tab.addEventListener('click', function () {
            // Remove active class from all tabs.
            tabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab.
            this.classList.add('active');
          });
        });
      });
    },
  };

})(Drupal, once);
