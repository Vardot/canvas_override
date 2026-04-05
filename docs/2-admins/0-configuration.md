# Configuration

Learn how to enable and configure Canvas Override on your content types.

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

No manual field creation or display configuration is needed.

### Disabling Canvas Override

To disable Canvas Override on a content type:

1. Uncheck the checkbox on the content type edit form.
2. Save the content type.

**What is preserved**: Existing per-content layouts are kept in the database
but will no longer be used for rendering. The `field_canvas_layout` field
remains on the content type (it is locked).

**What changes**: Content reverts to the ContentTemplate default. The Canvas
tab no longer appears for that content type.

**Re-enabling**: If you re-enable Canvas Override later, existing per-content
layouts are restored automatically.

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

### Via Drush

```bash
drush php:eval "\$type = \Drupal\node\Entity\NodeType::load('article'); \$type->setThirdPartySetting('canvas_override', 'enabled', TRUE); \$type->save();"
drush cr
```

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

## Multiple Content Types

You can enable Canvas Override on as many content types as needed. Each content
type is configured independently:

- Enable/disable is per content type.
- Each enabled content type gets its own dynamic permission.
- Content types can have different ContentTemplate defaults.

**Recommendation**: Only enable Canvas Override on content types where editors
actually need per-content layouts. Enabling it everywhere adds unnecessary
complexity.

## Configuration Checklist

Use this checklist after enabling Canvas Override on a content type:

- [ ] Canvas Override checkbox is enabled on the content type
- [ ] `field_canvas_layout` field exists (created automatically)
- [ ] View display is configured with the correct formatter (automatic)
- [ ] Appropriate permissions are assigned to roles
- [ ] Editors can see the Canvas tab on content items
- [ ] Cache is cleared (`drush cr`)

## Troubleshooting

### Checkbox Not Visible on Content Type Form

**Issue**: The Canvas layout fieldset does not appear on the content type edit
form.

**Solutions**:
- Check that you have the "Administer Canvas Override" permission
- Verify Canvas Override module is enabled: `drush pm:list --filter=canvas_override`
- Clear the cache: `drush cr`

### Field Not Created

**Issue**: Enabling Canvas Override does not create the `field_canvas_layout`
field.

**Solutions**:
1. Check the Drupal log at **Reports > Recent log messages**
   (`/admin/reports/dblog`) for errors.
2. Verify the Canvas module is installed and working.
3. Create the field manually if needed:
   ```bash
   drush php:eval "\Drupal\canvas_override\Hook\CanvasOverrideHooks::ensureCanvasField('article');"
   ```

### Settings Lost After Config Import

**Issue**: Canvas Override settings disappear after a configuration import.

**Solutions**:
- Verify the third-party settings are included in the exported
  `node.type.*.yml` files.
- Check that the config import includes the correct content type YAML.

## Next Steps

- [Configure permissions](1-permissions.md) for your team
- [Troubleshoot issues](2-troubleshooting.md) with common problems
- [Learn how editors use Canvas Override](../1-users/1-editing-layouts.md)
