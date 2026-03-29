# Canvas Override

Lets site builders enable per-node Canvas-based layout editing on the full content view mode for selected content types.

## Overview

Canvas Override provides Layout Builder-style per-node layout overrides using the [Canvas](https://www.drupal.org/project/canvas) module. When enabled for a content type, each node can have its own Canvas layout stored in a `field_canvas_layout` field. Nodes without a per-node layout fall back to the shared ContentTemplate default for the content type.

## Requirements

- Drupal ~11.3.0
- [drupal/canvas](https://www.drupal.org/project/canvas) ^1.2

## Installation

```bash
composer require drupal/canvas_override
drush en canvas_override
```

## Configuration

1. Go to **Structure > Content types** and edit a content type.
2. Open the **Canvas layout** fieldset in the vertical tabs.
3. Check **Enable per-node Canvas layout editing on the full content view mode**.
4. Save the content type.

This automatically:
- Creates a `field_canvas_layout` (component_tree) field on the content type.
- Configures the `full` and `default` view displays to render it.

## Usage

Once enabled for a content type, editors see a **Canvas** tab on each node. Clicking it opens the per-node Canvas editor where they can visually compose a unique page layout.

### Routes

| Path | Description |
|------|-------------|
| `/node/{node}/canvas` | Open the per-node Canvas editor |
| `/node/{node}/canvas/default` | Edit the shared ContentTemplate default layout |
| `/node/{node}/canvas/reset` | Reset the node's layout to the content type default |

### Permissions

Access to the Canvas tab and routes requires the `administer content templates` permission.

## How It Works

- `hook_entity_view_alter` — When a node has a non-empty `field_canvas_layout`, its rendered output is replaced entirely with the per-node canvas layout (using the `canvas_naive_render_sdc_tree` formatter). The standard `node.html.twig` wrapper is also removed since Canvas output is self-contained.
- `hook_form_node_type_form_alter` — Adds the Canvas layout checkbox to the content type form.
- `hook_node_type_presave` — Ensures the canvas field is created when a node type is saved programmatically with canvas_override enabled (e.g. via config import or recipes).
- `hook_entity_form_display_alter` — Hides content fields (body, field_content, field_image, etc.) from the Page data panel in the per-entity Canvas editor, since their content is composed in the Canvas layout itself.
- `hook_entity_operation` — Adds a **Canvas** operation link to node listings for users with the appropriate permission.

## Maintainer

[Vardot](https://www.drupal.org/vardot)

## License

GPL-2.0-or-later
