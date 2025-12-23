<?php

declare(strict_types=1);

namespace Drupal\myeventlane_metrics\Service;

use Drupal\commerce_price\Price;
use Drupal\node\NodeInterface;

/**
 * Interface for event metrics service.
 */
interface EventMetricsServiceInterface {

  /**
   * Gets the total capacity for an event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return int|null
   *   Total capacity, or NULL if unlimited.
   */
  public function getCapacityTotal(NodeInterface $event): ?int;

  /**
   * Gets the total attendee count for an event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return int
   *   The total number of attendees.
   */
  public function getAttendeeCount(NodeInterface $event): int;

  /**
   * Gets the checked-in attendee count for an event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return int
   *   The number of checked-in attendees.
   */
  public function getCheckedInCount(NodeInterface $event): int;

  /**
   * Gets the remaining capacity for an event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return int|null
   *   Remaining capacity, or NULL if unlimited.
   */
  public function getRemainingCapacity(NodeInterface $event): ?int;

  /**
   * Checks if the event is sold out.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return bool
   *   TRUE if sold out.
   */
  public function isSoldOut(NodeInterface $event): bool;

  /**
   * Gets the total revenue for an event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return \Drupal\commerce_price\Price|null
   *   The total revenue, or NULL if not applicable (RSVP-only events).
   */
  public function getRevenue(NodeInterface $event): ?Price;

  /**
   * Gets ticket breakdown by type.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return array
   *   Array of ticket type data with keys:
   *   - label (string): Ticket type label
   *   - sold (int): Number sold
   *   - revenue (Price): Revenue from this type
   */
  public function getTicketBreakdown(NodeInterface $event): array;

  /**
   * Gets the check-in rate (percentage).
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return float|null
   *   Check-in rate as a percentage (0-100), or NULL if no attendees.
   */
  public function getCheckInRate(NodeInterface $event): ?float;

}
