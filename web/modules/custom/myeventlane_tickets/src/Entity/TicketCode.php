<?php

declare(strict_types=1);

namespace Drupal\myeventlane_tickets\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Stores unique ticket verification codes.
 *
 * @ContentEntityType(
 *   id = "ticket_code",
 *   label = @Translation("Ticket Code"),
 *   base_table = "ticket_code",
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   }
 * )
 */
final class TicketCode extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('ID'))
      ->setReadOnly(TRUE);

    $fields['code'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Code'))
      ->setRequired(TRUE);

    $fields['order_item_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Order item'))
      ->setSetting('target_type', 'commerce_order_item')
      ->setRequired(TRUE);

    $fields['event_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Event'))
      ->setSetting('target_type', 'node')
      ->setRequired(TRUE);

    return $fields;
  }

}
