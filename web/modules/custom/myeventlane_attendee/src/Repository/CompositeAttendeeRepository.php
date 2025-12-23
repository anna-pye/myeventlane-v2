<?php

declare(strict_types=1);

namespace Drupal\myeventlane_attendee\Repository;

use Drupal\myeventlane_attendee\Attendee\AttendeeInterface;
use Drupal\node\NodeInterface;

/**
 * Composite repository that combines multiple source repositories.
 */
final class CompositeAttendeeRepository implements AttendeeRepositoryInterface {

  /**
   * The repositories to combine.
   *
   * @var \Drupal\myeventlane_attendee\Repository\AttendeeRepositoryInterface[]
   */
  private array $repositories = [];

  /**
   * Constructs the composite repository.
   *
   * @param \Drupal\myeventlane_attendee\Repository\AttendeeRepositoryInterface[] $repositories
   *   Array of repositories to combine.
   */
  public function __construct(array $repositories) {
    $this->repositories = $repositories;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceType(): string {
    return 'mixed';
  }

  /**
   * {@inheritdoc}
   */
  public function supports(NodeInterface $event): bool {
    // Composite always supports if any sub-repository supports.
    foreach ($this->repositories as $repository) {
      if ($repository->supports($event)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEvent(NodeInterface $event): array {
    $attendees = [];
    foreach ($this->repositories as $repository) {
      if ($repository->supports($event)) {
        $attendees = array_merge($attendees, $repository->loadByEvent($event));
      }
    }
    return $attendees;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByIdentifier(NodeInterface $event, string $identifier): ?AttendeeInterface {
    foreach ($this->repositories as $repository) {
      if ($repository->supports($event)) {
        $attendee = $repository->loadByIdentifier($event, $identifier);
        if ($attendee !== NULL) {
          return $attendee;
        }
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function countByEvent(NodeInterface $event): int {
    $count = 0;
    foreach ($this->repositories as $repository) {
      if ($repository->supports($event)) {
        $count += $repository->countByEvent($event);
      }
    }
    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function countCheckedIn(NodeInterface $event): int {
    $count = 0;
    foreach ($this->repositories as $repository) {
      if ($repository->supports($event)) {
        $count += $repository->countCheckedIn($event);
      }
    }
    return $count;
  }

}
