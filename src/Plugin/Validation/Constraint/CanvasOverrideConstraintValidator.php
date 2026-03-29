<?php

declare(strict_types=1);

namespace Drupal\canvas_override\Plugin\Validation\Constraint;

use Drupal\canvas\Plugin\Field\FieldType\ComponentTreeItem;
use Drupal\canvas\Plugin\Field\FieldType\ComponentTreeItemList;
use Drupal\canvas\Plugin\Validation\Constraint\ComponentTreeMeetsRequirementsConstraint;
use Drupal\canvas\Plugin\Validation\Constraint\ComponentTreeMeetsRequirementsConstraintValidator;
use Drupal\canvas\PropSource\PropSource;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Decorates the canvas constraint validator to allow field linking on nodes.
 *
 * For canvas_override-enabled node bundles, EntityFieldPropSource and
 * HostEntityUrlPropSource are permitted in component trees — removing the
 * upstream restriction that limits dynamic prop sources to ContentTemplates.
 */
final class CanvasOverrideConstraintValidator extends ConstraintValidator {

  public function __construct(
    private readonly ComponentTreeMeetsRequirementsConstraintValidator $inner,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get(ComponentTreeMeetsRequirementsConstraintValidator::class),
      $container->get(EntityTypeManagerInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(ExecutionContextInterface $context): void {
    parent::initialize($context);
    $this->inner->initialize($context);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    \assert($constraint instanceof ComponentTreeMeetsRequirementsConstraint);

    $host_entity = $this->resolveHostEntity($value);

    if ($host_entity instanceof NodeInterface) {
      $node_type = $this->entityTypeManager->getStorage('node_type')->load($host_entity->bundle());
      if ($node_type?->getThirdPartySetting('canvas_override', 'enabled', FALSE)) {
        // For canvas_override nodes, allow EntityField and HostEntityUrl prop
        // sources so editors can link component props to entity fields.
        $allowed_absence = array_values(array_diff(
          $constraint->inputs['absence'] ?? [],
          [PropSource::EntityField->value, PropSource::HostEntityUrl->value],
        ));
        $modified = new ComponentTreeMeetsRequirementsConstraint([
          'inputs' => [
            'absence' => $allowed_absence,
            'presence' => $constraint->inputs['presence'],
          ],
          'tree' => $constraint->tree,
        ]);
        $this->inner->validate($value, $modified);
        return;
      }
    }

    $this->inner->validate($value, $constraint);
  }

  /**
   * Resolves the host entity from a component tree value.
   */
  private function resolveHostEntity(mixed $value): mixed {
    if ($value instanceof ComponentTreeItem) {
      return $value->getParent()?->getParent()?->getEntity();
    }
    if ($value instanceof ComponentTreeItemList) {
      return $value->getEntity();
    }
    return NULL;
  }

}
