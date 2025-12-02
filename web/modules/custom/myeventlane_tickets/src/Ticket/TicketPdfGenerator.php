<?php

namespace Drupal\myeventlane_tickets\Ticket;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Dompdf\Dompdf;

/**
 * Generates ticket PDFs.
 */
class TicketPdfGenerator {

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  public function __construct(RendererInterface $renderer, ThemeHandlerInterface $theme_handler) {
    $this->renderer = $renderer;
    $this->themeHandler = $theme_handler;
  }

  /**
   * Build PDF output for a ticket.
   */
  public function buildPdf(string $ticket_code, $event, string $holder) : string {

    $render = [
      '#theme' => 'ticket_pdf',
      '#event' => $event,
      '#holder' => $holder,
      '#code'  => $ticket_code,
    ];

    $html = $this->renderer->renderInIsolation($render);

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->render();

    return $dompdf->output();
  }

}