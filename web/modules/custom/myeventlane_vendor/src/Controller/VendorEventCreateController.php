<?php

declare(strict_types=1);

namespace Drupal\myeventlane_vendor\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\myeventlane_core\Service\DomainDetector;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for vendor event creation form with tabbed UX.
 */
final class VendorEventCreateController extends VendorConsoleBaseController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The entity form builder.
   */
  protected EntityFormBuilderInterface $entityFormBuilder;

  /**
   * Constructs the controller.
   */
  public function __construct(
    DomainDetector $domain_detector,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFormBuilderInterface $entity_form_builder,
  ) {
    parent::__construct($domain_detector, $current_user);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('myeventlane_core.domain_detector'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('entity.form_builder'),
    );
  }

  /**
   * Displays the tabbed event creation form.
   */
  public function buildForm(): array {
    $this->assertVendorAccess();

    // Get or create a draft event for this user.
    $event = $this->getOrCreateDraftEvent();

    // Use Drupal's entity form builder - this handles all saving properly.
    // The form alter in myeventlane_vendor.module adds tab pane wrappers.
    $form = $this->entityFormBuilder->getForm($event, 'default');

    // Return the form wrapped in the console page layout.
    return $this->buildVendorPage('myeventlane_vendor_console_page', [
      '#title' => $this->t('Create event'),
      '#header_actions' => [],
      '#tabs' => [],
      '#body' => $form,
    ]);
  }

  /**
   * Gets or creates a draft event for the current user.
   *
   * @return \Drupal\node\NodeInterface
   *   The event node.
   */
  private function getOrCreateDraftEvent(): NodeInterface {
    $storage = $this->entityTypeManager->getStorage('node');
    $uid = (int) $this->currentUser->id();

    // Look for existing draft event owned by this user.
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'event')
      ->condition('uid', $uid)
      ->condition('status', 0)
      ->sort('created', 'DESC')
      ->range(0, 1);

    $ids = $query->execute();
    if (!empty($ids)) {
      $event = $storage->load(reset($ids));
      if ($event instanceof NodeInterface) {
        return $event;
      }
    }

    // Create new draft event.
    $event = $storage->create([
      'type' => 'event',
      'title' => 'New Event',
      'status' => 0,
      'uid' => $uid,
    ]);

    // Set vendor if available.
    $vendor_storage = $this->entityTypeManager->getStorage('myeventlane_vendor');
    $owner_ids = $vendor_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('uid', $uid)
      ->execute();
    $users_ids = $vendor_storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('field_vendor_users', $uid)
      ->execute();
    $vendor_ids = array_values(array_unique(array_merge(
      $owner_ids ?: [],
      $users_ids ?: []
    )));

    if (!empty($vendor_ids)) {
      $vendor = $vendor_storage->load(reset($vendor_ids));
      if ($vendor && $event->hasField('field_event_vendor')) {
        $event->set('field_event_vendor', $vendor);
      }
    }

    $event->save();
    return $event;
  }

}
