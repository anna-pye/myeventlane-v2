<?php

// This script checks all field configs/storage configs and reports
// those with invalid or missing field types. Use at your own risk (in dev).

// Bootstrapping of Drupal should already be done (when using drush scr or drush php:eval).

$field_type_manager = \Drupal::service('plugin.manager.field.field_type');
$config_factory = \Drupal::service('config.factory');

$invalid = [];

// Check field.storage.* configs
foreach ($config_factory->listAll('field.storage') as $name) {
  // Load config raw data
  $conf = \Drupal::config($name)->getRawData();
  // The key for storage configs is 'type'
  $type = $conf['type'] ?? '';
  if (!is_string($type) || !$field_type_manager->getDefinition($type, FALSE)) {
    $invalid[] = [
      'config_name' => $name,
      'kind' => 'field.storage',
      'type_value' => $type,
    ];
  }
}

// Check field.field.* configs
foreach ($config_factory->listAll('field.field') as $name) {
  $conf = \Drupal::config($name)->getRawData();
  // The key for field configs is 'field_type'
  $type = $conf['field_type'] ?? '';
  if (!is_string($type) || !$field_type_manager->getDefinition($type, FALSE)) {
    $invalid[] = [
      'config_name' => $name,
      'kind' => 'field.field',
      'type_value' => $type,
    ];
  }
}

if (empty($invalid)) {
  print "✔ No invalid/missing field config or storage entries found.\n";
}
else {
  print "❗ Found invalid field configs / storage definitions:\n";
  foreach ($invalid as $entry) {
    print "- {$entry['kind']}: {$entry['config_name']}  (field type: " . var_export($entry['type_value'], TRUE) . ")\n";
  }
  print "Total: " . count($invalid) . " invalid entries.\n";
}
