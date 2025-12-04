<?php

declare(strict_types=1);

namespace Drupal\myeventlane_event\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Helper class for altering ticket type paragraph forms.
 */
final class TicketTypeFormAlter {

  /**
   * Alters the ticket type paragraph form to add conditional fields.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function alterForm(array &$form, FormStateInterface $form_state): void {
    // For inline entity forms, the field names might be nested.
    // Try multiple selector patterns to handle different form contexts.
    $labelModeSelectors = [
      ':input[name*="[field_ticket_label_mode]"][name*="[value]"]',
      ':input[name*="field_ticket_label_mode"][name*="value"]',
      ':input[name="field_ticket_label_mode[0][value]"]',
    ];

    // Show preset label only when mode is 'preset'.
    if (isset($form['field_ticket_label_preset'])) {
      $form['field_ticket_label_preset']['#states'] = [
        'visible' => [
          $labelModeSelectors[0] => ['value' => 'preset'],
        ],
        'required' => [
          $labelModeSelectors[0] => ['value' => 'preset'],
        ],
      ];
    }

    // Show custom label only when mode is 'custom'.
    if (isset($form['field_ticket_label_custom'])) {
      $form['field_ticket_label_custom']['#states'] = [
        'visible' => [
          $labelModeSelectors[0] => ['value' => 'custom'],
        ],
        'required' => [
          $labelModeSelectors[0] => ['value' => 'custom'],
        ],
      ];
    }
  }

}

