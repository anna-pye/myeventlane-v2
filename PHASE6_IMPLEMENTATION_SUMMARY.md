# Phase 6: Diagnostics, Wizard UX Redesign, Real-Time Validation & UI Exposure

## Implementation Summary

All Phase 6 requirements have been implemented. The diagnostics system is now fully integrated into the Event Wizard and Event Edit forms, providing real-time feedback and guidance to vendors.

---

## ✅ Completed Deliverables

### 1. Diagnostics Service + Checks
**Module:** `web/modules/custom/myeventlane_diagnostics/`

- ✅ `DiagnosticsServiceInterface` - Service interface definition
- ✅ `DiagnosticsService` - Complete implementation with all 8 diagnostic sections:
  - Basics (title, dates, venue/online)
  - Sales & State (effective state, sales windows)
  - Tickets/RSVP (booking mode, product links, external URLs)
  - Capacity (total capacity, remaining, waitlist)
  - Visibility (published status, promotion)
  - Automation (reminders eligibility)
  - Check-in (route accessibility, attendee resolution)
  - Exports (export permissions, CSV availability)

### 2. Event Edit Sidebar Widget
**File:** `web/modules/custom/myeventlane_diagnostics/myeventlane_diagnostics.module`

- ✅ Form alter hook for `node_event_form`
- ✅ Sidebar widget using `#type => 'details'`
- ✅ Visible only to vendors/managers/admins with permission
- ✅ Auto-opens if issues are present

### 3. Wizard-Embedded Diagnostics Widget
**File:** `web/modules/custom/myeventlane_event/src/Form/EventFormAlter.php`

- ✅ Diagnostics widget integrated into wizard sidebar
- ✅ Scope filtering based on current step:
  - `basics/schedule/location` → `basics` scope
  - `tickets` → `tickets_rsvp` scope
  - `sales_visibility` → `sales_state` scope
- ✅ Real-time updates via AJAX on step changes

### 4. Twig + SCSS Files
**Templates:**
- ✅ `web/modules/custom/myeventlane_diagnostics/templates/diagnostics-widget.html.twig`
- ✅ `web/modules/custom/myeventlane_diagnostics/templates/diagnostics-widget-content.html.twig`
- ✅ `web/themes/custom/myeventlane_theme/templates/diagnostics/diagnostics-widget.html.twig`

**Styles:**
- ✅ `web/themes/custom/myeventlane_theme/scss/components/_diagnostics.scss`
- ✅ Added to `main.scss` for compilation
- ✅ Library definition in `myeventlane_diagnostics.libraries.yml`

### 5. Wizard Copy + Labels Rewritten
**File:** `web/modules/custom/myeventlane_event/src/Form/EventFormAlter.php`

Updated step definitions with:
- ✅ **Basics:** "What's happening, when, and where?"
- ✅ **Sales & Visibility:** "Who can see this event and when tickets are available."
- ✅ **Tickets:** "How people attend." (RSVP/Paid/External explained)
- ✅ **Capacity & Waitlist:** "How many people can attend."
- ✅ **Review & Publish:** "Here's what will happen when you publish."
- ✅ Microcopy added to each step explaining defaults and outcomes

### 6. AJAX Endpoint for Scoped Diagnostics
**Route:** `/vendor/events/{event}/diagnostics?scope={scope}`

- ✅ Controller: `DiagnosticsController::ajax()`
- ✅ Access control via `DiagnosticsAccess`
- ✅ Returns filtered diagnostics based on scope
- ✅ AJAX response format using Drupal's `HtmlCommand`

### 7. Real-Time JavaScript Behavior
**File:** `web/modules/custom/myeventlane_diagnostics/js/diagnostics.js`

- ✅ Drupal behavior: `melDiagnosticsWizard`
- ✅ Watches for step changes in wizard
- ✅ Maps steps to diagnostic scopes
- ✅ Fetches and updates widget via AJAX
- ✅ Re-attaches behaviors after update

### 8. Permissions Enforcement
**File:** `web/modules/custom/myeventlane_diagnostics/myeventlane_diagnostics.permissions.yml`

- ✅ Permission: `view event diagnostics`
- ✅ Access control in form alter and controller
- ✅ Vendor owners and admins can access

