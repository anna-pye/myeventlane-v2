<?php
/**
 * Drush script to add standard vendor fields to myeventlane_vendor entity.
 *
 * Usage:
 *   drush scr scripts/add_vendor_fields.drush.php
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Field\BaseFieldDefinition;

/** The vendor entity type ID from your module. */
$entity_type = 'myeventlane_vendor';
/** The vendor bundle. Adjust if bundle name differs. */
$bundle = 'myeventlane_vendor';

$fields = [
  'field_description' => [
    'type' => 'text_long',
    'label' => 'Description',
  ],
  'field_summary' => [
    'type' => 'text',
    'label' => 'Summary',
  ],
  'field_banner_image' => [
    'type' => 'image',
    'label' => 'Banner image',
  ],
  'field_logo_image' => [
    'type' => 'image',
    'label' => 'Logo image',
  ],
  'field_website' => [
    'type' => 'link',
    'label' => 'Website',
  ],
  'field_email' => [
    'type' => 'email',
    'label' => 'Contact email',
  ],
  'field_phone' => [
    'type' => 'telephone',
    'label' => 'Phone number',
  ],
  'field_address' => [
    'type' => 'string',
    'label' => 'Address',  // or use a more complex address module
  ],
  'field_social_links' => [
    'type' => 'link',
    'label' => 'Social links',
    'settings' => ['link_type' => 16], // allow external URLs
    'cardinality' => BaseFieldDefinition::CARDINALITY_UNLIMITED,
  ],
  'field_owner' => [
    'type' => 'entity_reference',
    'label' => 'Owner (user)',
    'settings' => [
      'target_type' => 'user',
      'handler' => 'default:user',
    ],
  ],
];

foreach ($fields as $field_name => $info) {
  // 1) Create field storage if not exists.
  $storage = \Drupal::entityTypeManager()->getStorage('field_storage_config');
  if (!$storage->load("$entity_type.$field_name")) {
    $storage_config = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $info['type'],
      'settings' => $info['settings'] ?? [],
      'cardinality' => $info['cardinality'] ?? 1,
    ]);
    $storage_config->save();
    print "Created storage: $field_name\n";
  }
  else {
    print "Storage already exists: $field_name\n";
  }

  // 2) Create field instance (field config) if not exists.
  $config = \Drupal::entityTypeManager()->getStorage('field_config');
  if (!$config->load("$entity_type.$bundle.$field_name")) {
    $field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $info['label'],
      'settings' => $info['settings'] ?? [],
    ]);
    $field->save();
    print "Created field: $field_name on bundle $bundle\n";
  }
  else {
    print "Field already exists: $field_name\n";
  }
}

print "All done. Clear cache and adjust form/display settings in the UI.\n";