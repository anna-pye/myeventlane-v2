<?php

namespace Drupal\myeventlane_rsvp\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Public RSVP form for MyEventLane.
 *
 * This form allows public users to RSVP for free events. It creates an
 * RsvpSubmission entity and optionally sends confirmation emails.
 */
class RsvpPublicForm extends FormBase {

  use StringTranslationTrait;

  /**
   * Entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Must NOT have type because FormBase defines this untyped.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Logger channel.
   */
  protected LoggerInterface $logger;

  /**
   * Messenger service.
   */
  protected MessengerInterface $messengerService;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $route_match,
    LoggerInterface $logger,
    MessengerInterface $messenger
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $route_match;
    $this->logger = $logger;
    $this->messengerService = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('logger.factory')->get('myeventlane_rsvp'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'myeventlane_rsvp_public_form';
  }

  /**
   * Tries to detect the event from the route.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The event node, or NULL if not found.
   */
  protected function getEventFromRoute(): ?NodeInterface {
    $candidate = $this->routeMatch->getParameter('node');
    if ($candidate instanceof NodeInterface && $candidate->bundle() === 'event') {
      return $candidate;
    }

    $candidate = $this->routeMatch->getParameter('event');
    if ($candidate instanceof NodeInterface && $candidate->bundle() === 'event') {
      return $candidate;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $event = NULL): array {
    $event = $event ?: $this->getEventFromRoute();
    $event_id = $event instanceof NodeInterface ? $event->id() : NULL;

    $form['#attributes']['class'][] = 'mel-rsvp-form';
    $form['#attributes']['class'][] = 'mel-rsvp-public-form';

    // Store event ID.
    $form['event_id'] = [
      '#type' => 'hidden',
      '#value' => $event_id,
    ];

    if (!$event_id) {
      $this->logger->warning('RSVP form built without event.');
      $form['error'] = [
        '#markup' => '<div class="mel-alert mel-alert--warning">' .
          $this->t('We could not determine the event. Please try again.') .
          '</div>',
      ];
      return $form;
    }

    // Intro text.
    $form['intro'] = [
      '#markup' => '<p class="mel-rsvp-intro" style="margin-bottom: 1.5rem; color: #666;">' .
        $this->t('Please enter your information to RSVP for this free event.') .
        '</p>',
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Your name'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#attributes' => [
        'placeholder' => $this->t('Enter your full name'),
        'autocomplete' => 'name',
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('Enter your email address'),
        'autocomplete' => 'email',
      ],
    ];

    $form['phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone (optional)'),
      '#required' => FALSE,
      '#attributes' => [
        'placeholder' => $this->t('Enter your phone number'),
        'autocomplete' => 'tel',
      ],
    ];

    $form['guests'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of guests'),
      '#description' => $this->t('Including yourself'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 10,
      '#default_value' => 1,
    ];

    // Accessibility needs field (optional).
    $accessibility_options = $this->getAccessibilityOptions();
    if (!empty($accessibility_options)) {
      $form['accessibility_needs'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Accessibility needs (optional)'),
        '#description' => $this->t('Let us know if you have any accessibility requirements.'),
        '#options' => $accessibility_options,
        '#required' => FALSE,
      ];
    }

    // Optional donation field.
    $form['donation'] = [
      '#type' => 'number',
      '#title' => $this->t('Optional donation'),
      '#description' => $this->t('Support this event with an optional donation. Amount in AUD.'),
      '#required' => FALSE,
      '#min' => 0,
      '#step' => 1,
      '#default_value' => 0,
      '#field_prefix' => '$',
    ];

    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('RSVP Now'),
      '#button_type' => 'primary',
      '#attributes' => [
        'class' => ['mel-btn', 'mel-btn-primary', 'mel-btn-lg', 'mel-btn-block'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $guests = (int) $form_state->getValue('guests');
    if ($guests < 1) {
      $form_state->setErrorByName('guests', $this->t('Please RSVP for at least one guest.'));
    }

    $email = $form_state->getValue('email');
    if (!empty($email) && !\Drupal::service('email.validator')->isValid($email)) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid email address.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $event_id = $values['event_id'] ?? NULL;

    if (!$event_id) {
      $this->logger->error('RSVP submission missing event_id.');
      $this->messengerService->addError($this->t('Event not found. Please try again.'));
      return;
    }

    $event = $this->entityTypeManager->getStorage('node')->load($event_id);

    if (!$event instanceof NodeInterface || $event->bundle() !== 'event') {
      $this->logger->error('Invalid event ID @id.', ['@id' => $event_id]);
      $this->messengerService->addError($this->t('Event not found. Please try again.'));
      return;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('rsvp_submission');

      // Create the RSVP submission entity.
      $submission = $storage->create([
        'event_id' => ['target_id' => $event->id()],
        'attendee_name' => $values['name'] ?? '',
        'name' => $values['name'] ?? '',
        'email' => $values['email'] ?? '',
        'phone' => $values['phone'] ?? '',
        'guests' => (int) ($values['guests'] ?? 1),
        'donation' => (float) ($values['donation'] ?? 0),
        'status' => 'confirmed',
      ]);

      $submission->save();

      $this->logger->notice('RSVP created for event @event by @name (@email)', [
        '@event' => $event->label(),
        '@name' => $values['name'],
        '@email' => $values['email'],
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('RSVP save failed for event @id: @m', [
        '@id' => $event->id(),
        '@m' => $e->getMessage(),
      ]);
      $this->messengerService->addError($this->t('We could not save your RSVP. Please try again.'));
      return;
    }

    // Save accessibility needs if provided and module is available.
    $accessibilityNeeds = $values['accessibility_needs'] ?? [];
    if (!empty($accessibilityNeeds)) {
      // Filter out unchecked values.
      $accessibilityNeeds = array_filter($accessibilityNeeds, function ($value) {
        return $value !== 0 && $value !== FALSE && $value !== '';
      });

      if (!empty($accessibilityNeeds)) {
        try {
          if (\Drupal::hasService('myeventlane_event_attendees.manager')) {
            $attendanceManager = \Drupal::service('myeventlane_event_attendees.manager');
            $attendeeData = [
              'name' => $values['name'] ?? '',
              'email' => $values['email'] ?? '',
              'status' => 'confirmed',
              'accessibility_needs' => array_values($accessibilityNeeds),
            ];
            $attendanceManager->createAttendance($event, $attendeeData, 'rsvp');
          }
        }
        catch (\Exception $e) {
          // Log but don't fail RSVP if attendance manager is unavailable.
          $this->logger->warning('Could not save accessibility needs for RSVP: @message', [
            '@message' => $e->getMessage(),
          ]);
        }
      }
    }

    // Send confirmation email if mailer service is available.
    try {
      if (\Drupal::hasService('myeventlane_rsvp.mailer')) {
        $mailer = \Drupal::service('myeventlane_rsvp.mailer');
        $mailer->sendConfirmation($submission, $event);
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Could not send RSVP confirmation email: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    $this->messengerService->addStatus(
      $this->t('Your RSVP has been confirmed for @event. You will receive a confirmation email shortly.', [
        '@event' => $event->label(),
      ])
    );

    // Redirect to thank you page or event page.
    $thankYouRoute = 'myeventlane_rsvp.thank_you';
    try {
      $url = Url::fromRoute($thankYouRoute, [
        'node' => $event->id(),
        'submission' => $submission->id(),
      ]);
      $form_state->setRedirectUrl($url);
    }
    catch (\Exception $e) {
      // Fallback to event page.
      $form_state->setRedirect('entity.node.canonical', ['node' => $event->id()]);
    }
  }

  /**
   * Gets accessibility taxonomy term options.
   *
   * @return array
   *   Array of term ID => term name.
   */
  protected function getAccessibilityOptions(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('taxonomy_term');
      $terms = $storage->loadByProperties(['vid' => 'accessibility']);
      $options = [];
      foreach ($terms as $term) {
        $options[$term->id()] = $term->label();
      }
      return $options;
    }
    catch (\Exception) {
      return [];
    }
  }

}
