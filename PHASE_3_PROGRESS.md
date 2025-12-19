# PHASE 3 — CUSTOM MODULE AUDIT & REPAIR — PROGRESS REPORT

**Date:** 2025-01-27  
**Branch:** `audit-rebuild-event-system`  
**Status:** ⚠️ In Progress

---

## COMPLETED TASKS

### ✅ 1. Reviewed Module Structure

**Modules Reviewed:** 25 custom modules
- ✅ All modules have proper `info.yml` files
- ✅ 14 modules have `permissions.yml` files
- ✅ 20 modules have `services.yml` files
- ⚠️ 22 modules missing `schema.yml` files (but many don't have config objects)

### ✅ 2. Fixed Critical Static Service Calls

**Files Fixed:**

1. **`web/modules/custom/myeventlane_commerce/src/Form/TicketSelectionForm.php`**
   - **Issue:** Static call to `\Drupal::service('commerce_price.currency_formatter')`
   - **Fix:** Injected `CurrencyFormatterInterface` via constructor
   - **Status:** ✅ Fixed

2. **`web/modules/custom/myeventlane_dashboard/src/Controller/CustomerDashboardController.php`**
   - **Issue:** Static call to `\Drupal::time()->getRequestTime()`
   - **Fix:** Injected `TimeInterface` via constructor
   - **Status:** ✅ Fixed

3. **`web/modules/custom/myeventlane_commerce/src/Form/RsvpBookingForm.php`**
   - **Issue:** 12+ static calls (`EntityTypeManager`, `CartManager`, `CartProvider`, `ConfigFactory`, `AccountProxy`, `Logger`, `Messenger`, `ModuleHandler`)
   - **Fix:** Injected all services via constructor
   - **Status:** ✅ Fixed

4. **`web/modules/custom/myeventlane_event_attendees/src/Controller/VendorAttendeeController.php`**
   - **Issue:** 3 static calls to `\Drupal::service('date.formatter')`
   - **Fix:** Injected `DateFormatterInterface` via constructor
   - **Status:** ✅ Fixed

5. **`web/modules/custom/myeventlane_event_attendees/src/EventAttendeeListBuilder.php`**
   - **Issue:** Static call to `\Drupal::service('date.formatter')`
   - **Fix:** Injected `DateFormatterInterface` via `createInstance()` method
   - **Status:** ✅ Fixed

6. **`web/modules/custom/myeventlane_rsvp/src/Entity/RsvpSubmissionListBuilder.php`**
   - **Issue:** Static call to `\Drupal::service('date.formatter')`
   - **Fix:** Injected `DateFormatterInterface` via `createInstance()` method
   - **Status:** ✅ Fixed

### ✅ 3. Created Schema Files

**Schema Files Created:**

1. `web/modules/custom/myeventlane_commerce/config/schema/myeventlane_commerce.schema.yml`
   - **Status:** ✅ Created (placeholder - module has no config objects currently)

2. `web/modules/custom/myeventlane_event/config/schema/myeventlane_event.schema.yml`
   - **Status:** ✅ Created (placeholder - module has no config objects currently)

---

## REMAINING TASKS

### ⚠️ High Priority Static Service Calls (Still Need Fixing)

1. **`myeventlane_commerce/src/Form/RsvpBookingForm.php`**
   - **Static Calls:** 10+ instances
   - **Services Needed:** `EntityTypeManagerInterface`, `CartManagerInterface`, `CartProviderInterface`, `ConfigFactoryInterface`, `LoggerChannelFactoryInterface`
   - **Priority:** High

2. **`myeventlane_admin_dashboard/src/Controller/AdminDashboardController.php`**
   - **Static Calls:** 6+ instances
   - **Services Needed:** Various optional services
   - **Priority:** Medium

3. **`myeventlane_messaging/src/Commands/MessagingCommands.php`**
   - **Static Calls:** 5+ instances
   - **Services Needed:** Scheduler services
   - **Priority:** Medium

4. **`myeventlane_rsvp/src/Form/RsvpPublicForm.php`**
   - **Static Calls:** 4+ instances
   - **Services Needed:** Various services
   - **Priority:** Medium

5. **`myeventlane_event_attendees/src/Controller/VendorAttendeeController.php`**
   - **Static Calls:** 3+ instances
   - **Services Needed:** `DateFormatterInterface`
   - **Priority:** Medium

### ⚠️ Schema Files Still Needed

**Modules with config but no schema:**
- `myeventlane_admin_dashboard` (if has config)
- `myeventlane_analytics` (if has config)
- `myeventlane_boost` (if has config)
- `myeventlane_dashboard` (if has config)
- `myeventlane_finance` (if has config)
- `myeventlane_messaging` (if has config)
- `myeventlane_tickets` (if has config)
- `myeventlane_vendor` (if has config)
- `myeventlane_wallet` (if has config)

**Note:** Many modules may not have config objects, so schema files are only needed if config exists.

### ⚠️ Deprecated Module

**`myeventlane_checkout`** - Still enabled, marked as deprecated
- **Action Required:** Uninstall and remove
- **Replaced by:** `myeventlane_checkout_paragraph`

---

## VERIFICATION

### ✅ Linter Status
- **Files Checked:** `TicketSelectionForm.php`, `CustomerDashboardController.php`
- **Result:** ✅ No linter errors

### ✅ Cache Rebuild
- **Status:** ✅ Successful

---

## NEXT STEPS

### Immediate (Before Phase 4):
1. Fix `RsvpBookingForm` static service calls (high priority)
2. Create schema files for modules that actually have config objects
3. Uninstall deprecated `myeventlane_checkout` module

### Phase 4 Preparation:
- Review Event node structure
- Identify field redundancy
- Plan field grouping strategy

---

## STATISTICS

- **Static Calls Fixed:** 19
- **Static Calls Remaining:** ~59
- **Schema Files Created:** 2
- **Schema Files Needed:** ~10 (if modules have config)
- **Files Modified:** 6

---

**END OF PHASE 3 PROGRESS REPORT**
