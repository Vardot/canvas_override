<?php

declare(strict_types=1);

namespace Drupal\canvas_override;

use Drupal\canvas\Plugin\Validation\Constraint\ComponentTreeMeetsRequirementsConstraintValidator;
use Drupal\canvas\Storage\ComponentTreeLoader;
use Drupal\canvas_override\Plugin\Validation\Constraint\CanvasOverrideConstraintValidator;
use Drupal\canvas_override\Storage\CanvasOverrideComponentTreeLoader;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Swaps canvas services with canvas_override versions.
 *
 * 1. Replaces ComponentTreeLoader with our subclass that allows per-node
 *    layouts. Requires the canvas module to have the final keyword removed from
 *    ComponentTreeLoader (patch: canvas-per-entity-canvas-layout-loader.patch).
 * 2. Decorates the ComponentTreeMeetsRequirements constraint validator to
 *    allow EntityField / HostEntityUrl prop sources on canvas_override nodes,
 *    eliminating the need to patch ComponentTreeItem.php.
 */
class CanvasOverrideServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container): void {
    $this->swapComponentTreeLoader($container);
    $this->decorateConstraintValidator($container);
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
    // Clear arguments: autowiring (inherited from canvas.services.yml _defaults)
    // resolves all three constructor parameters by type.
    $definition->setArguments([]);
  }

  /**
   * Wraps the constraint validator to allow field linking on per-node layouts.
   */
  private function decorateConstraintValidator(ContainerBuilder $container): void {
    if (!$container->hasDefinition(ComponentTreeMeetsRequirementsConstraintValidator::class)) {
      return;
    }

    // Clone the original validator definition for inner use.
    $innerDefinition = clone $container->getDefinition(ComponentTreeMeetsRequirementsConstraintValidator::class);
    $container->setDefinition('canvas_override.inner_constraint_validator', $innerDefinition);

    // Replace with our decorator.
    $definition = $container->getDefinition(ComponentTreeMeetsRequirementsConstraintValidator::class);
    $definition->setClass(CanvasOverrideConstraintValidator::class);
    $definition->setArguments([
      new Reference('canvas_override.inner_constraint_validator'),
      new Reference('entity_type.manager'),
    ]);
  }

}
