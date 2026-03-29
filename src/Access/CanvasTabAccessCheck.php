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
 */
final class CanvasTabAccessCheck implements AccessInterface {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function access(NodeInterface $node, AccountInterface $account): AccessResultInterface {
    if (!$account->hasPermission('administer content templates')) {
      return AccessResult::forbidden()->cachePerPermissions();
    }

    $node_type = $this->entityTypeManager->getStorage('node_type')->load($node->bundle());
    if (!$node_type) {
      return AccessResult::forbidden()->addCacheableDependency($node);
    }

    $enabled = (bool) $node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE);

    return AccessResult::allowedIf($enabled)
      ->addCacheableDependency($node_type)
      ->cachePerPermissions();
  }

}
