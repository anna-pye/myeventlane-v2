# MyEventLane Analytics Pageviews (Stub)

This module is a placeholder for future pageview tracking functionality.

## Status: NOT IMPLEMENTED

The traffic insights tab in the reporting module shows a stub message indicating that pageview tracking is not yet available.

## Future Implementation

When implemented, this module should:

1. Track event node pageviews per day
2. Store: event_id, date_bucket, count
3. Optionally track referrer source
4. Respect privacy (no IP storage, just counts)
5. Do not track logged-in admins
6. Add opt-out configuration if needed

This data would be used to provide:
- Conversion rate = orders/RSVPs / pageviews
- Traffic source breakdown
- Pageview trends over time

## Integration Points

- Event insights traffic tab: `/vendor/events/{event}/insights/traffic`
- Chart endpoint: Could add `myeventlane_reporting.chart.traffic` route
- Data service: Could integrate with `AnalyticsDataService` or create new service
