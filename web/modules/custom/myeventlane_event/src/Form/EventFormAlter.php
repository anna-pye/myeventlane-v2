<?php

declare(strict_types=1);

namespace Drupal\myeventlane_event\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Event node form wizard.
 *
 * Alters the default node event form into a server-authoritative multi-step
 * wizard with AJAX Next/Back navigation and scoped validation.
 */
final class EventFormAlter implements ContainerInjectionInterface {

  use StringTranslationTrait;

  private const STATE_KEY = 'mel_wizard_step';

  public function __construct(
    private readonly AccountProxyInterface $currentUser,
    private readonly RouteMatchInterface $routeMatch,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('current_user'),
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Alters the node event form.
   */
  public function alterForm(array &$form, FormStateInterface $form_state, string $form_id): void {
    // Only apply to the event node form.
    if (!str_starts_with($form_id, 'node_event_') && $form_id !== 'node_event_form') {
      return;
    }
    if (!isset($form['actions']) || !is_array($form['actions'])) {
      return;
    }

    // Attach wizard assets.
    $form['#attached']['library'][] = 'myeventlane_event/event_wizard';

    // Build steps based on actual form fields.
    $steps = $this->buildStepsFromForm($form);
    if (count($steps) < 2) {
      return;
    }

    $current = $this->getCurrentStep($form_state, $steps);

    // Wrapper for AJAX rebuild.
    $form['mel_wizard'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'mel-event-wizard-wrapper',
        'class' => ['mel-event-wizard'],
        'data-mel-current-step' => $current,
      ],
      '#weight' => -1000,
    ];

    // Hidden fields used by JS to request a step change.
    $form['mel_wizard']['wizard_current_step'] = [
      '#type' => 'hidden',
      '#value' => $current,
      '#attributes' => ['class' => ['js-mel-wizard-current-step']],
    ];
    $form['mel_wizard']['wizard_target_step'] = [
      '#type' => 'hidden',
      '#default_value' => '',
      '#attributes' => ['class' => ['js-mel-wizard-target-step']],
    ];

    // Stepper UI.
    $form['mel_wizard']['stepper'] = $this->buildStepper($steps, $current);

    // Move top-level fields into step containers.
    foreach ($steps as $step_id => $step) {
      $container_key = 'step_' . $step_id;
      $is_active = ($step_id === $current);

      $form['mel_wizard'][$container_key] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['mel-wizard-step'],
          'data-step' => $step_id,
        ],
        '#access' => $is_active,
      ];

      $form['mel_wizard'][$container_key]['_heading'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $step['label'],
        '#attributes' => ['class' => ['mel-wizard-step__title']],
      ];

      foreach ($step['fields'] as $field_name) {
        if (isset($form[$field_name])) {
          $form['mel_wizard'][$container_key][$field_name] = $form[$field_name];
          unset($form[$field_name]);
        }
      }

