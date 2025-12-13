<?php

declare(strict_types=1);

namespace Drupal\myeventlane_event\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to auto-populate store field when vendor is selected on event.
 */
final class EventVendorSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs an EventVendorSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      'entity.node.presave' => 'onNodePresave',
    ];
  }

  /**
   * Auto-populates store field when vendor is selected.
   *
   * @param mixed $event
   *   The entity presave event.
   */
  public function onNodePresave($event): void {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $event->getEntity();

    // Only process event nodes.
    if ($node->bundle() !== 'event' || !$node->hasField('field_event_vendor') || !$node->hasField('field_event_store')) {
      return;
    }

    // If vendor is set and store is empty, auto-populate store from vendor.
    if (!$node->get('field_event_vendor')->isEmpty() && $node->get('field_event_store')->isEmpty()) {
      $vendor_id = $node->get('field_event_vendor')->target_id;
      if ($vendor_id) {
        $vendor_storage = $this->entityTypeManager->getStorage('myeventlane_vendor');
        $vendor = $vendor_storage->load($vendor_id);

        if ($vendor && $vendor->hasField('field_vendor_store') && !$vendor->get('field_vendor_store')->isEmpty()) {
          $store = $vendor->get('field_vendor_store')->entity;
          if ($store) {
            $node->set('field_event_store', $store);
          }
        }
      }
    }

    // If vendor is changed, update store to match.
    if (!$node->get('field_event_vendor')->isEmpty() && !$node->isNew()) {
      $vendor_id = $node->get('field_event_vendor')->target_id;
      if ($vendor_id) {
        $vendor_storage = $this->entityTypeManager->getStorage('myeventlane_vendor');
        $vendor = $vendor_storage->load($vendor_id);

        if ($vendor && $vendor->hasField('field_vendor_store') && !$vendor->get('field_vendor_store')->isEmpty()) {
          $store = $vendor->get('field_vendor_store')->entity;
          $current_store_id = $node->get('field_event_store')->target_id ?? NULL;

          // Update store if it doesn't match vendor's store.
          if ($store && $current_store_id !== $store->id()) {
            $node->set('field_event_store', $store);
          }
        }
      }
    }
  }

}
