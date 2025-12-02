<?php

declare(strict_types=1);

namespace Drupal\myeventlane_admin_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Provides the admin overview dashboard page.
 */
final class AdminDashboardController extends ControllerBase {

  /**
   * Returns the admin overview page.
   *
   * @return array
   *   A render array for the admin dashboard.
   */
  public function overview(): array {
    return [
      '#theme' => 'admin_dashboard',
      '#event_count' => $this->getEventCount(),
      '#vendor_count' => $this->getVendorCount(),
      '#user_count' => $this->getUserCount(),
      '#recent_events' => $this->getRecentEvents(),
      '#quick_links' => $this->getQuickLinks(),
      '#attached' => [
        'library' => [
          'myeventlane_admin_dashboard/admin_dashboard',
        ],
      ],
      '#cache' => [
        'tags' => ['node_list', 'user_list', 'myeventlane_vendor_list'],
        'contexts' => ['user.permissions'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Gets the total count of published events.
   *
   * @return int
   *   The event count.
   */
  protected function getEventCount(): int {
    try {
      $count = $this->entityTypeManager()
        ->getStorage('node')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'event')
        ->condition('status', 1)
        ->count()
        ->execute();
      return (int) $count;
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Gets the total count of vendors.
   *
   * @return int
   *   The vendor count.
   */
  protected function getVendorCount(): int {
    try {
      $entity_type_manager = $this->entityTypeManager();
      if (!$entity_type_manager->hasDefinition('myeventlane_vendor')) {
        return 0;
      }
      $count = $entity_type_manager
        ->getStorage('myeventlane_vendor')
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();
      return (int) $count;
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Gets the total count of active users.
   *
   * @return int
   *   The user count.
   */
  protected function getUserCount(): int {
    try {
      $count = $this->entityTypeManager()
        ->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->condition('uid', 0, '>')
        ->count()
        ->execute();
      return (int) $count;
    }
    catch (\Exception $e) {
      return 0;
    }
  }

  /**
   * Gets recent events for the dashboard.
   *
   * @return array
   *   An array of recent event data.
   */
  protected function getRecentEvents(): array {
    try {
      $entity_type_manager = $this->entityTypeManager();
      $nids = $entity_type_manager
        ->getStorage('node')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'event')
        ->condition('status', 1)
        ->sort('created', 'DESC')
        ->range(0, 5)
        ->execute();

      if (empty($nids)) {
        return [];
      }

      $events = [];
      $nodes = $entity_type_manager->getStorage('node')->loadMultiple($nids);
      foreach ($nodes as $node) {
        $events[] = [
          'title' => $node->label(),
          'url' => $node->toUrl()->toString(),
          'created' => $node->getCreatedTime(),
        ];
      }
      return $events;
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Gets quick links for the admin dashboard.
   *
   * @return array
   *   An array of quick link data.
   */
  protected function getQuickLinks(): array {
    $links = [];

    // Content management.
    $links[] = [
      'title' => $this->t('Manage Events'),
      'url' => Url::fromRoute('system.admin_content')->toString(),
      'description' => $this->t('View and edit all content.'),
      'icon' => 'event',
    ];

    // Vendors.
    if ($this->entityTypeManager()->hasDefinition('myeventlane_vendor')) {
      $links[] = [
        'title' => $this->t('Manage Vendors'),
        'url' => Url::fromRoute('myeventlane_vendor.collection')->toString(),
        'description' => $this->t('View and manage vendor accounts.'),
        'icon' => 'vendor',
      ];
    }

    // Users.
    $links[] = [
      'title' => $this->t('Manage Users'),
      'url' => Url::fromRoute('entity.user.collection')->toString(),
      'description' => $this->t('View and manage user accounts.'),
      'icon' => 'user',
    ];

    // Reports.
    $links[] = [
      'title' => $this->t('Reports'),
      'url' => Url::fromRoute('system.admin_reports')->toString(),
      'description' => $this->t('View site reports and logs.'),
      'icon' => 'report',
    ];

    return $links;
  }

}
