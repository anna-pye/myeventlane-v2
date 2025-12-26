<?php

declare(strict_types=1);

namespace Drupal\myeventlane_venue\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the Venue entity forms.
 */
class VenueForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);
    $entity = $this->getEntity();

    // Group fields into logical sections.
    $form['basic_info'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic information'),
      '#weight' => -20,
    ];

    // Move name field into basic_info section.
    if (isset($form['name'])) {
      $form['name']['#group'] = 'basic_info';
    }

    // Location section.
    $form['location'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Location'),
      '#weight' => -10,
    ];

    if (isset($form['field_location'])) {
      $form['field_location']['#group'] = 'location';
    }
    if (isset($form['field_latitude'])) {
      $form['field_latitude']['#group'] = 'location';
    }
    if (isset($form['field_longitude'])) {
      $form['field_longitude']['#group'] = 'location';
    }
    if (isset($form['field_place_id'])) {
      $form['field_place_id']['#group'] = 'location';
    }

    // Vendor section.
    $form['vendor'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Vendor'),
      '#weight' => 0,
    ];

    if (isset($form['field_vendor'])) {
      $form['field_vendor']['#group'] = 'vendor';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();
    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New venue %label has been created.', $message_arguments));
        $this->logger('myeventlane_venue')->notice('Created new venue %label', $logger_arguments);
        $form_state->setRedirect('entity.myeventlane_venue.canonical', ['myeventlane_venue' => $entity->id()]);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The venue %label has been updated.', $message_arguments));
        $this->logger('myeventlane_venue')->notice('Updated venue %label.', $logger_arguments);
        $form_state->setRedirect('entity.myeventlane_venue.canonical', ['myeventlane_venue' => $entity->id()]);
        break;

      default:
        $form_state->setRedirect('entity.myeventlane_venue.collection');
    }

    return $result;
  }

}
