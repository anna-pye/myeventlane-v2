<?php

declare(strict_types=1);

namespace Drupal\myeventlane_vendor\Controller;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\myeventlane_core\Service\DomainDetector;
use Drupal\myeventlane_vendor\Service\MetricsAggregator;

/**
 * Event overview controller.
 */
final class VendorEventOverviewController extends VendorConsoleBaseController {

  /**
   * Constructs the controller.
   */
  public function __construct(DomainDetector $domain_detector, AccountProxyInterface $current_user, private readonly MetricsAggregator $metricsAggregator) {
    parent::__construct($domain_detector, $current_user);
  }

  /**
   * Displays the overview tab for an event.
   */
  public function overview(NodeInterface $event): array {
    $this->assertEventOwnership($event);
    $tabs = $this->eventTabs($event);
    $overview = $this->metricsAggregator->getEventOverview($event);
    $charts = $this->metricsAggregator->getEventCharts($event);

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
      'title' => $event->label() . ' — Overview',
      'tabs' => $tabs,
      'body' => [
        '#theme' => 'myeventlane_vendor_event_overview',
        '#event' => $event,
        '#overview' => $overview,
        '#charts' => $charts,
      ],
      'meta' => [$event->bundle(), 'status' => $event->isPublished() ? 'Published' : 'Draft'],
      '#attached' => [
        'drupalSettings' => [
          'vendorCharts' => $chart_data,
        ],
      ],
    ]);
  }

  /**
   * Builds event tabs for the console.
   */
  private function eventTabs(NodeInterface $event): array {
    $id = $event->id();

    return [
      ['label' => 'Overview', 'url' => "/vendor/events/{$id}/overview", 'active' => TRUE],
      ['label' => 'Tickets', 'url' => "/vendor/events/{$id}/tickets", 'active' => FALSE],
      ['label' => 'Attendees', 'url' => "/vendor/events/{$id}/attendees", 'active' => FALSE],
      ['label' => 'RSVPs', 'url' => "/vendor/events/{$id}/rsvps", 'active' => FALSE],
      ['label' => 'Analytics', 'url' => "/vendor/events/{$id}/analytics", 'active' => FALSE],
      ['label' => 'Boost', 'url' => "/event/{$id}/boost", 'active' => FALSE],
      ['label' => 'Settings', 'url' => "/vendor/events/{$id}/settings", 'active' => FALSE],
    ];
  }

}
