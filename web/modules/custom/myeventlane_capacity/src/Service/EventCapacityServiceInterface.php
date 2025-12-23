<?php

declare(strict_types=1);

namespace Drupal\myeventlane_capacity\Service;

use Drupal\node\NodeInterface;

/**
 * Interface for event capacity management.
 */
interface EventCapacityServiceInterface {

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
   * Gets the number of tickets/RSVPs sold/confirmed.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return int
   *   Count of sold/confirmed tickets/RSVPs.
   */
  public function getSoldCount(NodeInterface $event): int;

  /**
   * Gets the remaining capacity.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return int|null
   *   Remaining capacity, or NULL if unlimited.
   */
  public function getRemaining(NodeInterface $event): ?int;

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
   * Asserts that the event can accept the requested booking.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   * @param int $requested
   *   Number of tickets/RSVPs requested.
   *
   * @throws \Drupal\myeventlane_capacity\Exception\CapacityExceededException
   *   If capacity would be exceeded.
   */
  public function assertCanBook(NodeInterface $event, int $requested = 1): void;

}
