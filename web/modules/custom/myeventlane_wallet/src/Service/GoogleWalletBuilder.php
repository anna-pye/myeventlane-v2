<?php

declare(strict_types=1);

namespace Drupal\myeventlane_wallet\Service;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Builds Google Wallet pass links.
 */
final class GoogleWalletBuilder {

  /**
   * Constructs GoogleWalletBuilder.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly RendererInterface $renderer,
  ) {}

  /**
   * Generates a Google Wallet save link for an order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $orderItem
   *   The order item.
   *
   * @return string
   *   The Google Wallet save URL.
   */
  public function generateSaveLink(OrderItemInterface $orderItem): string {
    // TODO: Implement actual Google Wallet JWT generation.
    return 'https://pay.google.com/gp/v/save/placeholder';
  }

}






