# Canvas Override Documentation

Canvas Override lets site builders enable per-content Canvas-based layout
editing on the full content view mode for selected content types. When enabled,
each content item can have its own Canvas layout stored in a dedicated field.
Content without a custom layout falls back to the shared ContentTemplate
default.

## What Is Canvas Override?

The [Canvas](https://www.drupal.org/project/canvas) module provides a visual
page-building experience for Drupal. By default, Canvas layouts are defined at
the **content type** level through ContentTemplates. Canvas Override extends
this by allowing **per-content** layout overrides, giving editors the freedom
to customise individual pages without affecting the content type default.

## Documentation by Role

### For Site Builders and Content Editors

- [Installation](1-users/0-installation.md) - Requirements and setup
- [Editing Per-Content Layouts](1-users/1-editing-layouts.md) - Using the
  Canvas tab to build custom page layouts
- [Use Cases](1-users/2-use-cases.md) - Real-world scenarios and workflows

### For Site Administrators

- [Configuration](2-admins/0-configuration.md) - Enabling Canvas Override on
  content types and managing settings
- [Permissions](2-admins/1-permissions.md) - Understanding the three-tier
  permission system
- [Troubleshooting](2-admins/2-troubleshooting.md) - Common issues and
  solutions

### For Developers

- [Architecture](3-developers/0-architecture.md) - How Canvas Override
  integrates with Canvas and Drupal
- [Services and Extension Points](3-developers/1-hooks-and-services.md) -
  Service decorators and extension points
- [API Reference](3-developers/2-api-reference.md) - Key classes, methods,
  and extension points
- [Testing](3-developers/3-testing.md) - Running and writing tests

## Quick Links

- [Drupal.org Project Page](https://www.drupal.org/project/canvas_override)
- [Canvas Module](https://www.drupal.org/project/canvas)
- [Issue Queue](https://www.drupal.org/project/issues/canvas_override)

## Frequently Asked Questions

See [FAQ](faq.md) for answers to common questions about Canvas Override.