      // Hide step if it ended up empty (except Review).
      if ($step_id !== 'review' && count($form['mel_wizard'][$container_key]) <= 2) {
        $form['mel_wizard'][$container_key]['#access'] = FALSE;
      }
    }

    // Rewrite actions into wizard navigation.
    $this->rewriteActionsAsWizardNav($form, $form_state, $steps, $current);

    // Ensure wrapper exists for AJAX replacement.
    $form['mel_wizard']['#prefix'] = '<div id="mel-event-wizard-wrapper">';
    $form['mel_wizard']['#suffix'] = '</div>';
  }

  /**
   * Builds step definitions by checking what exists on the form.
   *
   * @return array<string, array{label: string, fields: string[]}>
   */
  private function buildStepsFromForm(array $form): array {
    // Order matters. Only include fields that exist on the form.
    $candidates = [
      'basics' => [
        'label' => (string) $this->t('Basics'),
        'fields' => [
          'title',
          'field_event_tagline',
          'field_event_description',
          'field_event_categories',
          'field_event_image',
        ],
      ],
      'schedule' => [
        'label' => (string) $this->t('Schedule'),
        'fields' => [
          'field_event_date',
          'field_event_end_date',
          'field_event_recurring',
        ],
      ],
      'location' => [
        'label' => (string) $this->t('Location'),
        'fields' => [
          'field_event_location_mode',
          'field_event_location',
          'field_event_lat',
          'field_event_lng',
          'field_event_online_url',
        ],
      ],
      'tickets' => [
        'label' => (string) $this->t('Tickets'),
        'fields' => [
          'field_event_mode',
          'field_event_ticket_types',
          'field_event_capacity',
          'field_event_external_url',
        ],
      ],
      'design' => [
        'label' => (string) $this->t('Design'),
        'fields' => [
          'field_event_theme',
          'field_event_primary_color',
          'field_event_secondary_color',
        ],
      ],
      'questions' => [
        'label' => (string) $this->t('Questions'),
        'fields' => [
          'field_event_questions',
        ],
      ],
      'review' => [
        'label' => (string) $this->t('Review'),
        'fields' => [],
      ],
    ];

    $steps = [];
    foreach ($candidates as $step_id => $def) {
      $existing = [];
      foreach ($def['fields'] as $field_name) {
        if (isset($form[$field_name])) {
          $existing[] = $field_name;
        }
      }

      // Always include Review.
      if ($step_id === 'review' || !empty($existing)) {
        $steps[$step_id] = [
          'label' => $def['label'],
          'fields' => $existing,
        ];
      }
    }

    return $steps;
  }

  /**
   * Returns current step id.
   *
   * @param array<string, array{label: string, fields: string[]}> $steps
   */
  private function getCurrentStep(FormStateInterface $form_state, array $steps): string {
    $requested = (string) ($form_state->getValue('wizard_target_step') ?? '');
    $current = (string) ($form_state->get(self::STATE_KEY) ?? '');

    if ($requested !== '' && isset($steps[$requested])) {
      $current = $requested;
    }
    if ($current === '' || !isset($steps[$current])) {
      $current = array_key_first($steps);
    }

    $form_state->set(self::STATE_KEY, $current);
    return $current;
  }

  /**
   * Rewrites actions into wizard navigation and keeps the original submit.
   *
   * @param array<string, array{label: string, fields: string[]}> $steps
   */
  private function rewriteActionsAsWizardNav(array &$form, FormStateInterface $form_state, array $steps, string $current): void {
    $step_ids = array_keys($steps);
    $index = array_search($current, $step_ids, TRUE);
    $is_first = ($index === 0);
    $is_last = ($index === (count($step_ids) - 1));

    $original_submit = $form['actions']['submit'] ?? NULL;

    // Preserve other actions, but show them only on the final step.
    $other_actions = $form['actions'];
    unset($other_actions['submit']);

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 1000,
      '#attributes' => ['class' => ['mel-wizard-actions']],
    ];

    $form['actions']['mel_wizard_back'] = [
      '#type' => 'submit',
      '#value' => (string) $this->t('Back'),
      '#submit' => [[$this, 'backSubmit']],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => 'mel-event-wizard-wrapper',
      ],
      '#access' => !$is_first,
      '#attributes' => ['class' => ['button', 'mel-wizard-actions__back']],
    ];

    $form['actions']['mel_wizard_next'] = [
      '#type' => 'submit',
      '#value' => (string) ($is_last ? $this->t('Finish') : $this->t('Next')),
      '#submit' => [[$this, 'nextSubmit']],
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => 'mel-event-wizard-wrapper',
      ],
      '#attributes' => ['class' => ['button', 'button--primary', 'mel-wizard-actions__next']],
      '#limit_validation_errors' => $this->buildLimitErrorsForStep($current, $steps),
    ];

    // Hidden "Go to step" submit used by stepper clicks.
    $form['actions']['mel_wizard_goto'] = [
      '#type' => 'submit',
      '#value' => (string) $this->t('Go'),
      '#submit' => [[$this, 'gotoSubmit']],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => [$this, 'ajaxCallback'],
        'wrapper' => 'mel-event-wizard-wrapper',
      ],
      '#attributes' => [
        'class' => ['js-mel-wizard-goto', 'visually-hidden'],
        'tabindex' => '-1',
      ],
    ];

    if ($original_submit) {
      $original_submit['#access'] = $is_last;
      $original_submit['#attributes']['class'][] = 'mel-wizard-actions__publish';
      $form['actions']['submit'] = $original_submit;
    }

    foreach ($other_actions as $key => $action) {
      if (is_array($action)) {
        $action['#access'] = $is_last;
      }
      $form['actions'][$key] = $action;
    }
  }

  /**
   * AJAX callback for wizard wrapper.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state): array {
    return $form['mel_wizard'] ?? $form;
  }

  /**
   * Next handler.
   */
  public function nextSubmit(array &$form, FormStateInterface $form_state): void {
    $steps = $this->buildStepsFromForm($form);
    $current = $this->getCurrentStep($form_state, $steps);

    $step_ids = array_keys($steps);
    $index = array_search($current, $step_ids, TRUE);
    if ($index === FALSE) {
      $form_state->setRebuild();
      return;
    }

    $next_index = min($index + 1, count($step_ids) - 1);
    $form_state->set(self::STATE_KEY, $step_ids[$next_index]);
    $form_state->setValue('wizard_target_step', $step_ids[$next_index]);
    $form_state->setRebuild();
  }

  /**
   * Back handler.
   */
  public function backSubmit(array &$form, FormStateInterface $form_state): void {
    $steps = $this->buildStepsFromForm($form);
    $current = $this->getCurrentStep($form_state, $steps);

    $step_ids = array_keys($steps);
    $index = array_search($current, $step_ids, TRUE);
    if ($index === FALSE) {
      $form_state->setRebuild();
      return;
    }

    $prev_index = max($index - 1, 0);
    $form_state->set(self::STATE_KEY, $step_ids[$prev_index]);
    $form_state->setValue('wizard_target_step', $step_ids[$prev_index]);
    $form_state->setRebuild();
  }

  /**
   * Stepper "go to step" handler (no validation).
   */
  public function gotoSubmit(array &$form, FormStateInterface $form_state): void {
    $steps = $this->buildStepsFromForm($form);
    $target = (string) ($form_state->getValue('wizard_target_step') ?? '');
    if ($target !== '' && isset($steps[$target])) {
      $form_state->set(self::STATE_KEY, $target);
    }
    $form_state->setRebuild();
  }

  /**
   * Builds #limit_validation_errors structure for a step.
   *
   * @param array<string, array{label: string, fields: string[]}> $steps
   *
   * @return array<int, array<int, string>>
   */
  private function buildLimitErrorsForStep(string $step_id, array $steps): array {
    $limited = [];
    if (isset($steps[$step_id])) {
      foreach ($steps[$step_id]['fields'] as $field_name) {
        $limited[] = [$field_name];
      }
    }
    if (!in_array(['title'], $limited, TRUE)) {
      $limited[] = ['title'];
    }
    return $limited;
  }

  /**
   * Builds stepper UI.
   *
   * @param array<string, array{label: string, fields: string[]}> $steps
   */
  private function buildStepper(array $steps, string $current): array {
    $items = [];
    foreach ($steps as $step_id => $step) {
      $is_active = ($step_id === $current);

      $items[$step_id] = [
        '#type' => 'link',
        '#title' => $step['label'],
        '#url' => Url::fromRoute('<current>'),
        '#attributes' => [
          'class' => array_values(array_filter([
            'mel-wizard-stepper__link',
            $is_active ? 'is-active' : NULL,
          ])),
          'data-step-target' => $step_id,
        ],
      ];
    }

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['mel-wizard-stepper']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => (string) $this->t('Event setup'),
        '#attributes' => ['class' => ['mel-wizard-stepper__title']],
      ],
      'items' => [
        '#theme' => 'item_list',
        '#items' => $items,
        '#attributes' => ['class' => ['mel-wizard-stepper__list']],
      ],
    ];
  }

}
