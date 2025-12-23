# Event State Machine + Sales Windows + Capacity Engine + Cancel/Refund + Check-in Implementation

## Summary

This document summarizes the implementation of the comprehensive Event State Machine system for MyEventLane v2, including:
- Event State Machine with authoritative state resolution
- Sales Windows (separate from event timing)
- Capacity Engine with oversell prevention
- Vendor Cancel/Refund Flow
- Mobile-first Check-in Mode

## New Modules Created

### 1. `myeventlane_event_state`
**Location:** `web/modules/custom/myeventlane_event_state/`

**Purpose:** Core event state machine and state resolution.

**Key Components:**
- `EventStateResolver` service - Resolves event state based on timing, capacity, and overrides
- State materialization hooks (presave, entity insert/update, cron)
- Vendor cancel event form and route
- Vendor refund request flow

**Files:**
- `myeventlane_event_state.info.yml`
- `myeventlane_event_state.services.yml`
- `myeventlane_event_state.module`
- `myeventlane_event_state.install`
- `myeventlane_event_state.routing.yml`
- `myeventlane_event_state.permissions.yml`
- `src/Service/EventStateResolverInterface.php`
- `src/Service/EventStateResolver.php`
- `src/Form/EventCancelForm.php`
- `src/Form/EventRefundRequestForm.php`
- `src/Controller/EventRefundsController.php`

**States:**
- `draft` - Unpublished events
- `scheduled` - Sales not yet started
- `live` - Sales open and capacity available
- `sold_out` - Capacity reached
- `ended` - Event has ended
- `cancelled` - Vendor cancelled
- `archived` - Archived by admin

### 2. `myeventlane_capacity`
**Location:** `web/modules/custom/myeventlane_capacity/`

**Purpose:** Capacity tracking and enforcement engine.

**Key Components:**
- `EventCapacityService` - Computes capacity, sold count, remaining
- Capacity cache invalidation on RSVP/order changes
- `CapacityExceededException` for validation

**Files:**
- `myeventlane_capacity.info.yml`
- `myeventlane_capacity.services.yml`
- `myeventlane_capacity.module`
- `src/Service/EventCapacityServiceInterface.php`
- `src/Service/EventCapacityService.php`
- `src/Exception/CapacityExceededException.php`

**Methods:**
- `getCapacityTotal()` - Returns total capacity (NULL = unlimited)
- `getSoldCount()` - Counts confirmed RSVPs + completed orders
- `getRemaining()` - Calculates remaining capacity
- `isSoldOut()` - Checks if event is sold out
- `assertCanBook()` - Validates booking request (throws exception if capacity exceeded)

### 3. `myeventlane_checkin`
**Location:** `web/modules/custom/myeventlane_checkin/`

**Purpose:** Mobile-first check-in system for events.

**Key Components:**
- `CheckInStorage` service - Abstracts RSVP and ticket attendee storage
- Check-in controller with multiple views (page, scan, list)
- Mobile-optimized templates with large touch targets
- Search functionality for attendees

**Files:**
- `myeventlane_checkin.info.yml`
- `myeventlane_checkin.services.yml`
- `myeventlane_checkin.module`
- `myeventlane_checkin.routing.yml`
- `myeventlane_checkin.permissions.yml`
- `myeventlane_checkin.libraries.yml`
- `src/Service/CheckInStorageInterface.php`
- `src/Service/CheckInStorage.php`
- `src/Controller/CheckInController.php`
- `templates/checkin-page.html.twig`
- `templates/checkin-list.html.twig`
- `css/checkin.css`
- `js/checkin.js`

**Routes:**
- `/vendor/events/{node}/check-in` - Main check-in page
- `/vendor/events/{node}/check-in/scan` - QR scan mode
- `/vendor/events/{node}/check-in/list` - Attendee list view
- `/vendor/events/{node}/check-in/toggle/{attendee_id}` - Toggle check-in status (AJAX)
- `/vendor/events/{node}/check-in/search` - Search attendees (AJAX)

