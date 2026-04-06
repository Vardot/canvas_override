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
        'title' => $this->t('%type_name: Use Canvas Override', ['%type_name' => $label]),
        'description' => $this->t('Edit per-content Canvas layouts on %type_name content.', ['%type_name' => $label]),
      ];

      $permissions["reset canvas layout for $bundle"] = [
        'title' => $this->t('%type_name: Reset Canvas layout', ['%type_name' => $label]),
        'description' => $this->t('Reset per-content Canvas layouts to the shared default on %type_name content.', ['%type_name' => $label]),
      ];

      $permissions["edit canvas default template for $bundle"] = [
        'title' => $this->t('%type_name: Edit Canvas default template', ['%type_name' => $label]),
        'description' => $this->t('Edit the shared Canvas default template for %type_name content.', ['%type_name' => $label]),
        'restrict access' => TRUE,
      ];
    }

    return $permissions;
  }

}