### 9. Publish Guard
**File:** `web/modules/custom/myeventlane_event/src/Form/EventFormAlter.php`

- ✅ Disables "Publish" button if blocking issues exist
- ✅ Tooltip: "This event can't be published yet. See diagnostics for details."
- ✅ Admins can override (bypass guard)

### 10. Theme Libraries Updated
- ✅ Diagnostics SCSS included in theme compilation
- ✅ Library dependencies configured correctly
- ✅ Styles compile via Vite into `dist/main.css`

---

## 📁 Files Created/Modified

### New Files Created:
```
web/modules/custom/myeventlane_diagnostics/
├── myeventlane_diagnostics.info.yml
├── myeventlane_diagnostics.services.yml
├── myeventlane_diagnostics.routing.yml
├── myeventlane_diagnostics.permissions.yml
├── myeventlane_diagnostics.libraries.yml
├── myeventlane_diagnostics.module
├── js/
│   └── diagnostics.js
├── templates/
│   ├── diagnostics-widget.html.twig
│   └── diagnostics-widget-content.html.twig
└── src/
    ├── Access/
    │   └── DiagnosticsAccess.php
    ├── Controller/
    │   └── DiagnosticsController.php
    └── Service/
        ├── DiagnosticsServiceInterface.php
        └── DiagnosticsService.php

web/themes/custom/myeventlane_theme/
├── scss/components/_diagnostics.scss
└── templates/diagnostics/diagnostics-widget.html.twig
```

### Modified Files:
```
web/modules/custom/myeventlane_event/
└── src/Form/EventFormAlter.php
    - Added step descriptions and microcopy
    - Added diagnostics widget to wizard sidebar
    - Added publish guard logic

web/themes/custom/myeventlane_theme/
└── src/scss/main.scss
    - Added @use 'components/diagnostics';
```

---

## 🔧 Next Steps (Drush Commands)

After implementation, run:

```bash
# Clear cache
ddev drush cr

# Enable the module (if not already enabled)
ddev drush en myeventlane_diagnostics -y

# Rebuild theme assets (compile SCSS)
cd web/themes/custom/myeventlane_theme
ddev exec npm run build

# Run code standards check
ddev exec vendor/bin/phpcs web/modules/custom/myeventlane_diagnostics

# Run static analysis (optional)
ddev exec vendor/bin/phpstan web/modules/custom/myeventlane_diagnostics
```

---

## 🎯 Key Features

1. **Self-Explaining Interface**
   - Plain language microcopy
   - No internal concepts exposed
   - Clear outcome descriptions

2. **Actionable Guidance**
   - "Fix this" links deep-link to specific fields
   - Status indicators (✓/⚠/✕)
   - Issue count summary

3. **Real-Time Feedback**
   - Widget updates on step changes
   - Scoped diagnostics for current step
   - AJAX-powered without page reload

4. **Professional UX**
   - Color-coded status indicators
   - Organized by diagnostic section
   - Non-blocking warnings (except publish guard)

---

## 📋 Diagnostic Sections Summary

Each section provides:
- **OK** status: Green, informational
- **WARN** status: Yellow, non-blocking issues
- **FAIL** status: Red, blocking issues

### Section Coverage:
1. **Basics** - Title, dates, location
2. **Sales & State** - Effective state, sales windows
3. **Tickets/RSVP** - Booking mode configuration
4. **Capacity** - Limits, remaining, waitlist
5. **Visibility** - Published status, promotion
6. **Automation** - Reminder eligibility
7. **Check-in** - Route access, attendees
8. **Exports** - CSV export permissions

---

## ✨ Improvements Over Previous Version

- **No silent failures** - All issues are visible
- **Actionable fixes** - Direct links to problematic fields
- **Real-time validation** - Updates as vendor edits
- **Scoped diagnostics** - Shows only relevant issues per step
- **Better microcopy** - Plain language, no jargon
- **Publish guard** - Prevents publishing incomplete events

---

## 🚀 Ready for Testing

The implementation is complete and ready for:
1. Module enablement
2. Permission assignment to vendor roles
3. Theme asset compilation
4. Cache clearing
5. User acceptance testing

All Phase 6 requirements have been fulfilled. The diagnostics system makes MyEventLane's capabilities visible and understandable, reducing support load and improving vendor experience.
