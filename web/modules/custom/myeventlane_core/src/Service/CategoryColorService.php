<?php

declare(strict_types=1);

namespace Drupal\myeventlane_core\Service;

/**
 * Provides stable, label-based category colors.
 */
final class CategoryColorService {

  public const DEFAULT_PALETTE = [
    '#F26D5B',
    '#6E7EF2',
    '#2BB673',
    '#F6C445',
    '#9B5DE5',
    '#00BBF9',
    '#F15BB5',
    '#00F5D4',
  ];

  /**
   * Explicit map of slug => hex color.
   *
   * @var array<string, string>
   */
  private array $map;

  /**
   * Constructs the service.
   */
  public function __construct() {
    $this->map = [
      // Keyed by slug of term name, lowercased, hyphenated.
      // Add your known categories here for stable colours.
      // Example:
      // 'music' => '#6E7EF2'.
      // 'workshops' => '#F6C445'.
      // 'lgbtq' => '#F15BB5'.
      // 'online' => '#00BBF9'.
    ];
  }

  /**
   * Returns a stable color for a label.
   */
  public function getColorForLabel(string $label, int $index = 0): string {
    $slug = $this->slugify($label);
    if (isset($this->map[$slug])) {
      return $this->map[$slug];
    }
    $palette = self::DEFAULT_PALETTE;
    return $palette[$index % count($palette)];
  }

  /**
   * Overrides the internal slug => color mapping.
   *
   * @param array<string, string> $map
   *   Map keyed by slug of the label.
   */
  public function setMap(array $map): void {
    $this->map = $map;
  }

  /**
   * Converts a label to a stable slug key.
   */
  private function slugify(string $label): string {
    $label = mb_strtolower(trim($label));
    $label = preg_replace('/[^a-z0-9]+/u', '-', $label) ?? $label;
    $label = trim($label, '-');
    return $label;
  }

}
