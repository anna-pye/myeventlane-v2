<?php

declare(strict_types=1);

namespace Drupal\myeventlane_event\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\myeventlane_core\Service\DomainDetector;
use Drupal\myeventlane_vendor\Controller\VendorConsoleBaseController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Guided event creation wizard controller.
 *
 * Provides a step-by-step wizard for vendors to create events.
 */
final class VendorEventWizardController extends VendorConsoleBaseController implements ContainerInjectionInterface {

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
   * Wizard steps definition.
   */
  private const STEPS = [
    'basics' => [
      'label' => 'Basics',
      'route' => 'myeventlane_event.wizard.basics',
      'fields' => ['title', 'body', 'field_event_image', 'field_event_type'],
      'required' => ['title', 'field_event_type'],
    ],
    'when-where' => [
      'label' => 'When & Where',
      'route' => 'myeventlane_event.wizard.when_where',
      'fields' => [
        'field_event_start',
        'field_event_end',
        'field_venue_name',
        'field_location',
        'field_location_latitude',
        'field_location_longitude',
        'field_external_url',
      ],
      'required' => ['field_event_start'],
    ],
    'tickets' => [
      'label' => 'Tickets',
      'route' => 'myeventlane_event.wizard.tickets',
      'fields' => [
        'field_capacity',
        'field_external_url',
      ],
      'required' => [],
    ],
    'review' => [
      'label' => 'Review & Publish',
      'route' => 'myeventlane_event.wizard.review',
      'fields' => [],
      'required' => [],
    ],
  ];

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
   * Creates a draft event and redirects to basics step.
   */
  public function createDraft(): RedirectResponse {
    $this->assertVendorAccess();
    $this->assertStripeConnected();

    $event = $this->getOrCreateDraftEvent();
    $url = Url::fromRoute('myeventlane_event.wizard.basics', ['event' => $event->id()]);
    return new RedirectResponse($url->toString());
  }

  /**
   * Basics step: Event name, summary, type.
   */
  public function basics(NodeInterface $event): array {
    $this->assertEventOwnership($event);
    return $this->buildStepPage($event, 'basics');
  }

  /**
   * When & Where step: Dates and location.
   */
  public function whenWhere(NodeInterface $event): array|RedirectResponse {
    $this->assertEventOwnership($event);
    $redirect = $this->assertStepAccess($event, 'when-where');
    if ($redirect) {
      return $redirect;
    }
    return $this->buildStepPage($event, 'when-where');
  }

  /**
   * Tickets step: RSVP settings or ticket types.
   */
  public function tickets(NodeInterface $event): array|RedirectResponse {
    $this->assertEventOwnership($event);
    $redirect = $this->assertStepAccess($event, 'tickets');
    if ($redirect) {
      return $redirect;
    }

    // Get event type to determine what to show.
    $event_type = NULL;
    if ($event->hasField('field_event_type') && !$event->get('field_event_type')->isEmpty()) {
      $event_type = $event->get('field_event_type')->value;
    }

    // For paid/both events, show link to ticket management instead of form.
    if (in_array($event_type, ['paid', 'both'], TRUE)) {
      $tickets_url = Url::fromRoute('myeventlane_vendor.console.event_tickets', ['event' => $event->id()]);
      
      return $this->buildVendorPage('myeventlane_event_wizard_tickets', [
        'title' => $this->t('Create event: Tickets'),
        'event' => $event,
        'event_type' => $event_type,
        'steps' => $this->buildStepNavigation($event, 'tickets'),
        'tickets_url' => $tickets_url->toString(),
      ]);
    }

    // For RSVP or external, show the form.
    return $this->buildStepPage($event, 'tickets');
  }

  /**
   * Review & Publish step.
   */
  public function review(NodeInterface $event): array|RedirectResponse {
    $this->assertEventOwnership($event);
    $redirect = $this->assertStepAccess($event, 'review');
    if ($redirect) {
      return $redirect;
    }

    $publish_url = Url::fromRoute('myeventlane_event.wizard.publish', ['event' => $event->id()]);
    
    $build = $this->buildVendorPage('myeventlane_event_wizard_review', [
      'title' => $this->t('Review & Publish'),
      'event' => $event,
      'steps' => $this->buildStepNavigation($event, 'review'),
      'summary' => $this->buildEventSummary($event),
      'publish_url' => $publish_url->toString(),
    ]);

    return $build;
  }

