<?php

declare(strict_types=1);

namespace Drupal\myeventlane_vendor\Controller;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\node\NodeInterface;
use Drupal\myeventlane_core\Service\DomainDetector;
use Drupal\myeventlane_vendor\Service\TicketSalesService;

/**
 * Event tickets controller.
 *
 * Displays real ticket sales data from Commerce.
 */
final class VendorEventTicketsController extends VendorConsoleBaseController {

  /**
   * Constructs the controller.
   */
  public function __construct(DomainDetector $domain_detector, AccountProxyInterface $current_user, private readonly TicketSalesService $ticketSalesService) {
    parent::__construct($domain_detector, $current_user);
  }

  /**
   * Displays tickets configuration for an event.
   */
  public function tickets(NodeInterface $event): array {
    $this->assertEventOwnership($event);
    $this->assertStripeConnected();
    $tabs = $this->eventTabs($event, 'tickets');
    $sales = $this->ticketSalesService->getSalesSummary($event);
    $tickets = $this->ticketSalesService->getTicketBreakdown($event);

    // Get edit URL for product.
    $editProductUrl = NULL;
    if ($event->hasField('field_product_target') && !$event->get('field_product_target')->isEmpty()) {
      $product = $event->get('field_product_target')->entity;
      if ($product) {
        $editProductUrl = '/admin/commerce/products/' . $product->id() . '/edit';
      }
    }

    return $this->buildVendorPage('myeventlane_vendor_console_page', [
      'title' => $event->label() . ' — Tickets',
      'tabs' => $tabs,
      'header_actions' => $editProductUrl ? [
        [
          'label' => 'Manage Tickets',
          'url' => $editProductUrl,
          'class' => 'mel-btn--secondary',
        ],
      ] : [],
      'body' => [
        '#theme' => 'myeventlane_vendor_event_tickets',
        '#event' => $event,
        '#sales' => $sales,
        '#tickets' => $tickets,
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
  private function eventTabs(NodeInterface $event, string $active = 'tickets'): array {
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
