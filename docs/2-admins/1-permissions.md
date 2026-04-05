# Permissions

Learn how to control who can manage and use Canvas Override on your site.

## Overview

Canvas Override provides a three-tier permission system that gives
administrators fine-grained control over per-content layout editing. Permissions
are managed through Drupal's standard permissions interface.

## Available Permissions

### Administer Canvas Override

- **Machine name**: `administer canvas override`
- **Restricted**: Yes (only assignable by users with "administer permissions")
- **Purpose**: Enable or disable Canvas Override on content types.
- **Grants access to**: The Canvas layout fieldset on content type edit forms,
  plus full access to the Canvas tab on all Canvas Override-enabled content.

Assign this to **site administrators** who manage content type configuration.

### Use Canvas Override for All Content Types

- **Machine name**: `use canvas override`
- **Purpose**: Edit per-content Canvas layouts on any Canvas Override-enabled
  content type.
- **Grants access to**: The Canvas tab and reset functionality on all content
  items whose content type has Canvas Override enabled.

Assign this to **senior editors** or **content leads** who work across all
content types.

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

Assign these to **section editors** who should only modify layouts for their
content types.

## Permission Hierarchy

```
administer canvas override
  └── Full access to all Canvas Override features
        ├── Enable/disable on content types
        ├── Canvas tab on all enabled content
        └── Reset layout on all enabled content

use canvas override
  └── Canvas tab and reset on ALL enabled content types

use canvas override for {bundle}
  └── Canvas tab and reset on ONE specific content type
```

A user needs **at least one** of these permissions to see the Canvas tab on a
content item. The permissions are checked with OR logic: any matching
permission grants access.

## Access Check Logic

The Canvas tab visibility is determined by two conditions, both of which must
be true:

1. **Permission check**: The user has `administer canvas override`, OR
   `use canvas override`, OR `use canvas override for {bundle}` (where
   `{bundle}` is the content type).
2. **Content type check**: Canvas Override is enabled on the content type
   (third-party setting).

## Configuring Permissions

### Via the Admin UI

1. Go to **People > Permissions** (`/admin/people/permissions`).
2. Search for "Canvas Override".
3. Check the appropriate boxes for each role.
4. Click **Save permissions**.

### Via Drush

```bash
# Grant global access to the "editor" role
drush role:perm:add editor 'use canvas override'

# Grant per-bundle access
drush role:perm:add editor 'use canvas override for article'

# Grant admin access
drush role:perm:add administrator 'administer canvas override'

# Verify permissions for a role
drush role:perm:list editor | grep canvas
```

## Recommended Setup by Role

| Role | Permission | Reason |
|------|-----------|--------|
| Administrator | Administer Canvas Override | Full control over configuration and all layouts |
| Content Lead | Use Canvas Override (global) | Manages layouts across all content types |
| Article Editor | Use Canvas Override for Article | Only edits article layouts |
| Event Editor | Use Canvas Override for Event | Only edits event layouts |
| Authenticated User | (none) | No layout editing access |
| Anonymous User | (none) | Never grant Canvas Override permissions |

### Example: Multi-Team Setup

For a site with separate editorial teams:

```bash
# Events team
drush role:perm:add events_editor 'use canvas override for event'

# Marketing team
drush role:perm:add marketing_editor 'use canvas override for landing_page'

# Content team
drush role:perm:add content_lead 'use canvas override'

# Site admin
drush role:perm:add administrator 'administer canvas override'
```

## Security Considerations

- **Restrict admin access**: The "Administer Canvas Override" permission is
  marked as restricted. Only grant it to trusted administrators.
- **Prefer per-bundle permissions**: Use per-bundle permissions over the global
  "Use Canvas Override" permission to follow the principle of least privilege.
- **Audit regularly**: Review which roles have Canvas Override permissions,
  especially after adding new content types with Canvas Override enabled.
- **New content types**: When you enable Canvas Override on a new content type,
  a new permission is generated. Remember to assign it to the appropriate
  roles.

## Caching

Permission checks are cached per:

- User permissions
- Content type entity

Clearing the Drupal cache (`drush cr`) refreshes permission checks. If a user
reports they cannot see the Canvas tab after permissions are assigned, clear the
cache first.

## Troubleshooting

### User Cannot See Canvas Tab

**Check**:
1. Does the user have at least one Canvas Override permission?
2. Is Canvas Override enabled on the content type?
3. Has the cache been cleared? `drush cr`
4. Verify permissions via Drush:
   ```bash
   drush role:perm:list editor | grep canvas
   ```

### Per-Bundle Permission Not Appearing

**Check**:
1. Is Canvas Override enabled on the content type? The per-bundle permission is
   only generated for enabled types.
2. Clear the cache: `drush cr`
3. Verify the content type setting:
   ```bash
   drush php:eval "echo \Drupal\node\Entity\NodeType::load('article')->getThirdPartySetting('canvas_override', 'enabled', FALSE) ? 'enabled' : 'disabled';"
   ```

### Admin User Cannot See Configuration

**Check**:
- The "Administer Canvas Override" permission is restricted. Verify it is
  assigned at **People > Permissions**.
- User 1 (the super admin) bypasses permission checks and always has access.

## Next Steps

- [Configure Canvas Override](0-configuration.md) on content types
- [Troubleshoot issues](2-troubleshooting.md) with common problems
- [Review use cases](../1-users/2-use-cases.md) for permission strategies
