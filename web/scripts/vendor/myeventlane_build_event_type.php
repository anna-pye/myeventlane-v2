<?php

/**
 * Full MyEventLane v2 Event Content Type Rebuild Script
 *
 * This script:
 * 1. Deletes any existing Event content type
 * 2. Deletes all Event fields and field storage
 * 3. Recreates the Event content type
 * 4. Recreates ALL Event fields:
 *    - Core metadata
 *    - Dates
 *    - Location (Address type + lat/lng + venue)
 *    - Organizer fields (shared vendor-style)
 *    - Store reference
 *
 * RUN WITH:
 *   ddev drush scr scripts/myeventlane_build_event_type.php
 */

use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

///////////////////////////////
// 1. DELETE OLD EVENT TYPE  //
///////////////////////////////

if ($old = NodeType::load('event')) {
  $old->delete();
  echo "Deleted old Event type\n";
}

// Delete all field storages that might be left over.
$fields_to_delete = [
  'field_event_summary',
  'field_event_description',
  'field_event_image',
  'field_event_category',
  'field_event_tags',
  'field_event_start',
  'field_event_end',
  'field_event_address',
  'field_event_venue',
  'field_event_lat',
  'field_event_lng',
  'field_event_organizer_name',
  'field_event_organizer_email',
  'field_event_organizer_phone',
  'field_event_organizer_logo',
  'field_event_store',
];

foreach ($fields_to_delete as $f) {
  if ($storage = FieldStorageConfig::load("node.$f")) {
    $storage->delete();
    echo "Deleted storage: $f\n";
  }
}

/////////////////////////////////////////
// 2. CREATE CLEAN EVENT CONTENT TYPE  //
/////////////////////////////////////////

$type = NodeType::create([
  'type' => 'event',
  'name' => 'Event',
  'description' => 'Event content type for MyEventLane.',
  'display_submitted' => FALSE,
]);
$type->save();

echo "Created Event content type\n";

////////////////////////////////////////////////
// 3. FIELD CREATION HELPER FUNCTION          //
////////////////////////////////////////////////

function create_field(string $field_name, string $type, array $storage_settings, array $instance_settings, string $label) {

  // Storage
  FieldStorageConfig::create([
    'field_name' => $field_name,
    'entity_type' => 'node',
    'type' => $type,
    'settings' => $storage_settings,
    'cardinality' => 1,
    'translatable' => TRUE,
  ])->save();

  // Instance
  FieldConfig::create([
    'field_name' => $field_name,
    'entity_type' => 'node',
    'bundle' => 'event',
    'label' => $label,
    'required' => FALSE,
    'settings' => $instance_settings,
  ])->save();
}

////////////////////////////////////////////////////
// 4. CREATE ALL MYEVENTLANE EVENT FIELDS         //
////////////////////////////////////////////////////

//
// 4.1 Summary
//
create_field(
  'field_event_summary',
  'string_long',
  [],
  [],
  'Short Summary'
);

//
// 4.2 Description with text+format
//
create_field(
  'field_event_description',
  'text_long',
  [],
  ['display_summary' => TRUE],
  'Event Description'
);

//
// 4.3 Image
//
create_field(
  'field_event_image',
  'image',
  [
    'uri_scheme' => 'public',
    'default_image' => [],
  ],
  [],
  'Event Image'
);

//
// 4.4 Category (taxonomy)
//
create_field(
  'field_event_category',
  'entity_reference',
  [
    'target_type' => 'taxonomy_term',
    'cardinality' => -1,
  ],
  [
    'handler' => 'default:taxonomy_term',
    'handler_settings' => [
      'target_bundles' => ['categories' => 'categories'],
    ],
  ],
  'Category'
);

//
// 4.5 Tags (taxonomy)
//
create_field(
  'field_event_tags',
  'entity_reference',
  [
    'target_type' => 'taxonomy_term',
    'cardinality' => -1,
  ],
  [
    'handler' => 'default:taxonomy_term',
    'handler_settings' => [
      'target_bundles' => ['tags' => 'tags'],
    ],
  ],
  'Tags'
);

//
// 4.6 Start date
//
create_field(
  'field_event_start',
  'datetime',
  ['datetime_type' => 'datetime'],
  [],
  'Event Start'
);

//
// 4.7 End date
//
create_field(
  'field_event_end',
  'datetime',
  ['datetime_type' => 'datetime'],
  [],
  'Event End'
);

//
// 4.8 Address (address module)
//
create_field(
  'field_event_address',
  'address',
  [],
  [
    'available_countries' => ['AU' => 'AU'],
  ],
  'Event Address'
);

//
// 4.9 Event venue label
//
create_field(
  'field_event_venue',
  'string',
  [],
  [],
  'Venue Name'
);

//
// 4.10 Latitude
//
create_field(
  'field_event_lat',
  'string',
  [],
  [],
  'Latitude'
);

//
// 4.11 Longitude
//
create_field(
  'field_event_lng',
  'string',
  [],
  [],
  'Longitude'
);

//
// 4.12 Organizer name
//
create_field(
  'field_event_organizer_name',
  'string',
  [],
  [],
  'Organizer Name'
);

//
// 4.13 Organizer email
//
create_field(
  'field_event_organizer_email',
  'email',
  [],
  [],
  'Organizer Email'
);

//
// 4.14 Organizer phone
//
create_field(
  'field_event_organizer_phone',
  'telephone',
  [],
  [],
  'Organizer Phone'
);

//
// 4.15 Organizer logo
//
create_field(
  'field_event_organizer_logo',
  'image',
  [
    'uri_scheme' => 'public',
    'default_image' => [],
  ],
  [],
  'Organizer Logo'
);

//
// 4.16 Store reference (vendor store)
//
create_field(
  'field_event_store',
  'entity_reference',
  ['target_type' => 'commerce_store'],
  [
    'handler' => 'default:commerce_store',
  ],
  'Vendor Store'
);

echo "All fields created successfully.\n";

/////////////////////////////////////////////////////////////
// 5. BASIC FORM & DISPLAY BUILDS (minimal safe defaults) //
/////////////////////////////////////////////////////////////

$form = EntityFormDisplay::create([
  'targetEntityType' => 'node',
  'bundle' => 'event',
  'mode' => 'default',
]);
$form->save();

$view = EntityViewDisplay::create([
  'targetEntityType' => 'node',
  'bundle' => 'event',
  'mode' => 'default',
]);
$view->save();

echo "Form & View displays created.\n";

echo "DONE.\n";