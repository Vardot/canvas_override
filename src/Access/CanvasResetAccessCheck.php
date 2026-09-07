<?php

declare(strict_types=1);

namespace Drupal\canvas_override\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Access check for the Canvas Override reset layout route.
 *
 * Grants access when:
 * - The content type has Canvas Override enabled, AND
 * - The user has 'administer canvas override', 'reset canvas layout',
 *   'reset canvas layout for <bundle>', 'use canvas override', OR
 *   'use canvas override for <bundle>'.
 */
final class CanvasResetAccessCheck implements AccessInterface {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function access(NodeInterface $node, AccountInterface $account): AccessResultInterface {
    $bundle = $node->bundle();

    $has_permission = $account->hasPermission('administer canvas override')
      || $account->hasPermission('reset canvas layout')
      || $account->hasPermission("reset canvas layout for $bundle")
      || $account->hasPermission('use canvas override')
      || $account->hasPermission("use canvas override for $bundle");

    if (!$has_permission) {
      return AccessResult::forbidden()->cachePerPermissions();
    }

    $node_type = $this->entityTypeManager->getStorage('node_type')->load($bundle);
    if (!$node_type) {
      return AccessResult::forbidden()->addCacheableDependency($node);
    }

    $enabled = (bool) $node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE);

    // The dedicated reset permissions deliberately allow resetting without
    // full node edit access (a "reset-only" role is supported), but the user
    // must at least be able to VIEW the node: without this, a reset-only
    // user could wipe the stored layout of unpublished or otherwise hidden
    // content they cannot even see.
    return AccessResult::allowedIf($enabled)
      ->addCacheableDependency($node_type)
      ->cachePerPermissions()
      ->andIf($node->access('view', $account, TRUE));
  }

}
