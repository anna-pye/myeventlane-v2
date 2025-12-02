<?php

declare(strict_types=1);

namespace Drupal\myeventlane_vendor;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a list builder for Vendor entities.
 */
final class VendorListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * Constructs a new VendorListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    DateFormatterInterface $date_formatter,
  ) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): self {
    return new self(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Vendor');
    $header['slug'] = $this->t('Slug');
    $header['owner'] = $this->t('Owner');
    $header['updated'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\myeventlane_vendor\Entity\Vendor $entity */
    $row['name'] = $entity->toLink();

    // Slug from configurable field.
    $row['slug'] = '-';
    if ($entity->hasField('field_slug') && !$entity->get('field_slug')->isEmpty()) {
      $row['slug'] = $entity->get('field_slug')->value;
    }

    // Owner from base field (uid) via EntityOwnerInterface.
    $row['owner'] = '-';
    $owner = $entity->getOwner();
    if ($owner !== NULL) {
      $row['owner'] = $owner->toLink();
    }

    // Updated timestamp via EntityChangedInterface.
    $changed = $entity->getChangedTime();
    $row['updated'] = $changed > 0 ? $this->dateFormatter->format($changed, 'short') : '-';

    return $row + parent::buildRow($entity);
  }

}
