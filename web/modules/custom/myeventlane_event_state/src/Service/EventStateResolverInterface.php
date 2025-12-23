<?php

declare(strict_types=1);

namespace Drupal\myeventlane_event_state\Service;

use Drupal\node\NodeInterface;

/**
 * Interface for event state resolution.
 */
interface EventStateResolverInterface {

  /**
   * Resolves the effective state for an event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return string
   *   The state ID: draft, scheduled, live, sold_out, ended, cancelled, archived.
   */
  public function resolveState(NodeInterface $event): string;

  /**
   * Gets the sales start datetime for an event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return int|null
   *   Unix timestamp, or NULL if not set.
   */
  public function getSalesStart(NodeInterface $event): ?int;

  /**
   * Gets the sales end datetime for an event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return int|null
   *   Unix timestamp, or NULL if not set.
   */
  public function getSalesEnd(NodeInterface $event): ?int;

}
