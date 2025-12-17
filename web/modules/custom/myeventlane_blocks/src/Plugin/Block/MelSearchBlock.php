<?php

declare(strict_types=1);

namespace Drupal\myeventlane_blocks\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the MEL search UI block.
 *
 * @Block(
 *   id = "myeventlane_search_block",
 *   admin_label = @Translation("MyEventLane: Search"),
 *   category = @Translation("MyEventLane")
 * )
 */
final class MelSearchBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#theme' => 'myeventlane_search_block',
      '#cache' => [
        'max-age' => 3600,
      ],
    ];
  }

}
