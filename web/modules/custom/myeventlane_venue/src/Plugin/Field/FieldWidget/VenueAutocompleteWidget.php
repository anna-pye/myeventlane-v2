<?php

declare(strict_types=1);

namespace Drupal\myeventlane_venue\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Venue autocomplete widget with vendor filtering.
 *
 * @FieldWidget(
 *   id = "myeventlane_venue_autocomplete",
 *   label = @Translation("Venue autocomplete (vendor-filtered)"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class VenueAutocompleteWidget extends WidgetBase {

  /**
   * The current user.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->currentUser = $container->get('current_user');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Get the current user's vendor.
    $vendor = $this->getCurrentUserVendor();
    if (!$vendor) {
      $element['target_id'] = [
        '#type' => 'markup',
        '#markup' => $this->t('You must be associated with a vendor to select venues.'),
      ];
      return $element;
    }

    // Build autocomplete with vendor filtering.
    $element['target_id'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'myeventlane_venue',
      '#title' => $this->t('Venue'),
      '#default_value' => $items[$delta]->entity ?? NULL,
      '#selection_handler' => 'default:myeventlane_venue',
      '#selection_settings' => [
        'target_bundles' => NULL,
        'sort' => [
          'field' => 'name',
          'direction' => 'ASC',
        ],
        'auto_create' => FALSE,
      ],
      '#validate_reference' => TRUE,
      '#required' => $this->fieldDefinition->isRequired(),
      '#autocomplete_route_name' => 'myeventlane_venue.autocomplete',
      '#autocomplete_route_parameters' => [],
    ];

    // Add vendor filter to selection handler via alter hook.
    // The VenueAccessControlHandler will enforce vendor access.
    $element['#element_validate'][] = [static::class, 'validateVenueAccess'];

    return $element;
  }

  /**
   * Element validate callback to ensure venue belongs to user's vendor.
   */
  public static function validateVenueAccess(array &$element, FormStateInterface $form_state, array &$complete_form): void {
    $value = $element['target_id']['#value'] ?? NULL;
    if (empty($value)) {
      return;
    }

    $entity_type_manager = \Drupal::entityTypeManager();
    $current_user = \Drupal::currentUser();

    // Get the venue.
    $venue = $entity_type_manager->getStorage('myeventlane_venue')->load($value);
    if (!$venue) {
      return;
    }

    // Check vendor access.
    $vendor = NULL;
    $uid = (int) $current_user->id();
    if ($uid > 0) {
      $vendor_storage = $entity_type_manager->getStorage('myeventlane_vendor');
      $query = $vendor_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('field_vendor_users', $uid)
        ->range(0, 1);
      $vendor_ids = $query->execute();
      if (!empty($vendor_ids)) {
        $vendor = $vendor_storage->load(reset($vendor_ids));
      }
    }

    if (!$vendor) {
      $form_state->setError($element['target_id'], t('You must be associated with a vendor to select venues.'));
      return;
    }

    // Check if venue belongs to this vendor.
    if ($venue->get('field_vendor')->target_id !== $vendor->id()) {
      $form_state->setError($element['target_id'], t('You can only select venues that belong to your vendor.'));
    }
  }

  /**
   * Gets the current user's vendor.
   *
   * @return \Drupal\myeventlane_vendor\Entity\Vendor|null
   *   The vendor entity, or NULL if not found.
   */
  protected function getCurrentUserVendor() {
    $uid = (int) $this->currentUser->id();
    if ($uid === 0) {
      return NULL;
    }

    $storage = $this->entityTypeManager->getStorage('myeventlane_vendor');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('field_vendor_users', $uid)
      ->range(0, 1);

    $ids = $query->execute();
    if (empty($ids)) {
      return NULL;
    }

    $vendor = $storage->load(reset($ids));
    return $vendor;
  }

}
