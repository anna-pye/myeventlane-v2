<?php

declare(strict_types=1);

namespace Drupal\myeventlane_boost\EventSubscriber;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to revoke boosts when payments are refunded or voided.
 */
final class BoostRefundSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a BoostRefundSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      'commerce_payment.refund.post_transition' => 'onRefundOrVoid',
      'commerce_payment.void.post_transition' => 'onRefundOrVoid',
    ];
  }

  /**
   * Handle refund or void transitions.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The workflow transition event.
   */
  public function onRefundOrVoid(WorkflowTransitionEvent $event): void {
    $payment = $event->getEntity();
    if (!$payment instanceof PaymentInterface) {
      return;
    }

    $order = $payment->getOrder();
    if (!$order instanceof OrderInterface) {
      return;
    }

    $this->logger->notice('Processing refund/void for payment @pid on order @oid', [
      '@pid' => $payment->id(),
      '@oid' => $order->id(),
    ]);

    $this->revokeBoostsFromOrder($order);
  }

  /**
   * Revoke boosts from all boost items in the order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   */
  private function revokeBoostsFromOrder(OrderInterface $order): void {
    $nodeStorage = $this->entityTypeManager->getStorage('node');

    foreach ($order->getItems() as $item) {
      if ($item->bundle() !== 'boost' || !$item->hasField('field_target_event')) {
        continue;
      }

      $targetId = $item->get('field_target_event')->target_id ?? NULL;
      if ($targetId === NULL) {
        continue;
      }

      $node = $nodeStorage->load($targetId);
      if (!$node instanceof NodeInterface || $node->bundle() !== 'event') {
        continue;
      }

      $node->set('field_promoted', 0);
      $node->set('field_promo_expires', NULL);
      $node->save();

      $this->logger->info('Revoked boost from event @nid due to refund/void on order @oid', [
        '@nid' => $targetId,
        '@oid' => $order->id(),
      ]);
    }
  }

}