## New Event Fields

All fields are added via `myeventlane_event_state.install`:

1. **`field_sales_start`** (datetime, optional)
   - When ticket/RSVP sales begin
   - Defaults to event publish time if empty

2. **`field_sales_end`** (datetime, optional)
   - When ticket/RSVP sales end
   - Defaults to event end time if empty

3. **`field_event_state`** (list_string)
   - Stores the resolved state (materialized for Views)
   - Values: draft, scheduled, live, sold_out, ended, cancelled, archived

4. **`field_event_state_override`** (list_string, optional)
   - Allows vendor/admin to force a state
   - Highest priority in state resolution (except admin rules)

5. **`field_cancel_reason`** (text_long)
   - Reason for cancellation (shown to attendees)

6. **`field_cancelled_at`** (datetime)
   - Timestamp when event was cancelled

7. **`field_event_capacity_total`** (integer)
   - Total capacity (0 or empty = unlimited)
   - Falls back to existing `field_capacity` if present

## Modified Files

### RSVP Module
- `web/modules/custom/myeventlane_rsvp/src/Form/RsvpPublicForm.php`
  - Added capacity validation in `validateForm()`
  - Prevents RSVP submission if capacity exceeded

### Commerce Module
- `web/modules/custom/myeventlane_commerce/src/Form/TicketSelectionForm.php`
  - Added capacity validation in `validateForm()`
  - Prevents adding tickets to cart if capacity exceeded

### Vendor Role
- `web/sites/default/config/sync/user.role.vendor.yml`
  - Added permissions:
    - `myeventlane_event_state.cancel_event`
    - `myeventlane_event_state.request_refunds`
    - `myeventlane_checkin.access`
    - `myeventlane_checkin.scan`
    - `myeventlane_checkin.toggle`

## State Resolution Logic

The `EventStateResolver` follows this precedence:

1. **Override field** (highest priority)
   - If `field_event_state_override` = `cancelled` → `cancelled`
   - If `field_event_state_override` = `archived` → `archived`

2. **Node published status**
   - If unpublished → `draft`

3. **Sales timing**
   - If `now < sales_start` → `scheduled`

4. **Event timing**
   - If `now > event_end` → `ended`

5. **Capacity check**
   - If `capacity remaining == 0` → `sold_out`

6. **Sales window**
   - If `sales_start ≤ now ≤ sales_end` AND `capacity remaining > 0` → `live`

7. **Default**
   - `live` (if event exists and is published)

## Capacity Engine Integration

### RSVP Events
- Counts `rsvp_submission` entities with `status = 'confirmed'`
- Falls back to legacy `myeventlane_rsvp` table if entity doesn't exist

### Paid Ticket Events
- Counts `commerce_order_item` entities with:
  - `field_target_event` = event ID
  - Parent order state = `completed`
  - Sums `quantity` field

### Capacity Enforcement Points
1. **RSVP Form** - Validates before submission
2. **Ticket Selection Form** - Validates before adding to cart
3. **Commerce Checkout** - Server-side validation (recommended to add)

## Vendor Cancel/Refund Flow

### Cancel Event
- **Route:** `/vendor/events/{node}/cancel`
- **Permission:** `myeventlane_event_state.cancel_event`
- **Form:** `EventCancelForm`
- **Actions:**
  - Sets `field_event_state_override = 'cancelled'`
  - Sets `field_cancel_reason` and `field_cancelled_at`
  - Optionally notifies attendees via email
  - Logs action (if audit logger service exists)

### Request Refunds
- **Route:** `/vendor/events/{node}/refunds`
- **Permission:** `myeventlane_event_state.request_refunds`
- **Controller:** `EventRefundsController`
- **Features:**
  - Lists all completed orders for event
  - Per-order refund request form
  - Status tracking (Not requested / Requested / Refunded / Failed)
  - **Note:** Actual Stripe refund processing requires integration (marked with @todo)

## Check-in System

