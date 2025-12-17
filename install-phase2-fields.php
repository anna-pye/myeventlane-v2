<?php

/**
 * @file
 * Install Phase 2 fields for myeventlane_vendor module.
 *
 * Run with: ddev drush php:script install-phase2-fields.php
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

echo "Installing Phase 2 fields...\n\n";

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
// VENDOR FIELDS
// ============================================

echo "\n=== Creating Vendor Fields ===\n";

// field_vendor_bio
create_field_storage('myeventlane_vendor', 'field_vendor_bio', 'text_long', ['translatable' => TRUE]);
create_field_instance('myeventlane_vendor', 'myeventlane_vendor', 'field_vendor_bio', 'Bio', [
  'description' => 'A longer description or biography of the vendor.',
  'required' => FALSE,
]);

// field_vendor_logo
create_field_storage('myeventlane_vendor', 'field_vendor_logo', 'image', ['translatable' => FALSE]);
create_field_instance('myeventlane_vendor', 'myeventlane_vendor', 'field_vendor_logo', 'Logo', [
  'description' => 'The vendor logo image.',
  'required' => FALSE,
  'settings' => [
    'file_directory' => 'vendor_logos',
    'file_extensions' => 'png gif jpg jpeg webp svg',
    'max_filesize' => '2 MB',
    'alt_field' => TRUE,
    'alt_field_required' => FALSE,
    'title_field' => FALSE,
    'title_field_required' => FALSE,
  ],
]);

// field_vendor_users
create_field_storage('myeventlane_vendor', 'field_vendor_users', 'entity_reference', [
  'settings' => ['target_type' => 'user'],
  'cardinality' => -1,
]);
create_field_instance('myeventlane_vendor', 'myeventlane_vendor', 'field_vendor_users', 'Users', [
  'description' => 'Additional users who can manage this vendor.',
  'required' => FALSE,
  'settings' => [
    'handler' => 'default:user',
    'handler_settings' => [
      'target_bundles' => NULL,
      'sort' => ['field' => 'name', 'direction' => 'ASC'],
      'filter' => ['type' => 'role', 'role' => ['authenticated' => 'authenticated']],
      'include_anonymous' => FALSE,
    ],
  ],
]);

// field_vendor_store
create_field_storage('myeventlane_vendor', 'field_vendor_store', 'entity_reference', [
  'settings' => ['target_type' => 'commerce_store'],
  'cardinality' => 1,
]);
create_field_instance('myeventlane_vendor', 'myeventlane_vendor', 'field_vendor_store', 'Store', [
  'description' => 'The Commerce store linked to this vendor.',
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

// Visibility toggles
$visibility_fields = [
  'field_public_show_email' => 'Show email publicly',
  'field_public_show_phone' => 'Show phone publicly',
  'field_public_show_location' => 'Show location publicly',
];

foreach ($visibility_fields as $field_name => $label) {
  create_field_storage('myeventlane_vendor', $field_name, 'boolean', [
    'settings' => ['on_label' => 'Yes', 'off_label' => 'No'],
  ]);
  create_field_instance('myeventlane_vendor', 'myeventlane_vendor', $field_name, $label, [
    'description' => "Whether to display the vendor $field_name on the public vendor page.",
    'required' => FALSE,
    'default_value' => [['value' => 0]],
    'settings' => ['on_label' => 'Yes', 'off_label' => 'No'],
  ]);
}

// ============================================
// COMMERCE STORE FIELDS
// ============================================

echo "\n=== Creating Commerce Store Stripe Fields ===\n";

// field_stripe_account_id
create_field_storage('commerce_store', 'field_stripe_account_id', 'string', [
  'settings' => ['max_length' => 255],
]);
create_field_instance('commerce_store', 'online', 'field_stripe_account_id', 'Stripe Account ID', [
  'description' => 'The Stripe Connect account ID for this store.',
  'required' => FALSE,
]);

// field_stripe_onboard_url
create_field_storage('commerce_store', 'field_stripe_onboard_url', 'link');
create_field_instance('commerce_store', 'online', 'field_stripe_onboard_url', 'Stripe Onboarding URL', [
  'description' => 'The Stripe Connect onboarding URL (admin-only, not shown in forms).',
  'required' => FALSE,
  'settings' => ['link_type' => 16, 'title' => 0],
]);

// field_stripe_dashboard_url
create_field_storage('commerce_store', 'field_stripe_dashboard_url', 'link');
create_field_instance('commerce_store', 'online', 'field_stripe_dashboard_url', 'Stripe Dashboard URL', [
  'description' => 'The Stripe Connect dashboard login URL (admin-only, not shown in forms).',
  'required' => FALSE,
  'settings' => ['link_type' => 16, 'title' => 0],
]);

// Stripe boolean fields
$stripe_bools = [
  'field_stripe_connected' => 'Stripe Connected',
  'field_stripe_charges_enabled' => 'Charges Enabled',
  'field_stripe_payouts_enabled' => 'Payouts Enabled',
];

foreach ($stripe_bools as $field_name => $label) {
  create_field_storage('commerce_store', $field_name, 'boolean', [
    'settings' => ['on_label' => 'Yes', 'off_label' => 'No'],
  ]);
  create_field_instance('commerce_store', 'online', $field_name, $label, [
    'description' => "Whether $label for this Stripe account.",
    'required' => FALSE,
    'default_value' => [['value' => 0]],
    'settings' => ['on_label' => 'Yes', 'off_label' => 'No'],
  ]);
}

// field_vendor_reference
create_field_storage('commerce_store', 'field_vendor_reference', 'entity_reference', [
  'settings' => ['target_type' => 'myeventlane_vendor'],
  'cardinality' => 1,
]);
create_field_instance('commerce_store', 'online', 'field_vendor_reference', 'Vendor', [
  'description' => 'The vendor this store belongs to.',
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
// UPDATE FORM DISPLAYS
// ============================================

echo "\n=== Updating Form Displays ===\n";

// Hide admin-only URL fields from store form
$store_form = EntityFormDisplay::load('commerce_store.online.default');
if ($store_form) {
  $store_form->removeComponent('field_stripe_onboard_url');
  $store_form->removeComponent('field_stripe_dashboard_url');
  $store_form->save();
  echo "Updated Commerce Store form display (hid admin-only URL fields)\n";
}

echo "\n=== All Phase 2 fields installed! ===\n";
echo "Run: ddev drush cr\n";

