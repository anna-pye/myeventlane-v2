<?php

declare(strict_types=1);

namespace Drupal\myeventlane_vendor\Controller;

use Drupal\node\NodeInterface;

/**
 * Controller for the "Page content" step.
 */
final class ManageEventContentController extends ManageEventControllerBase {

  /**
   * Renders the page content form.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return array
   *   Render array.
   */
  public function content(NodeInterface $event): array {
    // Get the content form.
    $form = $this->formBuilder()->getForm('Drupal\myeventlane_vendor\Form\EventContentForm', $event);

    // Build preview.
    $preview = $this->buildPreview($event);

    $content = [
      '#type' => 'container',
      '#attributes' => ['class' => ['mel-manage-event-content', 'mel-content-step']],
      'form' => $form,
      'preview' => $preview,
    ];

    return $this->buildPage($event, 'myeventlane_vendor.manage_event.content', $content);
  }

  /**
   * Builds a preview of the event page.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   *
   * @return array
   *   Preview render array.
   */
  private function buildPreview(NodeInterface $event): array {
    if ($event->isNew()) {
      return [
        '#markup' => '<p>' . $this->t('Preview will be available after saving the event.') . '</p>',
      ];
    }

    $view_builder = $this->entityTypeManager()->getViewBuilder('node');
    $preview = $view_builder->view($event, 'teaser');

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['mel-event-preview']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $this->t('Preview'),
      ],
      'preview' => $preview,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPageTitle(NodeInterface $event): string {
    return (string) $this->t('Page content');
  }

}
