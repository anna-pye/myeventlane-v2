<?php

declare(strict_types=1);

namespace Drupal\myeventlane_checkin\Service;

use Drupal\node\NodeInterface;

/**
 * Interface for check-in storage operations.
 */
interface CheckInStorageInterface {

  /**
   * Gets all attendees for an event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return array
   *   Array of attendee data with id, name, email, checked_in, checked_in_at.
   */
  public function getAttendees(NodeInterface $event): array;

  /**
   * Toggles check-in status for an attendee.
   *
   * @param int $attendeeId
   *   The attendee ID (RSVP submission ID or event_attendee ID).
   * @param string $type
   *   'rsvp' or 'ticket'.
   * @param int $checkedInBy
   *   User ID of person performing check-in.
   *
   * @return bool
   *   New checked-in status.
   */
  public function toggleCheckIn(int $attendeeId, string $type, int $checkedInBy): bool;

  /**
   * Searches attendees by name or email.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   * @param string $query
   *   Search query.
   *
   * @return array
   *   Matching attendees.
   */
  public function searchAttendees(NodeInterface $event, string $query): array;

}
