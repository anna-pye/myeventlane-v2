# Event Public Page Rebuild - MEL Funky Design

## Summary

The Event node full view page has been completely rebuilt with a modern, fun, funky MEL product page design. The new layout features:

- **Hero section** with gradient overlay, state badge, title, meta chips, and primary CTA
- **Two-column layout** with sticky sidebar on desktop
- **Card-based sections** for About, Accessibility, Organiser, Location, and Capacity
- **Mobile-first** responsive design with sticky bottom CTA bar
- **Pill-style calendar buttons** (Apple/Outlook .ics and Google Calendar)
- **Funky enhancements**: subtle confetti dots background, gradient wave, animated chips

## Files Created/Modified

### 1. Main Template
**File:** `web/themes/custom/myeventlane_theme/templates/node/node--event--full.html.twig`

Complete rewrite with:
- Hero section with image overlay
- State badge (Scheduled/Live/Sold out/Cancelled/Ended)
- Meta chips include for date, time, location, category
- Primary CTA in hero
- Two-column grid layout
- Sidebar with sticky positioning on desktop
- Card-based sections
- Mobile sticky CTA bar

### 2. Meta Chips Include
**File:** `web/themes/custom/myeventlane_theme/templates/event/_event-meta.html.twig`

New include template that renders:
- Date chip with calendar icon
- Time chip with clock icon
- Location chip with pin icon
- Optional category chip

### 3. SCSS Component
**File:** `web/themes/custom/myeventlane_theme/src/scss/components/_event-node.scss`

Complete styling file with:
- `.mel-event--full` wrapper
- `.mel-event-hero` with image and overlay
- `.mel-status-badge` for state indicators
- `.mel-event-meta` chips styling
- `.mel-event-grid` two-column layout
- `.mel-event-card` card components
- `.mel-event-sidebar` sticky sidebar
- `.mel-calendar-buttons` pill button group
- `.mel-sticky-cta` mobile bottom bar
- Extra funky enhancements (confetti dots, gradient wave, animated chips)

### 4. SCSS Import
**File:** `web/themes/custom/myeventlane_theme/src/scss/main.scss`

Added import:
```scss
@use 'components/event-node';
```

## Field Mappings & Variables

All variables are provided by `myeventlane_theme_preprocess_node()` in `myeventlane_theme.theme`:

### Event State
- **Variable:** `mel_event_state`
- **Values:** `scheduled`, `live`, `sold_out`, `cancelled`, `ended`, `draft`
- **Source:** `EventStateResolverInterface::resolveState()`

### CTA Data
- **Variable:** `mel_all_ctas`
- **Type:** Array of render arrays keyed by type (`tickets`, `rsvp`, `waitlist`, `external`)
- **Source:** `EventModeManager::getAllCtas()`
- **Variable:** `mel_is_bookable`
- **Type:** Boolean
- **Source:** `EventModeManager::isBookable()`
- **Variable:** `content.mel_ticket_cta`
- **Type:** Render array for primary CTA
- **Source:** `EventModeManager::getPrimaryCta()`

### Location Data
- **Variable:** `mel_venue`
- **Type:** String (venue name)
- **Source:** `field_location` or `field_event_venue` or `field_venue_name`
- **Variable:** `mel_address`
- **Type:** String (full address)
- **Source:** `field_location` or `field_event_address`
- **Variable:** `mel_lat`
- **Type:** Float (latitude)
- **Source:** `field_location_latitude` or `field_event_lat`
- **Variable:** `mel_lng`
- **Type:** Float (longitude)
- **Source:** `field_location_longitude` or `field_event_lng`

### Capacity Data
- **Variable:** `mel_capacity`
- **Type:** Array with keys:
  - `capacity`: int|null (total capacity)
  - `attendee_count`: int (current attendees)
  - `remaining`: int|null (remaining spots)
- **Source:** `EventMetricsServiceInterface`

