<?php

declare(strict_types=1);

namespace Drupal\myeventlane_event_attendees\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\myeventlane_event_attendees\Entity\EventAttendee;
use Drupal\myeventlane_event_attendees\Service\AttendanceManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller for vendor attendee management pages.
 */
final class VendorAttendeeController extends ControllerBase {

  /**
   * Constructs VendorAttendeeController.
   */
  public function __construct(
    private readonly AttendanceManagerInterface $attendanceManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('myeventlane_event_attendees.manager'),
    );
  }

  /**
   * Access check for vendor attendee routes.
   */
  public function access(NodeInterface $node, AccountInterface $account): AccessResultInterface {
    // Must be an event node.
    if ($node->bundle() !== 'event') {
      return AccessResult::forbidden('Not an event.');
    }

    // Admin can access all.
    if ($account->hasPermission('administer event attendees')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Check if user is the event author.
    $isOwner = (int) $node->getOwnerId() === (int) $account->id();

    if ($isOwner && $account->hasPermission('view own event attendees')) {
      return AccessResult::allowed()
        ->cachePerUser()
        ->addCacheableDependency($node);
    }

    return AccessResult::forbidden('You do not have access to view attendees for this event.');
  }

  /**
   * Access check for single attendee operations.
   */
  public function accessAttendee(EventAttendee $event_attendee, AccountInterface $account): AccessResultInterface {
    $event = $event_attendee->getEvent();

    if (!$event instanceof NodeInterface) {
      return AccessResult::forbidden('Event not found.');
    }

    // Admin can manage all.
    if ($account->hasPermission('administer event attendees')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Check if user is the event author.
    $isOwner = (int) $event->getOwnerId() === (int) $account->id();

    if ($isOwner && $account->hasPermission('manage own event attendees')) {
      return AccessResult::allowed()
        ->cachePerUser()
        ->addCacheableDependency($event);
    }

    return AccessResult::forbidden();
  }

  /**
   * Page title callback for attendee list.
   */
  public function listTitle(NodeInterface $node): string {
    return (string) $this->t('Attendees for @event', ['@event' => $node->label()]);
  }

  /**
   * Lists attendees for an event.
   */
  public function list(NodeInterface $node): array {
    $attendees = $this->attendanceManager->getAttendeesForEvent((int) $node->id());
    $availability = $this->attendanceManager->getAvailability($node);

    $rows = [];
    foreach ($attendees as $attendee) {
      $rows[] = [
        'name' => $attendee->getName(),
        'email' => $attendee->getEmail(),
        'status' => $attendee->getStatus(),
        'source' => $attendee->getSource(),
        'checked_in' => $attendee->isCheckedIn() ? $this->t('Yes') : $this->t('No'),
        'created' => \Drupal::service('date.formatter')->format($attendee->get('created')->value, 'short'),
      ];
    }

    $build = [];

    // Summary section.
    $build['summary'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['attendee-summary']],
    ];

    $build['summary']['stats'] = [
      '#theme' => 'item_list',
      '#title' => $this->t('Summary'),
      '#items' => [
        $this->t('Total attendees: @count', ['@count' => count($attendees)]),
        $this->t('Capacity: @capacity', [
          '@capacity' => $availability['capacity'] > 0 ? $availability['capacity'] : $this->t('Unlimited'),
        ]),
        $availability['remaining'] !== NULL
          ? $this->t('Spots remaining: @remaining', ['@remaining' => $availability['remaining']])
          : '',
      ],
    ];

    // Export link.
    $build['summary']['export'] = [
      '#type' => 'link',
      '#title' => $this->t('Export to CSV'),
      '#url' => \Drupal\Core\Url::fromRoute('myeventlane_event_attendees.vendor_export', ['node' => $node->id()]),
      '#attributes' => ['class' => ['button']],
    ];

    // Attendee table.
    $build['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Email'),
        $this->t('Status'),
        $this->t('Source'),
        $this->t('Checked in'),
        $this->t('Registered'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No attendees yet. Share your event to start collecting RSVPs or ticket sales.'),
      '#attributes' => ['class' => ['attendee-list']],
    ];

    $build['#cache'] = [
      'tags' => ['event_attendee_list:' . $node->id()],
      'contexts' => ['user'],
    ];

    return $build;
  }

  /**
   * Exports attendees as CSV.
   */
  public function export(NodeInterface $node): Response {
    $attendees = $this->attendanceManager->getAttendeesForEvent((int) $node->id());

    $filename = sprintf('attendees-%s-%s.csv',
      preg_replace('/[^a-z0-9]+/', '-', strtolower($node->label())),
      date('Y-m-d')
    );

    $response = new StreamedResponse(function () use ($attendees) {
      $handle = fopen('php://output', 'w');

      // Header row.
      fputcsv($handle, [
        'Name',
        'Email',
        'Phone',
        'Status',
        'Source',
        'Ticket Code',
        'Checked In',
        'Registered',
      ]);

      // Data rows.
      foreach ($attendees as $attendee) {
        fputcsv($handle, [
          $attendee->getName(),
          $attendee->getEmail(),
          $attendee->get('phone')->value ?? '',
          $attendee->getStatus(),
          $attendee->getSource(),
          $attendee->getTicketCode() ?? '',
          $attendee->isCheckedIn() ? 'Yes' : 'No',
          date('Y-m-d H:i', $attendee->get('created')->value),
        ]);
      }

      fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

    return $response;
  }

  /**
   * Checks in an attendee (AJAX endpoint).
   */
  public function checkIn(EventAttendee $event_attendee): Response {
    $success = $this->attendanceManager->checkIn($event_attendee);

    if ($success) {
      return new Response(json_encode([
        'success' => TRUE,
        'message' => (string) $this->t('@name has been checked in.', ['@name' => $event_attendee->getName()]),
      ]), 200, ['Content-Type' => 'application/json']);
    }

    return new Response(json_encode([
      'success' => FALSE,
      'message' => (string) $this->t('@name is already checked in.', ['@name' => $event_attendee->getName()]),
    ]), 200, ['Content-Type' => 'application/json']);
  }

}



