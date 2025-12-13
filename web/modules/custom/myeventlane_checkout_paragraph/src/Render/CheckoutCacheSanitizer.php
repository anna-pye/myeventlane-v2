<?php

declare(strict_types=1);

namespace Drupal\myeventlane_checkout_paragraph\Render;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Normalizes cache metadata for checkout pane render arrays.
 */
final class CheckoutCacheSanitizer implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['preRender'];
  }

  /**
   * Normalizes cache keys on the render array.
   *
   * @param array $element
   *   The render array element.
   *
   * @return array
   *   The normalized render array.
   */
  public static function preRender(array $element): array {
    // Move any stray 'max_age', 'contexts', 'tags' under a single '#cache'.
    // Also flatten nested '#cache' => ['#cache' => …].
    self::normalize($element);
    return $element;
  }

  /**
   * Recursively normalizes cache metadata in a render array.
   *
   * @param array $element
   *   The render array element to normalize (by reference).
   * @param int $depth
   *   Recursion depth guard.
   */
  private static function normalize(array &$element, int $depth = 0): void {
    if (!is_array($element) || $depth > 40) {
      return;
    }

    // Flatten accidentally nested #cache.
    if (isset($element['#cache']['#cache']) && is_array($element['#cache']['#cache'])) {
      self::mergeCache($element['#cache'], $element['#cache']['#cache']);
      unset($element['#cache']['#cache']);
    }

    // Hoist any top-level cache keys.
    foreach (['max_age', 'contexts', 'tags'] as $key) {
      if (array_key_exists($key, $element)) {
        $element['#cache'] = isset($element['#cache']) && is_array($element['#cache']) ? $element['#cache'] : [];
        if ($key === 'max_age') {
          $element['#cache']['max_age'] = (int) $element[$key];
        }
        elseif ($key === 'contexts') {
          $element['#cache']['contexts'] = self::uniqList(array_merge((array) ($element['#cache']['contexts'] ?? []), (array) $element[$key]));
        }
        else {
          $element['#cache']['tags'] = self::uniqList(array_merge((array) ($element['#cache']['tags'] ?? []), (array) $element[$key]));
        }
        unset($element[$key]);
      }
    }

    // Coerce types again.
    if (isset($element['#cache'])) {
      if (isset($element['#cache']['max_age'])) {
        $element['#cache']['max_age'] = (int) $element['#cache']['max_age'];
      }
      foreach (['contexts', 'tags'] as $list_key) {
        if (isset($element['#cache'][$list_key])) {
          $element['#cache'][$list_key] = self::uniqList((array) $element['#cache'][$list_key]);
        }
      }
    }

    // Recurse into children (non-# keys).
    foreach ($element as $child_key => &$child) {
      if ($child_key !== '#cache' && is_array($child) && !str_starts_with((string) $child_key, '#')) {
        self::normalize($child, $depth + 1);
      }
    }
    unset($child);
  }

  /**
   * Merges cache metadata arrays.
   *
   * @param array $destination
   *   Destination cache array to merge into (by reference).
   * @param array $source
   *   Source cache array.
   */
  private static function mergeCache(array &$destination, array $source): void {
    if (array_key_exists('max_age', $source)) {
      $destination['max_age'] = isset($destination['max_age'])
        ? min((int) $destination['max_age'], (int) $source['max_age'])
        : (int) $source['max_age'];
    }
    foreach (['contexts', 'tags'] as $list_key) {
      if (isset($source[$list_key])) {
        $destination[$list_key] = self::uniqList(array_merge((array) ($destination[$list_key] ?? []), (array) $source[$list_key]));
      }
    }
  }

  /**
   * Returns a de-duplicated, trimmed list.
   *
   * @param array $list
   *   List to normalize.
   *
   * @return array
   *   Unique list of scalar values as strings.
   */
  private static function uniqList(array $list): array {
    $out = [];
    foreach ($list as $value) {
      if (is_scalar($value)) {
        $string_value = trim((string) $value);
        if ($string_value !== '') {
          $out[$string_value] = TRUE;
        }
      }
    }
    return array_keys($out);
  }

}
