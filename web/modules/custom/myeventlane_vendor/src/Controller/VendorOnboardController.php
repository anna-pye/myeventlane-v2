<?php

declare(strict_types=1);

namespace Drupal\myeventlane_vendor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\myeventlane_vendor\Entity\Vendor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for vendor onboarding flow.
 */
class VendorOnboardController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static();
  }

  /**
   * Onboarding page that shows vendor creation form.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   A render array or redirect response.
   */
  public function onboard(): array|RedirectResponse {
    $current_user = $this->currentUser();

    // Anonymous users should not access this page.
    if ($current_user->isAnonymous()) {
      $login_url = Url::fromRoute('user.login', [], [
        'query' => ['destination' => '/vendor/onboard'],
      ]);
      return new RedirectResponse($login_url->toString());
    }

    // Check if user already has a vendor.
    $vendor_ids = $this->getUserVendors((int) $current_user->id());
    if (!empty($vendor_ids)) {
      // User already has a vendor, redirect to create event.
      $create_event_url = Url::fromRoute('myeventlane_vendor.create_event_gateway');
      return new RedirectResponse($create_event_url->toString());
    }

    // Create a new vendor entity and set the current user as owner.
    $vendor = Vendor::create([
      'uid' => (int) $current_user->id(),
    ]);

    // Get the vendor add form.
    $form_object = $this->entityTypeManager()
      ->getFormObject('myeventlane_vendor', 'add')
      ->setEntity($vendor);
    $form = $this->formBuilder()->getForm($form_object);

    // Add a custom message at the top.
    $form['#prefix'] = '<div class="vendor-onboard-message"><p>' . $this->t('To create events, you need to set up your organiser profile. Fill out the form below to get started.') . '</p></div>';

    // Override the form submit redirect to go back to /create-event.
    $form['actions']['submit']['#submit'][] = [$this, 'onboardFormSubmit'];

    return $form;
  }

  /**
   * Custom submit handler to redirect to create event after vendor creation.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function onboardFormSubmit(array $form, \Drupal\Core\Form\FormStateInterface $form_state): void {
    $vendor = $form_state->getFormObject()->getEntity();
    $current_user = $this->currentUser();

    // Ensure the current user is associated with the vendor.
    if ($vendor->hasField('field_vendor_users')) {
      $current_users = $vendor->get('field_vendor_users')->getValue();
      $user_ids = array_column($current_users, 'target_id');
      if (!in_array($current_user->id(), $user_ids, TRUE)) {
        $vendor->get('field_vendor_users')->appendItem(['target_id' => $current_user->id()]);
        $vendor->save();
      }
    }

    // Redirect to create event gateway.
    $form_state->setRedirect('myeventlane_vendor.create_event_gateway');
  }

  /**
   * Gets vendor IDs associated with a user.
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

