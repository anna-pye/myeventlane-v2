<?php

declare(strict_types=1);

namespace Drupal\myeventlane_boost\Plugin\Block;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Boost CTA block for event pages.
 *
 * @Block(
 *   id = "myeventlane_boost_cta",
 *   admin_label = @Translation("Boost CTA (MyEventLane)"),
 *   category = @Translation("MyEventLane")
 * )
 */
final class BoostCtaBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a BoostCtaBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly RouteMatchInterface $routeMatch,
    private readonly AccountInterface $currentUser,
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
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $node = $this->routeMatch->getParameter('node');

    if (!$node instanceof NodeInterface || $node->bundle() !== 'event') {
      return [];
    }

    $isOwner = ((int) $node->getOwnerId() === (int) $this->currentUser->id());
    $canPurchase = $this->currentUser->hasPermission('purchase boost for events');
    $canAdmin = $node->access('update', $this->currentUser);

    if (!($isOwner || $canPurchase || $canAdmin)) {
      return [];
    }

    $promoted = (bool) $node->get('field_promoted')->value;
    $expiresVal = $node->get('field_promo_expires')->value ?? NULL;

    $expiresTs = 0;
    if ($expiresVal) {
      try {
        $expiresTs = (new \DateTimeImmutable($expiresVal, new \DateTimeZone('UTC')))->getTimestamp();
      }
      catch (\Exception) {
        // Invalid date format.
      }
    }

    $isBoosted = $promoted && ($expiresTs > $this->time->getRequestTime());

    $ctaLink = [];
    if (!$isBoosted) {
      $ctaUrl = Url::fromRoute('myeventlane_boost.boost_page', ['node' => $node->id()]);
      $ctaLink = Link::fromTextAndUrl($this->t('Boost your event'), $ctaUrl)->toRenderable();
      $ctaLink['#attributes']['class'][] = 'button';
      $ctaLink['#attributes']['class'][] = 'button--primary';
    }

    return [
      '#theme' => 'boost_cta',
      '#event' => $node,
      '#is_boosted' => $isBoosted,
      '#expires' => $expiresVal,
      '#cta' => $ctaLink,
      '#attached' => ['library' => ['myeventlane_boost/boost']],
      '#cache' => [
        'max_age' => 0,
        'contexts' => ['user', 'route'],
        'tags' => $node->getCacheTags(),
      ],
    ];
  }

}
