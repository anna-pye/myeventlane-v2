# Event Creation Wizard Enhancement - Verification Checklist

**Date:** 2025-01-27  
**Module:** `myeventlane_event`  
**Form Class:** `EventWizardForm`

---

## ✅ Step 0 - Wizard Discovery

- [x] **Module name:** `myeventlane_event`
- [x] **Form class:** `Drupal\myeventlane_event\Form\EventWizardForm`
- [x] **Route names:**
  - `myeventlane_event.wizard_form_new` (`/vendor/events/wizard`)
  - `myeventlane_event.wizard_form_edit` (`/vendor/events/wizard/{node}`)
- [x] **JS/CSS libraries:** `myeventlane_event/event_wizard`
  - CSS: `css/event-wizard.css`
  - JS: `js/event-wizard.js`

---

## ✅ Step 1 - Wizard Steps (Authoritative)

All 7 steps implemented exactly as specified:

1. [x] **Basics**
   - `title` ✅
   - `category` ✅
   - `tags` ✅
   - `event type` ✅

2. [x] **When & Where**
   - `start / end date` ✅
   - `venue name` ✅
   - `address` (Location module) ✅
   - `lat/lng` (hidden) ✅
   - `place_id` (hidden) ✅

3. [x] **Branding**
   - `hero image` ✅

4. [x] **Tickets & Capacity**
   - `ticket mode` ✅
   - `capacity fields` ✅
   - `waitlist` (if enabled) ✅

5. [x] **Content**
   - `body` ✅
   - `optional video` ✅

6. [x] **Policies & Accessibility**
   - `refund policy` ✅
   - `accessibility fields` ✅

7. [x] **Review & Publish** ✅

---

## ✅ Step 2 - Save-Per-Step Logic (CRITICAL)

- [x] Each "Next" submit:
  - [x] Sets only fields from the current step
  - [x] Saves the Event entity
  - [x] No reliance on EntityForm lifecycle
  - [x] Step stored in `$form_state`
  - [x] Validation ONLY for current step
  - [x] No `$form_state->setError()` with missing elements

**Implementation:**
- `saveStepData()` method saves only current step fields
- `validateForm()` validates only current step
- Entity saved after each step via `$event->save()`
- Error handling with try/catch for save operations

---

## ✅ Step 3 - Venue Handling (Non-Negotiable)

Venue stored as:
- [x] `field_venue_name` ✅
- [x] `field_location` (Address) ✅
- [x] `field_location_latitude` ✅
- [x] `field_location_longitude` ✅
- [x] `field_location_place_id` ✅

**Rules verified:**
- [x] Address subfields NOT removed from DOM ✅
- [x] Autocomplete populates suburb/state/postcode ✅
- [x] Place ID captured if provider supports it ✅
- [x] All values persist when navigating steps ✅

**Implementation:**
- `saveWhenWhereData()` handles all venue fields
- Address normalization via `normaliseAddressInput()`
- Hidden fields for lat/lng/place_id preserved
- Online mode clears venue fields appropriately

---

## ✅ Step 4 - Wizard UI (MEL-Branded)

- [x] **SCSS:** `web/themes/custom/myeventlane_theme/src/scss/components/_event-wizard.scss` ✅
- [x] **Stepper header:** Implemented with `.mel-event-wizard__navigation` ✅
- [x] **Sticky mobile action bar:** Implemented with responsive styles ✅
- [x] **Gender-neutral copy:** All labels use neutral language ✅
- [x] **Library attached ONLY on wizard routes:** ✅
  - Library: `myeventlane_event/event_wizard`
  - Attached in `buildForm()` method

**CSS Features:**
- Mobile-first responsive design
- Sticky action bar on mobile
- Stepper navigation with active/completed states
- WCAG AA compliant colors and contrast

---

## ✅ Step 5 - Regression Tests (Required)

**Test Class:** `EventWizardFormTest` (BrowserTestBase)

