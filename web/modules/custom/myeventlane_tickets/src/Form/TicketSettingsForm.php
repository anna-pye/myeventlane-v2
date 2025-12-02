<?php

declare(strict_types=1);

namespace Drupal\myeventlane_tickets\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Ticket settings form for MyEventLane.
 */
final class TicketSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'myeventlane_tickets_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['myeventlane_tickets.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('myeventlane_tickets.settings');

    $form['pdf'] = [
      '#type' => 'details',
      '#title' => $this->t('PDF generation'),
      '#open' => TRUE,
    ];

    $form['pdf']['pdf_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable PDF tickets'),
      '#description' => $this->t('When enabled, attendees can download their tickets as a PDF file.'),
      '#default_value' => $config->get('pdf_enabled') ?? TRUE,
    ];

    $form['pdf']['pdf_expiry_days'] = [
      '#type' => 'number',
      '#title' => $this->t('PDF download expiry (days)'),
      '#description' => $this->t('Number of days ticket PDFs remain downloadable after purchase. Set to 0 for no expiry.'),
      '#default_value' => $config->get('pdf_expiry_days') ?? 365,
      '#min' => 0,
      '#max' => 3650,
      '#states' => [
        'visible' => [
          ':input[name="pdf_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['pdf']['include_qr_code'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include QR code on tickets'),
      '#description' => $this->t('When enabled, each ticket PDF includes a QR code for quick check-in at the event.'),
      '#default_value' => $config->get('include_qr_code') ?? TRUE,
      '#states' => [
        'visible' => [
          ':input[name="pdf_enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Display settings'),
      '#open' => TRUE,
    ];

    $form['display']['show_ticket_code'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show ticket code to attendees'),
      '#description' => $this->t('Display the unique ticket code on confirmation pages and emails.'),
      '#default_value' => $config->get('show_ticket_code') ?? TRUE,
    ];

    $form['display']['ticket_code_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Ticket code format'),
      '#description' => $this->t('Format used for generating ticket codes.'),
      '#options' => [
        'alphanumeric' => $this->t('Alphanumeric (ABC123XY)'),
        'numeric' => $this->t('Numeric only (12345678)'),
        'short' => $this->t('Short alphanumeric (AB12CD)'),
      ],
      '#default_value' => $config->get('ticket_code_format') ?? 'alphanumeric',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('myeventlane_tickets.settings')
      ->set('pdf_enabled', $form_state->getValue('pdf_enabled'))
      ->set('pdf_expiry_days', $form_state->getValue('pdf_expiry_days'))
      ->set('include_qr_code', $form_state->getValue('include_qr_code'))
      ->set('show_ticket_code', $form_state->getValue('show_ticket_code'))
      ->set('ticket_code_format', $form_state->getValue('ticket_code_format'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
