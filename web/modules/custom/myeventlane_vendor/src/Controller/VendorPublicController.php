<?php

declare(strict_types=1);

namespace Drupal\myeventlane_vendor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\myeventlane_vendor\Entity\Vendor;

/**
 * Public controller for Vendor directory.
 */
class VendorPublicController extends ControllerBase {

  /**
   * Public organiser list.
   *
   * @return array
   *   A render array for the vendor list.
   */
  public function list(): array {
    $storage = $this->entityTypeManager()->getStorage('myeventlane_vendor');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('name')
      ->execute();

    $vendors = $ids ? $storage->loadMultiple($ids) : [];

    return [
      '#theme' => 'myeventlane_vendor_list',
      '#vendors' => $vendors,
      '#cache' => [
        'tags' => ['myeventlane_vendor_list'],
      ],
    ];
  }

}

/**
 * Title callback for vendor canonical pages.
 */
class VendorTitleController extends ControllerBase {

  /**
   * Returns the page title for a vendor.
   *
   * @param \Drupal\myeventlane_vendor\Entity\Vendor $myeventlane_vendor
   *   The vendor entity.
   *
   * @return string|null
   *   The vendor label as page title.
   */
  public function title(Vendor $myeventlane_vendor): ?string {
    return $myeventlane_vendor->label();
  }

}
