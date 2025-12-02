<?php

declare(strict_types=1);

namespace Drupal\myeventlane_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\myeventlane_dashboard\Service\DashboardAccess;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for the admin dashboard.
 */
final class AdminDashboardController extends ControllerBase {

  /**
   * The dashboard access service.
   *
   * @var \Drupal\myeventlane_dashboard\Service\DashboardAccess
   */
  protected DashboardAccess $dashboardAccess;

  /**
   * Constructs an AdminDashboardController object.
   *
   * @param \Drupal\myeventlane_dashboard\Service\DashboardAccess $dashboard_access
   *   The dashboard access service.
   */
  public function __construct(DashboardAccess $dashboard_access) {
    $this->dashboardAccess = $dashboard_access;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('myeventlane_dashboard.access'),
    );
  }

  /**
   * Renders the admin dashboard page.
   *
   * @return array
   *   A render array for the admin dashboard.
   */
  public function dashboard(): array {
    $is_viewing_as_vendor = !$this->dashboardAccess->isAdminView();

    return [
      '#theme' => 'myeventlane_dashboard',
      '#is_admin' => TRUE,
      '#is_admin_viewing_as_vendor' => $is_viewing_as_vendor,
      '#attached' => [
        'library' => ['myeventlane_dashboard/dashboard'],
      ],
      '#cache' => [
        'contexts' => ['user.permissions', 'session'],
        'tags' => ['node_list', 'user_list', 'myeventlane_vendor_list'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Toggles between admin and vendor view modes.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response to the admin dashboard.
   */
  public function toggleView(): RedirectResponse {
    $this->dashboardAccess->toggle();
    return $this->redirect('myeventlane_dashboard.admin');
  }

}
