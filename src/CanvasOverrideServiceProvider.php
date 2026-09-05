<?php

declare(strict_types=1);

namespace Drupal\canvas_override;

use Drupal\canvas\Storage\ComponentTreeLoader;
use Drupal\canvas_override\Storage\CanvasOverrideComponentTreeLoader;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Swaps canvas services with canvas_override versions.
 *
 * Replaces ComponentTreeLoader with our subclass that allows per-node layouts.
 * The constraint validator decoration is handled in canvas_override.services.yml.
 */
class CanvasOverrideServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    $this->swapComponentTreeLoader($container);
  }

  /**
   * Replaces ComponentTreeLoader with our subclass for per-node layouts.
   */
  private function swapComponentTreeLoader(ContainerBuilder $container): void {
    if (!$container->hasDefinition(ComponentTreeLoader::class)) {
      return;
    }

    $definition = $container->getDefinition(ComponentTreeLoader::class);
    $definition->setClass(CanvasOverrideComponentTreeLoader::class);
    $definition->setArguments([]);
  }

}
