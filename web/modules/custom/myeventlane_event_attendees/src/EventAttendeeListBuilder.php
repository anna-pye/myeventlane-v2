<?php

namespace Drupal\myeventlane_event_attendees;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Admin list builder for EventAttendee.
 */
class EventAttendeeListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['event'] = $this->t('Event');
    $header['user'] = $this->t('User / Name');
    $header['status'] = $this->t('Status');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\myeventlane_event_attendees\Entity\EventAttendee $entity */

    $event = $entity->get('event_id')->entity;
    $user = $entity->get('user_id')->entity;

    $row['id'] = $entity->id();

    $row['event'] = $event ? Link::createFromRoute(
      $event->label(),
      'entity.node.canonical',
      ['node' => $event->id()]
    ) : '-';

    $row['user'] = $user
      ? $user->label()
      : $entity->get('first_name')->value . ' ' . $entity->get('last_name')->value;

    $row['status'] = $entity->get('status')->value;
    $row['created'] = \Drupal::service('date.formatter')->format(
      $entity->get('created')->value,
      'short'
    );

    return $row + parent::buildRow($entity);
  }

}