  /**
   * Publishes the event and redirects to success screen.
   */
  public function publish(NodeInterface $event): RedirectResponse {
    $this->assertEventOwnership($event);

    $event->setPublished();
    
    // Mark setup as complete when successfully published via wizard.
    if ($event->hasField('field_event_setup_complete')) {
      $event->set('field_event_setup_complete', TRUE);
    }
    
    $event->save();

    $this->getMessenger()->addStatus($this->t('Event "@title" has been published!', [
      '@title' => $event->label(),
    ]));

    // Redirect to success screen instead of event overview.
    $url = Url::fromRoute('myeventlane_event.wizard.success', ['event' => $event->id()]);
    return new RedirectResponse($url->toString());
  }

  /**
   * Post-publish success screen.
   */
  public function success(NodeInterface $event): array {
    $this->assertEventOwnership($event);

    // Verify event is published.
    if (!$event->isPublished()) {
      // If not published, redirect to review step.
      $url = Url::fromRoute('myeventlane_event.wizard.review', ['event' => $event->id()]);
      return new RedirectResponse($url->toString());
    }

    // Build URLs for next-steps buttons.
    $event_url = Url::fromRoute('entity.node.canonical', ['node' => $event->id()]);
    $tickets_url = Url::fromRoute('myeventlane_vendor.console.event_tickets', ['event' => $event->id()]);
    $create_url = Url::fromRoute('myeventlane_vendor.console.events_add');

    return $this->buildVendorPage('myeventlane_event_wizard_success', [
      'title' => $this->t('Event published successfully'),
      'event' => $event,
      'event_url' => $event_url->toString(),
      'tickets_url' => $tickets_url->toString(),
      'create_url' => $create_url->toString(),
    ]);
  }

  /**
   * Builds a step page with form.
   */
  private function buildStepPage(NodeInterface $event, string $step_id): array {
    $step = self::STEPS[$step_id] ?? NULL;
    if (!$step) {
      throw new \InvalidArgumentException("Unknown step: {$step_id}");
    }

    // Build partial form for this step.
    $form = $this->entityFormBuilder->getForm($event, 'default');
    $this->filterFormFields($form, $step['fields']);

    // Build step navigation separately (not in form).
    $steps_nav = $this->buildStepNavigation($event, $step_id);

    // Ensure actions container exists and is visible.
    if (!isset($form['actions'])) {
      $form['actions'] = [
        '#type' => 'actions',
      ];
    }
    $form['actions']['#access'] = TRUE;

    // Customize submit button text.
    if (isset($form['actions']['submit'])) {
      $next_step = $this->getNextStep($step_id);
      if ($next_step) {
        $form['actions']['submit']['#value'] = $this->t('Continue to @step →', ['@step' => $next_step['label']]);
      }
      else {
        $form['actions']['submit']['#value'] = $this->t('Continue →');
      }
      $form['actions']['submit']['#access'] = TRUE;
    }
    else {
      // Create submit button if it doesn't exist.
      $next_step = $this->getNextStep($step_id);
      $submit_label = $next_step 
        ? $this->t('Continue to @step →', ['@step' => $next_step['label']])
        : $this->t('Save');
      
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $submit_label,
        '#button_type' => 'primary',
        '#attributes' => ['class' => ['mel-btn', 'mel-btn--primary']],
      ];
    }

    // Add step-specific submit handler.
    if (!isset($form['actions']['#submit'])) {
      $form['actions']['#submit'] = [];
    }
    $form['actions']['#submit'][] = [$this, 'submitStep'];
    $form['#wizard_step'] = $step_id;
    $form['#wizard_event'] = $event;

