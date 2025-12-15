<?php

declare(strict_types=1);

namespace Drupal\myeventlane_wallet\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Signs Apple Wallet passes.
 */
final class WalletSigner {

  /**
   * Constructs WalletSigner.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Signs a pass manifest.
   *
   * @param string $manifestPath
   *   Path to the manifest.json file.
   *
   * @return string
   *   The signature.
   */
  public function sign(string $manifestPath): string {
    // @todo Implement actual signing with Apple certificates.
    return '';
  }

}
