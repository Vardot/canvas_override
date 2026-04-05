# API Reference

Key classes, methods, and constants in Canvas Override.

## Classes

### CanvasOverridePermissions

**Namespace**: `Drupal\canvas_override`

Provides dynamic per-bundle permissions.

#### Methods

**`perBundlePermissions(): array`**

Returns permission definitions for each Canvas Override-enabled content type.
Iterates over all node types, skips types where `canvas_override.enabled` is
`FALSE`, and returns an array keyed by permission machine name.

**Return format**:

```php
[
  'use canvas override for article' => [
    'title' => t('Use Canvas Override for %type content', ['%type' => 'Article']),
    'description' => t('Edit per-content Canvas layouts on %type content.', ['%type' => 'Article']),
  ],
]
```

---

### CanvasTabAccessCheck

**Namespace**: `Drupal\canvas_override\Access`

Route access check for Canvas Override routes.

#### Methods

**`access(AccountInterface $account, NodeInterface $node): AccessResultInterface`**

Returns `AccessResult::allowed()` when:

1. The node's content type has Canvas Override enabled (third-party setting).
2. The user has at least one of:
   - `administer canvas override`
   - `use canvas override`
   - `use canvas override for {bundle}`

Returns `AccessResult::neutral()` otherwise.

**Cache contexts**: Caches per user permissions and node type entity.

---

### CanvasOverrideComponentTreeLoader

**Namespace**: `Drupal\canvas_override\Storage`

Extends Canvas's `ComponentTreeLoader` to support per-content layouts.

#### Methods

**`getCanvasFieldName(FieldableEntityInterface $entity): ?string`**

For Canvas Override-enabled nodes, returns the component_tree field name.
Falls back to parent behavior for other entities.

**`load(FieldableEntityInterface $entity): ComponentTreeItemList`**

Loads the component tree for an entity. For nodes with an empty canvas field
(first editor open), copies the ContentTemplate default into the node's own
field to avoid returning a dangling `ComponentTreeItemList`.

---

### CanvasOverrideNodeViewBuilder

**Namespace**: `Drupal\canvas_override\EntityHandlers`

Extends Canvas's `ContentTemplateAwareViewBuilder`.

#### Methods

**`buildComponents(array &$build, array $entities, array $displays, string $view_mode): void`**

Splits entities into override and template groups:

- **Override entities**: Nodes with non-empty `field_canvas_layout`. Rendered
  via the decorated view builder.
- **Template entities**: Nodes without per-content layout. Rendered via parent
  class (ContentTemplate).

**`hasPerNodeOverride(FieldableEntityInterface $entity): bool`**

Returns `TRUE` if the entity has a non-empty `field_canvas_layout`.

---

### CanvasOverrideConstraintValidator

**Namespace**: `Drupal\canvas_override\Plugin\Validation\Constraint`

Decorates Canvas's `ComponentTreeMeetsRequirementsConstraintValidator`.

#### Methods

**`validate(mixed $value, Constraint $constraint): void`**

For Canvas Override-enabled nodes, modifies the constraint to allow
`EntityField` and `HostEntityUrl` prop sources (field linking). Delegates to
the original validator for all other entities.

---

### RestoreRequiredFieldsConstraintValidator

**Namespace**: `Drupal\canvas_override\Plugin\Validation\Constraint`

Validates and restores required fields during Canvas API requests.

#### Methods

**`validate(mixed $value, Constraint $constraint): void`**

For each required field (except `title` and `field_canvas_layout`):

- If current value is empty but original has a value: restores from original.
- If both are empty and a default value exists: uses the default.

Only runs on Canvas API routes for existing, Canvas Override-enabled nodes.

---

### CanvasRedirectController

**Namespace**: `Drupal\canvas_override\Controller`

#### Methods

**`redirectToEditor(NodeInterface $node): TrustedRedirectResponse`**

Redirects to `canvas/editor/node/{node_id}`. Throws
`NotFoundHttpException` if Canvas Override is not enabled on the node's
content type.

---

### CanvasResetController

**Namespace**: `Drupal\canvas_override\Controller`

#### Methods

**`reset(NodeInterface $node): RedirectResponse`**

Clears the `field_canvas_layout` field and saves the node. Redirects to
the node canonical view with a success message. Throws
`NotFoundHttpException` if Canvas Override is not enabled.

---

### CanvasOverrideServiceProvider

**Namespace**: `Drupal\canvas_override`

Extends `ServiceProviderBase`.

#### Methods

**`alter(ContainerBuilder $container): void`**

Swaps Canvas's `ComponentTreeLoader` service class with
`CanvasOverrideComponentTreeLoader`.

---

### CanvasOverrideHooks

**Namespace**: `Drupal\canvas_override\Hook`

Contains module integration logic.

#### Static Methods

**`ensureCanvasField(string $bundle): void`**

Creates and configures the `field_canvas_layout` field on a content type.
Creates field storage, field config, and view display settings.

## Constants

| Constant | Value | File |
|----------|-------|------|
| `CANVAS_OVERRIDE_FIELD_NAME` | `field_canvas_layout` | `canvas_override.module` |

## Routes

| Route | Path | Controller |
|-------|------|-----------|
| `canvas_override.node.canvas` | `/node/{node}/canvas` | `CanvasRedirectController::redirectToEditor` |
| `canvas_override.node.canvas.reset` | `/node/{node}/canvas/reset` | `CanvasResetController::reset` |

## Third-Party Settings

| Setting | Entity | Key | Values |
|---------|--------|-----|--------|
| Canvas Override enabled | NodeType | `canvas_override.enabled` | `TRUE` / `FALSE` |

## Static Permissions

Defined in `canvas_override.permissions.yml`:

| Machine Name | Restricted |
|-------------|-----------|
| `administer canvas override` | Yes |
| `use canvas override` | No |

Dynamic permissions are generated by `CanvasOverridePermissions::perBundlePermissions()`.

## Next Steps

- [Architecture](0-architecture.md) — High-level design overview
- [Services and Extension Points](1-hooks-and-services.md) — Service
  documentation
