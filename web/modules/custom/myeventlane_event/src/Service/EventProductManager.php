<?php

declare(strict_types=1);

namespace Drupal\myeventlane_event\Service;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

/**
 * Manages Commerce product creation and sync for MyEventLane events.
 *
 * This service automatically creates and manages ticket products for
 * RSVP events, ensuring every event that needs Commerce integration
 * has a linked product.
 */
final class EventProductManager {

  use StringTranslationTrait;

  /**
   * Constructs EventProductManager.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
    private readonly MessengerInterface $messenger,
  ) {}

  /**
   * Ensures an event has a linked RSVP product, creating if necessary.
   *
   * For RSVP events (field_event_type = 'rsvp'), this creates a hidden
   * Commerce product with a single $0 variation if one doesn't exist.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The product entity, or NULL if event doesn't need auto-creation.
   */
  public function ensureRsvpProduct(NodeInterface $event): ?object {
    if ($event->bundle() !== 'event') {
      return NULL;
    }

    $eventType = $event->get('field_event_type')->value ?? '';

    // Only auto-create for pure RSVP events (not paid, not both, not external).
    if ($eventType !== 'rsvp') {
      return NULL;
    }

    // Check if product already exists.
    if (!$event->get('field_product_target')->isEmpty()) {
      $product = $event->get('field_product_target')->entity;
      if ($product && $product->isPublished()) {
        return $product;
      }
    }

    // Create new RSVP product.
    return $this->createRsvpProduct($event);
  }

  /**
   * Creates an RSVP product for a new event (before event is saved).
   *
   * This is called during form submission to avoid field validation errors.
   *
   * @param string|null $eventTitle
   *   The event title. If empty, uses "Untitled Event".
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface|null
   *   The created product or NULL on failure.
   */
  public function createRsvpProductForNewEvent(?string $eventTitle): ?object {
    // Handle empty titles.
    if (empty($eventTitle)) {
      $eventTitle = 'Untitled Event';
    }
    $storeStorage = $this->entityTypeManager->getStorage('commerce_store');
    $stores = $storeStorage->loadByProperties(['is_default' => TRUE]);
    $store = reset($stores);

    if (!$store) {
      $stores = $storeStorage->loadMultiple();
      $store = reset($stores);
    }

    if (!$store) {
      $this->loggerFactory->get('myeventlane_event')->error('No Commerce store available for RSVP product creation.');
      return NULL;
    }

    try {
      // Create variation first.
      $variation = ProductVariation::create([
        'type' => 'ticket_variation',
        'sku' => 'rsvp-new-' . time() . '-' . rand(1000, 9999),
        'title' => 'Free RSVP',
        'price' => new Price('0', 'USD'),
        'status' => 1,
      ]);
      $variation->save();

      // Create product.
      $product = Product::create([
        'type' => 'ticket',
        'title' => $eventTitle . ' - RSVP',
        'stores' => [$store->id()],
        'variations' => [$variation],
        'status' => 1,
        'uid' => \Drupal::currentUser()->id(),
      ]);
      $product->save();

      $this->loggerFactory->get('myeventlane_event')->notice(
        'Created RSVP product @pid for new event "@title"',
        ['@pid' => $product->id(), '@title' => $eventTitle]
      );

      return $product;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('myeventlane_event')->error(
        'Failed to create RSVP product: @message',
        ['@message' => $e->getMessage()]
      );
      return NULL;
    }
  }

  /**
   * Creates a new RSVP product for an event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return \Drupal\commerce_product\Entity\ProductInterface
   *   The created product.
   */
  private function createRsvpProduct(NodeInterface $event): object {
    $storeStorage = $this->entityTypeManager->getStorage('commerce_store');
    $stores = $storeStorage->loadByProperties(['is_default' => TRUE]);
    $store = reset($stores);

    if (!$store) {
      // Fallback: load any store.
      $stores = $storeStorage->loadMultiple();
      $store = reset($stores);
    }

    // Create variation first (required for product).
    $variation = ProductVariation::create([
      'type' => 'ticket_variation',
      'sku' => 'rsvp-' . $event->id() . '-' . time(),
      'title' => 'Free RSVP',
      'price' => new Price('0', 'USD'),
      'status' => 1,
      'field_event' => ['target_id' => $event->id()],
    ]);
    $variation->save();

    // Create product.
    $product = Product::create([
      'type' => 'ticket',
      'title' => $event->label() . ' - RSVP',
      'stores' => [$store->id()],
      'variations' => [$variation],
      'status' => 1,
      'field_event' => ['target_id' => $event->id()],
      // Mark as auto-generated so we can hide it from vendor UI later.
      'uid' => $event->getOwnerId(),
    ]);
    $product->save();

    $this->loggerFactory->get('myeventlane_event')->notice(
      'Auto-created RSVP product @pid for event @eid (@title)',
      [
        '@pid' => $product->id(),
        '@eid' => $event->id(),
        '@title' => $event->label(),
      ]
    );

    return $product;
  }

  /**
   * Syncs product ↔ event relationship on event save.
   *
   * For RSVP events: ensures product exists and is linked.
   * For paid/both events: validates product link exists.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node being saved.
   */
  public function syncProductToEvent(NodeInterface $event): void {
    if ($event->bundle() !== 'event') {
      return;
    }

    $eventType = $event->get('field_event_type')->value ?? '';

    // RSVP events: link event to product if product exists.
    if ($eventType === 'rsvp') {
      if (!$event->get('field_product_target')->isEmpty()) {
        $product = $event->get('field_product_target')->entity;
        if ($product && $event->id()) {
          // Link product back to event if needed.
          if ($product->hasField('field_event')) {
            $productEventId = $product->get('field_event')->target_id;
            if ($productEventId != $event->id()) {
              $product->set('field_event', ['target_id' => $event->id()]);
              $product->save();
              
              $this->loggerFactory->get('myeventlane_event')->notice(
                'Linked RSVP product @pid to event @eid',
                ['@pid' => $product->id(), '@eid' => $event->id()]
              );
            }
          }
        }
      }
    }

    // Paid and Both events: ensure product link exists.
    // For events with ticket types, the product will be auto-created by
    // TicketTypeManager, so we don't show a warning if ticket types exist.
    if (in_array($eventType, ['paid', 'both'], TRUE)) {
      $hasTicketTypes = $event->hasField('field_ticket_types') 
        && !$event->get('field_ticket_types')->isEmpty();
      
      if ($event->get('field_product_target')->isEmpty() && !$hasTicketTypes) {
        $this->messenger->addWarning(
          $this->t('Please link a ticket product for this @type event, or define ticket types below.', [
            '@type' => $eventType === 'paid' ? 'paid' : 'hybrid',
          ])
        );
      }
    }
  }

  /**
   * Checks if a product is an auto-generated RSVP product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product entity.
   *
   * @return bool
   *   TRUE if this is an auto-generated RSVP product.
   */
  public function isAutoGeneratedRsvpProduct(object $product): bool {
    // Check if product has only one variation with $0 price.
    if ($product->bundle() !== 'ticket') {
      return FALSE;
    }

    $variations = $product->getVariations();
    if (count($variations) !== 1) {
      return FALSE;
    }

    $variation = reset($variations);
    $price = $variation->getPrice();

    return $price && $price->getNumber() === '0';
  }

}
