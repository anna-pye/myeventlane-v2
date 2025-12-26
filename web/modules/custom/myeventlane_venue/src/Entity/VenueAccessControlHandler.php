<?php

declare(strict_types=1);

namespace Drupal\myeventlane_venue\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\myeventlane_vendor\Entity\Vendor;

/**
 * Access control handler for Venue entities.
 *
 * Enforces vendor-based access: vendors can only access their own venues.
 */
class VenueAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\myeventlane_venue\Entity\Venue $entity */
    assert($entity instanceof Venue);

    // Admin permission bypasses all checks.
    if ($account->hasPermission('administer myeventlane venue')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        // Allow viewing if user has permission and venue belongs to their vendor.
        if ($account->hasPermission('view myeventlane venue')) {
          if ($this->userCanAccessVenue($entity, $account)) {
            return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
          }
        }
        return AccessResult::forbidden()->cachePerUser()->addCacheableDependency($entity);

      case 'update':
      case 'delete':
        // Allow update/delete if user has permission and venue belongs to their vendor.
        if ($account->hasPermission('edit myeventlane venue') || $account->hasPermission('delete myeventlane venue')) {
          if ($this->userCanAccessVenue($entity, $account)) {
            return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
          }
        }
        return AccessResult::forbidden()->cachePerUser()->addCacheableDependency($entity);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Allow creation if user has permission.
    if ($account->hasPermission('create myeventlane venue') || $account->hasPermission('administer myeventlane venue')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    return AccessResult::forbidden()->cachePerPermissions();
  }

  /**
   * Checks if a user can access a venue based on vendor membership.
   *
   * @param \Drupal\myeventlane_venue\Entity\Venue $venue
   *   The venue entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return bool
   *   TRUE if the user can access the venue, FALSE otherwise.
   */
  protected function userCanAccessVenue(Venue $venue, AccountInterface $account): bool {
    // If venue has no vendor, deny access (shouldn't happen in normal flow).
    if ($venue->get('field_vendor')->isEmpty()) {
      return FALSE;
    }

    $venue_vendor = $venue->get('field_vendor')->entity;
    if (!$venue_vendor instanceof Vendor) {
      return FALSE;
    }

    // Check if user is in the vendor's field_vendor_users.
    if ($venue_vendor->hasField('field_vendor_users') && !$venue_vendor->get('field_vendor_users')->isEmpty()) {
      foreach ($venue_vendor->get('field_vendor_users')->getValue() as $item) {
        if (isset($item['target_id']) && (int) $item['target_id'] === (int) $account->id()) {
          return TRUE;
        }
      }
    }

    // Also check if user is the vendor owner (uid field).
    if ($venue_vendor->getOwnerId() === (int) $account->id()) {
      return TRUE;
    }

    return FALSE;
  }

}