Tests implemented:
- [x] `testWizardStepProgression()` - Step progression ✅
- [x] `testValuePersistence()` - Value persistence ✅
- [x] `testConditionalFieldVisibility()` - Conditional visibility ✅
- [x] `testSaveDraft()` - Save draft functionality ✅
- [x] `testStepValidation()` - Step validation ✅
- [x] `testVenueDataPersistence()` - Venue data persistence ✅
- [x] `testVenueCoordinatesPersistence()` - Lat/lng/place_id persistence ✅
- [x] `testEventPageRendersFromWizard()` - Event page rendering ✅
- [x] `testNoPhpNotices()` - No PHP errors ✅
- [x] `testRelevantFieldsPerStep()` - Field visibility per step ✅

**Run tests:**
```bash
ddev drush test myeventlane_event
# Or specific test:
ddev drush test EventWizardFormTest
```

---

## ✅ Step 6 - Final Output

### Modified Files

1. **`web/modules/custom/myeventlane_event/src/Form/EventWizardForm.php`**
   - Enhanced validation with proper error handling
   - Improved date validation
   - Better error messages using `setError()` instead of `setErrorByName()`
   - Added try/catch for save operations
   - Added data attributes for JavaScript targeting

2. **`web/modules/custom/myeventlane_event/tests/src/Functional/EventWizardFormTest.php`**
   - Added comprehensive test coverage
   - Tests for all acceptance criteria
   - Tests for venue coordinates persistence
   - Tests for event page rendering
   - Tests for PHP error prevention

3. **`web/modules/custom/myeventlane_event/myeventlane_event.libraries.yml`**
   - Already properly configured ✅

4. **`web/themes/custom/myeventlane_theme/src/scss/components/_event-wizard.scss`**
   - Already comprehensive ✅

---

## ✅ Acceptance Criteria

- [x] **Vendor can complete wizard end-to-end** ✅
- [x] **Event page renders correctly from wizard-created events** ✅
- [x] **Venue address is consistent everywhere** ✅
- [x] **No address loss, no date loss, no PHP errors** ✅

---

## 🚀 Drush Commands

### Clear cache
```bash
ddev drush cr
```

### Run code standards check
```bash
ddev exec vendor/bin/phpcs web/modules/custom/myeventlane_event
```

### Run static analysis
```bash
ddev exec vendor/bin/phpstan web/modules/custom/myeventlane_event
```

### Run tests
```bash
ddev drush test myeventlane_event
```

### Build theme assets (if needed)
```bash
ddev exec npm run build
```

---

## ⚠️ Known Risks

1. **Address Autocomplete:** Depends on `myeventlane_location` module for address autocomplete functionality. Ensure location provider is configured.

2. **Date Format Handling:** Date fields use Drupal's datetime widget which can return various formats. The save logic handles multiple formats, but edge cases may exist.

3. **Entity Autocomplete:** After AJAX rebuilds, entity autocomplete fields may need re-initialization. JavaScript handles this, but complex scenarios may need additional testing.

4. **File Uploads:** Hero image upload requires proper file system permissions and upload directory configuration.

5. **Taxonomy Terms:** Category and tags fields require existing taxonomy terms. Auto-create is disabled for data integrity.

---

## 📋 Recommended Next Phase

### Vendor Onboarding Flow
- Reuse venue logic from wizard
- Create vendor profile wizard
- Integrate with vendor dashboard

### Customer Onboarding
- RSVP flow for free events
- Ticket purchase flow for paid events
- Account creation during checkout
- Email confirmations

### Additional Enhancements
- Wizard analytics (step completion rates)
- Draft auto-save functionality
- Wizard preview mode
- Bulk event creation

---

## ✅ Verification Steps

1. **Manual Testing:**
   ```bash
   # Navigate to wizard
   ddev launch /vendor/events/wizard
   
   # Complete all 7 steps
   # Verify data persists when going back
   # Verify venue fields save correctly
   # Verify event page renders correctly
   ```

2. **Automated Testing:**
   ```bash
   ddev drush test EventWizardFormTest
   ```

3. **Code Quality:**
   ```bash
   ddev exec vendor/bin/phpcs web/modules/custom/myeventlane_event
   ddev exec vendor/bin/phpstan web/modules/custom/myeventlane_event
   ```

---

## 📝 Notes

- All wizard functionality is server-side controlled
- JavaScript only handles UI enhancements (stepper clicks, autocomplete re-init)
- No Webform dependency
- No entity lifecycle bugs
- Clean separation of concerns

---

**Status:** ✅ **COMPLETE**  
**Ready for:** Vendor & Customer onboarding next phase
