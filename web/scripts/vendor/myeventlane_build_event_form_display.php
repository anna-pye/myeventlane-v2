<?php

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;

$bundle = 'event';
$entity_type = 'node';
$form_display_id = "$entity_type.$bundle.default";

/* Delete old form display if exists */
if ($existing = EntityFormDisplay::load($form_display_id)) {
  $existing->delete();
}

/* Create new display */
$display = EntityFormDisplay::create([
  'targetEntityType' => $entity_type,
  'bundle' => $bundle,
  'mode' => 'default',
  'status' => TRUE,
]);

/* Utility to add widgets */
function add_widget(EntityFormDisplay $display, $field, $type = 'string_textfield', array $settings = []) {
  if (!FieldConfig::load("node.$GLOBALS[bundle].$field")) {
    return;
  }
  $display->setComponent($field, [
    'type' => $type,
    'weight' => 0,
    'settings' => $settings,
  ]);
}

/* EVENT BASICS */
add_widget($display, 'title', 'string_textfield');
add_widget($display, 'field_event_hero', 'image_image');
add_widget($display, 'field_event_date', 'daterange_default');
add_widget($display, 'field_event_status', 'options_buttons');

/* VENUE */
add_widget($display, 'field_event_venue');
add_widget($display, 'field_event_address', 'address_default');
add_widget($display, 'field_event_lat');
add_widget($display, 'field_event_lng');

/* ORGANIZER */
add_widget($display, 'field_event_organizer_name');
add_widget($display, 'field_event_organizer_email');
add_widget($display, 'field_event_organizer_phone');
add_widget($display, 'field_event_organizer_logo', 'image_image');
add_widget($display, 'field_event_store', 'entity_reference_autocomplete');

/* TAGS / ACCESSIBILITY */
add_widget($display, 'field_event_categories', 'entity_reference_autocomplete');
add_widget($display, 'field_event_accessibility', 'entity_reference_autocomplete');

/* TICKETING */
add_widget($display, 'field_ticket_type', 'options_buttons');
add_widget($display, 'field_event_rsvp_target', 'entity_reference_autocomplete');
add_widget($display, 'field_event_product_target', 'entity_reference_autocomplete');

/* Save */
$display->save();

print "Event form display rebuilt.\n";