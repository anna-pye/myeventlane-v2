<?php

declare(strict_types=1);

namespace Drupal\Tests\myeventlane_boost\Unit;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\myeventlane_boost\EventSubscriber\BoostRefundSubscriber;
use Drupal\node\NodeInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\state_machine\Plugin\Workflow\WorkflowInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests the BoostRefundSubscriber.
 *
 * @group myeventlane_boost
 * @coversDefaultClass \Drupal\myeventlane_boost\EventSubscriber\BoostRefundSubscriber
 */
final class BoostRefundSubscriberTest extends TestCase {

  /**
   * Tests that refund revokes boost from targeted event.
   */
  public function testRefundRevokesBoost(): void {
    $setCalls = [];

    // Create mock node.
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('event');
    $node->expects($this->exactly(2))
      ->method('set')
      ->willReturnCallback(function ($field, $value) use (&$setCalls, $node) {
        $setCalls[] = [$field, $value];
        return $node;
      });
    $node->expects($this->once())->method('save');

    // Create mock node storage.
    $nodeStorage = $this->createMock(EntityStorageInterface::class);
    $nodeStorage->method('load')->with(123)->willReturn($node);

    // Create mock entity type manager.
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->with('node')->willReturn($nodeStorage);

    // Create mock logger.
    $logger = $this->createMock(LoggerInterface::class);

    // Create subscriber.
    $subscriber = new BoostRefundSubscriber($entityTypeManager, $logger);

    // Create mock field item list for target event.
    $fieldTargetEvent = $this->createMock(FieldItemListInterface::class);
    $fieldTargetEvent->target_id = 123;

    // Create mock order item.
    $item = $this->createMock(OrderItemInterface::class);
    $item->method('bundle')->willReturn('boost');
    $item->method('hasField')->with('field_target_event')->willReturn(TRUE);
    $item->method('get')->with('field_target_event')->willReturn($fieldTargetEvent);

    // Create mock order.
    $order = $this->createMock(OrderInterface::class);
    $order->method('getItems')->willReturn([$item]);
    $order->method('id')->willReturn(99);

    // Create mock payment.
    $payment = $this->createMock(PaymentInterface::class);
    $payment->method('getOrder')->willReturn($order);
    $payment->method('id')->willReturn(77);

    // Create workflow transition event.
    $transition = $this->createMock(WorkflowTransition::class);
    $transition->method('getId')->willReturn('refund');
    $workflow = $this->createMock(WorkflowInterface::class);

    $event = new WorkflowTransitionEvent($transition, $workflow, $payment, 'payment_state');

    // Execute.
    $subscriber->onRefundOrVoid($event);

    // Assert boost was revoked.
    $this->assertContains(['field_promoted', 0], $setCalls);
    $this->assertContains(['field_promo_expires', NULL], $setCalls);
  }

}
