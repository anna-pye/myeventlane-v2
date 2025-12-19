# PHASE 4 — EVENT ENTITY REARCHITECTURE — AUDIT REPORT

**Date:** 2025-01-27  
**Branch:** `audit-rebuild-event-system`  
**Status:** 🔍 Audit Complete

---

## EXECUTIVE SUMMARY

The Event node type has **31 fields** (including `body` and `title`). Analysis reveals **field redundancy** and **architectural inconsistencies** that need resolution.

**Key Findings:**
- ⚠️ **Location redundancy:** 5 location-related fields (should be 2-3)
- ⚠️ **Hidden unused fields:** 2 fields hidden and not used in code
- ⚠️ **Organizer redundancy:** 2 organizer/vendor fields (may be redundant)
- ✅ **Dates:** Clean (2 fields: start/end)
- ✅ **Capacity:** Clean (2 fields: capacity/waitlist)
- ⚠️ **Tickets/RSVP:** 3 fields, unclear relationships

---

## FIELD INVENTORY (31 Total)

### Core Fields (2)
- `title` - Event title ✅
- `body` - Event description ✅

### Location Fields (5) ⚠️ **REDUNDANCY DETECTED**

| Field | Type | Status | Usage | Recommendation |
|-------|------|--------|-------|---------------|
| `field_location` | Address | ✅ **PRIMARY** | Actively used | **KEEP** - Primary structured address |
| `field_venue_name` | String | ✅ **KEEP** | Actively used | **KEEP** - Friendly venue name |
| `field_location_latitude` | Decimal | ✅ **KEEP** | Auto-populated | **KEEP** - Map coordinates |
| `field_location_longitude` | Decimal | ✅ **KEEP** | Auto-populated | **KEEP** - Map coordinates |
| `field_event_location` | String | ❌ **REDUNDANT** | Hidden, not used | **REMOVE** - Redundant with `field_location` |

**Analysis:**
- `field_location` (address field) is the primary location field - actively used throughout codebase
- `field_venue_name` provides friendly venue name (e.g., "Grand Ballroom") - separate concern, keep
- `field_location_latitude`/`field_location_longitude` are auto-populated from address - needed for maps
- `field_event_location` (string) is HIDDEN in form, not used in code - **REDUNDANT**

**Recommendation:** Remove `field_event_location` (string field).

---

### Date Fields (2) ✅ **CLEAN**

| Field | Type | Status | Usage |
|-------|------|--------|-------|
| `field_event_start` | Datetime | ✅ | Start date/time |
| `field_event_end` | Datetime | ✅ | End date/time |

**Status:** ✅ No redundancy. Clean architecture.

---

### Organizer/Vendor Fields (2) ⚠️ **POTENTIAL REDUNDANCY**

| Field | Type | Status | Usage | Recommendation |
|-------|------|--------|-------|---------------|
| `field_event_vendor` | Entity Reference (Vendor) | ✅ **PRIMARY** | Heavily used | **KEEP** - Primary vendor association |
| `field_organizer` | Entity Reference (User) | ⚠️ **QUESTIONABLE** | Hidden, demo only | **REVIEW** - May be redundant |

**Analysis:**
- `field_event_vendor` is heavily used throughout codebase (queries, access control, dashboards)
- `field_organizer` is HIDDEN in form, only used in demo data generation
- Vendor entity likely has owner relationship to user already

**Recommendation:** 
- **Option A:** Remove `field_organizer` if vendor entity provides user relationship
- **Option B:** Keep if needed for events without vendor (edge case)

**Action Required:** Verify if vendor entity provides user relationship. If yes, remove `field_organizer`.

---

### Ticket/RSVP Fields (3) ⚠️ **NEEDS CLARIFICATION**

| Field | Type | Status | Usage | Recommendation |
|-------|------|--------|-------|---------------|
| `field_product_target` | Entity Reference (Product) | ✅ **KEEP** | Actively used | **KEEP** - Links to Commerce product |
| `field_ticket_types` | Paragraphs | ✅ **KEEP** | Actively used | **KEEP** - Ticket type definitions |
| `field_rsvp_target` | Entity Reference (Node) | ❌ **LEGACY?** | Hidden, not used | **REVIEW** - Purpose unclear |

