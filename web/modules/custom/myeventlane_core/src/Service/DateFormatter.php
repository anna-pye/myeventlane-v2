<?php

declare(strict_types=1);

namespace Drupal\myeventlane_core\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Service for consistent date and time formatting across MyEventLane.
 */
final class DateFormatter {

  /**
   * Constructs a DateFormatter service.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The core date formatter service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(
    protected TimeInterface $time,
    protected DateFormatterInterface $dateFormatter,
    protected LanguageManagerInterface $languageManager,
  ) {}

  /**
   * Get the current request timestamp.
   *
   * @return int
   *   The current UNIX timestamp.
   */
  public function getCurrentTimestamp(): int {
    return $this->time->getRequestTime();
  }

  /**
   * Format a timestamp in a human-readable way.
   *
   * @param int $timestamp
   *   The UNIX timestamp to format.
   * @param string $type
   *   The date format type (e.g., 'short', 'medium', 'long', 'custom').
   * @param string $format
   *   A custom PHP date format string (only used when $type is 'custom').
   * @param string|null $timezone
   *   The timezone to use, or NULL for the site default.
   * @param string|null $langcode
   *   The language code, or NULL for the current language.
   *
   * @return string
   *   The formatted date string.
   */
  public function format(
    int $timestamp,
    string $type = 'medium',
    string $format = '',
    ?string $timezone = NULL,
    ?string $langcode = NULL,
  ): string {
    $langcode = $langcode ?? $this->languageManager->getCurrentLanguage()->getId();
    return $this->dateFormatter->format($timestamp, $type, $format, $timezone, $langcode);
  }

  /**
   * Format an event date range for display.
   *
   * @param int $startTimestamp
   *   The start UNIX timestamp.
   * @param int|null $endTimestamp
   *   The end UNIX timestamp, or NULL if same as start.
   * @param string $dateFormat
   *   The date format type.
   * @param string $timeFormat
   *   A custom time format string.
   *
   * @return string
   *   A formatted date range string (e.g., "Dec 15, 2025, 7:00 PM - 10:00 PM").
   */
  public function formatEventDateRange(
    int $startTimestamp,
    ?int $endTimestamp = NULL,
    string $dateFormat = 'medium',
    string $timeFormat = 'g:i A',
  ): string {
    $date = $this->format($startTimestamp, $dateFormat);
    $startTime = $this->format($startTimestamp, 'custom', $timeFormat);

    if ($endTimestamp === NULL || $endTimestamp === $startTimestamp) {
      return $date . ', ' . $startTime;
    }

    // Check if same day.
    $startDay = $this->format($startTimestamp, 'custom', 'Y-m-d');
    $endDay = $this->format($endTimestamp, 'custom', 'Y-m-d');

    if ($startDay === $endDay) {
      $endTime = $this->format($endTimestamp, 'custom', $timeFormat);
      return $date . ', ' . $startTime . ' – ' . $endTime;
    }

    // Different days.
    $endDate = $this->format($endTimestamp, $dateFormat);
    $endTime = $this->format($endTimestamp, 'custom', $timeFormat);
    return $date . ', ' . $startTime . ' – ' . $endDate . ', ' . $endTime;
  }

  /**
   * Get a relative time string (e.g., "2 hours ago", "in 3 days").
   *
   * @param int $timestamp
   *   The UNIX timestamp.
   * @param int|null $referenceTimestamp
   *   The reference timestamp, or NULL for current time.
   *
   * @return string
   *   A relative time string.
   */
  public function formatRelative(int $timestamp, ?int $referenceTimestamp = NULL): string {
    $referenceTimestamp = $referenceTimestamp ?? $this->getCurrentTimestamp();
    return $this->dateFormatter->formatTimeDiffSince($timestamp, [
      'granularity' => 2,
    ]);
  }

  /**
   * Check if a timestamp is in the past.
   *
   * @param int $timestamp
   *   The UNIX timestamp to check.
   *
   * @return bool
   *   TRUE if the timestamp is in the past, FALSE otherwise.
   */
  public function isPast(int $timestamp): bool {
    return $timestamp < $this->getCurrentTimestamp();
  }

  /**
   * Check if a timestamp is in the future.
   *
   * @param int $timestamp
   *   The UNIX timestamp to check.
   *
   * @return bool
   *   TRUE if the timestamp is in the future, FALSE otherwise.
   */
  public function isFuture(int $timestamp): bool {
    return $timestamp > $this->getCurrentTimestamp();
  }

}
