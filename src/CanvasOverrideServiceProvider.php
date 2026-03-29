<?php

declare(strict_types=1);

namespace Drupal\canvas_override;

use Drupal\canvas\Storage\ComponentTreeLoader;
use Drupal\canvas_override\Storage\CanvasOverrideComponentTreeLoader;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Swaps ComponentTreeLoader with our subclass that allows per-node layouts.
 */
class CanvasOverrideServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    if ($container->hasDefinition(ComponentTreeLoader::class)) {
      $definition = $container->getDefinition(ComponentTreeLoader::class);
      $definition->setClass(CanvasOverrideComponentTreeLoader::class);
      // Clear existing arguments and let autowiring resolve all three.
      $definition->setArguments([]);
    }
  }

}
