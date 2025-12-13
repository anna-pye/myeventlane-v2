<?php

declare(strict_types=1);

namespace Drupal\myeventlane_vendor\Controller;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\myeventlane_core\Service\DomainDetector;
use Drupal\myeventlane_event_attendees\Service\AttendanceManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Event attendees controller for vendor console.
 */
final class VendorEventAttendeesController extends VendorConsoleBaseController {

  /**
   * Constructs the controller.
   */
  public function __construct(
    DomainDetector $domain_detector,
    AccountProxyInterface $current_user,
    private readonly AttendanceManagerInterface $attendanceManager,
  ) {
    parent::__construct($domain_detector, $current_user);
  }

  /**
   * Displays attendees for an event.
   */
  public function attendees(NodeInterface $event): array {
    $this->assertEventOwnership($event);
    $tabs = $this->eventTabs($event, 'attendees');

    $attendees = $this->attendanceManager->getAttendeesForEvent((int) $event->id());
    $availability = $this->attendanceManager->getAvailability($event);

    // Group attendees by source.
    $grouped = [
      'ticket' => [],
      'rsvp' => [],
      'manual' => [],
    ];

    foreach ($attendees as $attendee) {
      $source = $attendee->getSource();
      $grouped[$source][] = $attendee;
    }

    // Build attendee rows for the table.
    $rows = [];
    foreach ($attendees as $attendee) {
      $rows[] = [
        'name' => $attendee->getName(),
        'email' => $attendee->getEmail(),
        'source' => ucfirst($attendee->getSource()),
        'status' => ucfirst($attendee->getStatus()),
        'checked_in' => $attendee->isCheckedIn(),
        'ticket_code' => $attendee->getTicketCode() ?? '',
      ];
    }

    return $this->buildVendorPage('myeventlane_vendor_console_page', [
      'title' => $event->label() . ' — Attendees',
      'tabs' => $tabs,
      'header_actions' => [
        [
          'label' => 'Export CSV',
          'url' => Url::fromRoute('myeventlane_event_attendees.vendor_export', ['node' => $event->id()])->toString(),
          'class' => 'mel-btn--secondary',
        ],
      ],
      'body' => [
        '#theme' => 'myeventlane_vendor_event_attendees',
        '#event' => $event,
        '#attendees' => $rows,
        '#summary' => [
          'total' => count($attendees),
          'ticket' => count($grouped['ticket']),
          'rsvp' => count($grouped['rsvp']),
          'manual' => count($grouped['manual']),
          'capacity' => $availability['capacity'] > 0 ? $availability['capacity'] : 'Unlimited',
          'remaining' => $availability['remaining'],
        ],
      ],
    ]);
  }

  /**
   * Builds event tabs for the console.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   * @param string $active
   *   The key of the currently active tab.
   *
   * @return array
   *   Array of tab definitions.
   */
  private function eventTabs(NodeInterface $event, string $active = 'attendees'): array {
    $id = $event->id();

    $tabs = [
      ['label' => 'Overview', 'url' => "/vendor/events/{$id}/overview", 'key' => 'overview'],
      ['label' => 'Tickets', 'url' => "/vendor/events/{$id}/tickets", 'key' => 'tickets'],
      ['label' => 'Attendees', 'url' => "/vendor/events/{$id}/attendees", 'key' => 'attendees'],
      ['label' => 'RSVPs', 'url' => "/vendor/events/{$id}/rsvps", 'key' => 'rsvps'],
      ['label' => 'Analytics', 'url' => "/vendor/events/{$id}/analytics", 'key' => 'analytics'],
      ['label' => 'Boost', 'url' => "/event/{$id}/boost", 'key' => 'boost'],
      ['label' => 'Settings', 'url' => "/vendor/events/{$id}/settings", 'key' => 'settings'],
    ];

    // Set active state based on parameter.
    foreach ($tabs as &$tab) {
      $tab['active'] = ($tab['key'] === $active);
    }

    return $tabs;
  }

}
