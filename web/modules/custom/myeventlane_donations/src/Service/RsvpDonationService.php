<?php

declare(strict_types=1);

namespace Drupal\myeventlane_donations\Service;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\myeventlane_rsvp\Entity\RsvpSubmission;
use Drupal\node\NodeInterface;
use Drupal\price\Price;

/**
 * Service for handling RSVP attendee donations (attendee → vendor via Stripe Connect).
 */
final class RsvpDonationService {

  /**
   * Constructs a RsvpDonationService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cartProvider
   *   The cart provider.
   * @param \Drupal\commerce_cart\CartManagerInterface $cartManager
   *   The cart manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly CartProviderInterface $cartProvider,
    private readonly CartManagerInterface $cartManager,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Gets the logger for this service.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger.
   */
  private function logger(): \Psr\Log\LoggerInterface {
    return $this->loggerFactory->get('myeventlane_donations');
  }

  /**
   * Creates a donation order for an RSVP submission.
   *
   * @param \Drupal\myeventlane_rsvp\Entity\RsvpSubmission $submission
   *   The RSVP submission.
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   * @param float $amount
   *   The donation amount in AUD.
   *
   * @return \Drupal\commerce_order\Entity\OrderInterface|null
   *   The created order, or NULL if vendor store not found or Stripe not connected.
   *
   * @throws \Exception
   *   If order creation fails.
   */
  public function createDonationOrder(RsvpSubmission $submission, NodeInterface $event, float $amount): ?OrderInterface {
    // Get vendor store for this event.
    $store = $this->getVendorStore($event);
    if (!$store) {
      $this->logger()->warning('No store found for event @event_id', [
        '@event_id' => $event->id(),
      ]);
      return NULL;
    }

    // Verify Stripe Connect is enabled for this store.
    if (!$this->isStripeConnected($store)) {
      $this->logger()->warning('Stripe Connect not enabled for store @store_id', [
        '@store_id' => $store->id(),
      ]);
      return NULL;
    }

    // Get or create cart (anonymous cart for RSVP donations).
    $cart = $this->cartProvider->getCart('rsvp_donation', $store);

    if (!$cart) {
      $cart = $this->cartProvider->createCart('rsvp_donation', $store);
    }

    // Create order item.
    $orderItem = $this->createDonationOrderItem($amount, $event, $submission);
    $this->cartManager->addOrderItem($cart, $orderItem);

    $this->logger()->info('Created RSVP donation order @order_id for submission @submission_id: $@amount', [
      '@order_id' => $cart->id(),
      '@submission_id' => $submission->id(),
      '@amount' => number_format($amount, 2),
    ]);

    return $cart;
  }

  /**
   * Creates a donation order item.
   *
   * @param float $amount
   *   The donation amount in AUD.
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   * @param \Drupal\myeventlane_rsvp\Entity\RsvpSubmission $submission
   *   The RSVP submission.
   *
   * @return \Drupal\commerce_order\Entity\OrderItemInterface
   *   The created order item.
   */
  private function createDonationOrderItem(float $amount, NodeInterface $event, RsvpSubmission $submission): OrderItemInterface {
    $orderItemStorage = $this->entityTypeManager->getStorage('commerce_order_item');
    $orderItem = $orderItemStorage->create([
      'type' => 'rsvp_donation',
      'title' => 'Donation for ' . $event->label(),
      'unit_price' => new Price((string) $amount, 'AUD'),
      'quantity' => 1,
    ]);

    // Store event ID if field exists.
    if ($orderItem->hasField('field_target_event')) {
      $orderItem->set('field_target_event', ['target_id' => $event->id()]);
    }

    // Store RSVP submission ID in metadata.
    if ($orderItem->hasField('field_attendee_data')) {
      $metadata = [
        'donation_type' => 'rsvp',
        'rsvp_submission_id' => $submission->id(),
        'event_id' => $event->id(),
        'vendor_uid' => $event->getOwnerId(),
        'created_at' => time(),
      ];
      $orderItem->set('field_attendee_data', json_encode($metadata));
    }

    $orderItem->save();

    return $orderItem;
  }

  /**
   * Gets the vendor store for an event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface|null
   *   The vendor store, or NULL if not found.
   */
  private function getVendorStore(NodeInterface $event): ?StoreInterface {
    $vendorUid = (int) $event->getOwnerId();
    if ($vendorUid === 0) {
      return NULL;
    }

    $storeStorage = $this->entityTypeManager->getStorage('commerce_store');
    $storeIds = $storeStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $vendorUid)
      ->range(0, 1)
      ->execute();

    if (empty($storeIds)) {
      return NULL;
    }

    $store = $storeStorage->load(reset($storeIds));
    return $store instanceof StoreInterface ? $store : NULL;
  }

  /**
   * Checks if a store has Stripe Connect enabled.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   *
   * @return bool
   *   TRUE if Stripe Connect is enabled, FALSE otherwise.
   */
  private function isStripeConnected(StoreInterface $store): bool {
    // Check if charges are enabled (most reliable indicator).
    if ($store->hasField('field_stripe_charges_enabled') && !$store->get('field_stripe_charges_enabled')->isEmpty()) {
      return (bool) $store->get('field_stripe_charges_enabled')->value;
    }

    // Fallback: check connected flag.
    if ($store->hasField('field_stripe_connected') && !$store->get('field_stripe_connected')->isEmpty()) {
      return (bool) $store->get('field_stripe_connected')->value;
    }

    return FALSE;
  }

}
