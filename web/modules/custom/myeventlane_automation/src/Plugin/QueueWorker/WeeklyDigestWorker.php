<?php

declare(strict_types=1);

namespace Drupal\myeventlane_automation\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\myeventlane_automation\Service\AutomationDispatchService;
use Drupal\myeventlane_automation\Service\AutomationAuditLogger;
use Drupal\myeventlane_messaging\Service\MessagingManager;
use Drupal\myeventlane_core\Service\CategoryDigestGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Queue worker for weekly category digest notifications.
 *
 * @QueueWorker(
 *   id = "automation_weekly_digest",
 *   title = @Translation("Weekly category digest notification worker"),
 *   cron = {"time" = 300}
 * )
 */
final class WeeklyDigestWorker extends AutomationWorkerBase {

  /**
   * Constructs the worker.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AutomationDispatchService $dispatchService,
    AutomationAuditLogger $auditLogger,
    LoggerInterface $logger,
    protected readonly MessagingManager $messagingManager,
    protected readonly CategoryDigestGenerator $digestGenerator,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $dispatchService, $auditLogger, $logger);
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
      $container->get('myeventlane_messaging.manager'),
      $container->get('myeventlane_core.category_digest_generator'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $dispatchId = $data['dispatch_id'] ?? NULL;
    $userId = $data['user_id'] ?? NULL;

    if (!$dispatchId || !$userId) {
      $this->logger->error('WeeklyDigestWorker: Missing required data');
      return;
    }

    $user = $this->entityTypeManager->getStorage('user')->load($userId);
    if (!$user || !$user->isActive() || !$user->getEmail()) {
      $this->dispatchService->markFailed($dispatchId, 'User not found or inactive');
      return;
    }

    $email = $user->getEmail();

    // Check idempotency.
    $recipientHash = $this->dispatchService->hashRecipient($email);
    if ($this->dispatchService->isAlreadySent(NULL, AutomationDispatchService::TYPE_WEEKLY_CATEGORY_DIGEST, $recipientHash)) {
      $this->dispatchService->markSkipped($dispatchId, 'Already sent this week');
      return;
    }

    // Use CategoryDigestGenerator to send digest.
    try {
      $this->digestGenerator->sendDigest($user);
      $this->dispatchService->markSent($dispatchId);
      $this->auditLogger->log(
        NULL,
        'notification_sent',
        AutomationDispatchService::TYPE_WEEKLY_CATEGORY_DIGEST,
        $recipientHash
      );
      $this->logger->info('Sent weekly category digest to user @id', ['@id' => $userId]);
    }
    catch (\Exception $e) {
      $this->dispatchService->markFailed($dispatchId, $e->getMessage());
      $this->logger->error('Failed to send weekly digest: @message', ['@message' => $e->getMessage()]);
    }
  }

}
