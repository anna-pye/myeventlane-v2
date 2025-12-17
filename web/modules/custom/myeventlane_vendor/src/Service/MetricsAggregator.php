<?php

declare(strict_types=1);

namespace Drupal\myeventlane_vendor\Service;

use Drupal\node\NodeInterface;

/**
 * Aggregates metrics across ticket sales, RSVPs, audience, and boost.
 *
 * All data is now sourced from real database queries.
 */
final class MetricsAggregator {

  /**
   * Constructs the aggregator.
   */
  public function __construct(
    private readonly TicketSalesService $ticketSalesService,
    private readonly RsvpStatsService $rsvpStatsService,
    private readonly CategoryAudienceService $categoryAudienceService,
    private readonly BoostStatusService $boostStatusService,
  ) {}

  /**
   * Returns KPI cards for the vendor dashboard.
   *
   * Data is queried from real Commerce orders and RSVP submissions.
   *
   * @param int $userId
   *   The vendor user ID.
   *
   * @return array
   *   Array of KPI cards.
   */
  public function getVendorKpis(int $userId): array {
    // Get real revenue data.
    $revenue = $this->ticketSalesService->getVendorRevenue($userId);
    $rsvpCount = $this->rsvpStatsService->getVendorRsvpCount($userId);

    return [
      [
        'label' => 'Total Sales',
        'value' => $revenue['gross'] ?? '$0.00',
        'delta' => NULL,
      ],
      [
        'label' => 'RSVPs',
        'value' => (string) $rsvpCount,
        'delta' => NULL,
      ],
      [
        'label' => 'Net Earnings',
        'value' => $revenue['net'] ?? '$0.00',
        'delta' => NULL,
      ],
      [
        'label' => 'Tickets Sold',
        'value' => (string) ($revenue['tickets'] ?? 0),
        'delta' => NULL,
      ],
    ];
  }

  /**
   * Returns an event overview block.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return array
   *   Overview data including sales, RSVPs, audience, boost, tickets.
   */
  public function getEventOverview(NodeInterface $event): array {
    return [
      'sales' => $this->ticketSalesService->getSalesSummary($event),
      'rsvps' => $this->rsvpStatsService->getRsvpSummary($event),
      'audience' => $this->categoryAudienceService->getGeoBreakdown($event),
      'boost' => $this->boostStatusService->getBoostStatuses($event),
      'tickets' => $this->ticketSalesService->getTicketBreakdown($event),
    ];
  }

  /**
   * Returns chart data for an event.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return array
   *   Chart data including sales and RSVP time series.
   */
  public function getEventCharts(NodeInterface $event): array {
    return [
      'sales' => $this->ticketSalesService->getDailySalesSeries($event),
      'rsvps' => $this->rsvpStatsService->getDailyRsvpSeries($event),
    ];
  }

}
