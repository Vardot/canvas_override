<?php

declare(strict_types=1);

namespace Drupal\canvas_override\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Restores required field values before validation for canvas_override nodes.
 */
#[Constraint(
  id: 'CanvasOverrideRestoreRequiredFields',
  label: new TranslatableMarkup('Canvas Override Restore Required Fields', [], ['context' => 'Validation']),
  type: 'entity:node'
)]
class RestoreRequiredFieldsConstraint extends SymfonyConstraint {

  /**
   * The error message.
   *
   * @var string
   */
  public string $message = 'Required field @field_name could not be restored.';

}
