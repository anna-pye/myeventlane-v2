<?php

namespace Drupal\myeventlane_theme\Service;

use Drupal\Core\Extension\ExtensionList;

/**
 * Resolves Vite-built asset paths for the theme.
 */
class AssetResolver {

  protected ExtensionList $themeList;

  public function __construct(ExtensionList $theme_list) {
    $this->themeList = $theme_list;
  }

  /**
   * Returns full path to Vite manifest.json.
   */
  public function getManifestPath(): ?string {
    $path = $this->themeList->getPath('myeventlane_theme');
    $manifest = DRUPAL_ROOT . '/' . $path . '/dist/.vite/manifest.json';

    return file_exists($manifest) ? $manifest : NULL;
  }
}