<?php

declare(strict_types=1);

namespace Drupal\canvas_override;

use Drupal\canvas\Access\ComponentTreeEditAccessCheck;
use Drupal\canvas\Storage\ComponentTreeLoader;
use Drupal\canvas_override\Access\CanvasOverrideComponentTreeEditAccessCheck;
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
   * Container parameter recording whether the swap was applied at build time.
   */
  public const SWAPPED_PARAMETER = 'canvas_override.component_tree_loader_swapped';

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    $this->swapComponentTreeLoader($container);
    $this->hardenComponentTreeEditAccessCheck($container);
  }

  /**
   * Makes Canvas's component-tree edit access check fail safely.
   *
   * Canvas's ComponentTreeEditAccessCheck throws \LogicException (via the
   * loader) for entities Canvas cannot edit — every node bundle on an
   * unpatched Canvas — which surfaces as a 500 on the editor and canvas.api.*
   * routes. Retarget its service (consumed only through the access_check tag,
   * so nothing type-hints the concrete class) to a module check that composes
   * it and returns a cacheable 403 instead. Safe on patched Canvas too: the
   * injected loader is then the subclass and no exception is thrown.
   *
   * Unlike ComponentTreeLoader, ComponentTreeEditAccessCheck being final is not
   * a problem here — the module class composes it (its constructor is public)
   * rather than extending it, so the class always loads.
   *
   * @see \Drupal\canvas_override\Access\CanvasOverrideComponentTreeEditAccessCheck
   */
  private function hardenComponentTreeEditAccessCheck(ContainerBuilder $container): void {
    if (!$container->hasDefinition(ComponentTreeEditAccessCheck::class)) {
      return;
    }

    $definition = $container->getDefinition(ComponentTreeEditAccessCheck::class);
    $definition->setClass(CanvasOverrideComponentTreeEditAccessCheck::class);
    $definition->setArguments([]);
  }

  /**
   * Replaces ComponentTreeLoader with our subclass for per-node layouts.
   *
   * Skipped when Canvas ships ComponentTreeLoader as final. Subclassing a final
   * class is a PHP fatal raised the moment the subclass is loaded, which would
   * take the whole site down rather than disabling one feature -- so the swap
   * is the only place that may reference the subclass, and it must decide
   * without loading it. ::class is a compile-time constant and does not
   * autoload, and the reflection below reads Canvas's own class, so on an
   * unpatched Canvas our subclass is never loaded at all.
   *
   * @see \Drupal\canvas_override\Storage\CanvasOverrideComponentTreeLoader
   * @see https://www.drupal.org/i/3620603
   */
  private function swapComponentTreeLoader(ContainerBuilder $container): void {
    if (!$container->hasDefinition(ComponentTreeLoader::class)) {
      return;
    }

    if (!self::isComponentTreeLoaderExtendable()) {
      $container->setParameter(self::SWAPPED_PARAMETER, FALSE);
      return;
    }

    $definition = $container->getDefinition(ComponentTreeLoader::class);
    $definition->setClass(CanvasOverrideComponentTreeLoader::class);
    $definition->setArguments([]);
    $container->setParameter(self::SWAPPED_PARAMETER, TRUE);
  }

  /**
   * Checks whether Canvas allows ComponentTreeLoader to be extended.
   *
   * @return bool
   *   TRUE when the class exists and is not final.
   */
  public static function isComponentTreeLoaderExtendable(): bool {
    if (!class_exists(ComponentTreeLoader::class)) {
      return FALSE;
    }

    return !(new \ReflectionClass(ComponentTreeLoader::class))->isFinal();
  }

}
