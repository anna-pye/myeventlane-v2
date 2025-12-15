<?php

declare(strict_types=1);

namespace Drupal\myeventlane_wallet\Service;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\File\FileSystemInterface;

/**
 * Builds Apple Wallet .pkpass files.
 */
final class PkPassBuilder {

  /**
   * Constructs PkPassBuilder.
   */
  public function __construct(
    private readonly FileSystemInterface $fileSystem,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly WalletSigner $walletSigner,
  ) {}

  /**
   * Generates a .pkpass file for an order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $orderItem
   *   The order item.
   *
   * @return string
   *   Path to the generated .pkpass file.
   */
  public function generate(OrderItemInterface $orderItem): string {
    // @todo Implement actual pass generation.
    // For now, return a placeholder path.
    $tempDir = $this->fileSystem->getTempDirectory();
    $passPath = $tempDir . '/ticket_' . $orderItem->id() . '.pkpass';

    // Create placeholder file for now.
    file_put_contents($passPath, '');

    return $passPath;
  }

}
