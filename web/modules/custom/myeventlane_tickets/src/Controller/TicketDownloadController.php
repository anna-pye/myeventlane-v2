<?php

declare(strict_types=1);

namespace Drupal\myeventlane_tickets\Controller;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\myeventlane_tickets\Ticket\TicketPdfGenerator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for downloading ticket PDFs.
 */
final class TicketDownloadController extends ControllerBase {

  /**
   * The PDF generator.
   */
  protected TicketPdfGenerator $pdfGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = new static();
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->pdfGenerator = $container->get('myeventlane_tickets.pdf');
    return $instance;
  }

  /**
   * Ensures the current user can download for the given order item.
   *
   * This prevents insecure direct object reference (IDOR) issues where a user
   * could download tickets/passes by guessing order item IDs.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  private function assertOrderItemDownloadAccess(OrderItemInterface $order_item, AccountInterface $account): void {
    // Require login: these artifacts are tied to orders/attendees.
    if (!$account->isAuthenticated()) {
      throw new AccessDeniedHttpException();
    }

    // Allow privileged users.
    if ($account->hasPermission('administer myeventlane tickets') || $account->hasPermission('administer commerce_order')) {
      return;
    }

    $order = $order_item->getOrder();
    if (!$order) {
      throw new AccessDeniedHttpException();
    }

    // Only the purchasing customer can download their ticket.
    if ((int) $order->getCustomerId() !== (int) $account->id()) {
      throw new AccessDeniedHttpException();
    }
  }

  /**
   * Download a ticket PDF.
   *
   * @param string $order_item_id
   *   The order item ID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The PDF response.
   */
  public function download(string $order_item_id): Response {
    if (!ctype_digit($order_item_id)) {
      throw new NotFoundHttpException();
    }

    $order_item_id_int = (int) $order_item_id;
    // Load the order item.
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface|null $orderItem */
    $orderItem = $this->entityTypeManager
      ->getStorage('commerce_order_item')
      ->load($order_item_id_int);

    if (!$orderItem) {
      throw new NotFoundHttpException();
    }

    $this->assertOrderItemDownloadAccess($orderItem, $this->currentUser());

    // Get event from order item.
    $event = NULL;
    if ($orderItem->hasField('field_target_event') && !$orderItem->get('field_target_event')->isEmpty()) {
      $event = $orderItem->get('field_target_event')->entity;
    }

    // Fallback: try to get event from product variation.
    if (!$event) {
      $purchasedEntity = $orderItem->getPurchasedEntity();
      if ($purchasedEntity && $purchasedEntity->hasField('field_event') && !$purchasedEntity->get('field_event')->isEmpty()) {
        $event = $purchasedEntity->get('field_event')->entity;
      }
    }

    if (!$event) {
      throw new NotFoundHttpException();
    }

    // Try to find the associated event attendee record first.
    $attendeeStorage = $this->entityTypeManager->getStorage('event_attendee');
    $attendeeIds = $attendeeStorage->getQuery()
      // Access is verified above via order ownership. We bypass access checks
      // here because the attendee entity is typically accessible to event
      // owners/admins, while customers still need to download their ticket.
      ->accessCheck(FALSE)
      ->condition('order_item', $order_item_id_int)
      ->range(0, 1)
      ->execute();

    $ticketCode = '';
    $holderName = '';

    if (!empty($attendeeIds)) {
      /** @var \Drupal\myeventlane_event_attendees\Entity\EventAttendee $attendee */
      $attendee = $attendeeStorage->load(reset($attendeeIds));
      $ticketCode = $attendee->get('ticket_code')->value ?? '';
      $holderName = $attendee->get('name')->value ?? '';
    }

    // Fallback: get info from order item ticket holder paragraphs.
    if (empty($ticketCode) || empty($holderName)) {
      if ($orderItem->hasField('field_ticket_holder') && !$orderItem->get('field_ticket_holder')->isEmpty()) {
        $ticketHolders = $orderItem->get('field_ticket_holder')->referencedEntities();
        if (!empty($ticketHolders)) {
          $holder = reset($ticketHolders);
          $firstName = $holder->get('field_first_name')->value ?? '';
          $lastName = $holder->get('field_last_name')->value ?? '';
          $holderName = trim($firstName . ' ' . $lastName);
        }
      }

      // Generate ticket code if not found.
      if (empty($ticketCode)) {
        $order = $orderItem->getOrder();
        $ticketCode = sprintf('MEL-%d-%d-%d-%s',
          $event->id(),
          $order ? $order->id() : 0,
          $orderItem->id(),
          strtoupper(substr(md5(uniqid((string) mt_rand(), TRUE)), 0, 6))
        );
      }
    }

    // Generate PDF.
    $pdf_data = $this->pdfGenerator->buildPdf(
      $ticketCode,
      $event,
      $holderName
    );

    $response = new Response($pdf_data);
    $response->headers->set('Content-Type', 'application/pdf');
    $filename = $ticketCode ? "ticket-{$ticketCode}.pdf" : "ticket-{$order_item_id_int}.pdf";
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

    return $response;
  }

}
