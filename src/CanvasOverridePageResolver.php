<?php

declare(strict_types=1);

namespace Drupal\canvas_override;

use Drupal\canvas\Entity\ContentTemplate;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Backs a node's Canvas layout with its own canvas_page entity.
 *
 * Canvas only lets its editor operate on a canvas_page: ComponentTreeLoader
 * throws for any other entity type, and that restriction lives inside the
 * loader, which is final. Rather than replace the loader -- which needs a
 * patched Canvas -- give each overridden node a canvas_page of its own and
 * edit that. Canvas supports it natively, so no patch is involved.
 *
 * The node keeps its own component_tree field and renders from it exactly as
 * before; the page's tree is copied into that field whenever the page is
 * saved. One direction only, so there is nothing to reconcile.
 *
 * @see https://www.drupal.org/i/3620603
 * @see https://drupal.org/i/3498525
 */
final class CanvasOverridePageResolver {

  /**
   * Key-value collection mapping node ID to its backing canvas_page ID.
   *
   * A key-value map rather than a field: the mapping is internal plumbing,
   * not editorial data, and this keeps the change free of schema or config
   * updates on sites that already run the patched path.
   */
  public const COLLECTION = 'canvas_override.node_pages';

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly KeyValueFactoryInterface $keyValueFactory,
  ) {}

  /**
   * Returns the canvas_page backing a node, creating it when absent.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node whose layout is being edited.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The backing canvas_page.
   */
  public function getOrCreatePage(NodeInterface $node): object {
    $page = $this->getPage($node);
    if ($page) {
      return $page;
    }

    $storage = $this->entityTypeManager->getStorage('canvas_page');
    $page = $storage->create([
      'title' => $node->label(),
      // Never published in its own right: it exists to be edited, and the
      // node is what the site renders.
      'status' => FALSE,
    ]);
    $page->save();

    $this->store()->set((string) $node->id(), (int) $page->id());
    $this->seedFromNodeOrTemplate($node, $page);

    return $page;
  }

  /**
   * Returns the canvas_page backing a node, or NULL when there is none.
   */
  public function getPage(NodeInterface $node): ?object {
    $id = $this->store()->get((string) $node->id());
    if (!$id) {
      return NULL;
    }

    $page = $this->entityTypeManager->getStorage('canvas_page')->load($id);
    if (!$page) {
      // The page was deleted behind our back; forget the stale mapping so the
      // next request creates a fresh one instead of 404ing forever.
      $this->store()->delete((string) $node->id());
      return NULL;
    }

    return $page;
  }

  /**
   * Returns the node a canvas_page backs, or NULL when it backs none.
   */
  public function getNodeForPage(int|string $page_id): ?NodeInterface {
    foreach ($this->store()->getAll() as $nid => $mapped) {
      if ((int) $mapped === (int) $page_id) {
        $node = $this->entityTypeManager->getStorage('node')->load($nid);
        return $node instanceof NodeInterface ? $node : NULL;
      }
    }

    return NULL;
  }

  /**
   * Drops a node's backing page and its mapping.
   */
  public function deletePage(NodeInterface $node): void {
    $page = $this->getPage($node);
    if ($page) {
      $page->delete();
    }
    $this->store()->delete((string) $node->id());
  }

  /**
   * Copies a page's component tree into the node it backs.
   *
   * This is the whole sync: the node renders from its own field, so once the
   * tree is on the node nothing else in the render path changes.
   */
  public function syncPageToNode(object $page): bool {
    $node = $this->getNodeForPage($page->id());
    if (!$node || !$node->hasField(CANVAS_OVERRIDE_FIELD_NAME)) {
      return FALSE;
    }

    $tree = $page->getComponentTree();
    $node->set(CANVAS_OVERRIDE_FIELD_NAME, $tree->getValue());
    $node->setNewRevision(FALSE);
    $node->save();

    return TRUE;
  }

  /**
   * Seeds a fresh page from the node's own tree, else the ContentTemplate.
   */
  private function seedFromNodeOrTemplate(NodeInterface $node, object $page): void {
    $value = [];

    if ($node->hasField(CANVAS_OVERRIDE_FIELD_NAME) && !$node->get(CANVAS_OVERRIDE_FIELD_NAME)->isEmpty()) {
      // The node already has a layout (a patched site edited it, or a
      // previous page was deleted): carry it over so editing continues
      // where it left off.
      $value = $node->get(CANVAS_OVERRIDE_FIELD_NAME)->getValue();
    }
    else {
      $template = ContentTemplate::loadForEntity($node, 'full');
      if ($template) {
        $template_tree = $template->getComponentTree($node);
        if (!$template_tree->isEmpty()) {
          $value = $template_tree->getValue();
        }
      }
    }

    if ($value) {
      $page->set('components', $value);
      $page->save();
    }
  }

  /**
   * The node-to-page key-value store.
   */
  private function store(): object {
    return $this->keyValueFactory->get(self::COLLECTION);
  }

}
