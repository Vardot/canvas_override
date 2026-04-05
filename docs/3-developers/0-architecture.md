# Architecture

How Canvas Override integrates with the Canvas module and Drupal.

## Overview

Canvas Override integrates with the Canvas module through service decoration,
hook implementations, and constraint validation. This document explains the
architectural decisions and how the module's components fit together.

## Core Concept

The Canvas module provides visual page building through **ContentTemplates**
defined at the content type level. Canvas Override adds a per-content layer by:

1. Adding a `field_canvas_layout` (component_tree) field to content types.
2. Overriding Canvas's `ComponentTreeLoader` to support nodes as canvas
   entities.
3. Splitting the view builder to handle per-content vs. template rendering.
4. Managing required field validation during Canvas API requests.

## Module Components

### Service Provider

**`CanvasOverrideServiceProvider`** swaps Canvas's `ComponentTreeLoader` with
`CanvasOverrideComponentTreeLoader`. This is the primary integration point that
allows nodes to be treated as valid canvas entities.

### Component Tree Loader

**`CanvasOverrideComponentTreeLoader`** extends Canvas's `ComponentTreeLoader`:

- **`getCanvasFieldName(entity)`**: For Canvas Override-enabled nodes, returns
  the component_tree field name. Bypasses the upstream restriction that only
  allows ContentTemplate entities.
- **`load(entity)`**: When a node's canvas field is empty (first editor open),
  copies the ContentTemplate default components into the node's own field.
  This avoids returning a dangling `ComponentTreeItemList` that causes 500
  errors with `content_moderation`.

### View Builder

**`CanvasOverrideNodeViewBuilder`** extends Canvas's
`ContentTemplateAwareViewBuilder`:

- Splits entities into two groups in `buildComponents()`:
  - **Override entities**: Nodes with non-empty `field_canvas_layout` —
    rendered via the decorated view builder (field renders directly).
  - **Template entities**: Nodes without per-content layout — rendered via
    parent class logic (uses ContentTemplate).
- Adds appropriate cache contexts for both paths.

### Constraint Validator Decorator

**`CanvasOverrideConstraintValidator`** decorates Canvas's
`ComponentTreeMeetsRequirementsConstraintValidator`:

- For Canvas Override-enabled nodes: removes `EntityField` and
  `HostEntityUrl` from the "absence" constraint, allowing field linking on
  per-content layouts.
- For other entities: delegates to the original validator.

The service ID matches the original class name (required for Drupal's
`ClassResolver`).

### Access Check

**`CanvasTabAccessCheck`** implements the `_canvas_override_access` route
requirement:

- Checks user permissions (admin, global, or per-bundle).
- Checks that the content type has Canvas Override enabled.
- Caches per permissions and node type entity.

## Data Flow

### Enabling Canvas Override

```
Admin enables checkbox on content type form
  → Form submit handler saves third-party setting
  → ensureCanvasField() creates field, storage, and display config
  → Dynamic permission generated for the bundle
```

### Editing a Node in Canvas

```
Editor clicks Canvas tab
  → CanvasRedirectController redirects to Canvas editor
  → Canvas editor requests component tree via API
  → CanvasOverrideComponentTreeLoader::load()
    → If field empty: seeds from ContentTemplate
    → Returns component tree for editing
  → Non-layout fields hidden from editor form
  → Editor saves
  → Required field values restored from database
  → NotNull constraints bypassed for non-layout fields
  → RestoreRequiredFieldsConstraintValidator (final restoration)
  → Non-layout fields restored before save
  → Node saved with title + field_canvas_layout
```

### Rendering a Node

```
Node view requested
  → CanvasOverrideNodeViewBuilder::buildComponents()
    → Splits into override_entities and template_entities
    → Override: renders field_canvas_layout directly
    → Template: uses ContentTemplate via parent class
  → If node has per-content layout: output replaced with field_canvas_layout
    → Theme wrapper removed (Canvas output is self-contained)
```

## Required Field Handling

Canvas Override edits only the layout and title. Other required fields are not
present in the Canvas editor form. The module includes multiple safeguards at
different stages of the form and validation lifecycle to prevent required field
validation failures and ensure field values are preserved during Canvas API
requests.

Key design decision: the module uses `unset()` on form elements instead of
`#access = FALSE`. Canvas core's `extractFormValues()` still processes fields
with `#access = FALSE`, which clears their values. Using `unset()` prevents
form processing entirely.

## Service Definitions

```yaml
# Access check for Canvas tab routes
canvas_override.access_check:
  class: Drupal\canvas_override\Access\CanvasTabAccessCheck
  tags:
    - { name: access_check, applies_to: _canvas_override_access }

# Constraint validator decoration
# Service ID matches original class (required for ClassResolver)
Drupal\canvas\Plugin\...\ComponentTreeMeetsRequirementsConstraintValidator:
  class: Drupal\canvas_override\Plugin\...\CanvasOverrideConstraintValidator
  arguments:
    - '@canvas_override.inner_constraint_validator'
```

## Constants

| Constant | Value | Location |
|----------|-------|----------|
| `CANVAS_OVERRIDE_FIELD_NAME` | `field_canvas_layout` | `canvas_override.module` |

## Next Steps

- [Services and Extension Points](1-hooks-and-services.md) — Detailed service
  documentation
- [API Reference](2-api-reference.md) — Key classes and methods
