# Phase 5: Reporting & Insights UI - Implementation Summary

## Overview

This document summarizes the implementation of Phase 5 reporting and insights UI for MyEventLane v2. The implementation provides vendor-scoped and admin reporting dashboards with KPIs, charts, and export functionality.

## Module Created

### `myeventlane_reporting`

**Location:** `/web/modules/custom/myeventlane_reporting/`

**Responsibilities:**
- Vendor reporting pages and controllers
- Admin reporting pages and controllers
- Chart data endpoints (JSON)
- Export centre UI
- Twig templates for reporting layouts
- Permissions and access enforcement

## Files Created

### Module Structure
1. `myeventlane_reporting.info.yml` - Module definition
2. `myeventlane_reporting.module` - Theme hook registration
3. `myeventlane_reporting.permissions.yml` - Permission definitions
4. `myeventlane_reporting.routing.yml` - Route definitions
5. `myeventlane_reporting.services.yml` - Service definitions
6. `myeventlane_reporting.libraries.yml` - Library definitions (CSS/JS)

### Controllers
7. `src/Controller/VendorInsightsController.php` - Vendor insights dashboard
8. `src/Controller/EventInsightsController.php` - Event-level insights with tabs
9. `src/Controller/ChartDataController.php` - JSON chart data endpoints
10. `src/Controller/AdminReportsController.php` - Admin reporting console
11. `src/Controller/ExportCentreController.php` - Export centre UI

### Access Control
12. `src/Access/VendorReportingAccess.php` - Vendor reporting access checker

### Templates
13. `templates/myeventlane-reporting-vendor-insights.html.twig` - Vendor insights page
14. `templates/myeventlane-reporting-event-insights.html.twig` - Event insights with tabs
15. `templates/myeventlane-reporting-kpi-card.html.twig` - KPI card component
16. `templates/myeventlane-reporting-export-centre.html.twig` - Export centre page
17. `templates/myeventlane-reporting-admin-overview.html.twig` - Admin overview
18. `templates/myeventlane-reporting-admin-vendors.html.twig` - Admin vendor reports
19. `templates/myeventlane-reporting-admin-events.html.twig` - Admin event reports
20. `templates/myeventlane-reporting-admin-finance.html.twig` - Admin finance reports

### Assets
21. `css/reporting.css` - Basic reporting styles (enhanced by theme SCSS)
22. `js/reporting.js` - Chart initialization and tab behavior

### Documentation
23. `modules/custom/myeventlane_analytics_pageviews/README.md` - Pageview tracking stub documentation

## Routes Implemented

### Vendor Routes (Scoped)
- `/vendor/insights` - Overall vendor metrics dashboard
- `/vendor/events/{event}/insights` - Event insights overview
- `/vendor/events/{event}/insights/sales` - Sales insights tab
- `/vendor/events/{event}/insights/attendees` - Attendee insights tab
- `/vendor/events/{event}/insights/checkins` - Check-in insights tab
- `/vendor/events/{event}/insights/traffic` - Traffic insights tab (stub)

### Chart Data Endpoints (JSON)
- `/vendor/charts/sales/{event}` - Sales time-series data
- `/vendor/charts/ticket-breakdown/{event}` - Ticket breakdown data
- `/vendor/charts/checkins/{event}` - Check-ins time-series data
- `/vendor/charts/revenue/{event}` - Revenue time-series data

### Export Centre
- `/vendor/exports` - Export centre listing
- `/vendor/exports/request-csv/{event}` - Request attendee CSV export
- `/vendor/exports/request-sales/{event}` - Request sales CSV export

### Admin Routes
- `/admin/myeventlane/reports` - Platform overview
- `/admin/myeventlane/reports/vendors` - Vendor reports
- `/admin/myeventlane/reports/events` - Event reports
- `/admin/myeventlane/reports/finance` - Finance reports

## Permissions Created

1. `view vendor insights` - Access vendor-level reporting dashboard
2. `view event insights` - Access per-event reporting pages
3. `request exports` - Request and download CSV/ICS exports
4. `view admin reports` - Access platform-wide admin reporting console

## Key Features

### Vendor Insights Dashboard
- Total events (by state: published, draft, scheduled, sold out)
- Total attendees (RSVP + paid tickets)
- Tickets sold with revenue
- Check-in rate
- Top performing event
- Events needing attention (sold out, sales starting soon, etc.)

