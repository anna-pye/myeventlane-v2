<?php

declare(strict_types=1);

namespace Drupal\myeventlane_wallet\Controller;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\myeventlane_wallet\Service\GoogleWalletBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('myeventlane_wallet.google_wallet_builder'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Ensures the current user can generate wallet links for the given order item.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  private function assertOrderItemWalletAccess(OrderItemInterface $order_item): void {
    $account = $this->currentUser();

    if (!$account->isAuthenticated()) {
      throw new AccessDeniedHttpException();
    }

    if ($account->hasPermission('administer myeventlane wallet') || $account->hasPermission('administer commerce_order')) {
      return;
    }

    $order = $order_item->getOrder();
    if (!$order || (int) $order->getCustomerId() !== (int) $account->id()) {
      throw new AccessDeniedHttpException();
    }
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
    if (!ctype_digit($order_item_id)) {
      throw new BadRequestHttpException('Invalid order item ID.');
    }

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface|null $item */
    $item = $this->entityTypeManager
      ->getStorage('commerce_order_item')
      ->load((int) $order_item_id);
    if (!$item) {
      throw new NotFoundHttpException();
    }

    $this->assertOrderItemWalletAccess($item);

    $url = $this->googleBuilder->generateSaveLink($item);

    $parts = parse_url($url) ?: [];
    $scheme = $parts['scheme'] ?? '';
    if (strtolower($scheme) !== 'https') {
      throw new BadRequestHttpException('Invalid wallet URL.');
    }

    return [
      '#type' => 'link',
      '#title' => $this->t('Add to Google Wallet'),
      '#url' => Url::fromUri($url),
      '#attributes' => [
        'class' => ['mel-btn', 'mel-btn--google-wallet'],
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      ],
    ];
  }

}
