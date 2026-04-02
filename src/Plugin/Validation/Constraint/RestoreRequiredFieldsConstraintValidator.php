<?php

declare(strict_types=1);

namespace Drupal\canvas_override\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates and restores required field values for canvas_override nodes.
 *
 * This validator runs before other constraints and restores empty required
 * field values from the original entity in the database. This prevents
 * validation failures for required fields that were cleared during Canvas
 * form processing.
 */
class RestoreRequiredFieldsConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly RouteMatchInterface $routeMatch,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (!$value instanceof NodeInterface) {
      return;
    }

    // Only process during Canvas API requests.
    if (!\str_starts_with((string) $this->routeMatch->getRouteName(), 'canvas.api.')) {
      return;
    }

    // Skip new nodes.
    if ($value->isNew() || !$value->id()) {
      return;
    }

    // Check if canvas_override is enabled for this node type.
    $node_type = $this->entityTypeManager->getStorage('node_type')->load($value->bundle());
    if (!$node_type instanceof NodeTypeInterface) {
      return;
    }
    if (!$node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
      return;
    }

    // Load the original node from the database.
    $original = $this->entityTypeManager->getStorage('node')->loadUnchanged($value->id());
    if (!$original instanceof NodeInterface) {
      return;
    }

    // Fields that are being edited in Canvas Override - don't restore these.
    $preserved_fields = ['title', 'field_canvas_layout'];

    // Restore empty required fields from the original entity.
    foreach ($value->getFieldDefinitions() as $field_name => $definition) {
      if (in_array($field_name, $preserved_fields, TRUE)) {
        continue;
      }

      // Check if field is required or has NotNull constraint.
      $is_required = $definition->isRequired();

      if ($is_required && $original->hasField($field_name) && $value->hasField($field_name)) {
        $current_value = $value->get($field_name)->getValue();
        $original_value = $original->get($field_name)->getValue();

        // Restore if current is empty but original has a value.
        if (empty($current_value) && !empty($original_value)) {
          $value->set($field_name, $original_value);
        }
        // If both are empty and field has a default, use the default.
        elseif (empty($current_value) && empty($original_value)) {
          $default_value = $definition->getDefaultValue($value);
          if (!empty($default_value)) {
            $value->set($field_name, $default_value);
          }
        }
      }
    }
  }

}