    return $this->buildVendorPage('myeventlane_event_wizard_step', [
      'title' => $this->t('Create event: @step', ['@step' => $step['label']]),
      'step' => [
        'id' => $step_id,
        'label' => $step['label'],
      ],
      'form' => $form,
      'steps' => $steps_nav,
    ]);
  }

  /**
   * Filters form to show only specified fields.
   */
  private function filterFormFields(array &$form, array $allowed_fields): void {
    // Always keep form structure elements.
    $structure_keys = ['#', 'actions', 'advanced', 'meta', 'path', 'revision_information', 'status', 'author', 'options', 'promote', 'sticky'];
    
    foreach ($form as $key => &$element) {
      // Convert key to string for string operations.
      $key_string = (string) $key;
      
      // Skip structure/metadata keys - these are always kept visible.
      if (str_starts_with($key_string, '#') || in_array($key, $structure_keys, TRUE)) {
        // Ensure actions are always accessible.
        if ($key === 'actions' && is_array($element)) {
          $element['#access'] = TRUE;
        }
        continue;
      }

      // Check if this is a field we want to keep.
      $is_allowed = in_array($key, $allowed_fields, TRUE);
      
      // Also check for widget sub-elements (e.g., field_name widget).
      if (!$is_allowed && is_array($element)) {
        // Check if this element has a #field_name that matches allowed fields.
        if (isset($element['#field_name']) && in_array($element['#field_name'], $allowed_fields, TRUE)) {
          $is_allowed = TRUE;
        }
        // Check for widget array structure.
        elseif (isset($element['widget']) && is_array($element['widget'])) {
          // This is likely a field widget, check the parent key.
          foreach ($allowed_fields as $allowed_field) {
            if (str_starts_with($key_string, $allowed_field)) {
              $is_allowed = TRUE;
              break;
            }
          }
        }
      }

      if ($is_allowed) {
        // Keep this field visible, but recurse to handle nested structures.
        if (is_array($element)) {
          $this->filterFormFields($element, $allowed_fields);
        }
        continue;
      }

      // Hide field if it's not in allowed list.
      if (is_array($element)) {
        // Check if this looks like a field widget or field element.
        if (isset($element['#type']) || isset($element['widget']) || isset($element['#field_name']) || isset($element['#field_parents'])) {
          $element['#access'] = FALSE;
        }
        else {
          // Recurse into nested arrays (but not into hidden fields).
          $this->filterFormFields($element, $allowed_fields);
        }
      }
    }
  }

  /**
   * Submit handler for wizard steps.
   */
  public function submitStep(array &$form, FormStateInterface $form_state): void {
    $step_id = $form['#wizard_step'] ?? NULL;
    $event = $form['#wizard_event'] ?? NULL;

    if (!$step_id || !$event) {
      return;
    }

    // Event is saved by EntityFormBuilder, so we just redirect to next step.
    $next_step = $this->getNextStep($step_id);
    if ($next_step) {
      $url = Url::fromRoute($next_step['route'], ['event' => $event->id()]);
      $form_state->setRedirectUrl($url);
    }
  }

  /**
   * Gets or creates a draft event for the current user.
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
    $vendor = $this->getCurrentVendorOrNull();
    if ($vendor && $event->hasField('field_event_vendor')) {
      $event->set('field_event_vendor', $vendor);
    }

    $event->save();
    return $event;
  }

  /**
   * Asserts user can access a step (checks prerequisites).
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   Redirect response if step is not accessible, NULL otherwise.
   */
  private function assertStepAccess(NodeInterface $event, string $step_id): ?RedirectResponse {
    $step = self::STEPS[$step_id] ?? NULL;
    if (!$step) {
      return NULL;
    }

    // Check previous steps are complete.
    $step_keys = array_keys(self::STEPS);
    $current_index = array_search($step_id, $step_keys, TRUE);

    if ($current_index === FALSE || $current_index === 0) {
      return NULL;
    }

    // Check all previous steps have required fields.
    for ($i = 0; $i < $current_index; $i++) {
      $prev_step_id = $step_keys[$i];
      $prev_step = self::STEPS[$prev_step_id];
      foreach ($prev_step['required'] as $field_name) {
        if (!$event->hasField($field_name) || $event->get($field_name)->isEmpty()) {
          // Redirect to first incomplete step.
          $url = Url::fromRoute($prev_step['route'], ['event' => $event->id()]);
          return new RedirectResponse($url->toString());
        }
      }
    }

    return NULL;
  }

  /**
   * Gets the next step after the current one.
   */
  private function getNextStep(string $current_step_id): ?array {
    $step_keys = array_keys(self::STEPS);
    $current_index = array_search($current_step_id, $step_keys, TRUE);

    if ($current_index === FALSE || $current_index >= count($step_keys) - 1) {
      return NULL;
    }

    $next_step_id = $step_keys[$current_index + 1];
    return self::STEPS[$next_step_id] ?? NULL;
  }

  /**
   * Builds step navigation UI.
   */
  private function buildStepNavigation(NodeInterface $event, string $current_step_id): array {
    $navigation = [];
    $step_keys = array_keys(self::STEPS);

    foreach ($step_keys as $index => $step_id) {
      $step = self::STEPS[$step_id];
      $is_current = ($step_id === $current_step_id);
      $is_complete = $this->isStepComplete($event, $step_id);
      $is_accessible = $this->isStepAccessible($event, $step_id);

      $url = NULL;
      if ($is_accessible || $is_current) {
        $url = Url::fromRoute($step['route'], ['event' => $event->id()]);
      }

      $url_string = $url ? $url->toString() : NULL;
      
      $navigation[] = [
        'id' => $step_id,
        'label' => $step['label'],
        'url' => $url_string,
        'is_current' => $is_current,
        'is_complete' => $is_complete,
        'is_accessible' => $is_accessible,
        'step_number' => $index + 1,
      ];
    }

    return $navigation;
  }

  /**
   * Checks if a step is complete.
   */
  private function isStepComplete(NodeInterface $event, string $step_id): bool {
    $step = self::STEPS[$step_id] ?? NULL;
    if (!$step) {
      return FALSE;
    }

    foreach ($step['required'] as $field_name) {
      if (!$event->hasField($field_name) || $event->get($field_name)->isEmpty()) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Checks if a step is accessible (previous steps complete).
   */
  private function isStepAccessible(NodeInterface $event, string $step_id): bool {
    $step_keys = array_keys(self::STEPS);
    $current_index = array_search($step_id, $step_keys, TRUE);

    if ($current_index === FALSE || $current_index === 0) {
      return TRUE;
    }

    // Check all previous steps are complete.
    for ($i = 0; $i < $current_index; $i++) {
      $prev_step_id = $step_keys[$i];
      if (!$this->isStepComplete($event, $prev_step_id)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Builds event summary for review step.
   */
  private function buildEventSummary(NodeInterface $event): array {
    $summary = [];

    // Basics.
    $summary['basics'] = [
      'title' => $this->t('Event Basics'),
      'items' => [
        [
          'label' => $this->t('Event name'),
          'value' => $event->label(),
        ],
        [
          'label' => $this->t('Event type'),
          'value' => $event->hasField('field_event_type') && !$event->get('field_event_type')->isEmpty()
            ? $event->get('field_event_type')->value
            : $this->t('Not set'),
        ],
      ],
    ];

    // When & Where.
    $summary['when_where'] = [
      'title' => $this->t('When & Where'),
      'items' => [],
    ];

    // Start date/time.
    if ($event->hasField('field_event_start') && !$event->get('field_event_start')->isEmpty()) {
      $start_date = $event->get('field_event_start')->date;
      if ($start_date) {
        $summary['when_where']['items'][] = [
          'label' => $this->t('Start'),
          'value' => $start_date->format('F j, Y g:i A'),
        ];
      }
    }

    // End date/time.
    if ($event->hasField('field_event_end') && !$event->get('field_event_end')->isEmpty()) {
      $end_date = $event->get('field_event_end')->date;
      if ($end_date) {
        $summary['when_where']['items'][] = [
          'label' => $this->t('End'),
          'value' => $end_date->format('F j, Y g:i A'),
        ];
      }
    }

    // Venue name.
    if ($event->hasField('field_venue_name') && !$event->get('field_venue_name')->isEmpty()) {
      $summary['when_where']['items'][] = [
        'label' => $this->t('Venue'),
        'value' => $event->get('field_venue_name')->value,
      ];
    }

    // Location address.
    if ($event->hasField('field_location') && !$event->get('field_location')->isEmpty()) {
      $address = $event->get('field_location')->first();
      if ($address) {
        $address_parts = array_filter([
          $address->address_line1,
          $address->address_line2,
          $address->locality,
          $address->administrative_area,
          $address->postal_code,
        ]);
        if (!empty($address_parts)) {
          $summary['when_where']['items'][] = [
            'label' => $this->t('Location'),
            'value' => implode(', ', $address_parts),
          ];
        }
      }
    }

    // External URL (for online events).
    if ($event->hasField('field_external_url') && !$event->get('field_external_url')->isEmpty()) {
      $url = $event->get('field_external_url')->first();
      if ($url && $url->uri) {
        $summary['when_where']['items'][] = [
          'label' => $this->t('Online Link'),
          'value' => $url->uri,
        ];
      }
    }

    // Tickets.
    $summary['tickets'] = [
      'title' => $this->t('Tickets'),
      'items' => [],
    ];

    $event_type = $event->hasField('field_event_type') && !$event->get('field_event_type')->isEmpty()
      ? $event->get('field_event_type')->value
      : NULL;

    if ($event_type === 'external' && $event->hasField('field_external_url') && !$event->get('field_external_url')->isEmpty()) {
      $url = $event->get('field_external_url')->first();
      if ($url && $url->uri) {
        $summary['tickets']['items'][] = [
          'label' => $this->t('External URL'),
          'value' => $url->uri,
        ];
      }
    }
    elseif ($event_type === 'rsvp') {
      $summary['tickets']['items'][] = [
        'label' => $this->t('RSVP'),
        'value' => $this->t('RSVP enabled'),
      ];
    }
    elseif (in_array($event_type, ['paid', 'both'], TRUE)) {
      $summary['tickets']['items'][] = [
        'label' => $this->t('Ticketing'),
        'value' => $this->t('Ticketed event - manage on tickets page'),
      ];
    }

    // Capacity.
    if ($event->hasField('field_capacity') && !$event->get('field_capacity')->isEmpty()) {
      $summary['tickets']['items'][] = [
        'label' => $this->t('Capacity'),
        'value' => $event->get('field_capacity')->value,
      ];
    }

    return $summary;
  }

}
