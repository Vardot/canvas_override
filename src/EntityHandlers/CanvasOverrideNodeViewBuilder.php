<?php

declare(strict_types=1);

namespace Drupal\canvas_override\EntityHandlers;

use Drupal\canvas\AutoSave\AutoSaveManager;
use Drupal\canvas\EntityHandlers\ContentTemplateAwareViewBuilder;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Node view builder that supports per-node Canvas layout overrides.
 *
 * When a node has a populated field_canvas_layout, that per-node layout is
 * used for rendering instead of the shared ContentTemplate. This enables
 * Layout Builder-style per-node overrides: nodes without an override
 * automatically fall back to the shared ContentTemplate default.
 *
 * @see \Drupal\canvas\EntityHandlers\ContentTemplateAwareViewBuilder
 */
final class CanvasOverrideNodeViewBuilder extends ContentTemplateAwareViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode): void {
    $override_entities = [];
    $template_entities = [];

    foreach ($entities as $id => $entity) {
      if ($entity instanceof FieldableEntityInterface && $this->hasPerNodeOverride($entity)) {
        $override_entities[$id] = $entity;
      }
      else {
        $template_entities[$id] = $entity;
      }
    }

    // Process nodes using the shared ContentTemplate (existing parent logic).
    if (!empty($template_entities)) {
      parent::buildComponents($build, $template_entities, $displays, $view_mode);
    }

    // Process nodes with per-node layout overrides using field formatters.
    // Bypasses ContentTemplate so NaiveComponentTreeFormatter renders
    // field_canvas_layout directly.
    if (!empty($override_entities)) {
      $this->decorated->buildComponents($build, $override_entities, $displays, $view_mode);
      $is_preview = $this->isPreview();
      foreach ($override_entities as $id => $entity) {
        $build[$id]['#cache']['contexts'][] = 'route.name.is_canvas_editor_ui';
        if ($is_preview) {
          $build[$id]['#cache']['tags'][] = AutoSaveManager::CACHE_TAG;
        }
      }
    }
  }

  /**
   * Returns TRUE if the entity has a non-empty per-node canvas layout.
   */
  private function hasPerNodeOverride(FieldableEntityInterface $entity): bool {
    return $entity->hasField(\CANVAS_OVERRIDE_FIELD_NAME)
      && !$entity->get(\CANVAS_OVERRIDE_FIELD_NAME)->isEmpty();
  }

}
