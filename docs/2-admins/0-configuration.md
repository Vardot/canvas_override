# Configuration

## Enabling Canvas Override on a Content Type

Canvas Override is configured per content type. To enable it:

1. Go to **Structure > Content types** (`/admin/structure/types`).
2. Click **Edit** on the content type.
3. Open the **Canvas layout** fieldset in the vertical tabs.
4. Check **Enable per-content Canvas layout editing on the full content view
   mode**.
5. Click **Save**.

Only users with the **Administer Canvas Override** permission see this
fieldset.

### What Happens When You Enable

When you save a content type with Canvas Override enabled, the module
automatically:

1. Creates a `field_canvas_layout` field (type: `component_tree`) on the
   content type if it does not already exist.
2. Locks the field so it cannot be deleted through the UI.
3. Configures the `full` and `default` view displays to render the field using
   the `canvas_naive_render_sdc_tree` formatter.
4. Generates a dynamic permission: "Use Canvas Override for [content type]".

### Disabling Canvas Override

To disable Canvas Override on a content type:

1. Uncheck the checkbox on the content type edit form.
2. Save the content type.

Existing per-content layouts are preserved in the database but will no longer
be used for rendering. The `field_canvas_layout` field remains on the content
type (it is locked). If you re-enable Canvas Override later, existing
per-content layouts will be restored.

## Programmatic Configuration

Canvas Override uses Drupal's third-party settings system. You can enable it
programmatically:

```php
$node_type = \Drupal\node\Entity\NodeType::load('article');
$node_type->setThirdPartySetting('canvas_override', 'enabled', TRUE);
$node_type->save();
```

The `field_canvas_layout` field is automatically created when a content type is
saved with Canvas Override enabled, including during config imports and recipe
installations.

## View Display Configuration

The `field_canvas_layout` field is configured on the `full` and `default` view
modes with these settings:

| Setting | Value |
|---------|-------|
| Formatter | `canvas_naive_render_sdc_tree` |
| Label | Hidden |
| Weight | -2 |

You can adjust the weight or visibility through the **Manage display** tab on
the content type, but the formatter should remain
`canvas_naive_render_sdc_tree` for correct rendering.

## Content Type Settings Checklist

- [ ] Canvas Override checkbox is enabled.
- [ ] `field_canvas_layout` field exists on the content type.
- [ ] View display is configured with the correct formatter.
- [ ] Appropriate permissions are assigned to roles.
- [ ] Editors can see the Canvas tab on content items.

## Next Steps

- [Permissions](1-permissions.md) - Configure who can use Canvas Override.
- [Troubleshooting](2-troubleshooting.md) - Resolve common configuration
  issues.
