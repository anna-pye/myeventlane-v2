<?php

declare(strict_types=1);

namespace Drupal\myeventlane_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\myeventlane_api\Service\ApiAuthenticationService;
use Drupal\myeventlane_api\Service\ApiResponseFormatter;
use Drupal\myeventlane_api\Service\RateLimiterService;
use Drupal\myeventlane_vendor\Entity\Vendor;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base controller for vendor API endpoints.
 */
abstract class VendorApiBaseController extends ControllerBase {

  /**
   * Constructs VendorApiBaseController.
   */
  public function __construct(
    protected readonly ApiAuthenticationService $authenticationService,
    protected readonly ApiResponseFormatter $responseFormatter,
    protected readonly RateLimiterService $rateLimiter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('myeventlane_api.authentication'),
      $container->get('myeventlane_api.response_formatter'),
      $container->get('myeventlane_api.rate_limiter'),
    );
  }

  /**
   * Authenticates the request and returns the vendor.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Drupal\myeventlane_vendor\Entity\Vendor|null
   *   The authenticated vendor, or NULL if authentication failed.
   */
  protected function authenticate(Request $request): ?Vendor {
    return $this->authenticationService->authenticate($request);
  }

  /**
   * Checks rate limits for vendor API.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   * @param \Drupal\myeventlane_vendor\Entity\Vendor $vendor
   *   The vendor entity.
   *
   * @return array|null
   *   Rate limit check result, or NULL if allowed.
   */
  protected function checkRateLimit(Request $request, Vendor $vendor): ?array {
    $identifier = 'vendor:' . $vendor->id();
    $limit_check = $this->rateLimiter->checkLimit(
      $request,
      $identifier,
      RateLimiterService::DEFAULT_VENDOR_LIMIT,
      RateLimiterService::PERIOD_HOUR
    );

    if (!$limit_check['allowed']) {
      return $limit_check;
    }

    return NULL;
  }

  /**
   * Returns an authentication error response.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON error response.
   */
  protected function authenticationError(): JsonResponse {
    return $this->responseFormatter->error(
      'UNAUTHORIZED',
      'Authentication required. Provide a valid API key in the Authorization header.',
      401
    );
  }

  /**
   * Returns a rate limit error response.
   *
   * @param array $limit_check
   *   Rate limit check result.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON error response.
   */
  protected function rateLimitError(array $limit_check): JsonResponse {
    $response = $this->responseFormatter->error(
      'RATE_LIMIT_EXCEEDED',
      'Rate limit exceeded. Please try again later.',
      429
    );
    $response->headers->set('X-RateLimit-Remaining', (string) $limit_check['remaining']);
    $response->headers->set('X-RateLimit-Reset', (string) $limit_check['reset']);
    return $response;
  }

  /**
   * Checks if a vendor owns an event.
   *
   * @param \Drupal\myeventlane_vendor\Entity\Vendor $vendor
   *   The vendor entity.
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return bool
   *   TRUE if the vendor owns the event.
   */
  protected function vendorOwnsEvent(Vendor $vendor, NodeInterface $event): bool {
    // Check if event is linked to vendor.
    if ($event->hasField('field_event_vendor') && !$event->get('field_event_vendor')->isEmpty()) {
      $event_vendor = $event->get('field_event_vendor')->entity;
      if ($event_vendor && $event_vendor->id() === $vendor->id()) {
        return TRUE;
      }
    }

    // Also check if vendor owner matches event owner.
    if ($vendor->getOwnerId() && (int) $event->getOwnerId() === (int) $vendor->getOwnerId()) {
      return TRUE;
    }

    return FALSE;
  }

}
