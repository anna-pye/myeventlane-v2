<?php

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

// Helpers
function mel_fs($entity, $field, $type) {
  $id = "$entity.$field";
  if (!FieldStorageConfig::load($id)) {
    FieldStorageConfig::create([
      'field_name' => $field,
      'entity_type' => $entity,
      'type' => $type,
      'cardinality' => 1,
    ])->save();
    print "Created storage: $field\n";
  }
}

function mel_fi($entity, $bundle, $field, $label) {
  $id = "$entity.$bundle.$field";
  if (!FieldConfig::load($id)) {
    FieldConfig::create([
      'field_name' => $field,
      'entity_type' => $entity,
      'bundle' => $bundle,
      'label' => $label,
    ])->save();
    print "Created field: $label\n";
  }
}

$bundle = 'default';

print "Adding Stripe fields...\n";

// 1. Stripe Account ID
mel_fs('commerce_store', 'field_stripe_account_id', 'string');
mel_fi('commerce_store', $bundle, 'field_stripe_account_id', 'Stripe Account ID');

// 2. Onboarding URL (Link)
mel_fs('commerce_store', 'field_stripe_onboard_url', 'link');
mel_fi('commerce_store', $bundle, 'field_stripe_onboard_url', 'Stripe Onboarding URL');

// 3. Dashboard URL
mel_fs('commerce_store', 'field_stripe_dashboard_url', 'link');
mel_fi('commerce_store', $bundle, 'field_stripe_dashboard_url', 'Stripe Dashboard URL');

// 4. Boolean flags
$bools = [
  'field_stripe_connected' => 'Stripe Connected',
  'field_stripe_payouts_enabled' => 'Payouts Enabled',
  'field_stripe_charges_enabled' => 'Charges Enabled',
];

foreach ($bools as $f => $label) {
  mel_fs('commerce_store', $f, 'boolean');
  mel_fi('commerce_store', $bundle, $f, $label);
}

// Remove URL fields from vendor UI
$form = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load("commerce_store.$bundle.default");

if ($form) {
  $form->removeComponent('field_stripe_onboard_url');
  $form->removeComponent('field_stripe_dashboard_url');
  $form->save();
  print "Hid URL fields.\n";
}

print "Stripe store fields complete.\n";