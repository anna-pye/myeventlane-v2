<?php

/**
 * @file
 * Install Phase 3 event fields: vendor and store linkage.
 *
 * Run with: ddev drush php:script install-phase3-event-fields.php
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

echo "Installing Phase 3 event fields...\n\n";

// Helper function to create field storage if it doesn't exist.
function create_field_storage($entity_type, $field_name, $field_type, $settings = []) {
  $storage = FieldStorageConfig::loadByName($entity_type, $field_name);
  if (!$storage) {
    $storage_config = [
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $field_type,
    ] + $settings;
    $storage = FieldStorageConfig::create($storage_config);
    $storage->save();
    echo "Created field storage: $entity_type.$field_name\n";
    return TRUE;
  }
  echo "Field storage already exists: $entity_type.$field_name\n";
  return FALSE;
}

// Helper function to create field instance if it doesn't exist.
function create_field_instance($entity_type, $bundle, $field_name, $label, $settings = []) {
  $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
  if (!$field) {
    $field_config = [
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $label,
    ] + $settings;
    $field = FieldConfig::create($field_config);
    $field->save();
    echo "Created field: $entity_type.$bundle.$field_name\n";
    return TRUE;
  }
  echo "Field already exists: $entity_type.$bundle.$field_name\n";
  return FALSE;
}

// ============================================
// EVENT VENDOR FIELD
// ============================================

echo "\n=== Creating Event Vendor Field ===\n";

// field_event_vendor - reference to myeventlane_vendor
create_field_storage('node', 'field_event_vendor', 'entity_reference', [
  'settings' => ['target_type' => 'myeventlane_vendor'],
  'cardinality' => 1,
]);
create_field_instance('node', 'event', 'field_event_vendor', 'Vendor', [
  'description' => 'The vendor/organiser who owns this event.',
  'required' => FALSE,
  'settings' => [
    'handler' => 'default:myeventlane_vendor',
    'handler_settings' => [
      'target_bundles' => NULL,
      'sort' => ['field' => 'name', 'direction' => 'ASC'],
      'auto_create' => FALSE,
    ],
  ],
]);

// ============================================
// EVENT STORE FIELD
// ============================================

echo "\n=== Creating Event Store Field ===\n";

// field_event_store - reference to commerce_store
create_field_storage('node', 'field_event_store', 'entity_reference', [
  'settings' => ['target_type' => 'commerce_store'],
  'cardinality' => 1,
]);
create_field_instance('node', 'event', 'field_event_store', 'Store', [
  'description' => 'The Commerce store for this event (should match the vendor\'s store).',
  'required' => FALSE,
  'settings' => [
    'handler' => 'default:commerce_store',
    'handler_settings' => [
      'target_bundles' => ['online' => 'online'],
      'sort' => ['field' => 'name', 'direction' => 'ASC'],
      'auto_create' => FALSE,
    ],
  ],
]);

// ============================================
// UPDATE FORM DISPLAY
// ============================================

echo "\n=== Updating Event Form Display ===\n";

$form_display = EntityFormDisplay::load('node.event.default');
if ($form_display) {
  // Add vendor field after organizer (if organizer exists) or at weight 10
  if ($form_display->getComponent('field_organizer')) {
    $form_display->setComponent('field_event_vendor', [
      'type' => 'entity_reference_autocomplete',
      'weight' => 10,
      'settings' => [
        'match_operator' => 'CONTAINS',
        'match_limit' => 10,
        'size' => 60,
        'placeholder' => '',
      ],
    ]);
  } else {
    $form_display->setComponent('field_event_vendor', [
      'type' => 'entity_reference_autocomplete',
      'weight' => 9,
      'settings' => [
        'match_operator' => 'CONTAINS',
        'match_limit' => 10,
        'size' => 60,
        'placeholder' => '',
      ],
    ]);
  }

  // Add store field after vendor
  $form_display->setComponent('field_event_store', [
    'type' => 'entity_reference_autocomplete',
    'weight' => 11,
    'settings' => [
      'match_operator' => 'CONTAINS',
      'match_limit' => 10,
      'size' => 60,
      'placeholder' => '',
    ],
  ]);

  $form_display->save();
  echo "Updated event form display\n";
}

// ============================================
// UPDATE VIEW DISPLAY
// ============================================

echo "\n=== Updating Event View Display ===\n";

$view_display = EntityViewDisplay::load('node.event.default');
if ($view_display) {
  // Add vendor field to view
  $view_display->setComponent('field_event_vendor', [
    'type' => 'entity_reference_label',
    'weight' => 20,
    'label' => 'above',
    'settings' => [
      'link' => TRUE,
    ],
  ]);

  // Store field can be hidden (admin-only info)
  $view_display->removeComponent('field_event_store');

  $view_display->save();
  echo "Updated event view display\n";
}

echo "\n=== All Phase 3 event fields installed! ===\n";
echo "Run: ddev drush cr\n";

