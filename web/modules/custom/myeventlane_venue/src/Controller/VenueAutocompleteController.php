<?php

declare(strict_types=1);

namespace Drupal\myeventlane_venue\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\myeventlane_venue\Service\VenueAutocompleteService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for venue autocomplete.
 */
class VenueAutocompleteController extends ControllerBase {

  /**
   * The venue autocomplete service.
   */
  protected VenueAutocompleteService $autocompleteService;

  /**
   * Constructs a VenueAutocompleteController.
   *
   * @param \Drupal\myeventlane_venue\Service\VenueAutocompleteService $autocomplete_service
   *   The venue autocomplete service.
   */
  public function __construct(VenueAutocompleteService $autocomplete_service) {
    $this->autocompleteService = $autocomplete_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('myeventlane_venue.autocomplete')
    );
  }

  /**
   * Autocomplete callback for venue search.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with autocomplete suggestions.
   */
  public function autocomplete(Request $request): JsonResponse {
    $string = $request->query->get('q', '');
    $suggestions = $this->autocompleteService->getAutocompleteSuggestions($string);

    // Format for autocomplete.js.
    $matches = [];
    foreach ($suggestions as $suggestion) {
      $matches[] = [
        'value' => $suggestion['value'],
        'label' => $suggestion['label'],
        'venue_id' => $suggestion['venue_id'],
      ];
    }

    return new JsonResponse($matches);
  }

}
