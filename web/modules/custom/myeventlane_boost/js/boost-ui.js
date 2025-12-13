(function (Drupal) {
  'use strict';

  Drupal.behaviors.melBoostSelect = {
    attach: function (context, settings) {
      // Find all boost rows in the current context.
      const rows = context.querySelectorAll('.boost-row');
      if (!rows.length) {
        return;
      }

      // Find radio buttons - they might be in a different part of the DOM.
      const form = context.querySelector('form') || document.querySelector('form.myeventlane-boost-select-form');
      if (!form) {
        return;
      }

      const radios = form.querySelectorAll('.mel-boost-radios input[type="radio"]');
      if (!radios.length) {
        return;
      }

      const setSelected = (vid) => {
        // Set the actual radio value.
        radios.forEach(r => {
          if (r.value === String(vid)) {
            r.checked = true;
            // Trigger change event for Drupal forms.
            r.dispatchEvent(new Event('change', { bubbles: true }));
          }
        });

        // Update visual state of all rows.
        rows.forEach(row => {
          const rowVid = row.getAttribute('data-variation-id');
          const isSelected = rowVid === String(vid);
          row.setAttribute('aria-checked', isSelected ? 'true' : 'false');
          
          // Toggle selected class - remove from all first.
          row.classList.remove('is-selected');
          
          // Add to selected row.
          if (isSelected) {
            row.classList.add('is-selected');
            // Force a reflow to ensure CSS updates are applied.
            void row.offsetHeight;
            
            // Ensure checkmark is visible
            const checkmark = row.querySelector('.boost-row__radio-checkmark');
            if (checkmark) {
              checkmark.style.display = 'block';
            }
          }
        });
      };

      // Initialize: select first radio if none is selected.
      const checked = form.querySelector('.mel-boost-radios input[type="radio"]:checked');
      if (!checked && radios.length > 0) {
        radios[0].checked = true;
        setSelected(radios[0].value);
      } else if (checked) {
        setSelected(checked.value);
      }

      // Make each row clickable to select.
      rows.forEach(row => {
        // Remove any existing listeners to prevent duplicates.
        const newRow = row.cloneNode(true);
        row.parentNode.replaceChild(newRow, row);

        const vid = newRow.getAttribute('data-variation-id');
        if (!vid) {
          return;
        }

        // Click handler - use capture phase to ensure it fires.
        const handleClick = (e) => {
          e.preventDefault();
          e.stopPropagation();
          setSelected(vid);
        };

        // Add click listener with capture to ensure it fires before other handlers.
        newRow.addEventListener('click', handleClick, true);

        // Keyboard support.
        newRow.addEventListener('keydown', (e) => {
          if (e.key === ' ' || e.key === 'Enter') {
            e.preventDefault();
            e.stopPropagation();
            setSelected(vid);
          }
        });

        // Ensure row is focusable and clickable.
        newRow.setAttribute('tabindex', '0');
        newRow.style.cursor = 'pointer';
        newRow.style.userSelect = 'none';
      });
    }
  };
})(Drupal);
