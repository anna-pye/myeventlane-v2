<?php

declare(strict_types=1);

namespace Drupal\myeventlane_attendee\Repository;

use Drupal\myeventlane_attendee\Attendee\AttendeeInterface;
use Drupal\node\NodeInterface;

/**
 * Repository interface for loading attendees for an event.
 */
interface AttendeeRepositoryInterface {

  /**
   * Gets the source type this repository handles.
   *
   * @return string
   *   Either 'rsvp' or 'ticket'.
   */
  public function getSourceType(): string;

  /**
   * Checks if this repository can handle the given event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return bool
   *   TRUE if this repository should be used for this event.
   */
  public function supports(NodeInterface $event): bool;

  /**
   * Loads all attendees for an event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return array<AttendeeInterface>
   *   Array of attendee objects.
   */
  public function loadByEvent(NodeInterface $event): array;

  /**
   * Loads a single attendee by identifier.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   * @param string $identifier
   *   The identifier (email, ticket code, or attendee ID).
   *
   * @return \Drupal\myeventlane_attendee\Attendee\AttendeeInterface|null
   *   The attendee, or NULL if not found.
   */
  public function loadByIdentifier(NodeInterface $event, string $identifier): ?AttendeeInterface;

  /**
   * Counts total attendees for an event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return int
   *   The count.
   */
  public function countByEvent(NodeInterface $event): int;

  /**
   * Counts checked-in attendees for an event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return int
   *   The count of checked-in attendees.
   */
  public function countCheckedIn(NodeInterface $event): int;

}
