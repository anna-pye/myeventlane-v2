<?php

declare(strict_types=1);

namespace Drupal\myeventlane_diagnostics\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\NodeInterface;
use Drupal\myeventlane_diagnostics\Service\DiagnosticsServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for AJAX diagnostics endpoints.
 */
final class DiagnosticsController extends ControllerBase {

  /**
   * Constructs DiagnosticsController.
   */
  public function __construct(
    private readonly DiagnosticsServiceInterface $diagnosticsService,
    private readonly RendererInterface $renderer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('myeventlane_diagnostics.service'),
      $container->get('renderer'),
    );
  }

  /**
   * AJAX endpoint for diagnostics widget.
   */
  public function ajax(NodeInterface $event, Request $request): AjaxResponse {
    $scope = $request->query->get('scope');
    $diagnostics = $this->diagnosticsService->analyzeEvent($event, $scope);

    // Filter sections if scope is set.
    $sections = $diagnostics['sections'];
    if ($scope) {
      $sections = array_filter($sections, function ($key) use ($scope) {
        return $key === $scope;
      }, ARRAY_FILTER_USE_KEY);
      // Recalculate summary for scoped results.
      $diagnostics['summary'] = $this->calculateScopedSummary($sections);
    }

    // Render the widget content (innerHTML only).
    $build = [
      '#theme' => 'diagnostics_widget_content',
      '#summary' => $diagnostics['summary'],
      '#sections' => $sections,
    ];

    $html = (string) $this->renderer->render($build);

    $response = new AjaxResponse();
    // Replace the inner content of the diagnostics widget container.
    $widget_container = '<div class="mel-diagnostics">' . $html . '</div>';
    $response->addCommand(new HtmlCommand('#mel-diagnostics-widget', $widget_container));

    return $response;
  }

  /**
   * Calculates summary for scoped diagnostics.
   */
  private function calculateScopedSummary(array $sections): array {
    $totalIssues = 0;
    $hasFailures = FALSE;
    $hasWarnings = FALSE;

    foreach ($sections as $sectionItems) {
      foreach ($sectionItems as $item) {
        if ($item['status'] === 'fail') {
          $hasFailures = TRUE;
          $totalIssues++;
        }
        elseif ($item['status'] === 'warn') {
          $hasWarnings = TRUE;
          $totalIssues++;
        }
      }
    }

    $status = 'ok';
    if ($hasFailures) {
      $status = 'fail';
    }
    elseif ($hasWarnings) {
      $status = 'warn';
    }

    return [
      'status' => $status,
      'issue_count' => $totalIssues,
    ];
  }

}
