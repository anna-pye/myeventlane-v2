# Phase 3: Automation & Notifications - Implementation Summary

## Overview

Phase 3 implements a comprehensive automation engine for MyEventLane that sends notifications for:
1. Sales opening (scheduled → live)
2. Event reminders (24h and 2h before start)
3. Waitlist auto-invite when capacity frees up
4. Event cancellations (notify attendees)
5. Export ready notifications (CSV / ICS bundles)
6. Weekly category digests (if Flag follow system exists)

## New Module: myeventlane_automation

### Module Structure

```
web/modules/custom/myeventlane_automation/
├── myeventlane_automation.info.yml
├── myeventlane_automation.module
├── myeventlane_automation.install
├── myeventlane_automation.services.yml
├── myeventlane_automation.permissions.yml
├── config/
│   ├── install/
│   │   └── myeventlane_messaging.template.*.yml (7 email templates)
│   └── schema/
│       └── myeventlane_automation.schema.yml
└── src/
    ├── Service/
    │   ├── AutomationDispatchService.php
    │   ├── AutomationAuditLogger.php
    │   ├── AutomationScheduler.php
    │   └── ExportNotificationHelper.php
    └── Plugin/
        └── QueueWorker/
            ├── AutomationWorkerBase.php
            ├── SalesOpenWorker.php
            ├── Reminder24hWorker.php
            ├── Reminder2hWorker.php
            ├── WaitlistInviteWorker.php
            ├── EventCancelledWorker.php
            ├── ExportReadyWorker.php
            └── WeeklyDigestWorker.php
```

## Data Model

### Dispatch Table (`myeventlane_automation_dispatch`)

Tracks each automated notification attempt for idempotency and audit:

- `id` (serial) - Primary key
- `event_id` (int, nullable) - Event node ID (nullable for global emails)
- `notification_type` (varchar 64) - Type constant
- `recipient_hash` (varchar 64) - SHA256 hash of recipient identifier (privacy)
- `scheduled_for` (int) - Unix timestamp when scheduled
- `sent_at` (int, nullable) - Unix timestamp when sent
- `status` (varchar 32) - scheduled|sent|failed|skipped
- `attempts` (int) - Number of send attempts
- `last_error` (text, nullable) - Error message if failed
- `metadata` (text, nullable) - JSON metadata

### Audit Log Table (`myeventlane_automation_audit`)

Tracks automation actions for compliance:

- `id` (serial) - Primary key
- `event_id` (int, nullable) - Event node ID
- `action` (varchar 64) - Action type (e.g., 'notification_sent')
- `notification_type` (varchar 64, nullable) - Notification type if applicable
- `recipient_hash` (varchar 64, nullable) - Hash of recipient
- `created` (int) - Unix timestamp
- `metadata` (text, nullable) - JSON metadata

## Notification Types

Constants defined in `AutomationDispatchService`:

- `TYPE_SALES_OPEN` - Sales opening notification
- `TYPE_REMINDER_24H` - 24-hour reminder
- `TYPE_REMINDER_2H` - 2-hour reminder
- `TYPE_WAITLIST_INVITE` - Waitlist invitation
- `TYPE_EVENT_CANCELLED` - Event cancellation
- `TYPE_EXPORT_READY_CSV` - CSV export ready
- `TYPE_EXPORT_READY_ICS` - ICS export ready
- `TYPE_WEEKLY_CATEGORY_DIGEST` - Weekly category digest

## Services

### AutomationDispatchService

Manages dispatch records for idempotency:
- `createDispatch()` - Creates dispatch record
- `isAlreadySent()` - Checks idempotency
- `markSent()` / `markFailed()` / `markSkipped()` - Updates status
- `hashRecipient()` - Hashes recipient identifier for privacy

### AutomationScheduler

Cron handler that scans for events needing automation:
- `scan()` - Main scan method called by cron
- Scans for sales opening, reminders, waitlist invites, weekly digests

### AutomationAuditLogger

Writes audit log entries for compliance:
- `log()` - Logs an automation action

### ExportNotificationHelper

