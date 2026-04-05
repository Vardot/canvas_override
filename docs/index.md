# Canvas Override Documentation

A Drupal module that adds per-content layout editing to the Canvas page builder, giving editors the freedom to customise individual pages without affecting the content type default.

## What is Canvas Override?

The [Canvas](https://www.drupal.org/project/canvas) module provides a visual page-building experience for Drupal. By default, Canvas layouts are defined at the **content type** level through ContentTemplates — every page of that type shares the same layout structure.

Canvas Override extends this by allowing **per-content** layout overrides. Think of it as the difference between a shared content type display and per-content Layout Builder overrides, but for Canvas.

### Key Features

- **Per-Content Layouts**: Give individual pages their own unique Canvas layout while other content keeps the shared default
- **Visual Editing**: Use the full Canvas drag-and-drop editor to compose per-content layouts
- **Field Linking**: Connect component properties to entity fields so content updates are reflected automatically
- **Automatic Fallback**: Content without a custom layout falls back to the ContentTemplate default
- **Granular Permissions**: Control who can create overrides on a per-content-type basis
- **One-Click Reset**: Revert any page to the default layout when overrides are no longer needed
- **Zero Schema Changes**: The `field_canvas_layout` field is created and configured automatically when you enable Canvas Override on a content type

## Getting Started

### For Site Builders and Content Editors

If you manage content or build page layouts:

- [Installation and Setup](1-users/0-installation.md) — Get Canvas Override installed and running
- [Editing Per-Content Layouts](1-users/1-editing-layouts.md) — Learn how to use the Canvas tab to build custom page layouts
- [Use Cases](1-users/2-use-cases.md) — Real-world scenarios and workflows

### For Site Administrators

If you configure content types, permissions, and site settings:

- [Configuration](2-admins/0-configuration.md) — Enable Canvas Override on content types and manage settings
- [Permissions](2-admins/1-permissions.md) — Understand the three-tier permission system
- [Troubleshooting](2-admins/2-troubleshooting.md) — Common issues and solutions

### For Developers

If you are extending or integrating with Canvas Override:

- [Architecture](3-developers/0-architecture.md) — How Canvas Override integrates with Canvas and Drupal
- [Services and Extension Points](3-developers/1-hooks-and-services.md) — Service decorators, hooks, and extension points
- [API Reference](3-developers/2-api-reference.md) — Key classes, methods, and constants

## Quick Links

- [FAQ](faq.md) — Frequently asked questions
- [Drupal.org Project Page](https://www.drupal.org/project/canvas_override) — Downloads and releases
- [Canvas Module](https://www.drupal.org/project/canvas) — The parent page builder module
- [Issue Queue](https://www.drupal.org/project/issues/canvas_override) — Report bugs and request features

## Need Help?

- Check the [FAQ](faq.md) for common questions
- Review the relevant documentation section based on your role
- Visit the [issue queue](https://www.drupal.org/project/issues/canvas_override) for support
