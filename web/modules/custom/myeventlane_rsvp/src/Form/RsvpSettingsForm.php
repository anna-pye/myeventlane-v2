<?php

declare(strict_types=1);

namespace Drupal\myeventlane_rsvp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * RSVP settings form for MyEventLane.
 */
final class RsvpSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['myeventlane_rsvp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'myeventlane_rsvp_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('myeventlane_rsvp.settings');

    $form['notifications'] = [
      '#type' => 'details',
      '#title' => $this->t('Notification settings'),
      '#open' => TRUE,
    ];

    $form['notifications']['send_confirmation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send confirmation to attendees'),
      '#description' => $this->t('When enabled, attendees receive an email confirming their RSVP with event details and calendar links.'),
      '#default_value' => $config->get('send_confirmation') ?? TRUE,
    ];

    $form['notifications']['send_vendor_copy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send copy to organisers'),
      '#description' => $this->t('When enabled, event organisers receive a notification whenever someone RSVPs to their event.'),
      '#default_value' => $config->get('send_vendor_copy') ?? FALSE,
    ];

    $form['notifications']['digest_frequency'] = [
      '#type' => 'select',
      '#title' => $this->t('Organiser digest frequency'),
      '#description' => $this->t('How often organisers receive a summary of new RSVPs. Set to "Individual" to send each RSVP separately.'),
      '#options' => [
        'individual' => $this->t('Individual notifications'),
        'hourly' => $this->t('Hourly digest'),
        'daily' => $this->t('Daily digest'),
      ],
      '#default_value' => $config->get('digest_frequency') ?? 'daily',
      '#states' => [
        'visible' => [
          ':input[name="send_vendor_copy"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['waitlist'] = [
      '#type' => 'details',
      '#title' => $this->t('Waitlist settings'),
      '#open' => TRUE,
    ];

    $form['waitlist']['waitlist_enabled_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable waitlist by default'),
      '#description' => $this->t('When enabled, new events allow attendees to join a waitlist when the event reaches capacity.'),
      '#default_value' => $config->get('waitlist_enabled_default') ?? TRUE,
    ];

    $form['waitlist']['auto_promote'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Auto-promote from waitlist'),
      '#description' => $this->t('Automatically move attendees from the waitlist to confirmed when spots become available (e.g. after a cancellation).'),
      '#default_value' => $config->get('auto_promote') ?? TRUE,
    ];

    $form['waitlist']['notify_on_promotion'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Notify on waitlist promotion'),
      '#description' => $this->t('Send attendees an email when they are promoted from the waitlist to confirmed.'),
      '#default_value' => $config->get('notify_on_promotion') ?? TRUE,
    ];

    $form['cancellation'] = [
      '#type' => 'details',
      '#title' => $this->t('Cancellation settings'),
      '#open' => TRUE,
    ];

    $form['cancellation']['allow_cancellation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow attendees to cancel'),
      '#description' => $this->t('When enabled, attendees can cancel their RSVP via a link in their confirmation email.'),
      '#default_value' => $config->get('allow_cancellation') ?? TRUE,
    ];

    $form['cancellation']['cancellation_cutoff_hours'] = [
      '#type' => 'number',
      '#title' => $this->t('Cancellation cutoff (hours before event)'),
      '#description' => $this->t('Attendees cannot cancel within this many hours of the event start. Set to 0 to allow cancellation any time.'),
      '#default_value' => $config->get('cancellation_cutoff_hours') ?? 24,
      '#min' => 0,
      '#max' => 168,
      '#states' => [
        'visible' => [
          ':input[name="allow_cancellation"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['email'] = [
      '#type' => 'details',
      '#title' => $this->t('Email settings'),
      '#open' => FALSE,
    ];

    $form['email']['langcode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email language code'),
      '#description' => $this->t('Language code for RSVP emails (e.g. "en", "en-au"). Leave blank to use site default.'),
      '#default_value' => $config->get('langcode') ?? '',
      '#maxlength' => 12,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('myeventlane_rsvp.settings')
      ->set('send_confirmation', $form_state->getValue('send_confirmation'))
      ->set('send_vendor_copy', $form_state->getValue('send_vendor_copy'))
      ->set('digest_frequency', $form_state->getValue('digest_frequency'))
      ->set('waitlist_enabled_default', $form_state->getValue('waitlist_enabled_default'))
      ->set('auto_promote', $form_state->getValue('auto_promote'))
      ->set('notify_on_promotion', $form_state->getValue('notify_on_promotion'))
      ->set('allow_cancellation', $form_state->getValue('allow_cancellation'))
      ->set('cancellation_cutoff_hours', $form_state->getValue('cancellation_cutoff_hours'))
      ->set('langcode', $form_state->getValue('langcode'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