Helper service for export-ready notifications:
- `queueExportNotification()` - Queues export ready notification

## Queue Workers

All workers extend `AutomationWorkerBase` and implement:
- Idempotency checks via dispatch service
- Email sending via MessagingManager
- Audit logging
- Error handling

### Workers

1. **SalesOpenWorker** - Notifies vendors when sales open
2. **Reminder24hWorker** - Sends 24-hour reminders to attendees
3. **Reminder2hWorker** - Sends 2-hour reminders to attendees
4. **WaitlistInviteWorker** - Invites waitlisted users when capacity opens
5. **EventCancelledWorker** - Notifies attendees of cancellations
6. **ExportReadyWorker** - Notifies vendors when exports are ready
7. **WeeklyDigestWorker** - Sends weekly category digests

## Email Templates

Templates use `myeventlane_messaging` system and are stored as config:
- `sales_open`
- `event_reminder_24h`
- `event_reminder_2h`
- `waitlist_invite`
- `event_cancelled`
- `export_ready_csv`
- `export_ready_ics`

All templates include:
- MEL-branded HTML wrapper
- Plain-text fallback
- UTM parameters for tracking

## Integration Points

### EventCancelForm Integration

Updated `web/modules/custom/myeventlane_event_state/src/Form/EventCancelForm.php`:
- Replaced TODO with queue-based notification system
- Queues cancellation notifications for all attendees
- Uses automation dispatch service for idempotency

### Export Controllers

Added helper service for export notifications:
- `ExportNotificationHelper::queueExportNotification()`
- Integration point ready for file-based exports
- TODO added for current streamed export refactoring

## Cron Integration

`myeventlane_automation_cron()` calls `AutomationScheduler::scan()` which:
- Finds events with sales opening soon
- Finds events needing reminders (24h and 2h before start)
- Scans for waitlist capacity (event-driven preferred)
- Runs weekly digests (once per week on Monday)

## Security & Privacy

- **Email Hashing**: Recipient emails are hashed (SHA256) in dispatch table
- **Vendor Access**: Vendors can only view automation logs for their events
- **Idempotency**: Prevents duplicate notifications via dispatch table
- **Audit Trail**: All notifications logged to audit table

## Permissions

- `view automation dispatch log` - View automation dispatch log (restricted access)

## Installation Steps

1. **Enable module**:
   ```bash
   ddev drush en myeventlane_automation -y
   ```

2. **Run updates** (creates tables):
   ```bash
   ddev drush updb -y
   ```

3. **Import config** (email templates):
   ```bash
   ddev drush cim -y
   ```

4. **Clear cache**:
   ```bash
   ddev drush cr
   ```

## Testing Checklist

- [ ] Sales opening notifications sent when event goes live
- [ ] 24h reminders sent 24 hours before event start
- [ ] 2h reminders sent 2 hours before event start
- [ ] Waitlist invites sent when capacity opens (requires integration)
- [ ] Cancellation notifications sent when event cancelled
- [ ] Export ready notifications sent (requires file-based export refactoring)
- [ ] Weekly digests sent on Monday to users following categories
- [ ] Idempotency prevents duplicate notifications
- [ ] Audit log entries created for all notifications
- [ ] Dispatch table tracks all notification attempts

## Known TODOs / Future Work

1. **Waitlist Integration**: Currently scanned on cron; should be event-driven when capacity changes (refunds, cancellations)

2. **Export File Generation**: Current exports stream directly. Need to refactor to:
   - Generate file first
   - Save to temporary storage
   - Create secure download link
   - Queue notification

3. **Waitlist Claim Route**: `myeventlane_automation.waitlist_claim` route needed for waitlist invite links

4. **Event Fields**: Optional automation flags (`field_enable_reminders`, `field_waitlist_auto_invite`, etc.) should be added via schema module

5. **Weekly Digest Unsubscribe**: Add unsubscribe preference for category digests

## Files Changed/Added

### New Files (27 files)

