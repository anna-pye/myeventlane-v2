# PHASE 4 — EVENT ENTITY REARCHITECTURE — COMPLETE

**Date:** 2025-01-27  
**Branch:** `audit-rebuild-event-system`  
**Decision:** Event remains a **node** (not custom entity)  
**Status:** ✅ Complete

---

## SUMMARY

Phase 4 successfully audited the Event node structure, identified field redundancy, defined single source of truth, and prepared field removal/migration scripts.

---

## COMPLETED TASKS

### ✅ 1. Event Node Structure Review

- **Total Fields:** 31 fields
- **Fields Analyzed:** All 31 fields
- **Redundancy Identified:** 3 fields

### ✅ 2. Single Source of Truth Defined

**Location:**
- **Primary:** `field_location` (Address field)
- **Supporting:** `field_venue_name`, `field_location_latitude`, `field_location_longitude`
- **Removed:** `field_event_location` (string) - Redundant

**Dates:**
- **Primary:** `field_event_start`, `field_event_end`
- **Status:** ✅ Clean - no redundancy

**Capacity:**
- **Primary:** `field_capacity`, `field_waitlist_capacity`
- **Status:** ✅ Clean - no redundancy

**Tickets/RSVP:**
- **Primary:** `field_product_target` (Commerce product), `field_ticket_types` (Paragraphs)
- **Removed:** `field_rsvp_target` (node reference) - Purpose unclear, unused

**Organizer/Vendor:**
- **Primary:** `field_event_vendor` (Vendor entity)
- **Vendor Relationship:** Vendor entity has `uid` field (owner) via `EntityOwnerTrait`
- **Removed:** `field_organizer` (user reference) - Redundant with vendor->uid

### ✅ 3. Field Grouping Improved

**Updated Form Alter:**
- Removed `field_rsvp_target` from tickets tab
- Added `field_waitlist_capacity` to tickets tab
- Added `field_collect_per_ticket` to tickets tab
- Added `body` to basics tab

**Form Display Updated:**
- Removed `field_event_location`, `field_rsvp_target`, `field_organizer` from hidden list
- Fields will be removed after data migration

**View Display Updated:**
- Removed `field_event_location`, `field_rsvp_target` from hidden list

### ✅ 4. Scripts Created

1. **`remove_unused_fields.php`**
   - Removes `field_event_location`, `field_rsvp_target`, `field_organizer`
   - Checks for data before removal
   - Safe removal process

2. **`migrate_organizer_to_vendor.php`**
   - Migrates 6 events with `field_organizer` values
   - Creates vendors for organizer users if needed
   - Sets `field_event_vendor` on events
   - Prepares for `field_organizer` removal

### ✅ 5. Permissions Validated

**Vendor Role Permissions:**
- ✅ `create event content`
- ✅ `edit own event content`
- ✅ `delete own event content`

**Access Control:**
- ✅ Event owner (uid) can edit
- ✅ Vendor members (via `field_event_vendor` → `field_vendor_users`) can edit
- ✅ Admin can edit all

**Status:** ✅ Permissions properly configured

---

## FIELD REMOVAL PLAN

### Fields to Remove (3)

1. **`field_event_location`** (String)
   - **Data:** 0 records
   - **Status:** Safe to remove immediately
   - **Action:** Run removal script

2. **`field_rsvp_target`** (Node reference)
   - **Data:** 0 records
   - **Status:** Safe to remove immediately
   - **Action:** Run removal script

3. **`field_organizer`** (User reference)
   - **Data:** 6 records (demo events)
   - **Status:** Requires migration first
   - **Action:** 
     1. Run migration script
     2. Verify vendor relationships
     3. Run removal script

---

## DATA MIGRATION STATUS

### Events Requiring Migration (6)

All 6 events with `field_organizer` values are demo events:
- [DEMO] Tech Conference 2025
- [DEMO] Charity Gala
- [DEMO] Mystery Event
- [DEMO] Past Workshop
- A New Test
- The <Map 3

**Migration Plan:**
- Migration script will create vendors for organizer users
- Set `field_event_vendor` on events
- Remove `field_organizer` values

---

## FILES MODIFIED

1. `web/modules/custom/myeventlane_event/src/Form/EventFormAlter.php`
   - Updated field grouping (removed `field_rsvp_target`, added `body`, `field_waitlist_capacity`, `field_collect_per_ticket`)

2. `web/sites/default/config/sync/core.entity_form_display.node.event.default.yml`
   - Removed unused fields from hidden list

3. `web/sites/default/config/sync/core.entity_view_display.node.event.default.yml`
   - Removed unused fields from hidden list

## FILES CREATED

1. `web/modules/custom/myeventlane_event/scripts/remove_unused_fields.php`
2. `web/modules/custom/myeventlane_event/scripts/migrate_organizer_to_vendor.php`
3. `PHASE_4_EVENT_AUDIT.md` (audit report)
4. `PHASE_4_ARCHITECTURE.md` (architecture document)

---

## NEXT STEPS

### Before Field Removal:

1. **Run migration script:**
   ```bash
   ddev drush php:script web/modules/custom/myeventlane_event/scripts/migrate_organizer_to_vendor.php
   ```

2. **Verify migration:**
   - Check that all 6 events have `field_event_vendor` set
   - Verify vendor relationships are correct

3. **Run removal script:**
   ```bash
   ddev drush php:script web/modules/custom/myeventlane_event/scripts/remove_unused_fields.php
   ```

4. **Clear cache:**
   ```bash
   ddev drush cr
   ```

### After Field Removal:

- Export config to remove field configs from sync directory
- Update any remaining references in code
- Test event creation and editing

---

## FIELD ARCHITECTURE SUMMARY

### Final Field Count: 28 fields (after removal)

**Core (2):** `title`, `body`  
**Location (4):** `field_location`, `field_venue_name`, `field_location_latitude`, `field_location_longitude`  
**Dates (2):** `field_event_start`, `field_event_end`  
**Capacity (2):** `field_capacity`, `field_waitlist_capacity`  
**Tickets (3):** `field_event_type`, `field_product_target`, `field_ticket_types`  
**Metadata (8):** Various categorization and promotion fields  
**Accessibility (6):** Various accessibility fields  
**Commerce (3):** `field_event_store`, `field_attendee_questions`, `field_collect_per_ticket`

---

## VERIFICATION

### ✅ Permissions
- Vendor role has correct permissions
- Access control properly checks owner and vendor relationships

### ✅ Workflows
- Node form usable without wizard
- Wizard does not break API-based creation
- Business logic in services (not forms)

### ✅ Field Relationships
- Event → Vendor relationship clear
- Vendor → User relationship via `uid` field
- Product → Event relationship via `field_product_target`

---

**END OF PHASE 4 REPORT**
