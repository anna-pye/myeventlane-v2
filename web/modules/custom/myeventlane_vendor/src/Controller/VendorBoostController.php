<?php

declare(strict_types=1);

namespace Drupal\myeventlane_vendor\Controller;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\myeventlane_core\Service\DomainDetector;
use Drupal\myeventlane_vendor\Service\BoostStatusService;

/**
 * Boost management controller for vendor console.
 */
final class VendorBoostController extends VendorConsoleBaseController {

  /**
   * Constructs the controller.
   */
  public function __construct(DomainDetector $domain_detector, AccountProxyInterface $current_user, private readonly BoostStatusService $boostStatusService) {
    parent::__construct($domain_detector, $current_user);
  }

  /**
   * Displays Boost campaigns and controls.
   */
  public function boost(): array {
    $campaigns = $this->boostStatusService->getBoostStatuses(NULL);
    $events = $this->boostStatusService->getBoostableEvents();

    return $this->buildVendorPage('myeventlane_vendor_console_page', [
      'title' => 'Boost',
      'body' => [
        '#theme' => 'myeventlane_vendor_boost',
        '#campaigns' => $campaigns,
        '#events' => $events,
      ],
    ]);
  }

}
