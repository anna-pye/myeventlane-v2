<?php

declare(strict_types=1);

namespace Drupal\myeventlane_vendor\Controller;

use Drupal\node\NodeInterface;

/**
 * Controller for the "Event information" step.
 *
 * Wraps the existing node edit form.
 */
final class ManageEventEditController extends ManageEventControllerBase {

  /**
   * Renders the event information edit form.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return array
   *   Render array.
   */
  public function edit(NodeInterface $event): array {
    // Use simplified custom form instead of full node edit form.
    $form = $this->formBuilder()->getForm('Drupal\myeventlane_vendor\Form\EventInformationForm', $event);

    // Wrap form in container for styling.
    $content = [
      '#type' => 'container',
      '#attributes' => ['class' => ['mel-manage-event-content']],
      'form' => $form,
    ];

    return $this->buildPage($event, 'myeventlane_vendor.manage_event.edit', $content);
  }

  /**
   * {@inheritdoc}
   */
  protected function getPageTitle(NodeInterface $event): string {
    return (string) $this->t('Event information');
  }

}
