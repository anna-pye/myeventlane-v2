<?php

declare(strict_types=1);

namespace Drupal\myeventlane_vendor\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Boost / promotion status provider.
 */
final class BoostStatusService {

  /**
   * Constructs the service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly TimeInterface $time,
  ) {}

  /**
   * Returns current boost placements for an event.
   *
   * @param \Drupal\node\NodeInterface|null $event
   *   The event node, or NULL for all vendor events.
   *
   * @return array
   *   Array of boost status items.
   */
  public function getBoostStatuses(?NodeInterface $event = NULL): array {
    if ($event === NULL) {
      return $this->getAllActiveBoosts();
    }

    return $this->getEventBoostStatus($event);
  }

  /**
   * Get boost status for a single event.
   */
  private function getEventBoostStatus(NodeInterface $event): array {
    if ($event->bundle() !== 'event') {
      return [];
    }

    $promoted = (bool) ($event->get('field_promoted')->value ?? FALSE);
    $expiresValue = $event->get('field_promo_expires')->value ?? NULL;

    if (!$promoted || !$expiresValue) {
      return [];
    }

    try {
      $expires = new \DateTimeImmutable($expiresValue, new \DateTimeZone('UTC'));
      $now = new \DateTimeImmutable('@' . $this->time->getRequestTime());

      if ($expires <= $now) {
        return [
          [
            'label' => 'Homepage boost',
            'status' => 'Expired',
            'ends' => $expires->format('M j, Y'),
            'event_id' => $event->id(),
            'event_title' => $event->label(),
          ],
        ];
      }

      $diff = $now->diff($expires);
      $daysRemaining = $diff->days;

      return [
        [
          'label' => 'Homepage boost',
          'status' => 'Active',
          'ends' => $expires->format('M j, Y') . " ({$daysRemaining} days)",
          'event_id' => $event->id(),
          'event_title' => $event->label(),
        ],
      ];
    }
    catch (\Exception) {
      return [];
    }
  }

  /**
   * Get all active boosts for the current user's events.
   */
  private function getAllActiveBoosts(): array {
    $currentUser = \Drupal::currentUser();
    $now = new \DateTimeImmutable('@' . $this->time->getRequestTime());
    $nowFormatted = $now->format('Y-m-d\TH:i:s');

    // Query for promoted events owned by current user.
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $query = $nodeStorage->getQuery()
      ->condition('type', 'event')
      ->condition('uid', $currentUser->id())
      ->condition('field_promoted', 1)
      ->condition('field_promo_expires', $nowFormatted, '>')
      ->accessCheck(FALSE)
      ->sort('field_promo_expires', 'ASC');

    $nids = $query->execute();

    if (empty($nids)) {
      return [];
    }

    $events = $nodeStorage->loadMultiple($nids);
    $boosts = [];

    foreach ($events as $event) {
      $expiresValue = $event->get('field_promo_expires')->value;
      try {
        $expires = new \DateTimeImmutable($expiresValue, new \DateTimeZone('UTC'));
        $diff = $now->diff($expires);
        $daysRemaining = $diff->days;

        $boosts[] = [
          'label' => $event->label(),
          'status' => 'Active',
          'ends' => $expires->format('M j, Y') . " ({$daysRemaining} days left)",
          'event_id' => $event->id(),
          'event_title' => $event->label(),
          'boost_url' => "/event/{$event->id()}/boost",
        ];
      }
      catch (\Exception) {
        continue;
      }
    }

    return $boosts;
  }

  /**
   * Get events available for boosting (not currently boosted).
   */
  public function getBoostableEvents(): array {
    $currentUser = \Drupal::currentUser();
    $now = new \DateTimeImmutable('@' . $this->time->getRequestTime());
    $nowFormatted = $now->format('Y-m-d\TH:i:s');

    $nodeStorage = $this->entityTypeManager->getStorage('node');

    // Get all published events owned by user.
    $query = $nodeStorage->getQuery()
      ->condition('type', 'event')
      ->condition('uid', $currentUser->id())
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->sort('created', 'DESC');

    $nids = $query->execute();

    if (empty($nids)) {
      return [];
    }

    $events = $nodeStorage->loadMultiple($nids);
    $boostable = [];

    foreach ($events as $event) {
      $promoted = (bool) ($event->get('field_promoted')->value ?? FALSE);
      $expiresValue = $event->get('field_promo_expires')->value ?? NULL;

      $isCurrentlyBoosted = FALSE;
      if ($promoted && $expiresValue) {
        try {
          $expires = new \DateTimeImmutable($expiresValue, new \DateTimeZone('UTC'));
          $isCurrentlyBoosted = $expires > $now;
        }
        catch (\Exception) {
          // Invalid date.
        }
      }

      $boostable[] = [
        'id' => $event->id(),
        'title' => $event->label(),
        'is_boosted' => $isCurrentlyBoosted,
        'boost_url' => "/event/{$event->id()}/boost",
      ];
    }

    return $boostable;
  }

}

