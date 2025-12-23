<?php

declare(strict_types=1);

namespace Drupal\myeventlane_diagnostics\Service;

use Drupal\node\NodeInterface;

/**
 * Interface for event diagnostics service.
 */
interface DiagnosticsServiceInterface {

  /**
   * Analyzes an event and returns diagnostic information.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   * @param string|null $scope
   *   Optional scope filter (e.g., 'basics', 'tickets', 'capacity').
   *
   * @return array
   *   Structured diagnostic data with keys:
   *   - summary: ['status' => 'ok|warn|fail', 'issue_count' => int]
   *   - sections: array of diagnostic items by section
   */
  public function analyzeEvent(NodeInterface $event, ?string $scope = NULL): array;

}
