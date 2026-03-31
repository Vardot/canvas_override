<?php

declare(strict_types=1);

namespace Drupal\canvas_override;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Provides dynamic per-content-type permissions for Canvas Override.
 */
final class CanvasOverridePermissions {

  use StringTranslationTrait;

  /**
   * Returns per-bundle permissions for each Canvas Override-enabled content type.
   *
   * @return array
   *   An array of permission definitions keyed by permission machine name.
   */
  public function perBundlePermissions(): array {
    $permissions = [];

    foreach (NodeType::loadMultiple() as $node_type) {
      if (!$node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
        continue;
      }

      $bundle = $node_type->id();
      $label = $node_type->label();

      $permissions["use canvas override for $bundle"] = [
        'title' => $this->t('Use Canvas Override for %type content', ['%type' => $label]),
        'description' => $this->t('Edit per-node Canvas layouts on %type nodes.', ['%type' => $label]),
      ];
    }

    return $permissions;
  }

}
