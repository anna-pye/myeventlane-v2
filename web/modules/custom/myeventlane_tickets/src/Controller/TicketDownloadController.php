<?php

namespace Drupal\myeventlane_tickets\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for downloading ticket PDFs.
 */
class TicketDownloadController extends ControllerBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\myeventlane_tickets\Ticket\TicketPdfGenerator
   */
  protected $pdfGenerator;

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
   * Download a ticket PDF.
   *
   * @param int $order_item_id
   *   The order item ID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The PDF response.
   */
  public function download(int $order_item_id): Response {
    // Load the order item.
    $orderItem = $this->entityTypeManager
      ->getStorage('commerce_order_item')
      ->load($order_item_id);

    if (!$orderItem) {
      return new Response('Order item not found', 404);
    }

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
      return new Response('Event not found', 404);
    }

    // Try to find the associated event attendee record first.
    $attendeeStorage = $this->entityTypeManager->getStorage('event_attendee');
    $attendeeIds = $attendeeStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('order_item', $order_item_id)
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
    $filename = $ticketCode ? "ticket-{$ticketCode}.pdf" : "ticket-{$order_item_id}.pdf";
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

    return $response;
  }

}