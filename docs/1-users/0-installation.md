# Installation

## Requirements

- **Drupal**: 11.3.0 or later
- **PHP**: 8.3 or later
- **Canvas module**: ^1 (`drupal/canvas`)
- **Content module**: Included in Drupal core (`node`)
- **Field module**: Included in Drupal core

## Installation

### Using Composer (Recommended)

```bash
composer require drupal/canvas_override
```

### Enable the Module

Using Drush:

```bash
drush en canvas_override
```

Or via the Drupal admin UI:

1. Go to **Extend** (`/admin/modules`).
2. Search for "Canvas Override".
3. Check the checkbox and click **Install**.

## Verify Installation

After installation, verify that:

1. The module appears in the list at **Extend** (`/admin/modules`).
2. The permissions page at **People > Permissions** shows "Canvas Override"
   permissions.
3. Content type edit forms show the **Canvas layout** fieldset.

## Initial Configuration

After installing, you need to enable Canvas Override on each content type where
you want per-content layouts:

1. Go to **Structure > Content types**.
2. Click **Edit** on the content type.
3. Open the **Canvas layout** fieldset in the vertical tabs.
4. Check **Enable per-content Canvas layout editing on the full content view
   mode**.
5. Save the content type.

This automatically creates and configures the `field_canvas_layout` field.

## Assigning Permissions

Assign the appropriate permissions at **People > Permissions**:

- Give **Administer Canvas Override** to site administrators.
- Give **Use Canvas Override for all content types** or per-bundle permissions
  to content editors.

See the [Permissions guide](../2-admins/1-permissions.md) for details.

## Next Steps

- [Editing Per-Content Layouts](1-editing-layouts.md) - Learn how to use the
  Canvas editor on individual content items.
- [Configuration](../2-admins/0-configuration.md) - Detailed configuration
  options for administrators.
