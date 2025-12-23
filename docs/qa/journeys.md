# MyEventLane QA Journeys

This document describes key user journeys for testing and QA of the MyEventLane platform.

## Vendor Event Creation Flow

### Prerequisites
- Vendor account with `create event content` permission
- Vendor entity linked to user account

### Journey: Creating a New Event

1. **Access Event Creation**
   - Navigate to vendor dashboard
   - Click "Create Event" or navigate to `/vendor/events/add`

2. **Wizard Steps**
   The event wizard presents 5 steps:
   
   **Step 1: Basics**
   - Event title (required)
   - Event description/body
   - Event image
   - Event type/category
   - Event start and end dates/times
   - Location mode (venue/online)
   - If venue: address field (defaults to Australia)
   - If online: online event URL

   **Step 2: Sales & Visibility**
   - Sales start date/time (optional - defaults to publish time)
   - Sales end date/time (optional)
   - Published status checkbox
   - Promoted checkbox

   **Step 3: Tickets**
   - Booking type selection (RSVP, Paid, Both, External)
   - For Paid/Both: Ticket product selection
   - For External: External URL
   - Event type field configuration

   **Step 4: Capacity & Waitlist**
   - Capacity limit (optional - unlimited if not set)
   - Waitlist capacity (optional)

   **Step 5: Review & Publish**
   - Review summary of event configuration
   - Diagnostics widget shows all issues
   - Publish button (disabled if blocking diagnostics exist)
   - Save draft button

3. **Diagnostics Widget**
   - Appears in right sidebar during wizard
   - Updates in real-time as you complete steps
   - Shows status indicators: ✓ (ok), ⚠ (warning), ✕ (fail)
   - Provides "Fix this" links to relevant fields
   - Blocks publish if critical issues exist (admins can override)

4. **Validation**
   - Title must be at least 3 characters
   - Start date must be set
   - End date must be after start date
   - Location must be specified (venue or online)
   - Organisation (vendor) is auto-assigned (hidden from vendors)
   - Paid events must have a ticket product linked
   - External events must have a URL

5. **Save/Publish**
   - "Save draft" saves as unpublished
   - "Publish" button only available on Review step
   - Publish disabled if diagnostics show blocking issues
   - Admin users can override and publish despite warnings

### Location Field Behavior

- **Default Country**: Australia (AU) is automatically selected
- **Online Event Toggle**: When "online" is selected:
  - Address widget is hidden
  - Address values are cleared (if previously set)
  - Online URL field becomes visible and required
- **Address Autocomplete**: Uses location module autocomplete
- **Geocoding**: Coordinates are saved when address is selected

### Organisation Field Constraints

- Field is **hidden from vendors** in the form
- System automatically assigns organisation based on:
  - Vendor entity linked to current user
  - Or default vendor based on user's vendor membership
- Diagnostics check:
  - ❌ **Fail** if organisation is missing
  - ⚠️ **Warn** if organisation doesn't match user's vendor (non-admins)
- Admins can view and override organisation assignment

## Diagnostics Blocking Rules

### Blocking Issues (Prevent Publish)
- Missing event title
- Missing start date
- Invalid date range (end before start)
- Missing organisation/vendor assignment
- Paid event without ticket product
- External event without URL

### Warning Issues (Allow Publish)
- Missing location (venue or online URL)
- Capacity not set (recommended but not required)
- Sales window configuration warnings
- Waitlist not enabled for events with capacity

### Diagnostics Widget Features

- **Real-time Updates**: Widget refreshes via AJAX when step changes
- **Scope-based Filtering**: Only shows relevant diagnostics for current step
- **Fix Links**: Direct links to problematic fields with fragment anchors
- **Status Summary**: Overall status indicator at top
- **Friendly Messages**: Plain language explanations, no technical jargon

## Public Event Page Behavior

### Event State Badge

The event hero displays a state badge indicating current status:
- **Scheduled**: Sales haven't started yet
- **Live**: Event is currently accepting bookings
- **Sold Out**: Event has reached capacity
- **Cancelled**: Event has been cancelled
- **Ended**: Event has ended
- **Draft**: Event is not published (only visible to editors)

### CTA Logic (Single Source of Truth)

| State | CTA Display |
|-------|-------------|
| Scheduled | Disabled button: "Sales open on [date]" |
| Live (RSVP) | "RSVP Now" button (enabled) |
| Live (Paid) | "Buy Tickets" button (enabled) |
| Live (Both) | Shows both RSVP and Tickets options |
| Sold Out | "Join Waitlist" button (if waitlist enabled) or "Sold Out" (disabled) |
| Cancelled | No CTA |
| Ended | No CTA |
| Draft | No CTA (page not visible to public) |

### Event Page Layout

**Hero Section**
- Full-width image with pastel gradient overlay
- Title, date/time, location
- State badge
- CTA (desktop: in sidebar, mobile: sticky bottom)

**Content Area**
- Two-column layout on desktop (main content + sidebar)
- Single column on mobile
- About section
- Accessibility section (if present)
- Organiser section

**Sidebar (Sticky on Desktop)**
- Date & time with calendar links (Google, Outlook, .ics download)
- Location with embedded map (if coordinates available)
- Capacity/attendance information
- Desktop CTA button

**Mobile Sticky CTA**
- Fixed bottom bar on mobile
- Shows current CTA based on event state
- Large tap target for accessibility

## Admin Override Checks

- Admins can view diagnostics but are not blocked by them
- Admins can see organisation field (vendors cannot)
- Admins can publish events with blocking issues
- Diagnostics still show issues but don't prevent action for admins
- Permission required: `administer nodes` or `bypass node access`

## Testing Checklist

### Vendor Event Creation
- [ ] Can create event through wizard
- [ ] All 5 wizard steps are accessible
- [ ] Diagnostics widget appears in sidebar
- [ ] Diagnostics update when changing steps
- [ ] Organisation field is hidden
- [ ] Location defaults to Australia
- [ ] Online toggle hides address field
- [ ] Publish button disabled with blocking issues
- [ ] Can save draft at any time
- [ ] Can publish from Review step (if no blocking issues)

### Diagnostics
- [ ] Widget shows correct status indicators
- [ ] Fix links navigate to correct fields
- [ ] AJAX updates work without page refresh
- [ ] Scope filtering shows only relevant diagnostics
- [ ] Organisation check fails if missing
- [ ] Organisation check warns on mismatch (non-admins)

### Public Event Page
- [ ] State badge displays correctly
- [ ] CTA matches event state
- [ ] Scheduled events show disabled CTA with date
- [ ] Live events show enabled CTA
- [ ] Sold out events show waitlist or disabled CTA
- [ ] Sidebar is sticky on desktop
- [ ] Mobile sticky CTA appears on mobile
- [ ] Calendar links work correctly
- [ ] Map displays if coordinates available
- [ ] Capacity info displays correctly

### Mobile Experience
- [ ] Sticky CTA at bottom of screen
- [ ] Large tap targets (minimum 44x44px)
- [ ] No hover-only interactions
- [ ] Sidebar is collapsible/accessible
- [ ] Hero image responsive
- [ ] Content readable without horizontal scroll

## Notes

- All date/time fields use site timezone
- Location field uses Address module with Australia as default
- State resolution uses EventStateResolver service (canonical source)
- CTA logic uses EventModeManager service (canonical source)
- Diagnostics use existing services (no duplicated logic)
