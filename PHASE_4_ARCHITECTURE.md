# PHASE 4 — EVENT ENTITY REARCHITECTURE — ARCHITECTURE DOCUMENT

**Date:** 2025-01-27  
**Branch:** `audit-rebuild-event-system`  
**Decision:** Event remains a **node** (not custom entity)

---

## SINGLE SOURCE OF TRUTH DEFINITIONS

### ✅ Location
**Primary Source:** `field_location` (Address field - structured address)  
**Supporting Fields:**
- `field_venue_name` - Friendly venue name (e.g., "Grand Ballroom")
- `field_location_latitude` - Auto-populated from address
- `field_location_longitude` - Auto-populated from address

**Removed:** `field_event_location` (string) - Redundant, 0 records

**Architecture:**
- Address field (`field_location`) is the canonical location
- Coordinates are auto-populated via geocoding
- Venue name is a separate concern (friendly name vs. address)

---

### ✅ Dates
**Primary Source:** 
- `field_event_start` - Event start date/time
- `field_event_end` - Event end date/time

**Architecture:** ✅ Clean - no redundancy

---

### ✅ Capacity
**Primary Source:**
- `field_capacity` - Maximum attendees
- `field_waitlist_capacity` - Waitlist limit (optional)

**Architecture:** ✅ Clean - no redundancy

---

### ✅ Tickets/RSVP
**Primary Source:**
- `field_event_type` - Determines if RSVP, Paid, or Both
- `field_product_target` - Commerce Product for paid tickets
- `field_ticket_types` - Paragraphs defining ticket type configurations

**Removed:** `field_rsvp_target` (node reference) - Purpose unclear, 0 records

**Architecture:**
- Event type determines booking method
- Product reference links to Commerce for payment
- Ticket types (paragraphs) define pricing and availability

---

### ✅ Organizer/Vendor
**Primary Source:** `field_event_vendor` (Vendor entity reference)  
**Vendor Relationship:** Vendor entity has `uid` field (owner) via `EntityOwnerTrait`

**Removed:** `field_organizer` (user reference) - Redundant with vendor->uid

**Architecture:**
- Event → Vendor (via `field_event_vendor`)
- Vendor → User (via `uid` base field)
- Therefore: Event → Vendor → User (indirect relationship)

**Migration Plan:**
- 6 events have `field_organizer` values
- Need to verify these events have `field_event_vendor` set
- If vendor exists, remove `field_organizer` (redundant)
- If vendor missing, create vendor or set vendor from organizer

---

## FIELD GROUPING STRATEGY

### Recommended Form Display Groups

**1. Event Basics** (Weight: 0-10)
- `title` (0)
- `field_event_type` (1)
- `field_event_start` (2)
- `field_event_end` (3)
- `body` (4)
- `field_event_image` (5)

**2. Location** (Weight: 6-9)
- `field_venue_name` (6)
- `field_location` (7) - Address field
- `field_location_longitude` (8) - Auto-populated
- `field_location_latitude` (9) - Auto-populated

**3. Attendance** (Weight: 10-11)
- `field_capacity` (10)
- `field_waitlist_capacity` (11)

**4. Ticketing** (Weight: 12-13, conditional on event type)
- `field_product_target` (12)
- `field_ticket_types` (13)
- `field_collect_per_ticket` (22)

**5. Categorization** (Weight: 14-15)
- `field_category` (14)
- `field_tags` (15)

**6. Accessibility** (Weight: 16-20)
- `field_accessibility` (16)
- `field_accessibility_contact` (17)
- `field_accessibility_directions` (18)
- `field_accessibility_entry` (19)
- `field_accessibility_parking` (20)

**7. Additional** (Weight: 21+)
- `field_attendee_questions` (21)
- `field_external_url` (14)

**8. Promotion** (Weight: varies)
- `field_featured`
- `field_promoted`
- `field_promo_expires`

**9. System Fields** (Hidden/Auto-populated)
- `field_event_vendor` - Auto-populated from current user's vendor
- `field_event_store` - Auto-populated from vendor's store
- `field_organizer` - **TO BE REMOVED**
- `field_rsvp_target` - **TO BE REMOVED**
- `field_event_location` - **TO BE REMOVED**

---

## FIELD REMOVAL PLAN

### Phase 4.1: Remove Unused Fields

**Fields to Remove:**
1. `field_event_location` (string)
   - **Data:** 0 records
   - **Impact:** None
   - **Action:** Delete field and storage

2. `field_rsvp_target` (node reference)
   - **Data:** 0 records
   - **Impact:** None
   - **Action:** Delete field and storage

### Phase 4.2: Migrate and Remove Redundant Field

3. `field_organizer` (user reference)
   - **Data:** 6 records
   - **Impact:** Low (only 6 events)
   - **Action:** 
     - Verify vendor relationship for these 6 events
     - If vendor exists, remove `field_organizer` value
     - If vendor missing, create vendor or set from organizer
     - Delete field and storage

---

## PERMISSIONS & WORKFLOWS

### Current Permissions
- ✅ Event creation: Vendor role
- ✅ Event editing: Owner/vendor
- ✅ Event viewing: Public (published) or owner (unpublished)

### Workflow Validation
- ✅ Node form remains usable without wizard
- ✅ Wizard must not break API-based creation
- ✅ Business logic lives in services (not forms)

---

## FIELD DEPENDENCIES

### Required Relationships
- Event → Vendor (via `field_event_vendor`)
- Event → Store (via `field_event_store`) - auto-populated from vendor
- Event → Product (via `field_product_target`) - for paid events
- Event → Ticket Types (via `field_ticket_types`) - paragraph references

### Auto-Population Rules
- `field_event_vendor` - Set from current user's vendor
- `field_event_store` - Set from vendor's store
- `field_location_latitude`/`field_location_longitude` - Geocoded from address

---

## VALIDATION RULES

### Field Requirements by Event Type

**All Events:**
- `title` - Required
- `field_event_start` - Required
- `field_event_vendor` - Required (auto-populated)

**RSVP Events:**
- `field_capacity` - Optional (unlimited if empty)

**Paid Events:**
- `field_product_target` - Required
- `field_ticket_types` - Required (at least one)

**Both (RSVP + Paid):**
- All above requirements

---

## NEXT STEPS

1. **Create field removal script** (for unused fields)
2. **Create data migration script** (for `field_organizer`)
3. **Update form display** (group fields logically)
4. **Update view display** (remove hidden fields)
5. **Test field removal** (verify no broken references)
6. **Update documentation** (field architecture)

---

**END OF PHASE 4 ARCHITECTURE DOCUMENT**