**Analysis:**
- `field_product_target` - Links Event to Commerce Product for paid tickets - **REQUIRED**
- `field_ticket_types` - Paragraphs defining ticket types (Full Price, Concession, etc.) - **REQUIRED**
- `field_rsvp_target` - References another node - HIDDEN, not used in code - **PURPOSE UNCLEAR**

**Questions:**
1. What is the purpose of `field_rsvp_target`? (References another event node - why?)
2. Is this for event series or recurring events?
3. If unused, should it be removed?

**Recommendation:** 
- **If unused:** Remove `field_rsvp_target`
- **If for event series:** Document purpose and make visible if needed

---

### Capacity Fields (2) ✅ **CLEAN**

| Field | Type | Status | Usage |
|-------|------|--------|-------|
| `field_capacity` | Integer | ✅ | Maximum attendees |
| `field_waitlist_capacity` | Integer | ✅ | Waitlist limit |

**Status:** ✅ No redundancy. Clean architecture.

---

### Metadata Fields (8) ✅ **CLEAN**

| Field | Type | Status | Usage |
|-------|------|--------|-------|
| `field_event_type` | List String | ✅ | RSVP/Paid/Both |
| `field_category` | Entity Reference | ✅ | Event categories |
| `field_tags` | Entity Reference | ✅ | Event tags |
| `field_featured` | Boolean | ✅ | Featured flag |
| `field_promoted` | Boolean | ✅ | Promoted flag |
| `field_promo_expires` | Datetime | ✅ | Promotion expiry |
| `field_external_url` | Link | ✅ | External booking URL |
| `field_event_image` | Image | ✅ | Event image |

**Status:** ✅ All fields serve distinct purposes.

---

### Accessibility Fields (6) ✅ **CLEAN**

| Field | Type | Status | Usage |
|-------|------|--------|-------|
| `field_accessibility` | Entity Reference | ✅ | Accessibility terms |
| `field_accessibility_contact` | Text | ✅ | Contact info |
| `field_accessibility_directions` | Text | ✅ | Directions |
| `field_accessibility_entry` | Text | ✅ | Entry info |
| `field_accessibility_parking` | Text | ✅ | Parking info |

**Status:** ✅ All fields serve distinct accessibility purposes.

---

### Commerce/System Fields (3) ✅ **CLEAN**

| Field | Type | Status | Usage |
|-------|------|--------|-------|
| `field_event_store` | Entity Reference | ✅ | Commerce store (auto-populated) |
| `field_attendee_questions` | Paragraphs | ✅ | Custom questions |
| `field_collect_per_ticket` | Boolean | ✅ | Per-ticket data collection |

**Status:** ✅ All fields serve distinct purposes.

---

## REDUNDANCY SUMMARY

### Fields to Remove (2)

1. **`field_event_location`** (String field)
   - **Reason:** Redundant with `field_location` (address field)
   - **Status:** Hidden in form, not used in code
   - **Impact:** Low - no code references found
   - **Action:** Remove field and storage

2. **`field_rsvp_target`** (Node reference)
   - **Reason:** Purpose unclear, hidden, not used
   - **Status:** Hidden in form, no code references
   - **Impact:** Low - no code references found
   - **Action:** **REQUIRES CLARIFICATION** - Verify if needed for event series

### Fields to Review (1)

3. **`field_organizer`** (User reference)
   - **Reason:** May be redundant with vendor entity's user relationship
   - **Status:** Hidden in form, only used in demo data
   - **Impact:** Medium - verify vendor entity structure
   - **Action:** **REQUIRES CLARIFICATION** - Check if vendor entity has user owner

---

## SINGLE SOURCE OF TRUTH ANALYSIS

