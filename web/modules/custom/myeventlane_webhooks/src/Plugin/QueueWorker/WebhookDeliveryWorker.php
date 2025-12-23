<?php

declare(strict_types=1);

namespace Drupal\myeventlane_webhooks\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\myeventlane_webhooks\Service\WebhookDeliveryService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker for delivering webhooks.
 *
 * @QueueWorker(
 *   id = "myeventlane_webhook_delivery",
 *   title = @Translation("Webhook Delivery"),
 *   cron = {"time" = 60}
 * )
 */
final class WebhookDeliveryWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs WebhookDeliveryWorker.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly WebhookDeliveryService $deliveryService,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('myeventlane_webhooks.delivery_service'),
      $container->get('logger.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $logger = $this->loggerFactory->get('myeventlane_webhooks');

    try {
      $success = $this->deliveryService->deliverWebhook($data);
      if ($success) {
        $logger->info('Webhook delivered successfully. Delivery ID: @id', [
          '@id' => $data['delivery_id'] ?? 'unknown',
        ]);
      }
      else {
        $logger->warning('Webhook delivery failed. Delivery ID: @id', [
          '@id' => $data['delivery_id'] ?? 'unknown',
        ]);
      }
    }
    catch (\Exception $e) {
      $logger->error('Error delivering webhook: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

}
