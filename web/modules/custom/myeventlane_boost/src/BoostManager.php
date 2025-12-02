<?php

declare(strict_types=1);

namespace Drupal\myeventlane_boost;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Service to apply and manage boost status on events.
 */
final class BoostManager {

  /**
   * Constructs a BoostManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly TimeInterface $time,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Apply or extend a boost on an event node.
   *
   * @param int $eventNid
   *   The event node ID.
   * @param int $days
   *   Number of days to boost for.
   */
  public function applyBoost(int $eventNid, int $days): void {
    $event = $this->entityTypeManager->getStorage('node')->load($eventNid);
    if (!$event instanceof NodeInterface || $event->bundle() !== 'event') {
      $this->logger->warning('Attempted to boost invalid node @nid', ['@nid' => $eventNid]);
      return;
    }

    $now = new \DateTimeImmutable('@' . $this->time->getRequestTime());
    $currentValue = $event->get('field_promo_expires')->value ?? NULL;

    $base = $now;
    if ($currentValue) {
      try {
        $existing = new \DateTimeImmutable($currentValue, new \DateTimeZone('UTC'));
        if ($existing > $now) {
          $base = $existing;
        }
      }
      catch (\Exception) {
        // Invalid date, use now.
      }
    }

    $expires = $base->modify(sprintf('+%d days', max(1, $days)))
      ->setTimezone(new \DateTimeZone('UTC'));

    $event->set('field_promoted', 1);
    $event->set('field_promo_expires', $expires->format('Y-m-d\TH:i:s'));
    $event->save();

    $this->logger->info('Applied/Extended Boost: event @nid +@days days (until @exp)', [
      '@nid' => $eventNid,
      '@days' => $days,
      '@exp' => $expires->format(\DATE_ATOM),
    ]);
  }

  /**
   * Revoke a boost from an event node.
   *
   * @param int $eventNid
   *   The event node ID.
   */
  public function revokeBoost(int $eventNid): void {
    $event = $this->entityTypeManager->getStorage('node')->load($eventNid);
    if (!$event instanceof NodeInterface || $event->bundle() !== 'event') {
      return;
    }

    $event->set('field_promoted', 0);
    $event->set('field_promo_expires', NULL);
    $event->save();

    $this->logger->info('Revoked boost from event @nid', ['@nid' => $eventNid]);
  }

  /**
   * Check if an event is currently boosted.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return bool
   *   TRUE if boosted and not expired.
   */
  public function isBoosted(NodeInterface $event): bool {
    if ($event->bundle() !== 'event') {
      return FALSE;
    }

    $promoted = (bool) $event->get('field_promoted')->value;
    if (!$promoted) {
      return FALSE;
    }

    $expiresValue = $event->get('field_promo_expires')->value ?? NULL;
    if ($expiresValue === NULL) {
      return FALSE;
    }

    try {
      $expires = new \DateTimeImmutable($expiresValue, new \DateTimeZone('UTC'));
      $now = new \DateTimeImmutable('@' . $this->time->getRequestTime());
      return $expires > $now;
    }
    catch (\Exception) {
      return FALSE;
    }
  }

}
