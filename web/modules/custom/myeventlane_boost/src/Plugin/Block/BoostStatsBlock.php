<?php

declare(strict_types=1);

namespace Drupal\myeventlane_boost\Plugin\Block;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block showing boost statistics.
 *
 * @Block(
 *   id = "myeventlane_boost_stats_block",
 *   admin_label = @Translation("Boost: Stats"),
 *   category = @Translation("MyEventLane")
 * )
 */
final class BoostStatsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a BoostStatsBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly TimeInterface $time,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $now = $this->time->getRequestTime();
    $nowIso = gmdate('Y-m-d\TH:i:s', $now);
    $soonIso = gmdate('Y-m-d\TH:i:s', $now + (48 * 3600));

    $nodeStorage = $this->entityTypeManager->getStorage('node');

    $active = $nodeStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'event')
      ->condition('status', 1)
      ->condition('field_promoted', 1)
      ->condition('field_promo_expires', $nowIso, '>')
      ->count()
      ->execute();

    $expiring = $nodeStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'event')
      ->condition('status', 1)
      ->condition('field_promoted', 1)
      ->condition('field_promo_expires', $nowIso, '>')
      ->condition('field_promo_expires', $soonIso, '<=')
      ->count()
      ->execute();

    return [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Active boosts: @n', ['@n' => $active]),
        $this->t('Expiring ≤ 48h: @n', ['@n' => $expiring]),
      ],
      '#cache' => [
        'max_age' => 300,
        'contexts' => ['user.permissions'],
        'tags' => ['node_list:event'],
      ],
    ];
  }

}
