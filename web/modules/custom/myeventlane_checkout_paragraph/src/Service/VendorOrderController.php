<?php

declare(strict_types=1);

namespace Drupal\myeventlane_checkout_paragraph\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\myeventlane_checkout_paragraph\Service\TicketAttendeeRenderer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for vendor order attendee rendering.
 */
final class VendorOrderController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Renders attendees for an order.
   */
  public function __construct(
    private readonly TicketAttendeeRenderer $attendeeRenderer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('myeventlane_checkout_paragraph.attendee_renderer'),
    );
  }

  /**
   * Displays a simple attendee table for a commerce order.
   */
  public function attendees(OrderInterface $commerce_order): array {
    return [
      '#theme' => 'table',
      '#header' => [
        $this->t('First Name'),
        $this->t('Last Name'),
        $this->t('Email'),
      ],
      '#rows' => $this->attendeeRenderer->renderForOrder($commerce_order),
      '#cache' => ['max-age' => 0],
    ];
  }

}
