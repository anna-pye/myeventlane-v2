<?php
/**
 * Drush script to find (and optionally delete) invalid field config / storage.
 *
 * Usage:
 *   drush scr cleanup_invalid_fields.drush.php         # just report
 *   drush scr cleanup_invalid_fields.drush.php --delete  # delete invalid configs
 */

if (!\Drupal::hasService('plugin.manager.field.field_type')) {
  echo "Error: Field Type plugin manager not available — are you bootstrapping Drupal correctly?\n";
  exit(1);
}

$field_type_manager = \Drupal::service('plugin.manager.field.field_type');
$config_factory = \Drupal::service('config.factory');

$found = [];
$removed = [];

echo "Scanning field.storage configs...\n";
foreach ($config_factory->listAll('field.storage') as $name) {
  $conf = \Drupal::config($name)->getRawData();
  $type = $conf['type'] ?? '';
  if (!is_string($type) || !$field_type_manager->getDefinition($type, FALSE)) {
    $found[] = "$name (storage) — type: " . var_export($type, TRUE);
    if (\Drush\Drush::input()->getOption('delete')) {
      \Drupal::configFactory()->getEditable($name)->delete();
      $removed[] = $name;
    }
  }
}

echo "Scanning field.field configs...\n";
foreach ($config_factory->listAll('field.field') as $name) {
  $conf = \Drupal::config($name)->getRawData();
  $type = $conf['field_type'] ?? '';
  if (!is_string($type) || !$field_type_manager->getDefinition($type, FALSE)) {
    $found[] = "$name (field) — field_type: " . var_export($type, TRUE);
    if (\Drush\Drush::input()->getOption('delete')) {
      \Drupal::configFactory()->getEditable($name)->delete();
      $removed[] = $name;
    }
  }
}

if (empty($found)) {
  echo "✔ No invalid field config or storage found.\n";
} else {
  echo "⚠ Invalid configs found:\n";
  foreach ($found as $item) {
    echo "  - $item\n";
  }
  if (\Drush\Drush::input()->getOption('delete')) {
    echo "Deleted " . count($removed) . " config object(s).\n";
  } else {
    echo "Run again with --delete to remove them.\n";
  }
}
echo "Done.\n";