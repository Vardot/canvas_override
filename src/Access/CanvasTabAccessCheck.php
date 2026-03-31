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
 * Access check: only show the Canvas tab if the content type has it enabled.
 *
 * Grants access when:
 * - The content type has Canvas Override enabled, AND
 * - The user has 'administer canvas override', 'use canvas override',
 *   OR the per-bundle 'use canvas override for <bundle>' permission.
 */
final class CanvasTabAccessCheck implements AccessInterface {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function access(NodeInterface $node, AccountInterface $account): AccessResultInterface {
    $bundle = $node->bundle();

    // Check Canvas Override-specific permissions (global or per-bundle).
    $has_permission = $account->hasPermission('administer canvas override')
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

    return AccessResult::allowedIf($enabled)
      ->addCacheableDependency($node_type)
      ->cachePerPermissions();
  }

}
