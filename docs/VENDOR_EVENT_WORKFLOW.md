# Vendor Event Creation & Management Workflow

## Overview

This document outlines the recommended workflow for vendors to create and manage events in MyEventLane using the new Humanitix-style vendor journey.

## Current Implementation

### Sidebar Cleanup

The vendor event management pages now:
- **Hide the Drupal admin sidebar** - Only the custom vendor navigation is shown
- **Hide local tasks/tabs** - Clean interface without Drupal's default tabs
- **Full-width content area** - Maximum space for the event management interface

### Event Creation Flow

1. **User clicks "Create Event"** → `/create-event`
2. **Gateway checks:**
   - Anonymous → Redirect to login
   - No vendor → Redirect to `/vendor/onboard`
   - Has vendor → **Creates new event** and redirects to `/vendor/event/{id}/edit`

3. **Vendor Journey Steps:**
   - `/vendor/event/{id}/edit` - Event information (title, dates, location, images)
   - `/vendor/event/{id}/design` - Page design (hero, logo, colors, CTA)
   - `/vendor/event/{id}/content` - Page content (description, body text)
   - `/vendor/event/{id}/tickets` - Ticket types (pricing, capacity)
   - `/vendor/event/{id}/checkout-questions` - Custom attendee questions
   - `/vendor/event/{id}/promote` - Promote (coming soon)
   - `/vendor/event/{id}/payments` - Payments & fees (coming soon)
   - `/vendor/event/{id}/comms` - Communications (coming soon)
   - `/vendor/event/{id}/advanced` - Advanced settings (coming soon)

## Recommended Workflow

### Phase 1: Initial Setup (First Time Users)

1. **Registration & Onboarding**
   - User registers → Creates organiser account
   - Completes vendor onboarding (`/vendor/onboard`)
   - Sets up vendor profile (name, description, logo)

2. **First Event Creation**
   - Clicks "Create Event" → New event created automatically
   - Lands on "Event information" step
   - Fills in basic details (title, dates, location)

### Phase 2: Event Information (Step 1)

**Purpose:** Core event details that are required for all events.

**Fields to Complete:**
- Event title
- Start/end dates and times
- Location (address or online)
- Event image/hero
- Event description (brief)
- Event type (paid/RSVP/both)
- Visibility settings

**Best Practices:**
- Complete all required fields before moving to next step
- Upload a high-quality hero image (1200x630px recommended)
- Write a clear, concise description

**Navigation:**
- "Continue" → Goes to "Page design"
- Can save and return later (event saved as Draft)

### Phase 3: Page Design (Step 2)

**Purpose:** Visual branding and appearance.

**Fields to Complete:**
- Hero/banner image (edit via Event information step)
- Event logo (edit via Event information step)
- Primary color theme
- "Get tickets" button label

**Best Practices:**
- Use brand colors that match your vendor profile
- Keep button labels clear and action-oriented
- Preview changes before saving

**Navigation:**
- "Back" → Returns to Event information
- "Continue" → Goes to Page content

### Phase 4: Page Content (Step 3)

**Purpose:** Rich content and detailed information.

**Fields to Complete:**
- Full event description (rich text)
- Additional content sections (if available)
- FAQ (if applicable)

**Best Practices:**
- Use rich text formatting for readability
- Include all relevant details attendees need
- Add FAQs for common questions

**Navigation:**
- "Back" → Returns to Page design
- "Continue" → Goes to Ticket types

### Phase 5: Ticket Types (Step 4)

**Purpose:** Configure pricing and capacity.

**Fields to Complete:**
- Ticket type name
- Price (or free)
- Capacity/quantity
- Sales start/end dates
- Ticket description

**Best Practices:**
- Create clear ticket tier names (e.g., "Early Bird", "General Admission", "VIP")
- Set appropriate capacities
- Use sales windows to create urgency

**Navigation:**
- "Back" → Returns to Page content
- "Continue" → Goes to Checkout questions

### Phase 6: Checkout Questions (Step 5)

**Purpose:** Collect additional attendee information.

**Fields to Complete:**
- Custom questions (if needed)
- Question type (text, select, checkbox)
- Required vs optional
- Apply to (buyer/all guests/specific tickets)

**Best Practices:**
- Only ask necessary questions
- Keep questions short and clear
- Use required questions sparingly

**Navigation:**
- "Back" → Returns to Ticket types
- "Continue" → (Currently goes to "coming soon" steps)

### Phase 7: Review & Publish

**Before Publishing:**
1. Review all steps using navigation sidebar
2. Click "Preview" to see public view
3. Test ticket purchase flow (if applicable)
4. Verify all information is correct

**Publishing:**
- Click "Publish" button in header
- Event becomes publicly visible
- Can be found in event listings

## Accessibility Features

### Form Accessibility
- All form fields have proper labels
- Required fields are clearly marked
- Error messages are descriptive
- Keyboard navigation supported
- Screen reader friendly

### Image Accessibility
- Alt text required for all images
- Clear guidance provided for alt text
- Image previews available

### Navigation Accessibility
- Clear step indicators
- Keyboard-accessible navigation
- Focus management
- ARIA labels where needed

## Best Practices Summary

1. **Complete steps in order** - Each step builds on the previous
2. **Save frequently** - Use "Save" button to preserve work
3. **Preview before publishing** - Always check the public view
4. **Test the flow** - Try purchasing a ticket as a test user
5. **Keep it simple** - Don't overcomplicate ticket types or questions
6. **Use clear language** - Write for your audience, not for yourself

## Future Enhancements (Coming Soon)

- **Promote:** Social media sharing, email campaigns
- **Payments & Fees:** Configure payment methods, fees
- **Comms:** Automated emails, SMS notifications
- **Advanced:** Custom fields, integrations, analytics

## Technical Notes

- Events are saved as Draft until published
- All changes are saved immediately (no "Save" required, but button available)
- Navigation preserves context across steps
- Admin sidebar is hidden on vendor pages for cleaner interface


