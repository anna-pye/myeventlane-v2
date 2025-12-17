<?php

declare(strict_types=1);

namespace Drupal\myeventlane_checkout_paragraph\Service;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Renders ticket holder data into table rows.
 */
final class TicketAttendeeRenderer {

  /**
   * Builds render arrays for ticket holders in an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order to render attendees from.
   *
   * @return array
   *   Render arrays for table rows.
   */
  public function renderForOrder(OrderInterface $order): array {
    $rows = [];

    foreach ($order->getItems() as $item) {
      if (!$item->hasField('field_ticket_holder')) {
        continue;
      }
      $holders = $item->get('field_ticket_holder')->referencedEntities();
      foreach ($holders as $holder) {
        if (!$holder instanceof ParagraphInterface) {
          continue;
        }

        $rows[] = [
          '#theme' => 'attendee_row',
          '#data' => [
            'first' => $holder->get('field_first_name')->value,
            'last' => $holder->get('field_last_name')->value,
            'email' => $holder->get('field_email')->value,
          ],
        ];
      }
    }

    return $rows;
  }

}
