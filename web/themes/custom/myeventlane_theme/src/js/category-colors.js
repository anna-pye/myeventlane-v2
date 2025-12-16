/**
 * @file
 * Category color utilities for JavaScript.
 *
 * Provides access to category colors exported from PHP ColorService
 * via drupalSettings.
 */

/**
 * Gets the color for a category term name.
 *
 * @param {string} term
 *   The taxonomy term name (e.g., "Food & Drink", "Workshop").
 *
 * @returns {string}
 *   The hex color code, or a fallback color if not found.
 */
export function getColorForCategory(term) {
  const categoryColors = drupalSettings.myeventlane?.categoryColors || {};
  
  if (!term) {
    return '#cccccc';
  }

  // Normalize the term name.
  let key = term.toLowerCase();
  key = key.replace(/\s/g, '-');
  key = key.replace('&', 'and');
  key = key.replace(/[^a-z0-9-+]/g, '');

  // Try exact match first.
  if (categoryColors[key]) {
    return categoryColors[key];
  }

  // Try variations.
  const variations = [
    key.replace(/-/g, ''),
    key.replace('and', '&'),
  ];

  for (const variation of variations) {
    if (categoryColors[variation]) {
      return categoryColors[variation];
    }
  }

  // Fallback color.
  return '#cccccc';
}

/**
 * Gets all category colors.
 *
 * @returns {Object}
 *   Object mapping category keys to hex color codes.
 */
export function getAllCategoryColors() {
  return drupalSettings.myeventlane?.categoryColors || {};
}
