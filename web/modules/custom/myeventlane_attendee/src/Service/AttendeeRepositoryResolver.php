<?php

declare(strict_types=1);

namespace Drupal\myeventlane_attendee\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\myeventlane_attendee\Repository\AttendeeRepositoryInterface;
use Drupal\myeventlane_attendee\Repository\CompositeAttendeeRepository;
use Drupal\myeventlane_event_state\Service\EventStateResolverInterface;
use Drupal\node\NodeInterface;

/**
 * Resolves the appropriate attendee repository for an event.
 */
final class AttendeeRepositoryResolver {

  /**
   * The repositories, keyed by source type.
   *
   * @var \Drupal\myeventlane_attendee\Repository\AttendeeRepositoryInterface[]
   */
  private array $repositories = [];

  /**
   * Constructs the resolver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\myeventlane_event_state\Service\EventStateResolverInterface $stateResolver
   *   The event state resolver.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EventStateResolverInterface $stateResolver,
  ) {}

  /**
   * Adds a repository to the resolver.
   *
   * Called via service collector tag.
   *
   * @param \Drupal\myeventlane_attendee\Repository\AttendeeRepositoryInterface $repository
   *   The repository.
   * @param string $id
   *   The service ID.
   */
  public function addRepository(AttendeeRepositoryInterface $repository, string $id): void {
    $this->repositories[$repository->getSourceType()] = $repository;
  }

  /**
   * Gets the appropriate repository for an event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return \Drupal\myeventlane_attendee\Repository\AttendeeRepositoryInterface
   *   The repository.
   */
  public function getRepository(NodeInterface $event): AttendeeRepositoryInterface {
    $supporting = [];
    foreach ($this->repositories as $repository) {
      if ($repository->supports($event)) {
        $supporting[] = $repository;
      }
    }

    // If no repositories support this event, return an empty composite.
    if (empty($supporting)) {
      return new CompositeAttendeeRepository([]);
    }

    // If only one repository supports it, return that one.
    if (count($supporting) === 1) {
      return reset($supporting);
    }

    // If multiple repositories support it, return a composite.
    return new CompositeAttendeeRepository($supporting);
  }

}
