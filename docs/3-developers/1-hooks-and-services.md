# Services and Extension Points

Canvas Override's key services, third-party settings, and how to extend them.

## Services

### canvas_override.access_check

- **Class**: `CanvasTabAccessCheck`
- **Tag**: `access_check` (applies to `_canvas_override_access`)
- **Purpose**: Route access check for Canvas tab and reset routes.
- **Logic**: Checks user permissions (admin, global, or per-bundle) and
  verifies that Canvas Override is enabled on the node's content type.

### CanvasOverrideConstraintValidator

- **Class**: `CanvasOverrideConstraintValidator`
- **Decorates**: Canvas's `ComponentTreeMeetsRequirementsConstraintValidator`
- **Purpose**: Allows field linking (EntityField and HostEntityUrl prop
  sources) on per-content Canvas layouts.
- **Note**: The service ID must match the original class name for Drupal's
  `ClassResolver` to resolve it correctly.

### CanvasOverrideComponentTreeLoader

- **Extends**: Canvas's `ComponentTreeLoader`
- **Registered via**: `CanvasOverrideServiceProvider::alter()`
- **Purpose**: Allows nodes to be treated as valid canvas entities. When a
  node's canvas field is empty (first editor open), copies the
  ContentTemplate default into the node's own field.

### CanvasOverrideNodeViewBuilder

- **Extends**: Canvas's `ContentTemplateAwareViewBuilder`
- **Purpose**: Splits rendering between nodes with per-content layouts (rendered
  directly from `field_canvas_layout`) and nodes without (rendered via
  ContentTemplate).

## Third-Party Settings

Canvas Override uses Drupal's third-party settings on `NodeType` entities:

| Key | Type | Description |
|-----|------|-------------|
| `canvas_override.enabled` | `bool` | Whether per-content Canvas layouts are enabled for this content type |

### Reading the Setting

```php
$enabled = $node_type->getThirdPartySetting('canvas_override', 'enabled', FALSE);
```

### Setting Programmatically

```php
$node_type->setThirdPartySetting('canvas_override', 'enabled', TRUE);
$node_type->save();
```

## Field Creation

The `ensureCanvasField()` static method creates and configures the
`field_canvas_layout` field on a content type:

```php
CanvasOverrideHooks::ensureCanvasField('article');
```

This creates:

- Field storage (type: `component_tree`, locked).
- Field config (label: "Canvas Layout").
- View display on `full` and `default` view modes with the
  `canvas_naive_render_sdc_tree` formatter.

## Next Steps

- [Architecture](0-architecture.md) — High-level design overview
- [API Reference](2-api-reference.md) — Key classes and methods