**Module structure:**
- `web/modules/custom/myeventlane_automation/myeventlane_automation.info.yml`
- `web/modules/custom/myeventlane_automation/myeventlane_automation.module`
- `web/modules/custom/myeventlane_automation/myeventlane_automation.install`
- `web/modules/custom/myeventlane_automation/myeventlane_automation.services.yml`
- `web/modules/custom/myeventlane_automation/myeventlane_automation.permissions.yml`

**Services:**
- `web/modules/custom/myeventlane_automation/src/Service/AutomationDispatchService.php`
- `web/modules/custom/myeventlane_automation/src/Service/AutomationAuditLogger.php`
- `web/modules/custom/myeventlane_automation/src/Service/AutomationScheduler.php`
- `web/modules/custom/myeventlane_automation/src/Service/ExportNotificationHelper.php`

**Queue Workers:**
- `web/modules/custom/myeventlane_automation/src/Plugin/QueueWorker/AutomationWorkerBase.php`
- `web/modules/custom/myeventlane_automation/src/Plugin/QueueWorker/SalesOpenWorker.php`
- `web/modules/custom/myeventlane_automation/src/Plugin/QueueWorker/Reminder24hWorker.php`
- `web/modules/custom/myeventlane_automation/src/Plugin/QueueWorker/Reminder2hWorker.php`
- `web/modules/custom/myeventlane_automation/src/Plugin/QueueWorker/WaitlistInviteWorker.php`
- `web/modules/custom/myeventlane_automation/src/Plugin/QueueWorker/EventCancelledWorker.php`
- `web/modules/custom/myeventlane_automation/src/Plugin/QueueWorker/ExportReadyWorker.php`
- `web/modules/custom/myeventlane_automation/src/Plugin/QueueWorker/WeeklyDigestWorker.php`

**Email Templates:**
- `web/modules/custom/myeventlane_automation/config/install/myeventlane_messaging.template.sales_open.yml`
- `web/modules/custom/myeventlane_automation/config/install/myeventlane_messaging.template.event_reminder_24h.yml`
- `web/modules/custom/myeventlane_automation/config/install/myeventlane_messaging.template.event_reminder_2h.yml`
- `web/modules/custom/myeventlane_automation/config/install/myeventlane_messaging.template.waitlist_invite.yml`
- `web/modules/custom/myeventlane_automation/config/install/myeventlane_messaging.template.event_cancelled.yml`
- `web/modules/custom/myeventlane_automation/config/install/myeventlane_messaging.template.export_ready_csv.yml`
- `web/modules/custom/myeventlane_automation/config/install/myeventlane_messaging.template.export_ready_ics.yml`

**Config Schema:**
- `web/modules/custom/myeventlane_automation/config/schema/myeventlane_automation.schema.yml`

### Modified Files (2 files)

- `web/modules/custom/myeventlane_event_state/src/Form/EventCancelForm.php` - Integrated cancellation notifications
- `web/modules/custom/myeventlane_checkout_paragraph/src/Controller/AttendeeExportController.php` - Added TODO for export notification integration

## Drush Commands

```bash
# Enable module
ddev drush en myeventlane_automation -y

# Run database updates (creates tables)
ddev drush updb -y

# Import configuration (email templates)
ddev drush cim -y

# Clear cache
ddev drush cr

# Run cron manually (test automation)
ddev drush cron

# Check queue status
ddev drush queue:list

# Process queues manually
ddev drush queue:run automation_sales_open
ddev drush queue:run automation_reminder_24h
ddev drush queue:run automation_reminder_2h
ddev drush queue:run automation_waitlist_invite
ddev drush queue:run automation_event_cancelled
ddev drush queue:run automation_export_ready
ddev drush queue:run automation_weekly_digest
```

## Notes

- All automation is idempotent via dispatch table
- All notifications are queue-based (never sent directly from controllers)
- All actions are auditable via audit log table
- Email addresses are hashed for privacy in dispatch/audit tables
- Weekly digests only run on Mondays (once per week)
- Export notifications require file-based export refactoring (TODO)
- Waitlist invites should ideally be event-driven, not cron-based (TODO)