### Categories
- **Variable:** `mel_categories`
- **Type:** Array of category objects with `tid`, `name`, `color`
- **Source:** `field_category`, `field_categories`, or `field_event_category`

### Node Fields Used
- `node.label` - Event title
- `node.field_event_start` - Start date/time
- `node.field_event_end` - End date/time
- `content.field_event_image` - Hero image
- `content.body` - About section content
- `content.field_accessibility` - Accessibility terms
- `node.getOwner()` - Organiser user entity

## Calendar Button URLs

Calendar buttons are generated in the template using:

1. **Apple / Outlook (.ics):**
   - Route: `myeventlane_rsvp.ics_download`
   - Parameters: `{'node': node.id}`

2. **Google Calendar:**
   - URL: `https://calendar.google.com/calendar/render?action=TEMPLATE`
   - Parameters: `text`, `dates`, `details`, `location`

The template generates these URLs directly (no service call needed in Twig).

## CTA Logic

The CTA is handled by `event-cta.html.twig` include, which uses:

- **Scheduled:** Disabled button "Sales open on [date]"
- **Live + RSVP:** "RSVP Now" button
- **Live + Paid:** "Buy Tickets" button
- **Sold out:** "Join Waitlist" button (if available)
- **Cancelled/Ended:** No CTA button, shows status banner

## Map Rendering

The map is rendered by JavaScript from `myeventlane_location` module:
- Container: `.myeventlane-event-map-container`
- Data attributes: `data-latitude`, `data-longitude`, `data-title`
- Library: `myeventlane_location/event_map` (attached automatically when coords exist)

## Build Commands

After making changes, rebuild the theme assets:

```bash
# From theme directory
cd web/themes/custom/myeventlane_theme

# Development build (watch mode)
ddev exec npm run dev

# Production build
ddev exec npm run build

# Clear Drupal cache
ddev drush cr
```

## Design Features

### Hero Section
- Full-width hero image with gradient overlay (secondary → primary → accent)
- State badge with backdrop blur
- Large title with text shadow
- Meta chips row (date, time, location, category)
- Primary CTA button

### Grid Layout
- **Desktop (1024px+):** Two-column grid
  - Left: Main content (About, Accessibility, Organiser)
  - Right: Sticky sidebar (CTA, Date/Time, Calendar, Location/Map, Capacity)
- **Mobile:** Single column with sticky bottom CTA bar

### Cards
- All sections use `.mel-event-card` base class
- Hover lift effect on desktop
- Consistent padding and border radius
- Box shadow for depth

### Calendar Buttons
- Pill-shaped buttons (`.mel-btn-pill`)
- Ghost variant (outlined)
- Grouped in flex container
- "Apple / Outlook (.ics)" and "Google Calendar"

### Funky Enhancements
- Subtle confetti dots background pattern (very light, 3% opacity)
- Gradient wave at bottom of hero
- Animated chip hover effect (ripple)
- Card hover lift animation

## Accessibility

- All icons have `aria-hidden="true"` with text labels
- Time elements use proper `<time>` tags with `datetime` attributes
- Address uses `<address>` semantic tag
- Focus states on all interactive elements
- WCAG-compliant color contrasts

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Mobile-first responsive design
- CSS Grid and Flexbox
- Backdrop filter (with fallback)

## Next Steps

1. **Build assets:** Run `ddev exec npm run build` in theme directory
2. **Clear cache:** Run `ddev drush cr`
3. **Test:** View an event node in full view mode
4. **Polish pass:** Review spacing, button hierarchy, typography
5. **Screenshot:** Capture the new event page for final review

## Notes

- No inline CSS or JS (all in SCSS files)
- No `|raw` filters in Twig (safe output only)
- Uses existing MEL tokens and SCSS pipeline
- Uses existing state/CTA logic from Phase 1–5
- Map uses existing `myeventlane_location` module output
- Calendar URLs generated in template (no backend changes)
