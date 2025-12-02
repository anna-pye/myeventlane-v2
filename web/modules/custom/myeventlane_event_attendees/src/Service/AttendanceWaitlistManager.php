<?php

declare(strict_types=1);

namespace Drupal\myeventlane_event_attendees\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\myeventlane_event_attendees\Entity\EventAttendee;

/**
 * Waitlist management service for MyEventLane.
 *
 * Handles waitlist-specific operations such as auto-promotion when spots
 * become available.
 */
final class AttendanceWaitlistManager {

  /**
   * Constructs AttendanceWaitlistManager.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly AttendanceManagerInterface $attendanceManager,
  ) {}

  /**
   * Gets all waitlisted attendees for an event.
   *
   * @param int $eventId
   *   The event node ID.
   *
   * @return \Drupal\myeventlane_event_attendees\Entity\EventAttendee[]
   *   Array of waitlisted attendees, ordered by creation time (FIFO).
   */
  public function getWaitlist(int $eventId): array {
    return $this->attendanceManager->getAttendeesForEvent(
      $eventId,
      EventAttendee::STATUS_WAITLIST
    );
  }

  /**
   * Gets the waitlist position for an attendee.
   *
   * @param \Drupal\myeventlane_event_attendees\Entity\EventAttendee $attendee
   *   The attendee to check.
   *
   * @return int|null
   *   The position (1-based) or NULL if not waitlisted.
   */
  public function getWaitlistPosition(EventAttendee $attendee): ?int {
    if (!$attendee->isWaitlisted()) {
      return NULL;
    }

    $eventId = $attendee->getEventId();
    if ($eventId === NULL) {
      return NULL;
    }

    $waitlist = $this->getWaitlist($eventId);
    $position = 1;

    foreach ($waitlist as $waitlisted) {
      if ((int) $waitlisted->id() === (int) $attendee->id()) {
        return $position;
      }
      $position++;
    }

    return NULL;
  }

  /**
   * Gets the waitlist count for an event.
   *
   * @param int $eventId
   *   The event node ID.
   *
   * @return int
   *   Number of people on the waitlist.
   */
  public function getWaitlistCount(int $eventId): int {
    return $this->attendanceManager->getAttendeeCount(
      $eventId,
      [EventAttendee::STATUS_WAITLIST]
    );
  }

  /**
   * Promotes attendees from waitlist when capacity becomes available.
   *
   * @param int $eventId
   *   The event node ID.
   * @param int $spots
   *   Number of spots to fill from waitlist.
   *
   * @return \Drupal\myeventlane_event_attendees\Entity\EventAttendee[]
   *   Array of promoted attendees.
   */
  public function promoteMultiple(int $eventId, int $spots = 1): array {
    $promoted = [];

    for ($i = 0; $i < $spots; $i++) {
      $attendee = $this->attendanceManager->promoteFromWaitlist($eventId);
      if ($attendee === NULL) {
        break;
      }
      $promoted[] = $attendee;
    }

    return $promoted;
  }

  /**
   * Checks if an event has a waitlist enabled.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return bool
   *   TRUE if waitlist is enabled.
   */
  public function isWaitlistEnabled(object $event): bool {
    // Check if the event has a waitlist field and it's enabled.
    if ($event->hasField('field_waitlist_enabled') && !$event->get('field_waitlist_enabled')->isEmpty()) {
      return (bool) $event->get('field_waitlist_enabled')->value;
    }

    // Default to TRUE if field doesn't exist (backward compatibility).
    return TRUE;
  }

}
