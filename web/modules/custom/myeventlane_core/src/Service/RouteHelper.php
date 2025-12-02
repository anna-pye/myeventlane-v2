<?php

declare(strict_types=1);

namespace Drupal\myeventlane_core\Service;

use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Helper service for route lookups and URL generation.
 *
 * Provides convenient methods for checking route existence and
 * generating URLs throughout the MyEventLane platform.
 */
class RouteHelper {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected RouteProviderInterface $routeProvider;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected UrlGeneratorInterface $urlGenerator;

  /**
   * Constructs a RouteHelper object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(
    RouteProviderInterface $route_provider,
    UrlGeneratorInterface $url_generator,
  ) {
    $this->routeProvider = $route_provider;
    $this->urlGenerator = $url_generator;
  }

  /**
   * Checks if a route exists.
   *
   * @param string $route_name
   *   The route name to check.
   *
   * @return bool
   *   TRUE if the route exists, FALSE otherwise.
   */
  public function routeExists(string $route_name): bool {
    try {
      $this->routeProvider->getRouteByName($route_name);
      return TRUE;
    }
    catch (RouteNotFoundException $e) {
      return FALSE;
    }
  }

  /**
   * Generates a URL from a route name.
   *
   * @param string $route_name
   *   The route name.
   * @param array $params
   *   Optional route parameters.
   * @param array $options
   *   Optional URL options (e.g., 'absolute' => TRUE).
   *
   * @return string
   *   The generated URL string.
   */
  public function url(string $route_name, array $params = [], array $options = []): string {
    return $this->urlGenerator->generateFromRoute($route_name, $params, $options);
  }

  /**
   * Generates an absolute URL from a route name.
   *
   * @param string $route_name
   *   The route name.
   * @param array $params
   *   Optional route parameters.
   *
   * @return string
   *   The absolute URL string.
   */
  public function absoluteUrl(string $route_name, array $params = []): string {
    return $this->url($route_name, $params, ['absolute' => TRUE]);
  }

  /**
   * Safely generates a URL, returning a fallback if route doesn't exist.
   *
   * @param string $route_name
   *   The route name.
   * @param array $params
   *   Optional route parameters.
   * @param string $fallback
   *   Fallback URL if route doesn't exist.
   *
   * @return string
   *   The generated URL or fallback.
   */
  public function safeUrl(string $route_name, array $params = [], string $fallback = '/'): string {
    if (!$this->routeExists($route_name)) {
      return $fallback;
    }
    return $this->url($route_name, $params);
  }

}
