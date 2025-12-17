<?php

namespace Drupal\myeventlane_wallet\EventSubscriber;

use Drupal\Core\Entity\EntityEvents;
use Drupal\Core\Entity\EntityEvent;
use Drupal\Core\Entity\EntityEventSubscriberInterface;

/**
 * Event subscriber for ticket-related entity updates.
 */
class TicketUpdatedSubscriber implements EntityEventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[EntityEvents::UPDATE][] = ['onUpdate'];
    return $events;
  }

  /**
   * Handles entity update events.
   *
   * @param \Drupal\Core\Entity\EntityEvent $event
   *   The entity event.
   */
  public function onUpdate(EntityEvent $event) {
    $entity = $event->getEntity();

    if ($entity->getEntityTypeId() === 'commerce_order_item') {
      // @todo Fix wallet pass regeneration when order items are updated.
    }
  }

}
