<?php

declare(strict_types=1);

namespace Drupal\myeventlane_api\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\myeventlane_api\Service\ApiAuthenticationService;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for MyEventLane API.
 */
final class ApiCommands extends DrushCommands {

  /**
   * Constructs ApiCommands.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ApiAuthenticationService $authenticationService,
  ) {
    parent::__construct();
  }

  /**
   * Generates an API key for a vendor.
   *
   * @param int $vendor_id
   *   The vendor ID.
   *
   * @command myeventlane-api:generate-key
   * @aliases mel-api-key
   * @usage ddev drush mel-api-key 1
   *   Generate an API key for vendor ID 1.
   */
  public function generateApiKey(int $vendor_id): void {
    $storage = $this->entityTypeManager->getStorage('myeventlane_vendor');
    $vendor = $storage->load($vendor_id);

    if (!$vendor) {
      $this->logger()->error('Vendor @id not found.', ['@id' => $vendor_id]);
      return;
    }

    try {
      $api_key = $this->authenticationService->regenerateApiKey($vendor);
      $this->output()->writeln('');
      $this->output()->writeln('<info>✓ API key generated successfully!</info>');
      $this->output()->writeln('');
      $this->output()->writeln('<comment>Vendor:</comment> ' . $vendor->getName() . ' (ID: ' . $vendor->id() . ')');
      $this->output()->writeln('');
      $this->output()->writeln('<comment>API Key:</comment>');
      $this->output()->writeln('<info>' . $api_key . '</info>');
      $this->output()->writeln('');
      $this->output()->writeln('<comment>⚠️  IMPORTANT: Save this key securely. It will not be shown again.</comment>');
      $this->output()->writeln('');
      $this->output()->writeln('<comment>Usage:</comment>');
      $this->output()->writeln('  curl -H "Authorization: Bearer ' . $api_key . '" \\');
      $this->output()->writeln('    https://your-site.com/api/v1/vendor/events');
      $this->output()->writeln('');
    }
    catch (\Exception $e) {
      $this->logger()->error('Error generating API key: @message', ['@message' => $e->getMessage()]);
    }
  }

  /**
   * Lists all vendors with their API key status.
   *
   * @command myeventlane-api:list-vendors
   * @aliases mel-api-vendors
   * @usage ddev drush mel-api-vendors
   *   List all vendors and their API key status.
   */
  public function listVendors(): void {
    $storage = $this->entityTypeManager->getStorage('myeventlane_vendor');
    $vendors = $storage->loadMultiple();

    if (empty($vendors)) {
      $this->output()->writeln('No vendors found.');
      return;
    }

    $this->output()->writeln('');
    $this->output()->writeln(sprintf('%-10s %-30s %-15s', 'ID', 'Name', 'API Key'));
    $this->output()->writeln(str_repeat('-', 55));

    foreach ($vendors as $vendor) {
      $has_key = $vendor->getApiKeyHash() ? 'Yes' : 'No';
      $this->output()->writeln(sprintf('%-10s %-30s %-15s', $vendor->id(), substr($vendor->getName(), 0, 30), $has_key));
    }

    $this->output()->writeln('');
  }

}
