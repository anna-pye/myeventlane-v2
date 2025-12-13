<?php

declare(strict_types=1);

namespace Drupal\myeventlane_analytics\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Report generator service.
 *
 * Generates PDF and enhanced CSV reports.
 */
final class ReportGeneratorService {

  /**
   * Constructs ReportGeneratorService.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly RendererInterface $renderer,
    private readonly AnalyticsDataService $dataService,
  ) {}

  /**
   * Generates a comprehensive analytics report for an event.
   *
   * @param int $eventId
   *   The event node ID.
   * @param int|null $startDate
   *   Start date timestamp (optional).
   * @param int|null $endDate
   *   End date timestamp (optional).
   *
   * @return array
   *   Render array for the report.
   */
  public function generateEventReport(int $eventId, ?int $startDate = NULL, ?int $endDate = NULL): array {
    $eventNode = $this->entityTypeManager->getStorage('node')->load($eventId);
    if (!$eventNode) {
      return [];
    }

    $timeSeries = $this->dataService->getSalesTimeSeries($eventId, 'day', $startDate, $endDate);
    $ticketBreakdown = $this->dataService->getTicketTypeBreakdown($eventId);
    $conversionFunnel = $this->dataService->getConversionFunnel($eventId);

    // Calculate totals.
    $totalRevenue = 0.0;
    $totalTickets = 0;
    foreach ($timeSeries as $point) {
      $totalRevenue += $point['revenue'];
      $totalTickets += $point['ticket_count'];
    }

    $build = [
      '#theme' => 'myeventlane_analytics_report',
      '#event' => $eventNode,
      '#time_series' => $timeSeries,
      '#ticket_breakdown' => $ticketBreakdown,
      '#conversion_funnel' => $conversionFunnel,
      '#total_revenue' => $totalRevenue,
      '#total_tickets' => $totalTickets,
      '#start_date' => $startDate ? date('Y-m-d', $startDate) : NULL,
      '#end_date' => $endDate ? date('Y-m-d', $endDate) : NULL,
    ];

    return $build;
  }

}



