<?php

declare(strict_types=1);

namespace Drupal\myeventlane_demo\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\myeventlane_demo\Service\DemoDataManager;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush commands for seeding and cleaning up demo events.
 */
final class DemoCommands extends DrushCommands {

  /**
   * Constructs DemoCommands.
   */
  public function __construct(
    private readonly DemoDataManager $demoDataManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('myeventlane_demo.data_manager'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Seeds demo events for testing all booking modes.
   *
   * Creates a set of demo events covering:
   * - RSVP-only events
   * - Tickets-only events (with Commerce products)
   * - Combined RSVP + Tickets events
   * - Coming soon events (no booking configured)
   * - Past events (to test expired behaviour)
   *
   * All demo content is prefixed with "[DEMO]" for easy identification.
   */
  #[CLI\Command(name: 'myeventlane_demo:seed', aliases: ['mel-demo-seed'])]
  #[CLI\Usage(name: 'drush myeventlane_demo:seed', description: 'Create all demo events')]
  public function seedEvents(): void {
    // Check if store exists.
    $store = $this->demoDataManager->getStore();
    if (!$store) {
      $this->logger()->error('No Commerce store found. Please create a store first.');
      $this->io()->error('No Commerce store exists. Create a store first.');
      $this->io()->text('Create at: /admin/commerce/config/stores/add');
      return;
    }

    // Check if demo data already exists.
    if ($this->demoDataManager->demoDataExists()) {
      $msg = 'Demo events already exist. Delete and create fresh?';
      if (!$this->io()->confirm($msg, FALSE)) {
        $this->io()->text('Aborted. Run "drush mel-demo-cleanup" first.');
        return;
      }
      $this->demoDataManager->cleanup();
      $this->io()->success('Existing demo data cleaned up.');
    }

    $this->io()->title('Seeding Demo Events');
    $this->io()->text('Using store: ' . $store->getName());
    $this->io()->newLine();

    try {
      $results = $this->demoDataManager->seedEvents();

      $this->io()->success(sprintf(
        'Created %d demo events and %d demo products.',
        count($results['events']),
        count($results['products'])
      ));

      // Display created events.
      $this->io()->section('Created Events');
      $rows = [];
      $nodeStorage = $this->entityTypeManager->getStorage('node');
      foreach ($results['events'] as $eventId) {
        $node = $nodeStorage->load($eventId);
        if ($node) {
          $mode = $node->get('field_event_type')->value ?: '(none)';
          $rows[] = [$eventId, $node->getTitle(), $mode];
        }
      }
      $this->io()->table(['ID', 'Title', 'Mode'], $rows);

      // Display created products.
      if (!empty($results['products'])) {
        $this->io()->section('Created Products');
        $productRows = [];
        $productStorage = $this->entityTypeManager->getStorage('commerce_product');
        foreach ($results['products'] as $productId) {
          $product = $productStorage->load($productId);
          if ($product) {
            $variationCount = count($product->getVariations());
            $productRows[] = [$productId, $product->getTitle(), $variationCount];
          }
        }
        $this->io()->table(['ID', 'Title', 'Variations'], $productRows);
      }

      $this->io()->newLine();
      $this->io()->text('View events at: /admin/content?type=event');
      $this->io()->text('To clean up: drush myeventlane_demo:cleanup');

    }
    catch (\Exception $e) {
      $this->logger()->error('Failed: @message', ['@message' => $e->getMessage()]);
      $this->io()->error('Failed to seed demo events: ' . $e->getMessage());
    }
  }

  /**
   * Removes all demo events and associated products.
   *
   * Identifies demo content by the "[DEMO]" title prefix and deletes:
   * - All demo event nodes
   * - All Commerce products linked to demo events (if prefixed [DEMO])
   *
   * This command is safe and will not affect non-demo content.
   */
  #[CLI\Command(name: 'myeventlane_demo:cleanup', aliases: ['mel-demo-cleanup'])]
  #[CLI\Usage(name: 'drush myeventlane_demo:cleanup', description: 'Delete all demo data')]
  #[CLI\Option(name: 'force', description: 'Skip confirmation prompt')]
  public function cleanup(array $options = ['force' => FALSE]): void {
    if (!$this->demoDataManager->demoDataExists()) {
      $this->io()->success('No demo data found. Nothing to clean up.');
      return;
    }

    if (!$options['force']) {
      $msg = 'Delete all demo events and products (prefixed "[DEMO]")?';
      if (!$this->io()->confirm($msg, FALSE)) {
        $this->io()->text('Aborted.');
        return;
      }
    }

    $this->io()->title('Cleaning Up Demo Data');

    try {
      $results = $this->demoDataManager->cleanup();

      $this->io()->success(sprintf(
        'Deleted %d demo events and %d demo products.',
        $results['events_deleted'],
        $results['products_deleted']
      ));

    }
    catch (\Exception $e) {
      $this->logger()->error('Failed: @message', ['@message' => $e->getMessage()]);
      $this->io()->error('Failed to clean up demo data: ' . $e->getMessage());
    }
  }

  /**
   * Shows the status of demo data.
   *
   * Lists all existing demo events and their booking modes.
   */
  #[CLI\Command(name: 'myeventlane_demo:status', aliases: ['mel-demo-status'])]
  #[CLI\Usage(name: 'drush myeventlane_demo:status', description: 'Show demo data status')]
  public function status(): void {
    $this->io()->title('Demo Data Status');

    $store = $this->demoDataManager->getStore();
    if ($store) {
      $storeInfo = $store->getName() . ' (ID: ' . $store->id() . ')';
      $this->io()->text('Commerce store: ' . $storeInfo);
    }
    else {
      $this->io()->warning('No Commerce store found.');
    }

    $this->io()->newLine();

    if (!$this->demoDataManager->demoDataExists()) {
      $this->io()->text('No demo events found.');
      $this->io()->text('Run "drush myeventlane_demo:seed" to create demo data.');
      return;
    }

    // Find and list demo events.
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $eventIds = $nodeStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'event')
      ->condition('title', DemoDataManager::DEMO_PREFIX, 'STARTS_WITH')
      ->execute();

    $events = $nodeStorage->loadMultiple($eventIds);

    $rows = [];
    foreach ($events as $event) {
      $mode = $event->get('field_event_type')->value ?: '(none)';
      $start = $event->get('field_event_start')->value ?? 'N/A';
      $hasProduct = !$event->get('field_product_target')->isEmpty() ? 'Yes' : 'No';
      $rows[] = [
        $event->id(),
        $event->getTitle(),
        $mode,
        $start,
        $hasProduct,
      ];
    }

    $this->io()->table(
      ['ID', 'Title', 'Mode', 'Start Date', 'Has Product'],
      $rows
    );
    $this->io()->text(sprintf('Total: %d demo events', count($events)));
  }

}
