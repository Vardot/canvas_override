<?php

declare(strict_types=1);

namespace Drupal\canvas_override\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Restores required field values before validation for canvas_override nodes.
 *
 * @Constraint(
 *   id = "CanvasOverrideRestoreRequiredFields",
 *   label = @Translation("Canvas Override Restore Required Fields", context = "Validation"),
 *   type = "entity:node"
 * )
 */
class RestoreRequiredFieldsConstraint extends Constraint {

  /**
   * The error message.
   *
   * @var string
   */
  public string $message = 'Required field @field_name could not be restored.';

}
