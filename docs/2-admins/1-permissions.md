# Permissions

## Overview

Canvas Override provides a three-tier permission system that gives
administrators fine-grained control over who can manage and use per-content
layouts.

## Available Permissions

### Administer Canvas Override

- **Machine name**: `administer canvas override`
- **Restricted**: Yes (only assignable by users with "administer permissions")
- **Purpose**: Enable or disable Canvas Override on content types.
- **Grants access to**: The Canvas layout fieldset on content type edit forms.
- **Also grants**: Full access to the Canvas tab on all Canvas
  Override-enabled content.

Assign this to site administrators who manage content type configuration.

### Use Canvas Override for All Content Types

- **Machine name**: `use canvas override`
- **Purpose**: Edit per-content Canvas layouts on any Canvas Override-enabled
  content type.
- **Grants access to**: The Canvas tab and reset functionality on all content
  whose content type has Canvas Override enabled.

Assign this to senior editors or content leads who work across all content
types.

### Use Canvas Override for [Content Type]

- **Machine name**: `use canvas override for {bundle}`
- **Purpose**: Edit per-content Canvas layouts only for a specific content
  type.
- **Generated dynamically**: One permission is created for each content type
  that has Canvas Override enabled.
- **Examples**:
  - `use canvas override for article`
  - `use canvas override for landing_page`
  - `use canvas override for event`

Assign these to editors who should only modify layouts for specific content
types.

## Permission Hierarchy

```
administer canvas override
  |-- Full access to all Canvas Override features
        |-- Enable/disable on content types
        |-- Canvas tab on all enabled content
        |-- Reset layout on all enabled content

use canvas override
  |-- Canvas tab and reset on ALL enabled content types

use canvas override for {bundle}
  |-- Canvas tab and reset on ONE specific content type
```

A user needs **at least one** of these permissions to see the Canvas tab on a
content item. The permissions are checked with OR logic: any matching
permission grants access.

## Access Check Logic

The Canvas tab visibility is determined by two conditions:

1. **Permission check**: The user has `administer canvas override`, OR
   `use canvas override`, OR `use canvas override for {bundle}` (where
   `{bundle}` is the content type).
2. **Content type check**: Canvas Override is enabled on the content type
   (third-party setting).

Both conditions must be true for the tab to appear.

## Configuring Permissions

### Via the Admin UI

1. Go to **People > Permissions** (`/admin/people/permissions`).
2. Search for "Canvas Override".
3. Check the appropriate boxes for each role.
4. Save permissions.

### Via Drush

```bash
# Grant global access to the "editor" role
drush role:perm:add editor 'use canvas override'

# Grant per-bundle access
drush role:perm:add editor 'use canvas override for article'

# Grant admin access
drush role:perm:add administrator 'administer canvas override'
```

## Recommended Setup by Role

| Role | Permission | Reason |
|------|-----------|--------|
| Administrator | Administer Canvas Override | Full control over configuration |
| Content Lead | Use Canvas Override (global) | Manages layouts across all types |
| Article Editor | Use Canvas Override for Article | Only edits article layouts |
| Event Editor | Use Canvas Override for Event | Only edits event layouts |
| Authenticated User | (none) | No layout editing access |

## Security Considerations

- **Restrict admin access**: The "Administer Canvas Override" permission is
  marked as restricted. Only grant it to trusted administrators.
- **Use per-bundle permissions**: Prefer per-bundle permissions over the global
  "Use Canvas Override" permission to follow the principle of least privilege.
- **Audit regularly**: Review which roles have Canvas Override permissions,
  especially after adding new content types.

## Caching

Permission checks are cached per:

- User permissions
- Content type entity

Clearing the Drupal cache (`drush cr`) refreshes permission checks.

## Next Steps

- [Configuration](0-configuration.md) - Enable Canvas Override on content
  types.
- [Troubleshooting](2-troubleshooting.md) - Permission-related issues.
