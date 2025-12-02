<?php

declare(strict_types=1);

namespace Drupal\myeventlane_wallet\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\myeventlane_wallet\Service\PkPassBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for Apple Wallet pass downloads.
 */
final class WalletAppleController extends ControllerBase {

  /**
   * Constructs WalletAppleController.
   */
  public function __construct(
    private readonly PkPassBuilder $pkpassBuilder,
    private readonly FileSystemInterface $fileSystem,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('myeventlane_wallet.pkpass_builder'),
      $container->get('file_system'),
    );
  }

  /**
   * Downloads an Apple Wallet pass for an order item.
   *
   * @param string $order_item_id
   *   The order item ID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The pass file response.
   */
  public function download(string $order_item_id): Response {
    $item = OrderItem::load($order_item_id);
    if (!$item) {
      throw new NotFoundHttpException();
    }

    $passPath = $this->pkpassBuilder->generate($item);

    return new Response(
      file_get_contents($passPath),
      200,
      [
        'Content-Type' => 'application/vnd.apple.pkpass',
        'Content-Disposition' => 'attachment; filename="ticket.pkpass"',
      ]
    );
  }

}
