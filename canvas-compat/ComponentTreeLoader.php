<?php

declare(strict_types=1);

// phpcs:ignoreFile
// cspell:disable
//
// OPTION 2 (issue #3621557): unpatched-Canvas replacement for
// Drupal\canvas\Storage\ComponentTreeLoader.
//
// This is a VERBATIM copy of Canvas's own ComponentTreeLoader with exactly two
// changes, identical to the #3567225 PHP patch:
//   1. the class is no longer `final`;
//   2. the promoted constructor properties are `protected`, not `private`.
// Everything else must stay byte-for-byte identical to Canvas.
//
// It is NOT autoloaded by the module's PSR-4 rules (it deliberately lives
// outside src/ and declares Canvas's namespace). It is required, under Canvas's
// own FQCN, by canvas_override.class_override.php ONLY when the installed Canvas
// still ships ComponentTreeLoader as `final` (i.e. the #3567225 patch is not
// applied). On patched Canvas — or any Canvas whose class is already
// un-finalised — this file is never loaded and Canvas's real class is used.
//
// MAINTENANCE: re-sync this copy with Canvas's src/Storage/ComponentTreeLoader.php
// on every Canvas release (re-apply the two changes above). Last synced with:
//   drupal/canvas 1.10.1
//
// @see \Drupal\canvas_override\CanvasOverrideClassOverride
// @see https://www.drupal.org/project/canvas_override/issues/3621557
// @see https://www.drupal.org/project/canvas_override/issues/3567225

namespace Drupal\canvas\Storage;

use Drupal\canvas\Entity\ComponentTreeEntityInterface;
use Drupal\canvas\Entity\Page;
use Drupal\canvas\Plugin\Field\FieldType\ComponentTreeItem;
use Drupal\canvas\Plugin\Field\FieldType\ComponentTreeItemList;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Handles loading a component tree from entities.
 */
class ComponentTreeLoader {

  public function __construct(
    protected readonly EntityFieldManagerInterface $entityFieldManager,
    protected readonly ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Loads a component tree from an entity.
   *
   * @param \Drupal\canvas\Entity\ComponentTreeEntityInterface|\Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity that stores the component tree. If it does not specifically
   *   implement ComponentTreeEntityInterface, then it is expected to be a
   *   fieldable entity with at least one field that stores a component tree.
   *
   * @return \Drupal\canvas\Plugin\Field\FieldType\ComponentTreeItemList
   */
  public function load(ComponentTreeEntityInterface|FieldableEntityInterface $entity): ComponentTreeItemList {
    if ($entity instanceof ComponentTreeEntityInterface) {
      return $entity->getComponentTree();
    }
    $field_name = $this->getCanvasFieldName($entity);
    $item = $entity->get($field_name);
    \assert($item instanceof ComponentTreeItemList);
    return $item;
  }

  /**
   * Gets the Canvas field name from the entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The Canvas field name, or throws an exception
   *   if not found or not supported entity type/bundle.
   *
   * @throws \LogicException
   */
  public function getCanvasFieldName(FieldableEntityInterface $entity): string {
    // @todo Remove this restriction once other entity types and bundles are
    //   allowed in https://drupal.org/i/3498525.
    $articles_allowed_only_on_tests = $entity->getEntityTypeId() === 'node' && $entity->bundle() === 'article' && (drupal_valid_test_ua() || $this->moduleHandler->moduleExists('canvas_test_article_fields'));
    if ($entity->getEntityTypeId() !== Page::ENTITY_TYPE_ID && !$articles_allowed_only_on_tests) {
      throw new \LogicException('For now Canvas only works if the entity is a canvas_page! Other entity types and bundles must use content templates for now, see https://drupal.org/i/3498525');
    }

    $map = $this->entityFieldManager->getFieldMapByFieldType(ComponentTreeItem::PLUGIN_ID);

    foreach ($map[$entity->getEntityTypeId()] ?? [] as $field_name => $info) {
      if (\in_array($entity->bundle(), $info['bundles'], TRUE)) {
        return $field_name;
      }
    }
    throw new \LogicException("This entity does not have a Canvas field!");
  }

}
