<?php

declare(strict_types=1);

namespace Drupal\myeventlane_api\Service;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Service for formatting API responses with consistent schema.
 */
final class ApiResponseFormatter {

  /**
   * API version.
   */
  public const VERSION = 'v1';

  /**
   * Creates a successful API response.
   *
   * @param mixed $data
   *   The response data.
   * @param int $status_code
   *   HTTP status code (default 200).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  public function success($data, int $status_code = 200): JsonResponse {
    $response_data = [
      'meta' => [
        'version' => self::VERSION,
        'generated_at' => \Drupal::time()->getRequestTime(),
      ],
      'data' => $data,
    ];

    return new JsonResponse($response_data, $status_code);
  }

  /**
   * Creates an error API response.
   *
   * @param string $code
   *   Error code (e.g., 'FORBIDDEN', 'NOT_FOUND').
   * @param string $message
   *   Error message.
   * @param int $status_code
   *   HTTP status code.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  public function error(string $code, string $message, int $status_code = 400): JsonResponse {
    $response_data = [
      'meta' => [
        'version' => self::VERSION,
      ],
      'error' => [
        'code' => $code,
        'message' => $message,
      ],
    ];

    return new JsonResponse($response_data, $status_code);
  }

  /**
   * Creates a paginated response.
   *
   * @param array $items
   *   Array of items.
   * @param int $page
   *   Current page number.
   * @param int $limit
   *   Items per page.
   * @param int $total
   *   Total number of items.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  public function paginated(array $items, int $page, int $limit, int $total): JsonResponse {
    $total_pages = (int) ceil($total / $limit);

    $response_data = [
      'meta' => [
        'version' => self::VERSION,
        'generated_at' => \Drupal::time()->getRequestTime(),
        'pagination' => [
          'page' => $page,
          'limit' => $limit,
          'total' => $total,
          'total_pages' => $total_pages,
        ],
      ],
      'data' => $items,
    ];

    return new JsonResponse($response_data);
  }

}
