<?php

declare(strict_types=1);

namespace Drupal\myeventlane_tickets\Ticket;

use Drupal\Core\Render\RendererInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Dompdf\Dompdf;

/**
 * Generates PDF output for tickets.
 */
final class TicketPdfGenerator {

  public function __construct(
    private readonly RendererInterface $renderer,
  ) {}

  /**
   * LEGACY: Generate PDF for an order item.
   *
   * ⚠️ May represent multiple tickets.
   */
  public function generatePdfForOrderItem(OrderItemInterface $order_item): Response {
    // Get event from order item.
    $event = NULL;
    if ($order_item->hasField('field_target_event') && !$order_item->get('field_target_event')->isEmpty()) {
      $event = $order_item->get('field_target_event')->entity;
    }

    // Fallback: try to get event from product variation.
    if (!$event) {
      $purchasedEntity = $order_item->getPurchasedEntity();
      if ($purchasedEntity && $purchasedEntity->hasField('field_event') && !$purchasedEntity->get('field_event')->isEmpty()) {
        $event = $purchasedEntity->get('field_event')->entity;
      }
    }

    if (!$event) {
      throw new \LogicException('Event not found for order item.');
    }

    // Extract event data.
    $event_title = $event->label();
    $event_start = NULL;
    if ($event->hasField('field_event_start') && !$event->get('field_event_start')->isEmpty()) {
      $event_start = $event->get('field_event_start')->value;
    }

    $location = '';
    if ($event->hasField('field_location') && !$event->get('field_location')->isEmpty()) {
      $address_field = $event->get('field_location')->first();
      if ($address_field) {
        $address = $address_field->getValue();
        $parts = [];
        if (!empty($address['address_line1'])) {
          $parts[] = $address['address_line1'];
        }
        if (!empty($address['address_line2'])) {
          $parts[] = $address['address_line2'];
        }
        if (!empty($address['locality'])) {
          $parts[] = $address['locality'];
        }
        if (!empty($address['administrative_area'])) {
          $parts[] = $address['administrative_area'];
        }
        if (!empty($address['postal_code'])) {
          $parts[] = $address['postal_code'];
        }
        $location = implode(', ', $parts);
      }
    }

    // Get holder name from order item.
    $holderName = '';
    if ($order_item->hasField('field_ticket_holder') && !$order_item->get('field_ticket_holder')->isEmpty()) {
      $ticketHolders = $order_item->get('field_ticket_holder')->referencedEntities();
      if (!empty($ticketHolders)) {
        $holder = reset($ticketHolders);
        $firstName = $holder->get('field_first_name')->value ?? '';
        $lastName = $holder->get('field_last_name')->value ?? '';
        $holderName = trim($firstName . ' ' . $lastName);
      }
    }

    // Generate ticket code for legacy.
    $ticketCode = sprintf('MEL-%d-%d-%d-%s',
      $event->id(),
      $order_item->getOrder() ? $order_item->getOrder()->id() : 0,
      $order_item->id(),
      strtoupper(substr(md5(uniqid((string) mt_rand(), TRUE)), 0, 6))
    );

    $build = [
      '#theme' => 'ticket_pdf',
      '#order_item' => $order_item,
      '#event_title' => $event_title,
      '#event_start' => $event_start,
      '#location' => $location,
      '#holder' => $holderName,
      '#code' => $ticketCode,
      '#legacy' => TRUE,
    ];

    return $this->renderPdf($build, 'tickets-order-item-' . $order_item->id() . '.pdf');
  }

  /**
   * CANONICAL v2: Generate PDF for a single issued ticket.
   */
  public function generatePdfForTicket($ticket): Response {
    if (
      $ticket->get('holder_name')->isEmpty() ||
      $ticket->get('holder_email')->isEmpty()
    ) {
      throw new \LogicException('Ticket holder details must be assigned before PDF generation.');
    }

    $event = $ticket->get('event_id')->entity;
    if (!$event) {
      throw new \LogicException('Event not found for ticket.');
    }

    // Extract event data.
    $event_title = $event->label();
    $event_start = NULL;
    if ($event->hasField('field_event_start') && !$event->get('field_event_start')->isEmpty()) {
      $event_start = $event->get('field_event_start')->value;
    }

    $location = '';
    if ($event->hasField('field_location') && !$event->get('field_location')->isEmpty()) {
      $address_field = $event->get('field_location')->first();
      if ($address_field) {
        $address = $address_field->getValue();
        $parts = [];
        if (!empty($address['address_line1'])) {
          $parts[] = $address['address_line1'];
        }
        if (!empty($address['address_line2'])) {
          $parts[] = $address['address_line2'];
        }
        if (!empty($address['locality'])) {
          $parts[] = $address['locality'];
        }
        if (!empty($address['administrative_area'])) {
          $parts[] = $address['administrative_area'];
        }
        if (!empty($address['postal_code'])) {
          $parts[] = $address['postal_code'];
        }
        $location = implode(', ', $parts);
      }
    }

    $ticketCode = $ticket->get('ticket_code')->value;
    $holderName = $ticket->get('holder_name')->value;

    $build = [
      '#theme' => 'ticket_pdf',
      '#ticket' => $ticket,
      '#event_title' => $event_title,
      '#event_start' => $event_start,
      '#location' => $location,
      '#holder' => $holderName,
      '#code' => $ticketCode,
      '#legacy' => FALSE,
    ];

    return $this->renderPdf(
      $build,
      'ticket-' . $ticketCode . '.pdf'
    );
  }

  /**
   * Shared PDF renderer.
   */
  private function renderPdf(array $build, string $filename): Response {
    // Render in isolation (no theme switching needed).
    $html = $this->renderer->renderInIsolation($build);

    // Convert HTML to PDF using Dompdf.
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->render();
    $pdfContent = $dompdf->output();

    $response = new Response($pdfContent);
    $response->headers->set('Content-Type', 'application/pdf');
    $response->headers->set(
      'Content-Disposition',
      ResponseHeaderBag::DISPOSITION_ATTACHMENT . '; filename="' . $filename . '"'
    );

    return $response;
  }

}
