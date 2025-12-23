<?php

declare(strict_types=1);

namespace Drupal\myeventlane_api\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Service for logging API access for audit purposes.
 */
final class ApiAuditLogger {

  /**
   * Constructs ApiAuditLogger.
   */
  public function __construct(
    private readonly Connection $database,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Logs an API request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   * @param string $endpoint
   *   The endpoint path.
   * @param int|null $vendor_id
   *   The vendor ID (if authenticated).
   * @param string|null $ip_address
   *   The client IP address.
   * @param int $response_code
   *   The HTTP response code.
   */
  public function logRequest(Request $request, string $endpoint, ?int $vendor_id = NULL, ?string $ip_address = NULL, int $response_code = 200): void {
    $ip = $ip_address ?: $request->getClientIp();

    try {
      $this->database->insert('myeventlane_api_audit_log')
        ->fields([
          'endpoint' => $endpoint,
          'method' => $request->getMethod(),
          'vendor_id' => $vendor_id,
          'ip_address' => $this->normalizeIp($ip),
          'user_agent' => $request->headers->get('User-Agent', ''),
          'response_code' => $response_code,
          'timestamp' => \Drupal::time()->getRequestTime(),
        ])
        ->execute();
    }
    catch (\Exception $e) {
      // Log to watchdog if database insert fails.
      $this->loggerFactory->get('myeventlane_api')->error('Failed to write audit log: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Normalizes IP address for storage.
   *
   * @param string $ip
   *   The IP address.
   *
   * @return string
   *   Normalized IP address.
   */
  private function normalizeIp(string $ip): string {
    // For IPv6, store a hash to avoid storage issues.
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
      return 'ipv6:' . substr(hash('sha256', $ip), 0, 16);
    }
    return $ip;
  }

}