### Features
- **Mobile-first design** - Large touch targets (44x44px minimum)
- **QR scan mode** - For ticket code scanning (template ready, QR library integration needed)
- **Name/email search** - Fast search with AJAX
- **Toggle check-in** - Mark checked in / undo with one tap
- **Status badges** - Clear visual indicators

### Data Storage
- **RSVP attendees:** Uses `rsvp_submission` entity fields:
  - `checked_in` (boolean)
  - `checked_in_at` (timestamp)
- **Ticket attendees:** Uses `event_attendee` entity fields:
  - `checked_in` (boolean)
  - `checked_in_at` (timestamp)
  - `checked_in_by` (user reference)

### Access Control
- Event owner always has access
- Vendor staff roles (to be implemented - marked with @todo)

## Installation Steps

1. **Enable modules:**
   ```bash
   ddev drush en myeventlane_event_state myeventlane_capacity myeventlane_checkin -y
   ```

2. **Run install hooks:**
   ```bash
   ddev drush updb -y
   ```
   This will create all new fields.

3. **Import configuration:**
   ```bash
   ddev drush cim -y
   ```
   This will add new permissions to vendor role.

4. **Clear cache:**
   ```bash
   ddev drush cr
   ```

5. **Resync states for existing events (optional):**
   ```bash
   ddev drush php:eval "
     \$resolver = \Drupal::service('myeventlane_event_state.resolver');
     \$storage = \Drupal::entityTypeManager()->getStorage('node');
     \$events = \$storage->loadByProperties(['type' => 'event', 'status' => 1]);
     foreach (\$events as \$event) {
       \$state = \$resolver->resolveState(\$event);
       if (\$event->hasField('field_event_state')) {
         \$event->set('field_event_state', \$state);
         \$event->save();
       }
     }
   "
   ```

## Integration Points (TODO)

The following integration points are marked with `@todo` comments and require further implementation:

1. **Stripe Refund Processing**
   - Location: `EventRefundRequestForm`
   - Currently logs refund requests; needs Stripe API integration

2. **Cancellation Email Notifications**
   - Location: `EventCancelForm::submitForm()`
   - Function: `_myeventlane_event_state_notify_cancellation()`
   - Needs integration with messaging module

3. **Vendor Staff Roles**
   - Location: `CheckInController::checkEventAccess()`
   - Currently only checks event ownership
   - Needs vendor staff role system

4. **QR Code Scanning Library**
   - Location: `checkin-scan.html.twig` template
   - Template created but QR scanning JS needs library integration

5. **Audit Log Table**
   - Referenced in `EventCancelForm` but table creation not implemented
   - Optional feature for tracking state changes

6. **Commerce Checkout Validation**
   - Recommended to add capacity check in checkout validation
   - Currently only validated in ticket selection form

## Testing Checklist

- [ ] Create event with sales windows
- [ ] Verify state transitions (scheduled → live → sold_out → ended)
- [ ] Test capacity enforcement in RSVP form
- [ ] Test capacity enforcement in ticket form
- [ ] Cancel event and verify state override
- [ ] Request refunds for paid event
- [ ] Check in RSVP attendee
- [ ] Check in ticket attendee
- [ ] Search attendees by name/email
- [ ] Verify state materialization on RSVP save
- [ ] Verify state materialization on order completion
- [ ] Verify cron resync works

## Performance Considerations

- **Caching:** State and capacity calculations are cached (5 minutes TTL)
- **Cache invalidation:** Automatic on RSVP/order create/update/delete
- **Views-friendly:** State is materialized in `field_event_state` for efficient filtering
- **Cron fallback:** Daily cron resyncs states for all published events

## Security

- All vendor routes check event ownership
- Permissions enforced at route level
- Capacity checks prevent overselling
- Check-in access restricted to event owners (staff roles TODO)

## Notes

- The implementation follows Drupal 11 best practices
- Uses dependency injection throughout
- No `\Drupal::` static calls in new code
- All Twig templates avoid `|raw` filter
- JavaScript uses `once()` and Drupal behaviors
- Mobile-first CSS with touch-friendly targets
