# Frequently Asked Questions

Common questions and answers about Canvas Override.

## General Questions

### What is Canvas Override?

Canvas Override is a Drupal module that adds per-content layout editing
capabilities to the Canvas page builder. It lets editors create unique layouts
for individual content items while other content continues using the content
type's shared ContentTemplate default.

### Who should use Canvas Override?

- **Sites with diverse content** — where some pages need unique layouts
- **Marketing teams** — creating campaign-specific landing pages
- **Editorial teams** — featuring longform articles with custom presentations
- **Multi-editor sites** — where different teams manage different content types
- **Sites migrating layouts** — gradually rolling out new designs

### How does Canvas Override differ from Canvas?

Canvas provides visual page building through ContentTemplates defined at the
content type level. Canvas Override extends this by allowing each content item
to have its own layout. Think of it as the difference between a content type
display and per-content Layout Builder overrides, but for Canvas.

### Does Canvas Override replace Canvas?

No. Canvas Override requires Canvas and builds on top of it. The Canvas module
handles the visual editor, component system, and rendering. Canvas Override
adds the per-content override layer.

### Is Canvas Override free?

Yes, Canvas Override is free and open-source software distributed under the
GPL license.

---

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

See the [Installation Guide](1-users/0-installation.md) for detailed
instructions.

### Do I need to configure anything after installation?

Yes. You need to enable Canvas Override on each content type where you want
per-content layouts:

1. Go to **Structure > Content types**.
2. Edit the content type.
3. Open the **Canvas layout** fieldset.
4. Check the checkbox.
5. Save.

See [Configuration](2-admins/0-configuration.md) for details.

---

## Usage

### How do editors access per-content layouts?

Once Canvas Override is enabled for a content type, a **Canvas** tab appears on
each content item. Clicking it opens the Canvas editor for that specific
content.

### What happens if I don't create a per-content layout?

The content uses the shared ContentTemplate default for its content type.
Per-content layouts are optional — nothing changes unless an editor explicitly
creates an override.

### Can I reset a content item's layout to the default?

Yes. Use the **Reset to default layout** tab on the content page, or visit
`/node/{id}/canvas/reset`. This clears the per-content layout and reverts to
the ContentTemplate default.

### Are content fields (body, images, etc.) available in per-content layouts?

Yes. Canvas Override enables field linking on per-content layouts, so editors
can connect component properties to entity fields like body, images, and custom
fields. The standard Drupal edit form is used for field data entry, while
Canvas handles the visual layout.

### Where do I edit content field values?

Use the content item's **Edit** tab (the standard Drupal edit form). Canvas
Override hides entity fields from the Canvas editor's Page data panel to keep
the editing experience focused on layout.

### Can I override layouts for only some content items?

Yes. Enabling Canvas Override on a content type does not force overrides on
every content item. Only content items where an editor has explicitly created a
per-content layout will use an override. All other content continues using the
ContentTemplate default.

---

## Permissions

### What permissions does Canvas Override provide?

Three levels:

1. **Administer Canvas Override** — Enable or disable Canvas Override on
   content types. Also grants full access to all Canvas tabs.
2. **Use Canvas Override for all content types** — Edit per-content layouts on
   any enabled content type.
3. **Use Canvas Override for [type]** — Edit per-content layouts only for a
   specific content type (generated dynamically per enabled type).

### Can I give editors access to Canvas Override on only certain content types?

Yes. Use the per-bundle permissions (e.g. "Use Canvas Override for Article") to
grant access on a per-content-type basis. See
[Permissions](2-admins/1-permissions.md) for setup details.

### Why don't I see the Canvas tab on a content item?

Check that:

1. Canvas Override is enabled on the content type.
2. The user has at least one Canvas Override permission.
3. The Canvas module is installed and working.
4. The cache is cleared: `drush cr`

---

## Performance

### Does Canvas Override affect rendering performance?

Minimal impact. Content with per-content layouts renders its own field
directly. Content without overrides falls back to the ContentTemplate as usual.
The view builder splits processing efficiently between the two groups.

### Does the module add database queries?

Canvas Override uses the existing entity field system. The `field_canvas_layout`
field is loaded as part of normal entity loading, so there are no additional
queries for rendering.

---

## Troubleshooting

### Required field validation errors when saving in Canvas editor

Canvas Override includes multiple validation safeguards to prevent this. If you
encounter required field errors, ensure you are running the latest version of
both Canvas and Canvas Override. See the
[Troubleshooting guide](2-admins/2-troubleshooting.md) for details.

### The Page data panel shows all entity fields

Canvas Override hides entity fields from the Page data panel by default. If
fields are visible, clear the cache with `drush cr` and verify the module is
enabled.

### Canvas tab not appearing

See the Permissions FAQ above. Also verify that the content type has Canvas
Override enabled in its settings and that the cache is cleared.

### Layout not rendering on the front end

Check that:
1. The content item has a saved per-content layout (not empty).
2. You are viewing the `full` view mode.
3. The view display is configured correctly.

See [Troubleshooting](2-admins/2-troubleshooting.md) for detailed diagnostics.

---

## Getting Help

### Where can I get support?

1. **Documentation**: Review the [complete documentation](index.md)
2. **Issue Queue**: [drupal.org/project/issues/canvas_override](https://www.drupal.org/project/issues/canvas_override)
3. **Canvas Module**: For Canvas-specific issues, see the [Canvas issue queue](https://www.drupal.org/project/issues/canvas)

### How do I report a bug?

1. Search existing issues first.
2. Create a detailed bug report:
   - Steps to reproduce
   - Expected vs actual behavior
   - Drupal version
   - Module version
3. Post to the [issue queue](https://www.drupal.org/project/issues/canvas_override).

### How can I contribute?

- Report bugs
- Suggest features
- Submit patches
- Improve documentation
- Test new releases

Visit the [project page](https://www.drupal.org/project/canvas_override) to
contribute.

---

## Next Steps

- [Install the module](1-users/0-installation.md)
- [Configure content types](2-admins/0-configuration.md)
- [Set up permissions](2-admins/1-permissions.md)
- [Review use cases](1-users/2-use-cases.md)
- [Explore the architecture](3-developers/0-architecture.md)
