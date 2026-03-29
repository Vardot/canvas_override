<?php

declare(strict_types=1);

namespace Drupal\canvas_override\Storage;

use Drupal\canvas\Entity\ContentTemplate;
use Drupal\canvas\Entity\ComponentTreeEntityInterface;
use Drupal\canvas\Plugin\Field\FieldType\ComponentTreeItem;
use Drupal\canvas\Plugin\Field\FieldType\ComponentTreeItemList;
use Drupal\canvas\Storage\ComponentTreeLoader;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\node\NodeInterface;

/**
 * Extends ComponentTreeLoader to allow per-node Canvas layouts.
 *
 * Bypasses the upstream entity type restriction for any node bundle that has
 * canvas_override enabled via third-party settings.
 *
 * When a per-entity canvas field is empty (first-time editor open), copies
 * the default ContentTemplate components and saves them directly to the node
 * field so the template layout becomes the persistent starting point for
 * per-entity customisation.
 */
class CanvasOverrideComponentTreeLoader extends ComponentTreeLoader {

  public function __construct(
    EntityFieldManagerInterface $entityFieldManager,
    ModuleHandlerInterface $moduleHandler,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($entityFieldManager, $moduleHandler);
  }

  /**
   * {@inheritdoc}
   *
   * When loading a per-entity canvas tree that is empty (first editor open),
   * copies the default ContentTemplate components and saves them to the node
   * so the editor immediately shows — and the user can customise — the
   * shared default layout as their starting point.
   */
  public function load(ComponentTreeEntityInterface|FieldableEntityInterface $entity): ComponentTreeItemList {
    if ($entity instanceof ComponentTreeEntityInterface) {
      return $entity->getComponentTree();
    }

    $field_name = $this->getCanvasFieldName($entity);
    $item = $entity->get($field_name);
    \assert($item instanceof ComponentTreeItemList);

    // Seed from ContentTemplate only when the per-entity field is truly empty.
    // Copy the template component values into $item (the node's own field list)
    // rather than returning a dangling ComponentTreeItemList from
    // ContentTemplate::getComponentTree(). Returning a dangling list causes a
    // 500 error: content_moderation's entityFieldAccess hook calls getEntity()
    // on the list, gets a config-entity adapter whose getEntityTypeId() returns
    // "" and EntityTypeManager throws "The "" entity type does not exist."
    if ($item->isEmpty() && $entity instanceof NodeInterface) {
      $template = ContentTemplate::loadForEntity($entity, 'full');
      if ($template) {
        $template_tree = $template->getComponentTree($entity);
        if (!$template_tree->isEmpty()) {
          // setValue() with the raw component array seeds the node's own list
          // in-memory without saving — the editor persists it on user save.
          $item->setValue($template_tree->getValue());
        }
      }
    }

    return $item;
  }

  /**
   * {@inheritdoc}
   */
  public function getCanvasFieldName(FieldableEntityInterface $entity): string {
    // For nodes with canvas_override enabled, bypass the upstream restriction.
    if ($entity instanceof NodeInterface) {
      $node_type = $this->entityTypeManager->getStorage('node_type')->load($entity->bundle());
      if ($node_type && $node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
        $map = $this->entityFieldManager->getFieldMapByFieldType(ComponentTreeItem::PLUGIN_ID);
        foreach ($map[$entity->getEntityTypeId()] ?? [] as $field_name => $info) {
          if (\in_array($entity->bundle(), $info['bundles'], TRUE)) {
            return $field_name;
          }
        }
        throw new \LogicException("Node bundle '{$entity->bundle()}' has canvas_override enabled but no component_tree field.");
      }
    }

    // Fall back to the original behavior for everything else.
    return parent::getCanvasFieldName($entity);
  }

}
