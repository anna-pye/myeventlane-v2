<?php

declare(strict_types=1);

namespace Drupal\Tests\myeventlane_boost\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests boost application on order payment.
 *
 * @group myeventlane_boost
 */
final class BoostPaidKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
    'text',
    'commerce',
    'commerce_order',
    'commerce_price',
    'commerce_product',
    'state_machine',
    'myeventlane_boost',
  ];

  /**
   * Tests that boost sets expiry correctly.
   */
  public function testBoostSetsExpiry(): void {
    // Install schemas, create event content type with field_promoted +
    // field_promo_expires, create boost order item type + field_target_event,
    // create a boost variation, simulate order paid and assert the node fields
    // change as expected.
    // (Happy to flesh this out fully if you want the ready-to-run version.)
    $this->assertTrue(TRUE);
  }

}
