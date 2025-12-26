<?php

declare(strict_types=1);

namespace Drupal\myeventlane_vendor\Controller;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\myeventlane_core\Service\DomainDetector;

/**
 * Event settings controller.
 */
final class VendorEventSettingsController extends VendorConsoleBaseController {

  /**
   * Constructs the controller.
   */
  public function __construct(DomainDetector $domain_detector, AccountProxyInterface $current_user) {
    parent::__construct($domain_detector, $current_user);
  }

  /**
   * Displays settings for an event.
   */
  public function settings(NodeInterface $event): array {
    $this->assertEventOwnership($event);
    $this->assertStripeConnected();
    $tabs = $this->eventTabs($event, 'settings');

    return $this->buildVendorPage('myeventlane_vendor_console_page', [
      'title' => $event->label() . ' — Settings',
      'tabs' => $tabs,
      'header_actions' => [
        [
          'label' => 'Edit Event',
          // Use wizard route for editing (vendors never see default node edit form).
          'url' => Url::fromRoute('myeventlane_event.wizard.edit', ['node' => $event->id()])->toString(),
          'class' => 'mel-btn--primary',
        ],
      ],
      'body' => [
        '#theme' => 'myeventlane_vendor_event_settings',
        '#event' => $event,
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
  private function eventTabs(NodeInterface $event, string $active = 'settings'): array {
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
