#!/usr/bin/env bash
# save as find_bad_field_config.sh, then run: ddev ssh -s web && bash find_bad_field_config.sh

DB_ARGS="-u$(drush sql:login | cut -d ' ' -f3) -p$(drush sql:login | cut -d ' ' -f5) $(drush sql:connection --database)"

echo "Searching config table for suspicious field config patterns..."

# look for serialized data containing field_type: ""  or field.field.0
ddev drush sqlq "
  SELECT 
    collection, name,
    CASE
      WHEN data LIKE '%field_type:\"\"%' THEN 'field_type empty string'
      WHEN data LIKE '%field_type: \"\"%' THEN 'field_type empty string (space)'
      WHEN data LIKE '%field\.field\.0%' THEN 'possible broken field.field index'
      ELSE 'other match'
    END as reason,
    LENGTH(data) as data_length
  FROM config
  WHERE
    data LIKE '%field_type:%' OR data LIKE '%field.field.0%'
  LIMIT 100;
"