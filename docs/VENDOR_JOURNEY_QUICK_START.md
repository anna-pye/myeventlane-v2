# Vendor Journey - Quick Start Guide

## Access the New Vendor Journey

1. **Find an Event ID:**
   - You can use event ID: **16** (from the test)
   - Or find any event you own: `/node/add/event` or check `/vendor/dashboard`

2. **Access the Manage Event Console:**
   - Visit: `/vendor/event/{event_id}/edit`
   - Example: `https://myeventlane.ddev.site/vendor/event/16/edit`

3. **Available Steps:**
   - `/vendor/event/{event_id}/edit` - Event information
   - `/vendor/event/{event_id}/design` - Page design
   - `/vendor/event/{event_id}/content` - Page content
   - `/vendor/event/{event_id}/tickets` - Ticket types
   - `/vendor/event/{event_id}/checkout-questions` - Checkout questions
   - `/vendor/event/{event_id}/promote` - Promote (coming soon)
   - `/vendor/event/{event_id}/payments` - Payments (coming soon)
   - `/vendor/event/{event_id}/comms` - Comms (coming soon)
   - `/vendor/event/{event_id}/advanced` - Advanced (coming soon)

## Troubleshooting

### If you see a blank page or error:

1. **Clear all caches:**
   ```bash
   ddev drush cr
   ```

2. **Check browser console** for JavaScript errors

3. **Check Drupal logs:**
   ```bash
   ddev drush watchdog-show --tail=50
   ```

4. **Verify you're logged in** as a user who owns the event or has vendor permissions

5. **Check access:**
   - You must be the event owner (node author)
   - OR be in the vendor's `field_vendor_users` field
   - OR be a site admin

### If the layout doesn't look right:

1. **CSS might not be loading** - check browser DevTools Network tab for `manage-event.css`

2. **Clear browser cache** - hard refresh (Cmd+Shift+R on Mac, Ctrl+Shift+R on Windows)

3. **Verify library is attached:**
   ```bash
   ddev drush php-eval "\$library = \Drupal::service('library.discovery')->getLibraryByName('myeventlane_vendor', 'manage_event'); var_dump(\$library);"
   ```

## Testing Checklist

- [ ] Visit `/vendor/event/16/edit` (or your event ID)
- [ ] See left sidebar with navigation steps
- [ ] See right panel with the event edit form
- [ ] Click through different steps in the navigation
- [ ] Verify "Continue" and "Back" buttons work
- [ ] Check that "coming soon" steps are disabled
- [ ] Verify styling looks correct (two-column layout)

## Quick Test Command

```bash
# Get an event ID and test URL
ddev drush php-eval "\$event = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'event']); if (!empty(\$event)) { \$event = reset(\$event); echo 'Test URL: https://myeventlane.ddev.site/vendor/event/' . \$event->id() . '/edit' . PHP_EOL; }"
```


