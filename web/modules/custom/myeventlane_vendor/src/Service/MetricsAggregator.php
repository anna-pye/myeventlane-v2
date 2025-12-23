<?php

declare(strict_types=1);

namespace Drupal\myeventlane_vendor\Service;

use Drupal\myeventlane_metrics\Service\EventMetricsServiceInterface;
use Drupal\node\NodeInterface;

/**
 * Aggregates metrics across ticket sales, RSVPs, audience, and boost.
 *
 * All data is now sourced from EventMetricsService and other services.
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
    private readonly EventMetricsServiceInterface $eventMetricsService,
  ) {}

  /**
   * Returns KPI cards for the vendor dashboard.
   *
   * Data is queried from EventMetricsService.
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
   * Returns an event overview block using EventMetricsService.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return array
   *   Overview data including sales, RSVPs, audience, boost, tickets.
   */
  public function getEventOverview(NodeInterface $event): array {
    // Use EventMetricsService for core metrics.
    $attendeeCount = $this->eventMetricsService->getAttendeeCount($event);
    $checkedInCount = $this->eventMetricsService->getCheckedInCount($event);
    $remainingCapacity = $this->eventMetricsService->getRemainingCapacity($event);
    $isSoldOut = $this->eventMetricsService->isSoldOut($event);
    $revenue = $this->eventMetricsService->getRevenue($event);
    $checkInRate = $this->eventMetricsService->getCheckInRate($event);
    $ticketBreakdown = $this->eventMetricsService->getTicketBreakdown($event);

    // Get additional data from other services.
    $salesSummary = $this->ticketSalesService->getSalesSummary($event);
    $rsvpSummary = $this->rsvpStatsService->getRsvpSummary($event);

    return [
      'attendees' => [
        'total' => $attendeeCount,
        'checked_in' => $checkedInCount,
        'check_in_rate' => $checkInRate,
      ],
      'capacity' => [
        'total' => $this->eventMetricsService->getCapacityTotal($event),
        'remaining' => $remainingCapacity,
        'sold_out' => $isSoldOut,
      ],
      'revenue' => $revenue ? [
        'amount' => $revenue->getNumber(),
        'currency' => $revenue->getCurrencyCode(),
      ] : NULL,
      'sales' => $salesSummary,
      'rsvps' => $rsvpSummary,
      'audience' => $this->categoryAudienceService->getGeoBreakdown($event),
      'boost' => $this->boostStatusService->getBoostStatuses($event),
      'tickets' => $ticketBreakdown,
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
