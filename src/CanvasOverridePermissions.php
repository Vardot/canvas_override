<?php

declare(strict_types=1);

namespace Drupal\canvas_override;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides dynamic per-content-type permissions for Canvas Override.
 */
final class CanvasOverridePermissions {

  use StringTranslationTrait;

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Returns per-bundle permissions for each Canvas Override-enabled content type.
   *
   * @return array
   *   An array of permission definitions keyed by permission machine name.
   */
  public function perBundlePermissions(): array {
    $permissions = [];

    foreach ($this->entityTypeManager->getStorage('node_type')->loadMultiple() as $node_type) {
      if (!$node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
        continue;
      }

      $bundle = $node_type->id();
      $label = $node_type->label();

      $permissions["use canvas override for $bundle"] = [
        'title' => $this->t('Use Canvas Override for %type content', ['%type' => $label]),
        'description' => $this->t('Edit per-content Canvas layouts on %type content.', ['%type' => $label]),
      ];
    }

    return $permissions;
  }

}
