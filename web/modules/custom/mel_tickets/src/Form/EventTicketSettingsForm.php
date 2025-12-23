<?php

declare(strict_types=1);

namespace Drupal\mel_tickets\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for Event Ticket Settings edit form.
 */
final class EventTicketSettingsForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Pre-populate event if not set (from route parameter).
    /** @var \Drupal\mel_tickets\Entity\EventTicketSettings $entity */
    $entity = $this->entity;
    if ($entity->isNew() && $entity->get('event')->isEmpty()) {
      $route_match = \Drupal::service('current_route_match');
      $event = $route_match->getParameter('event');
      if ($event && $event->id()) {
        $entity->set('event', $event->id());
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): void {
    $entity = $this->entity;
    $status = $entity->save();

    $event_id = $entity->getEventId();

    if ($status === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Created the ticket settings for this event.'));
    }
    else {
      $this->messenger()->addStatus($this->t('Saved the ticket settings.'));
    }

    $form_state->setRedirect('mel_tickets.event_tickets_settings', ['event' => $event_id]);
  }

}
