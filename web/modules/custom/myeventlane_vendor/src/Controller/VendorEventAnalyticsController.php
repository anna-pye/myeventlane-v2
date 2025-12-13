<?php

declare(strict_types=1);

namespace Drupal\myeventlane_vendor\Controller;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\myeventlane_core\Service\DomainDetector;
use Drupal\myeventlane_vendor\Service\MetricsAggregator;

/**
 * Event analytics controller.
 *
 * Displays real analytics data from Commerce and RSVP.
 */
final class VendorEventAnalyticsController extends VendorConsoleBaseController {

  /**
   * Constructs the controller.
   */
  public function __construct(DomainDetector $domain_detector, AccountProxyInterface $current_user, private readonly MetricsAggregator $metricsAggregator) {
    parent::__construct($domain_detector, $current_user);
  }

  /**
   * Displays analytics for an event.
   */
  public function analytics(NodeInterface $event): array {
    $this->assertEventOwnership($event);
    $tabs = $this->eventTabs($event, 'analytics');
    $charts = $this->metricsAggregator->getEventCharts($event);
    $overview = $this->metricsAggregator->getEventOverview($event);

    $chart_data = [
      'event-sales' => [
        'type' => 'line',
        'labels' => array_column($charts['sales'] ?? [], 'date'),
        'datasets' => [
          [
            'label' => 'Sales',
            'data' => array_column($charts['sales'] ?? [], 'amount'),
            'borderColor' => '#2563eb',
            'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
          ],
        ],
      ],
      'event-rsvps' => [
        'type' => 'line',
        'labels' => array_column($charts['rsvps'] ?? [], 'date'),
        'datasets' => [
          [
            'label' => 'RSVPs',
            'data' => array_column($charts['rsvps'] ?? [], 'rsvps'),
            'borderColor' => '#10b981',
            'backgroundColor' => 'rgba(16, 185, 129, 0.12)',
          ],
        ],
      ],
    ];

    return $this->buildVendorPage('myeventlane_vendor_console_page', [
      'title' => $event->label() . ' — Analytics',
      'tabs' => $tabs,
      'body' => [
        '#theme' => 'myeventlane_vendor_event_analytics',
        '#event' => $event,
        '#charts' => $charts,
        '#overview' => $overview,
      ],
      '#attached' => [
        'drupalSettings' => [
          'vendorCharts' => $chart_data,
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
  private function eventTabs(NodeInterface $event, string $active = 'analytics'): array {
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

    foreach ($tabs as &$tab) {
      $tab['active'] = ($tab['key'] === $active);
    }

    return $tabs;
  }

}
