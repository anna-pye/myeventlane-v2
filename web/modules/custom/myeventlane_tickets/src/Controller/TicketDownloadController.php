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
    $instance->pdfGenerator = $container->get('myeventlane_tickets.pdf_generator');
    return $instance;
  }

  /**
   * Download a ticket PDF.
   */
  public function download(int $ticket_id): Response {

    $ticket = $this->entityTypeManager
      ->getStorage('myeventlane_ticket')
      ->load($ticket_id);

    if (!$ticket) {
      return new Response('Ticket not found', 404);
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $ticket */

    // FIXED: remove all ->get() calls on the entity.
    $event = $ticket->get('event_id')->entity ?? NULL;
    $ticket_code = $ticket->get('ticket_code')->value ?? '';
    $holder = $ticket->get('holder_name')->value ?? '';

    $pdf_data = $this->pdfGenerator->buildPdf(
      $ticket_code,
      $event,
      $holder
    );

    $response = new Response($pdf_data);
    $response->headers->set('Content-Type', 'application/pdf');
    $response->headers->set('Content-Disposition', 'attachment; filename="ticket.pdf"');

    return $response;
  }

}