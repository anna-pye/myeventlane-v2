<?php

// Load all event node IDs without access checking.
$ids = \Drupal::entityQuery('node')
  ->condition('type', 'event')
  ->accessCheck(FALSE)
  ->execute();

if ($ids) {
  $storage = \Drupal::entityTypeManager()->getStorage('node');
  $nodes = $storage->loadMultiple($ids);
  $storage->delete($nodes);
}

print "Deleted " . count($ids) . " event nodes.\n";