# Frequently Asked Questions

## General

### What is Canvas Override?

Canvas Override is a Drupal module that adds per-content layout editing
capabilities to the Canvas page builder. It lets editors create unique layouts
for individual content items while other content continues using the content
type's shared ContentTemplate default.

### How does Canvas Override differ from Canvas?

Canvas provides visual page building through ContentTemplates defined at the
content type level. Canvas Override extends this by allowing each content item
to have its own layout. Think of it as the difference between a content type
display and per-content Layout Builder overrides, but for Canvas.

### Does Canvas Override replace Canvas?

No. Canvas Override requires Canvas and builds on top of it. The Canvas module
handles the visual editor, component system, and rendering. Canvas Override
adds the per-content override layer.

## Installation and Setup

### What are the requirements?

- Drupal 11.3.0 or later
- Canvas module ^1
- PHP 8.3 or later

### How do I install Canvas Override?

```bash
composer require drupal/canvas_override
drush en canvas_override
```

### Do I need to configure anything after installation?

Yes. You need to enable Canvas Override on each content type where you want
per-content layouts. Go to **Structure > Content types**, edit the content
type, open the **Canvas layout** fieldset, and check the checkbox.

## Usage

### How do editors access per-content layouts?

Once Canvas Override is enabled for a content type, a **Canvas** tab appears on
each content item. Clicking it opens the Canvas editor for that specific
content.

### What happens if I don't create a per-content layout?

The content uses the shared ContentTemplate default for its content type.
Per-content layouts are optional.

### Can I reset a content item's layout to the default?

Yes. Use the **Reset to default layout** tab on the content page, or visit
`/node/{id}/canvas/reset`. This clears the per-content layout and reverts to
the ContentTemplate default.

### Are content fields (body, images, etc.) available in per-content layouts?

Yes. Canvas Override enables field linking on per-content layouts, so editors
can connect component properties to entity fields like body, images, and custom
fields. The standard Drupal edit form is used for field data entry, while
Canvas handles the visual layout.

## Permissions

### What permissions does Canvas Override provide?

Three levels:

1. **Administer Canvas Override** - Enable or disable Canvas Override on
   content types.
2. **Use Canvas Override for all content types** - Edit per-content layouts on
   any enabled content type.
3. **Use Canvas Override for [type]** - Edit per-content layouts only for a
   specific content type.

### Can I give editors access to Canvas Override on only certain content types?

Yes. Use the per-bundle permissions (e.g. "Use Canvas Override for Article") to
grant access on a per-content-type basis.

### Why don't I see the Canvas tab on a content item?

Check that:

1. Canvas Override is enabled on the content type.
2. The user has at least one Canvas Override permission.
3. The Canvas module is installed and working.

## Performance

### Does Canvas Override affect rendering performance?

Minimal impact. Content with per-content layouts renders its own field
directly. Content without overrides falls back to the ContentTemplate as usual.
The view builder splits processing efficiently between the two groups.

### Does the module add database queries?

Canvas Override uses the existing entity field system. The `field_canvas_layout`
field is loaded as part of normal entity loading, so there are no additional
queries for rendering.

## Troubleshooting

### Required field validation errors when saving in Canvas editor

Canvas Override includes multiple validation safeguards to prevent this. If you
encounter required field errors, ensure you are running the latest version of
both Canvas and Canvas Override. See the
[Troubleshooting guide](2-admins/2-troubleshooting.md) for details.

### The Page data panel shows all entity fields

Canvas Override hides entity fields from the Page data panel by default. If
fields are visible, check that the module is active (clear the cache with
`drush cr`).

### Canvas tab not appearing

See the permissions FAQ above. Also verify that the content type has Canvas
Override enabled in its settings.
