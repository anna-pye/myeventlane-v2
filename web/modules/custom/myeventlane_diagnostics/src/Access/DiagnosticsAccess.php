<?php

declare(strict_types=1);

namespace Drupal\myeventlane_diagnostics\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Access control for diagnostics endpoints.
 */
final class DiagnosticsAccess implements AccessInterface {

  /**
   * Checks access to diagnostics.
   */
  public function access(NodeInterface $event, AccountInterface $account): AccessResult {
    // Must be an event.
    if ($event->bundle() !== 'event') {
      return AccessResult::forbidden();
    }

    // Check if user can view diagnostics.
    if (!$account->hasPermission('view event diagnostics')) {
      return AccessResult::forbidden()->addCacheContexts(['user.permissions']);
    }

    // Check if user owns the event or is admin.
    if ($event->getOwnerId() === (int) $account->id() || $account->hasPermission('administer nodes')) {
      return AccessResult::allowed()->addCacheContexts(['user', 'user.permissions']);
    }

    return AccessResult::forbidden()->addCacheContexts(['user']);
  }

}
