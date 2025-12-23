<?php

declare(strict_types=1);

namespace Drupal\myeventlane_automation\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\node\NodeInterface;

/**
 * Helper service for queuing export-ready notifications.
 */
final class ExportNotificationHelper {

  /**
   * Constructs the helper.
   */
  public function __construct(
    private readonly AutomationDispatchService $dispatchService,
    private readonly QueueFactory $queueFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly TimeInterface $time,
  ) {}

  /**
   * Queues an export-ready notification for a vendor.
   *
   * @param \Drupal\node\NodeInterface $event
   *   The event node.
   * @param string $exportType
   *   Export type: 'csv' or 'ics'.
   * @param string $fileUrl
   *   Download URL for the export file.
   *
   * @return bool
   *   TRUE if queued successfully.
   */
  public function queueExportNotification(NodeInterface $event, string $exportType, string $fileUrl): bool {
    try {
      $eventId = (int) $event->id();
      $vendor = $this->entityTypeManager->getStorage('user')->load($event->getOwnerId());
      if (!$vendor || !$vendor->getEmail()) {
        return FALSE;
      }

      $vendorEmail = $vendor->getEmail();
      $vendorHash = $this->dispatchService->hashRecipient($vendorEmail);

      $notificationType = $exportType === 'csv'
        ? AutomationDispatchService::TYPE_EXPORT_READY_CSV
        : AutomationDispatchService::TYPE_EXPORT_READY_ICS;

      // Check idempotency.
      if ($this->dispatchService->isAlreadySent($eventId, $notificationType, $vendorHash)) {
        return FALSE;
      }

      $now = $this->time->getRequestTime();
      $dispatchId = $this->dispatchService->createDispatch(
        $eventId,
        $notificationType,
        $vendorHash,
        $now,
        ['export_type' => $exportType, 'file_url' => $fileUrl]
      );

      $queue = $this->queueFactory->get('automation_export_ready');
      $queue->createItem([
        'dispatch_id' => $dispatchId,
        'event_id' => $eventId,
        'export_type' => $exportType,
        'file_url' => $fileUrl,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      \Drupal::logger('myeventlane_automation')->error('Failed to queue export notification: @message', [
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

}
