# Troubleshooting

Common issues and solutions for Canvas Override.

## Canvas Tab Not Visible

### Symptoms

The **Canvas** tab does not appear on content pages.

### Possible Causes and Solutions

**1. Canvas Override not enabled on the content type**

Go to **Structure > Content types**, edit the content type, and check that
the **Canvas layout** checkbox is enabled in the vertical tabs.

**2. User lacks permissions**

The user needs at least one of:
- Administer Canvas Override
- Use Canvas Override for all content types
- Use Canvas Override for [specific content type]

Check permissions at **People > Permissions** (`/admin/people/permissions`).

Verify via Drush:
```bash
drush role:perm:list editor | grep canvas
```

**3. Cache needs clearing**

```bash
drush cr
```

**4. Canvas module not installed**

Canvas Override requires the Canvas module. Verify it is installed:

```bash
drush pm:list --filter=canvas
```

---

## Required Field Validation Errors

### Symptoms

Saving content in the Canvas editor produces validation errors for required
fields (e.g. "Body field is required").

### Explanation

Canvas Override edits only the layout and title. Other required fields are not
present in the Canvas editor form. The module includes multiple safeguards to
prevent validation failures:

- Removes NotNull constraints from non-layout fields during Canvas API
  requests.
- Restores required field values from the database before validation.
- Restores field values before content save.

### Solutions

1. **Update to the latest version** of both Canvas and Canvas Override.

2. **Clear caches**:
   ```bash
   drush cr
   ```

3. **Check for conflicting modules** that alter content validation or form
   processing.

4. **Verify field values exist**: If a required field was recently added but
   has no stored value, the restoration logic cannot retrieve an original
   value. Ensure existing content has values for all required fields before
   editing in Canvas.

---

## Page Data Panel Shows Entity Fields

### Symptoms

The Canvas editor's Page data panel displays entity fields (body, images,
etc.) instead of being hidden.

### Explanation

Canvas Override hides entity fields from the Page data panel because field
content is managed through the standard Drupal edit form, not the Canvas
editor.

### Solutions

1. **Clear caches**:
   ```bash
   drush cr
   ```

2. **Verify the module is enabled**:
   ```bash
   drush pm:list --filter=canvas_override
   ```

3. **Check module execution order**: Another module may be altering the form
   display after Canvas Override. Check for conflicting hook implementations.

---

## Layout Not Rendering on Content View

### Symptoms

Content with a per-content Canvas layout does not display the custom layout on
the full content view.

### Possible Causes

**1. Wrong view mode**

Canvas Override only affects the `full` view mode. If viewing in teaser or
another view mode, the per-content layout is not used.

**2. View display misconfigured**

Check that `field_canvas_layout` is enabled on the `full` view display with
the `canvas_naive_render_sdc_tree` formatter:

1. Go to **Structure > Content types > [Type] > Manage display**.
2. Select the **Full content** view mode.
3. Verify `field_canvas_layout` is visible and using the correct formatter.

**3. Empty field**

The `field_canvas_layout` field may be empty. Open the Canvas editor on the
content item and save a layout.

---

## Reset Not Working

### Symptoms

Clicking "Reset to default layout" does not revert the content to the
ContentTemplate default.

### Solutions

1. **Verify the route**: Visit `/node/{id}/canvas/reset` directly.

2. **Check permissions**: The user needs Canvas Override permissions to access
   the reset route.

3. **Clear caches** after resetting:
   ```bash
   drush cr
   ```

---

## Field Not Created on Content Type

### Symptoms

Enabling Canvas Override on a content type does not create the
`field_canvas_layout` field.

### Solutions

1. **Check for errors** in the Drupal log at **Reports > Recent log messages**
   (`/admin/reports/dblog`).

2. **Verify Canvas module**: The `component_tree` field type is provided by
   Canvas. Ensure Canvas is installed and working:
   ```bash
   drush pm:list --filter=canvas
   ```

3. **Create manually** if automatic creation fails:
   ```bash
   drush php:eval "\Drupal\canvas_override\Hook\CanvasOverrideHooks::ensureCanvasField('article');"
   ```

---

## Performance Issues

### Symptoms

Pages with per-content Canvas layouts load slowly.

### Solutions

1. **Check the component tree size**: Very large component trees with many
   nested components may affect rendering time. Simplify the layout if
   possible.

2. **Enable caching**: Ensure Drupal's render cache and page cache are
   enabled:
   ```bash
   drush state:set system.performance 'cache.page.max_age' 3600
   ```

3. **Review custom components**: Slow components affect all pages that use
   them, not just Canvas Override pages.

---

## Quick Diagnostic Commands

Use these commands to quickly diagnose Canvas Override issues:

```bash
# Check module status
drush pm:list --filter=canvas

# Check if Canvas Override is enabled on a content type
drush php:eval "echo \Drupal\node\Entity\NodeType::load('article')->getThirdPartySetting('canvas_override', 'enabled', FALSE) ? 'enabled' : 'disabled';"

# Check permissions for a role
drush role:perm:list editor | grep canvas

# Clear all caches
drush cr

# Check for errors in the log
drush watchdog:show --severity=error --count=10
```

## Getting Help

- Check the [FAQ](../faq.md) for common questions.
- Visit the [issue queue](https://www.drupal.org/project/issues/canvas_override)
  for known issues and support.
- For Canvas-specific issues, see the
  [Canvas issue queue](https://www.drupal.org/project/issues/canvas).
