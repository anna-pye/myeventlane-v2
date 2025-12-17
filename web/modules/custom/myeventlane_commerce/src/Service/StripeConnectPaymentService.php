<?php

declare(strict_types=1);

namespace Drupal\myeventlane_commerce\Service;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\myeventlane_core\Service\StripeService;
use Psr\Log\LoggerInterface;

/**
 * Service for handling Stripe Connect payments for ticket sales.
 */
final class StripeConnectPaymentService {

  use StringTranslationTrait;

  /**
   * Constructs a StripeConnectPaymentService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\myeventlane_core\Service\StripeService $stripeService
   *   The Stripe service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly StripeService $stripeService,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Gets the Stripe Connect account ID for an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return string|null
   *   The Stripe account ID (acct_xxx), or NULL if not found or not needed.
   */
  public function getStripeAccountIdForOrder(OrderInterface $order): ?string {
    // Get the store from the order.
    $store = $order->getStore();
    if (!$store) {
      return NULL;
    }

    // Check if this is a Boost purchase (should use platform account, not Connect).
    foreach ($order->getItems() as $item) {
      if ($item->bundle() === 'boost') {
        // Boost purchases use platform account, not Connect.
        return NULL;
      }
    }

    // Check if order has paid items that require Connect.
    $hasPaidItems = FALSE;
    foreach ($order->getItems() as $item) {
      $purchasedEntity = $item->getPurchasedEntity();
      if ($purchasedEntity) {
        $price = $purchasedEntity->getPrice();
        if ($price && $price->getNumber() > 0) {
          $hasPaidItems = TRUE;
          break;
        }
      }
    }

    // If no paid items, no Connect needed.
    if (!$hasPaidItems) {
      return NULL;
    }

    // Get Stripe account ID from store.
    if ($store->hasField('field_stripe_account_id') && !$store->get('field_stripe_account_id')->isEmpty()) {
      $accountId = $store->get('field_stripe_account_id')->value;
      if (!empty($accountId)) {
        return $accountId;
      }
    }

    return NULL;
  }

  /**
   * Validates that an order can be processed with Stripe Connect.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return array{valid: bool, message: string|null}
   *   Validation result with 'valid' boolean and optional 'message'.
   */
  public function validateOrderForConnect(OrderInterface $order): array {
    // Check if order has paid items.
    $hasPaidItems = FALSE;
    $eventIds = [];

    foreach ($order->getItems() as $item) {
      // Skip Boost items (they use platform account).
      if ($item->bundle() === 'boost') {
        continue;
      }

      $purchasedEntity = $item->getPurchasedEntity();
      if ($purchasedEntity) {
        $price = $purchasedEntity->getPrice();
        if ($price && $price->getNumber() > 0) {
          $hasPaidItems = TRUE;

          // Get event ID from order item if available.
          if ($item->hasField('field_target_event') && !$item->get('field_target_event')->isEmpty()) {
            $eventIds[] = $item->get('field_target_event')->target_id;
          }
        }
      }
    }

    // If no paid items, validation passes (RSVP/free events).
    if (!$hasPaidItems) {
      return ['valid' => TRUE, 'message' => NULL];
    }

    // Check if store has Stripe Connect account.
    $store = $order->getStore();
    if (!$store) {
      return [
        'valid' => FALSE,
        'message' => $this->t('No store found for this order.'),
      ];
    }

    $accountId = $this->getStripeAccountIdForOrder($order);
    if (empty($accountId)) {
      // Get event title for better error message.
      $eventTitle = 'this event';
      if (!empty($eventIds)) {
        $eventNode = $this->entityTypeManager->getStorage('node')->load(reset($eventIds));
        if ($eventNode) {
          $eventTitle = $eventNode->label();
        }
      }

      return [
        'valid' => FALSE,
        'message' => $this->t('This event\'s organiser has not set up card payments yet. Please try another event or contact the organiser.'),
      ];
    }

    return ['valid' => TRUE, 'message' => NULL];
  }

  /**
   * Calculates application fee for an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param float $feePercentage
   *   Fee percentage (e.g., 0.03 for 3%).
   * @param int $fixedFeeCents
   *   Fixed fee in cents (e.g., 30 for $0.30).
   *
   * @return int
   *   Application fee in cents.
   */
  public function calculateApplicationFee(OrderInterface $order, float $feePercentage = 0.03, int $fixedFeeCents = 30): int {
    $totalAmount = 0;

    foreach ($order->getItems() as $item) {
      // Skip Boost items (they don't use Connect).
      if ($item->bundle() === 'boost') {
        continue;
      }

      $totalPrice = $item->getTotalPrice();
      if ($totalPrice) {
        // Convert to cents.
        $totalAmount += (int) round($totalPrice->getNumber() * 100);
      }
    }

    return $this->stripeService->calculateApplicationFee($totalAmount, $feePercentage, $fixedFeeCents);
  }

  /**
   * Gets payment intent parameters for Connect destination charge.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return array
   *   Parameters to add to PaymentIntent creation, or empty array if not needed.
   */
  public function getConnectPaymentIntentParams(OrderInterface $order): array {
    $accountId = $this->getStripeAccountIdForOrder($order);
    if (empty($accountId)) {
      return [];
    }

    $applicationFee = $this->calculateApplicationFee($order);

    return [
      'application_fee_amount' => $applicationFee,
      'transfer_data' => [
        'destination' => $accountId,
      ],
    ];
  }

}
