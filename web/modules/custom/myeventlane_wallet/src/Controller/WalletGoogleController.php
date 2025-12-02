<?php

declare(strict_types=1);

namespace Drupal\myeventlane_wallet\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\myeventlane_wallet\Service\GoogleWalletBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for Google Wallet pass links.
 */
final class WalletGoogleController extends ControllerBase {

  /**
   * Constructs WalletGoogleController.
   */
  public function __construct(
    private readonly GoogleWalletBuilder $googleBuilder,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('myeventlane_wallet.google_wallet_builder'),
    );
  }

  /**
   * Returns a Google Wallet save link for an order item.
   *
   * @param string $order_item_id
   *   The order item ID.
   *
   * @return array
   *   A render array with the wallet link.
   */
  public function link(string $order_item_id): array {
    $item = OrderItem::load($order_item_id);
    if (!$item) {
      throw new NotFoundHttpException();
    }

    $url = $this->googleBuilder->generateSaveLink($item);

    return [
      '#type' => 'link',
      '#title' => $this->t('Add to Google Wallet'),
      '#url' => \Drupal\Core\Url::fromUri($url),
      '#attributes' => [
        'class' => ['mel-btn', 'mel-btn--google-wallet'],
        'target' => '_blank',
      ],
    ];
  }

}
