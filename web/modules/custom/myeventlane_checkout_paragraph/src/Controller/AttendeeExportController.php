<?php

declare(strict_types=1);

namespace Drupal\myeventlane_checkout_paragraph\Controller;

use Drupal\commerce_order\OrderItemStorageInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller for vendor attendee export.
 */
final class AttendeeExportController extends ControllerBase implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * AttendeeExportController constructor.
   */
  public function __construct(
    private readonly OrderItemStorageInterface $orderItemStorage,
    private readonly MessengerInterface $messengerService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager')->getStorage('commerce_order_item'),
      $container->get('messenger'),
    );
  }

  /**
   * Access check: vendor owner or admin.
   */
  public function access(NodeInterface $event, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer nodes')) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    $is_owner = (int) $event->getOwnerId() === (int) $account->id();
    return $is_owner
      ? AccessResult::allowed()->cachePerUser()->addCacheableDependency($event)
      : AccessResult::forbidden('Not the event owner.')->addCacheableDependency($event);
  }

  /**
   * Export attendee info for a given event as CSV.
   */
  public function export(NodeInterface $event): StreamedResponse|RedirectResponse {
    $access = $this->access($event, $this->currentUser());
    if ($access->isForbidden()) {
      $this->messengerService->addError($this->t('You do not have access to export this event.'));
      return $this->redirect('<front>');
    }

    $filename = 'attendees-' . $event->id() . '.csv';
    $question_labels = $this->collectQuestionLabels($event);

    return new StreamedResponse(function () use ($question_labels) {
      $handle = fopen('php://output', 'w');
      if (!$handle) {
        return;
      }

      $header = array_merge(
        [$this->t('First Name')->render(), $this->t('Last Name')->render(), $this->t('Email')->render()],
        $question_labels
      );
      fputcsv($handle, $header);

      foreach ($this->orderItemStorage->loadMultiple() as $item) {
        if (!$item->hasField('field_ticket_holder') || $item->get('field_ticket_holder')->isEmpty()) {
          continue;
        }

        foreach ($item->get('field_ticket_holder')->referencedEntities() as $attendee) {
          if (!$attendee instanceof ParagraphInterface || $attendee->bundle() !== 'attendee_answer') {
            continue;
          }

          $row = [
            $attendee->get('field_first_name')->value ?? '',
            $attendee->get('field_last_name')->value ?? '',
            $attendee->get('field_email')->value ?? '',
          ];

          $answer_map = $this->collectAnswers($attendee);
          foreach ($question_labels as $label) {
            $row[] = $answer_map[$label] ?? '';
          }
          fputcsv($handle, $row);
        }
      }

      fclose($handle);
    }, 200, [
      'Content-Type' => 'text/csv',
      'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);
  }

  /**
   * Demo queue handler placeholder.
   */
  public function queueExport(NodeInterface $event): RedirectResponse {
    $this->messengerService->addStatus($this->t('Export queued (demo).'));
    return $this->redirect('<front>');
  }

  /**
   * Collects question labels from the event configuration.
   *
   * @param \Drupal\node\NodeInterface $event
   *   Event node.
   *
   * @return string[]
   *   Ordered labels.
   */
  private function collectQuestionLabels(NodeInterface $event): array {
    $labels = [];
    if ($event->hasField('field_attendee_questions') && !$event->get('field_attendee_questions')->isEmpty()) {
      foreach ($event->get('field_attendee_questions')->referencedEntities() as $question) {
        $labels[] = $question->get('field_question_label')->value ?? 'Extra';
      }
    }
    return $labels;
  }

  /**
   * Collects answers for an attendee.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $attendee
   *   Attendee paragraph.
   *
   * @return array
   *   Label => answer map.
   */
  private function collectAnswers(ParagraphInterface $attendee): array {
    $map = [];
    if ($attendee->hasField('field_attendee_questions') && !$attendee->get('field_attendee_questions')->isEmpty()) {
      foreach ($attendee->get('field_attendee_questions')->referencedEntities() as $question) {
        if ($question instanceof ParagraphInterface && $question->bundle() === 'attendee_extra_field') {
          $label = $question->get('field_question_label')->value ?? 'Extra';
          $answer = $question->get('field_attendee_extra_field')->value ?? '';
          $map[$label] = $answer;
        }
      }
    }
    return $map;
  }

}
