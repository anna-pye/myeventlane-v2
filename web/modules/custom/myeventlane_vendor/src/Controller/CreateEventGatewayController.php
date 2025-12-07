<?php

declare(strict_types=1);

namespace Drupal\myeventlane_vendor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Gateway controller for event creation that enforces vendor onboarding.
 */
class CreateEventGatewayController extends ControllerBase {

  /**
   * Redirects users based on their vendor status.
   *
   * Logic:
   * - Anonymous users → login with destination back to /create-event
   * - Logged-in users without vendor → /vendor/onboard
   * - Logged-in users with vendor → /node/add/event (with vendor pre-filled)
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function gateway(): RedirectResponse {
    $current_user = $this->currentUser();

    // Anonymous users: redirect to login with destination.
    if ($current_user->isAnonymous()) {
      $login_url = Url::fromRoute('user.login', [], [
        'query' => ['destination' => '/create-event'],
      ]);
      return new RedirectResponse($login_url->toString());
    }

    // Find vendors associated with the current user.
    $vendor_ids = $this->getUserVendors((int) $current_user->id());

    // No vendor found: redirect to onboarding.
    if (empty($vendor_ids)) {
      $onboard_url = Url::fromRoute('myeventlane_vendor.onboard');
      return new RedirectResponse($onboard_url->toString());
    }

    // One vendor: redirect to event creation with vendor pre-filled.
    if (count($vendor_ids) === 1) {
      $vendor_id = reset($vendor_ids);
      $event_url = Url::fromRoute('node.add', ['node_type' => 'event'], [
        'query' => ['vendor' => $vendor_id],
      ]);
      return new RedirectResponse($event_url->toString());
    }

    // Multiple vendors: redirect to vendor selection page.
    // For now, we'll pick the first one, but could redirect to a selection page.
    $vendor_id = reset($vendor_ids);
    $event_url = Url::fromRoute('node.add', ['node_type' => 'event'], [
      'query' => ['vendor' => $vendor_id],
    ]);
    return new RedirectResponse($event_url->toString());
  }

  /**
   * Gets vendor IDs associated with a user.
   *
   * Checks both:
   * - uid (owner) field
   * - field_vendor_users (multi-user field)
   *
   * @param int $uid
   *   The user ID.
   *
   * @return array
   *   Array of vendor IDs (as integers).
   */
  private function getUserVendors(int $uid): array {
    $storage = $this->entityTypeManager()->getStorage('myeventlane_vendor');
    
    // Check vendors where user is the owner (uid field).
    $owner_ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('uid', $uid)
      ->execute();

    // Check vendors where user is in field_vendor_users.
    $users_ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('field_vendor_users', $uid)
      ->execute();

    // Merge and return unique IDs, converting to integers.
    $all_ids = array_merge(
      $owner_ids ?: [],
      $users_ids ?: []
    );
    
    // Convert string keys to integer values and ensure uniqueness.
    return array_values(array_unique(array_map('intval', $all_ids)));
  }

}


