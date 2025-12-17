<?php

declare(strict_types=1);

namespace Drupal\myeventlane_shared\Service;

/**
 * Provides category color definitions for taxonomy-based UI rendering.
 *
 * Centralizes color mapping for event categories to ensure consistency
 * across PHP, JavaScript, and SCSS implementations.
 */
class ColorService {

  /**
   * Gets the complete category color map.
   *
   * @return array
   *   Associative array mapping category keys to hex color codes.
   */
  public function getCategoryColors(): array {
    return [
      'music' => '#A8C8FF',
      'workshop' => '#FFE2B2',
      'food-drink' => '#FBC6C6',
      // Common normalized variant used by Twig/PHP normalization.
      'food-and-drink' => '#FBC6C6',
      'food' => '#FBC6C6',
      'lgbtq' => '#D6C6FF',
      'lgbtqi+' => '#D6C6FF',
      'arts' => '#B7F1DC',
      'movie' => '#F4B9EF',
      'community' => '#BBE9F3',
      'sports' => '#FFE2B2',
      'tech' => '#A8C8FF',
      'education' => '#B7F1DC',
      'wellness' => '#B7F1DC',
    ];
  }

  /**
   * Gets the color for a specific taxonomy term name.
   *
   * Normalizes the term name by:
   * - Converting to lowercase
   * - Replacing spaces with hyphens
   * - Replacing '&' with 'and'
   *
   * @param string $term_name
   *   The taxonomy term name (e.g., "Food & Drink", "Workshop").
   *
   * @return string|null
   *   The hex color code, or NULL if no match found.
   */
  public function getColorForTerm(string $term_name): ?string {
    // Normalize the term name.
    $key = strtolower($term_name);
    $key = str_replace([' ', '&'], ['-', 'and'], $key);
    $key = preg_replace('/[^a-z0-9-+]/', '', $key);

    $map = $this->getCategoryColors();

    // Try exact match first.
    if (isset($map[$key])) {
      return $map[$key];
    }

    // Try variations (e.g., "food-drink" vs "food & drink").
    $variations = [
      str_replace('-', '', $key),
      str_replace('and', '&', $key),
    ];

    foreach ($variations as $variation) {
      if (isset($map[$variation])) {
        return $map[$variation];
      }
    }

    return NULL;
  }

  /**
   * Gets a fallback color for unknown categories.
   *
   * @return string
   *   A default hex color code.
   */
  public function getFallbackColor(): string {
    return '#cccccc';
  }

}
