(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.myeventlaneCheckin = {
    attach: function (context, settings) {
      // Toggle check-in buttons.
      once('checkin-toggle', '.checkin-toggle-button', context).forEach(function (button) {
        button.addEventListener('click', function (e) {
          e.preventDefault();
          const attendeeId = button.dataset.attendeeId;
          const type = button.dataset.type;
          const eventId = settings.myeventlane_checkin?.eventId || null;

          if (!attendeeId || !eventId) {
            return;
          }

          // Disable button during request.
          button.disabled = true;
          button.textContent = Drupal.t('Processing...');

          fetch('/vendor/events/' + eventId + '/check-in/toggle/' + attendeeId + '?type=' + type, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
          })
            .then(function (response) {
              return response.json();
            })
            .then(function (data) {
              if (data.success) {
                // Reload page to update status.
                window.location.reload();
              } else {
                alert(Drupal.t('Failed to update check-in status.'));
                button.disabled = false;
                button.textContent = Drupal.t('Retry');
              }
            })
            .catch(function (error) {
              console.error('Error:', error);
              alert(Drupal.t('An error occurred. Please try again.'));
              button.disabled = false;
            });
        });
      });

      // Search functionality.
      const searchInput = once('checkin-search', '#checkin-search-input', context)[0];
      if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function () {
          clearTimeout(searchTimeout);
          const query = this.value.trim();

          searchTimeout = setTimeout(function () {
            if (query.length >= 2) {
              // Perform search.
              const eventId = settings.myeventlane_checkin?.eventId || null;
              if (eventId) {
                fetch('/vendor/events/' + eventId + '/check-in/search?q=' + encodeURIComponent(query))
                  .then(function (response) {
                    return response.json();
                  })
                  .then(function (data) {
                    // Update results display.
                    const resultsDiv = document.getElementById('checkin-search-results');
                    if (resultsDiv) {
                      // @todo: Render search results.
                    }
                  });
              }
            }
          }, 300);
        });
      }
    }
  };

})(Drupal, once);
