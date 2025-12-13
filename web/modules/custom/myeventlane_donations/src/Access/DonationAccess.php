<?php

declare(strict_types=1);

namespace Drupal\myeventlane_donations\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\myeventlane_core\Service\DomainDetector;

/**
 * Access control for donation routes.
 */
final class DonationAccess {

  /**
   * Checks access for platform donation routes (vendor domain only).
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   * @param \Drupal\myeventlane_core\Service\DomainDetector $domain_detector
   *   The domain detector service.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function platformAccess(RouteMatchInterface $route_match, AccountInterface $account, DomainDetector $domain_detector): AccessResult {
    // Must be on vendor domain.
    if (!$domain_detector->isVendorDomain()) {
      return AccessResult::forbidden('Platform donations are only available on the vendor domain.');
    }

    // Must be logged in.
    if ($account->isAnonymous()) {
      return AccessResult::forbidden('You must be logged in to make a donation.');
    }

    return AccessResult::allowed()->cachePerPermissions();
  }

  /**
   * Checks access for vendor donation list routes (vendor domain only).
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to check access for.
   * @param \Drupal\myeventlane_core\Service\DomainDetector $domain_detector
   *   The domain detector service.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function vendorAccess(RouteMatchInterface $route_match, AccountInterface $account, DomainDetector $domain_detector): AccessResult {
    // Must be on vendor domain.
    if (!$domain_detector->isVendorDomain()) {
      return AccessResult::forbidden('Vendor donation pages are only available on the vendor domain.');
    }

    // Must be logged in.
    if ($account->isAnonymous()) {
      return AccessResult::forbidden('You must be logged in to view donations.');
    }

    return AccessResult::allowed()->cachePerPermissions();
  }

}

