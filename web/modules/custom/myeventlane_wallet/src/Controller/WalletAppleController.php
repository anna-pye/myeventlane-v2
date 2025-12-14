<?php

declare(strict_types=1);

namespace Drupal\myeventlane_wallet\Controller;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\myeventlane_wallet\Service\PkPassBuilder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('myeventlane_wallet.pkpass_builder'),
      $container->get('file_system'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Ensures the current user can download for the given order item.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  private function assertOrderItemDownloadAccess(OrderItemInterface $order_item): void {
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
   * Downloads an Apple Wallet pass for an order item.
   *
   * @param string $order_item_id
   *   The order item ID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The pass file response.
   */
  public function download(string $order_item_id): Response {
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

    $this->assertOrderItemDownloadAccess($item);

    $passPath = $this->pkpassBuilder->generate($item);
    $realPath = $this->fileSystem->realpath($passPath) ?: $passPath;

    if (!is_file($realPath) || !is_readable($realPath)) {
      throw new NotFoundHttpException();
    }

    $response = new BinaryFileResponse($realPath);
    $response->headers->set('Content-Type', 'application/vnd.apple.pkpass');
    $response->setContentDisposition('attachment', 'ticket.pkpass');
    // Passes are typically generated in a temp directory.
    $response->deleteFileAfterSend(TRUE);

    return $response;
  }

}