### ✅ Location
**Primary:** `field_location` (Address field)  
**Supporting:** `field_venue_name`, `field_location_latitude`, `field_location_longitude`  
**Status:** ✅ Clear hierarchy (address is primary, coordinates auto-populated)

### ✅ Dates
**Primary:** `field_event_start`, `field_event_end`  
**Status:** ✅ Single source of truth

### ✅ Capacity
**Primary:** `field_capacity`, `field_waitlist_capacity`  
**Status:** ✅ Single source of truth

### ⚠️ Tickets/RSVP
**Primary:** `field_product_target` (Commerce product), `field_ticket_types` (Paragraphs)  
**Unclear:** `field_rsvp_target` (purpose unknown)  
**Status:** ⚠️ Needs clarification on `field_rsvp_target`

### ⚠️ Organizer
**Primary:** `field_event_vendor` (Vendor entity)  
**Unclear:** `field_organizer` (User reference)  
**Status:** ⚠️ Needs verification if vendor entity provides user relationship

---

## FIELD GROUPING RECOMMENDATIONS

### Current State
Fields are not logically grouped in form display. All fields appear in a flat structure.

### Recommended Groups

1. **Event Basics**
   - `title`
   - `body`
   - `field_event_image`
   - `field_category`
   - `field_tags`

2. **Schedule**
   - `field_event_start`
   - `field_event_end`

3. **Location**
   - `field_venue_name`
   - `field_location` (address)
   - `field_location_latitude` (auto-populated)
   - `field_location_longitude` (auto-populated)

4. **Attendance Type**
   - `field_event_type`
   - `field_capacity`
   - `field_waitlist_capacity`

5. **Ticketing** (conditional on event type)
   - `field_product_target`
   - `field_ticket_types`
   - `field_collect_per_ticket`

6. **Accessibility**
   - `field_accessibility`
   - `field_accessibility_contact`
   - `field_accessibility_directions`
   - `field_accessibility_entry`
   - `field_accessibility_parking`

7. **Promotion**
   - `field_featured`
   - `field_promoted`
   - `field_promo_expires`

8. **Advanced** (hidden/auto-populated)
   - `field_event_vendor` (auto-populated)
   - `field_event_store` (auto-populated)
   - `field_attendee_questions`
   - `field_external_url`

---

## PERMISSIONS & WORKFLOWS

### Current Permissions
- ✅ Event creation: Vendor role
- ✅ Event editing: Owner/vendor
- ✅ Event viewing: Public (published) or owner (unpublished)

### Workflow Issues
- ⚠️ No clear draft/publish workflow
- ⚠️ No event approval process
- ⚠️ Auto-populated fields may need review

---

## IMMEDIATE ACTIONS REQUIRED

### Before Field Removal:

1. **Verify `field_rsvp_target` purpose**
   - Check if used for event series
   - Check if needed for recurring events
   - Document purpose or remove

2. **Verify `field_organizer` necessity**
   - Check vendor entity structure
   - Verify if vendor has user owner relationship
   - Determine if events can exist without vendor

3. **Data Migration Plan**
   - If removing `field_event_location`, migrate any existing data to `field_location`
   - If removing `field_organizer`, ensure vendor relationship covers use case

---

## RECOMMENDED FIELD ARCHITECTURE

### Keep (29 fields)
- All core fields
- All metadata fields
- All accessibility fields
- All commerce fields
- Location: `field_location`, `field_venue_name`, `field_location_latitude`, `field_location_longitude`
- Organizer: `field_event_vendor` (primary)
- Tickets: `field_product_target`, `field_ticket_types`

### Remove (2 fields) - **PENDING CLARIFICATION**
- `field_event_location` (string) - Redundant
- `field_rsvp_target` (node reference) - Purpose unclear

### Review (1 field)
- `field_organizer` (user reference) - May be redundant

---

## NEXT STEPS

1. **Clarify field purposes** (ask product owner)
2. **Create data migration plan** (if removing fields)
3. **Group fields logically** in form display
4. **Validate permissions** and workflows
5. **Document field relationships** and dependencies

---

**END OF PHASE 4 AUDIT REPORT**
