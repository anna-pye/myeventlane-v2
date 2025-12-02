<?php

declare(strict_types=1);

namespace Drupal\myeventlane_dashboard\Service;

use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service for managing dashboard access and view modes.
 */
class DashboardAccess {

  /**
   * Session key for storing view mode preference.
   */
  protected const SESSION_KEY = 'myeventlane_view_as_vendor';

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Constructs a DashboardAccess object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    AccountProxyInterface $current_user,
    RequestStack $request_stack,
  ) {
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
  }

  /**
   * Checks if the current view is admin view.
   *
   * @return bool
   *   TRUE if viewing as admin, FALSE if viewing as vendor.
   */
  public function isAdminView(): bool {
    $session = $this->requestStack->getSession();
    return $session->get(self::SESSION_KEY) !== TRUE;
  }

  /**
   * Toggles between admin and vendor view modes.
   */
  public function toggle(): void {
    $session = $this->requestStack->getSession();
    $current = $session->get(self::SESSION_KEY, FALSE);
    $session->set(self::SESSION_KEY, !$current);
  }

  /**
   * Gets the vendor store ID for the current user.
   *
   * @return int|null
   *   The vendor store ID, or NULL if not applicable.
   */
  public function getVendorStoreId(): ?int {
    // If admin is viewing as vendor, return a mock store ID.
    if ($this->currentUser->hasPermission('access admin dashboard')) {
      $session = $this->requestStack->getSession();
      if ($session->get(self::SESSION_KEY) === TRUE) {
        return 1;
      }
      return NULL;
    }

    // For actual vendors, return their store ID.
    // @todo Integrate with Commerce store when available.
    return 1;
  }

}