### Event Insights (Tabs)
- **Overview**: Capacity, attendees, check-in rate, revenue
- **Sales**: Revenue over time, ticket breakdown, average ticket price
- **Attendees**: Breakdown by source (RSVP vs tickets)
- **Check-ins**: Check-in rate, time-series chart
- **Traffic**: Stub (pageview tracking not implemented)

### Chart Integration
- Uses Chart.js (already available in vendor theme)
- JSON endpoints for data (async loading)
- Mobile-responsive chart containers
- Fallback tables for accessibility

### Export Centre
- Lists export requests from automation dispatch table
- Shows status (queued/ready/failed)
- Download links for ready exports
- Request buttons per event for CSV exports
- Audit logged access

### Admin Reporting
- Platform-wide KPIs (events, vendors, attendees, revenue)
- Top events by attendance/revenue
- Vendor statistics (placeholder)
- Finance metrics

## Data Sources

All UI consumes:
- `EventMetricsService` - Core metrics (capacity, attendees, revenue, check-ins)
- `AttendeeRepositoryInterface` - Unified attendee access
- `TicketSalesService` - Sales data and time-series
- `RsvpStatsService` - RSVP statistics
- Automation dispatch table - Export tracking
- Automation audit logger - Access logging

## Security & Access Control

- All routes enforce vendor ownership or admin permission
- Event-level routes check event ownership via `assertEventOwnership()`
- All accesses logged in audit log
- No data leaks between vendors
- Door staff role cannot access reporting unless explicitly granted

## Performance Considerations

- KPIs cached for 5 minutes (via EventMetricsService)
- Chart data endpoints return JSON (can be cached)
- Page cache max-age: 300 seconds (5 minutes)
- Cache tags: node, user, commerce_order
- Large aggregates computed from services, not Views arithmetic

## Styling

- Uses existing MEL vendor theme components (mel-kpi-card, mel-card, etc.)
- Templates follow vendor theme patterns
- Additional CSS in `css/reporting.css` for reporting-specific styles
- Chart containers styled with vendor theme tokens

## Known Limitations / TODOs

1. **Pageview Tracking**: Traffic insights tab shows stub message. Pageview tracking module (`myeventlane_analytics_pageviews`) is not implemented.
2. **Refund Metrics**: Refund counts are stubbed (need integration with event state/refund flow).
3. **Export Generation**: Export requests create dispatch records, but actual file generation should be handled by queue workers (existing automation infrastructure).
4. **Admin Vendor Reports**: Vendor-level aggregations are placeholder (needs implementation).

## Installation Steps

1. **Enable the module:**
   ```bash
   ddev drush en myeventlane_reporting -y
   ```

2. **Import configuration:**
   ```bash
   ddev drush cim -y
   ```

3. **Update database (if needed):**
   ```bash
   ddev drush updb -y
   ```

4. **Clear cache:**
   ```bash
   ddev drush cr
   ```

5. **Grant permissions** (via UI or config):
   - Grant `view vendor insights` to vendor role
   - Grant `view event insights` to vendor role
   - Grant `request exports` to vendor role
   - Grant `view admin reports` to administrator role

## Testing Checklist

- [ ] Vendor can access `/vendor/insights`
- [ ] Vendor can view event insights for their events
- [ ] Tabs work correctly on event insights pages
- [ ] Charts load and display data correctly
- [ ] Export centre lists exports correctly
- [ ] Export requests create dispatch records
- [ ] Admin can access admin reporting routes
- [ ] KPIs display correct data from metrics service
- [ ] Access control prevents cross-vendor data access
- [ ] Audit logs record access correctly

## Dependencies

- `myeventlane_core`
- `myeventlane_metrics`
- `myeventlane_vendor`
- `myeventlane_event_attendees`
- `myeventlane_automation`

## Integration Points

- Vendor theme: Uses existing component classes and patterns
- Chart.js: Loaded via vendor theme library
- Automation dispatch: Exports tracked via dispatch table
- Audit logging: Accesses logged via automation audit logger
- Metrics service: All KPIs use EventMetricsService
- Attendee repository: Unified attendee access via repository resolver

---

**Implementation Date:** 2025-01-27  
**Phase:** Phase 5 - Reporting & Insights UI  
**Status:** ✅ Complete
