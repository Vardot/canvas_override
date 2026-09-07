<?php

declare(strict_types=1);

namespace Drupal\canvas_override\Access;

use Drupal\canvas\Access\ComponentTreeEditAccessCheck;
use Drupal\canvas\Storage\ComponentTreeLoader;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Fails safely when Canvas cannot edit an entity's component tree.
 *
 * Canvas's own ComponentTreeEditAccessCheck (the check behind
 * _canvas_component_tree_edit_access on the editor and canvas.api.* routes)
 * calls ComponentTreeLoader::load(), which throws \LogicException for any
 * entity Canvas does not support editing yet — every node bundle on an
 * unpatched Canvas, where the loader still refuses non-canvas_page entities.
 * An uncaught exception in an access check is an HTTP 500 on every such
 * request.
 *
 * On unpatched Canvas, Canvas Override already disables the per-content
 * feature (the content type checkbox is greyed out with an explanation), but a
 * bundle can still carry the enabled flag from a config import made on a
 * patched site, and its Canvas Override / Canvas editor links still resolve.
 * Hitting them then 500s instead of denying access.
 *
 * This check composes Canvas's final access check (its constructor is public,
 * so it can be instantiated even though the class cannot be extended),
 * delegates to it verbatim, and converts the \LogicException into a cacheable
 * 403. Behaviour is byte-identical for canvas_page and for any entity Canvas
 * does support; only the unsupported-entity 500 becomes a clean 403.
 *
 * This class is swapped in for Canvas's service in
 * CanvasOverrideServiceProvider. It is registered under Canvas's own service
 * id, which is consumed only through the access_check tag (nothing type-hints
 * the concrete Canvas class), so the swap is safe on both patched and
 * unpatched Canvas.
 *
 * @see \Drupal\canvas_override\CanvasOverrideServiceProvider
 * @see \Drupal\canvas\Access\ComponentTreeEditAccessCheck
 */
final class CanvasOverrideComponentTreeEditAccessCheck implements AccessInterface {

  /**
   * Canvas's access check, composed rather than extended (it is final).
   */
  private readonly ComponentTreeEditAccessCheck $inner;

  public function __construct(ComponentTreeLoader $componentTreeLoader) {
    $this->inner = new ComponentTreeEditAccessCheck($componentTreeLoader);
  }

  /**
   * Delegates to Canvas, converting an unsupported-entity error into a 403.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity containing a component tree.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account being checked.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Canvas's own result, or a cacheable 403 when Canvas cannot edit the
   *   entity's component tree on this site.
   */
  public function access(EntityInterface $entity, AccountInterface $account): AccessResultInterface {
    try {
      return $this->inner->access($entity, $account);
    }
    catch (\LogicException) {
      // Canvas cannot resolve a component-tree field for this entity — e.g. a
      // node on an unpatched Canvas. Deny rather than let the exception become
      // a 500. Vary by the entity so it re-evaluates once Canvas is patched.
      return AccessResult::forbidden('Canvas cannot edit this entity type on this site. Apply the Canvas patch or upgrade to a Canvas release that supports per-content layouts.')
        ->addCacheContexts(['user.permissions'])
        ->addCacheableDependency($entity);
    }
  }

}
