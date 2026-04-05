# Installation and Setup

This guide will help you install and set up the Canvas Override module on your Drupal site.

## Requirements

### Core Requirements

- Drupal 11.3.0 or later
- PHP 8.3 or later

### Required Modules

- [Canvas](https://www.drupal.org/project/canvas) ^1 — The visual page builder that Canvas Override extends
- **Node** (Drupal core) — Content management
- **Field** (Drupal core) — Field API

## Installation Methods

### Method 1: Using Composer (Recommended)

```bash
composer require drupal/canvas_override
drush en canvas_override
```

### Method 2: Using Drush

If the module is already downloaded:

```bash
drush en canvas_override
```

### Method 3: Manual Installation

1. Download the module from [Drupal.org](https://www.drupal.org/project/canvas_override).
2. Extract the archive to your `modules/contrib` directory.
3. Navigate to **Administration > Extend** (`/admin/modules`).
4. Find "Canvas Override" in the module list.
5. Check the box and click **Install**.

## Verifying Installation

After installation, verify that the module is working:

1. Navigate to **Administration > Extend** (`/admin/modules`) and confirm
   Canvas Override appears in the list.
2. Go to **People > Permissions** (`/admin/people/permissions`) and search for
   "Canvas Override" — you should see the permission entries.
3. Edit any content type at **Structure > Content types** — the **Canvas
   layout** fieldset should appear in the vertical tabs.

## Initial Configuration

After installing, you need to enable Canvas Override on each content type where
you want per-content layouts.

### Step 1: Enable on a Content Type

1. Go to **Structure > Content types** (`/admin/structure/types`).
2. Click **Edit** on the content type you want to enable.
3. Open the **Canvas layout** fieldset in the vertical tabs.
4. Check **Enable per-content Canvas layout editing on the full content view
   mode**.
5. Click **Save**.

This automatically creates and configures the `field_canvas_layout` field on
the content type. No manual field setup is needed.

### Step 2: Assign Permissions

Navigate to **People > Permissions** (`/admin/people/permissions`) and assign
the appropriate permissions:

| Role | Recommended Permission |
|------|----------------------|
| Administrator | Administer Canvas Override |
| Content Lead | Use Canvas Override (all types) |
| Section Editor | Use Canvas Override for [specific type] |

See the [Permissions guide](../2-admins/1-permissions.md) for detailed setup.

### Step 3: Verify the Canvas Tab

1. Navigate to a content item whose type has Canvas Override enabled.
2. You should see a **Canvas** tab in the local tasks area.
3. Click it to open the per-content Canvas editor.

If the tab does not appear, check the [Troubleshooting guide](../2-admins/2-troubleshooting.md).

## What Happens After Installation?

Once you enable Canvas Override on a content type:

- **A new field is created** — `field_canvas_layout` (component_tree) is added
  to the content type automatically.
- **View displays are configured** — The `full` and `default` view modes are
  set up with the correct Canvas renderer.
- **A Canvas tab appears** — Editors with the right permission see a Canvas tab
  on each content item.
- **A per-bundle permission is generated** — "Use Canvas Override for
  [type]" appears on the permissions page.
- **Existing content is unaffected** — Until an editor creates a per-content
  layout, content continues rendering with the ContentTemplate default.

## Installation Checklist

- [ ] Canvas module is installed and working
- [ ] Canvas Override module is enabled
- [ ] At least one content type has Canvas Override enabled
- [ ] Permissions are assigned to appropriate roles
- [ ] Editors can see the Canvas tab on content items
- [ ] Cache is cleared (`drush cr`)

## Troubleshooting

### Module Won't Enable

**Issue**: Error when trying to enable Canvas Override.

**Solutions**:
- Ensure the Canvas module is installed: `drush pm:list --filter=canvas`
- Clear the cache: `drush cr`
- Check PHP error logs for specific errors

### Canvas Tab Not Appearing After Installation

**Issue**: Permissions and configuration are set but the tab is missing.

**Solutions**:
- Clear the cache: `drush cr`
- Verify the content type has Canvas Override enabled in its settings
- Check that the user has at least one Canvas Override permission
- See [Troubleshooting](../2-admins/2-troubleshooting.md) for more details

## Next Steps

- [Edit a per-content layout](1-editing-layouts.md) using the Canvas editor
- [Configure permissions](../2-admins/1-permissions.md) for your team
- [Review configuration options](../2-admins/0-configuration.md) for
  administrators
- [Explore use cases](2-use-cases.md) for real-world examples
