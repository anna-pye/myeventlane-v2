<?php

declare(strict_types=1);

namespace Drupal\myeventlane_automation\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\myeventlane_automation\Service\AutomationDispatchService;
use Drupal\myeventlane_automation\Service\AutomationAuditLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for automation queue workers.
 */
abstract class AutomationWorkerBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs the worker.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly AutomationDispatchService $dispatchService,
    protected readonly AutomationAuditLogger $auditLogger,
    protected readonly LoggerInterface $logger,
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
      $container->get('myeventlane_automation.dispatch'),
      $container->get('myeventlane_automation.audit_logger'),
      $container->get('logger.factory')->get('myeventlane_automation'),
    );
  }

}
