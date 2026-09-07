# Canvas Override

Lets site builders enable per-content Canvas-based layout editing on the full content view mode for selected content types.

## Overview

Canvas Override provides Layout Builder-style per-content layout overrides using the [Canvas](https://www.drupal.org/project/canvas) module. When enabled for a content type, each content item can have its own Canvas layout stored in a `field_canvas_layout` field. Content without a custom layout falls back to the shared ContentTemplate default for the content type.

## Requirements

- Drupal ~11.3.0
- [drupal/canvas](https://www.drupal.org/project/canvas) ^1

## Installation

```bash
composer require drupal/canvas_override
drush en canvas_override
```

## Configuration

1. Go to **Structure > Content types** and edit a content type.
2. Open the **Canvas layout** fieldset in the vertical tabs.
3. Check **Enable per-node Canvas layout editing on the full content view mode**.
4. Save the content type.

This automatically:
- Creates a `field_canvas_layout` (component_tree) field on the content type.
- Configures the `full` and `default` view displays to render it.

## Usage

Once enabled for a content type, editors see a **Canvas** tab on each content item. Clicking it opens the per-content Canvas editor where they can visually compose a unique page layout.

### Routes

| Path | Description |
|------|-------------|
| `/node/{node}/canvas` | Open the per-content Canvas editor |
| `/node/{node}/canvas/reset` | Confirm and reset the content layout to the content type default |

The shared ContentTemplate default layout is edited through the **Edit
template** tab that Canvas itself provides on content. Canvas Override limits
that tab to users with the **Edit Canvas default template** permission (or the
per-type variant).

### Permissions

Canvas Override provides three permission levels:

- **Administer Canvas Override** — Enable or disable Canvas Override on content types.
- **Use Canvas Override for all content types** — Edit per-content layouts on any enabled content type.
- **Use Canvas Override for [type]** — Edit per-content layouts for a specific content type (generated dynamically).

## How It Works

- When content has a non-empty `field_canvas_layout`, its rendered output is
  replaced entirely with the per-content Canvas layout. Content without a
  custom layout falls back to the shared ContentTemplate default.
- Content type forms include a **Canvas layout** fieldset where administrators
  can enable or disable per-content layout editing.
- The `field_canvas_layout` field is created automatically when Canvas Override
  is enabled on a content type, including during config imports and recipe
  installations.
- Content fields (body, images, etc.) are hidden from the Canvas editor's Page
  data panel. Editors use the standard Drupal edit form for field data and the
  Canvas editor for layout.
- A **Canvas** operation link appears in content listings for users with the
  appropriate permission.

For more details, see the [documentation](docs/index.md).

## Testing

Canvas Override ships two layers of automated tests:

- **Automated functional acceptance testing** with
  [varbase-e2e](https://www.npmjs.com/package/varbase-e2e) (Playwright +
  Cucumber-js) under `tests/features/`, split into a Drupal Standard suite and
  a Drupal CMS suite. It drives a real browser to verify the content type form,
  the Canvas tabs, the per-content editor redirect, the reset action, tab
  access control, permission registration and accessibility.
- **PHPUnit** kernel / functional-javascript / unit coverage under `tests/src/`.

```bash
corepack enable && yarn install
yarn playwright install --with-deps chromium
LAUNCH_URL="https://your-site.ddev.site" yarn test            # Drupal Standard
LAUNCH_URL="https://your-site.ddev.site" yarn test:drupalcms  # Drupal CMS
```

See the [Testing documentation](docs/4-testing/0-overview.md) for setup, the
GitLab CI pipeline and the `gitlab-ci-local` runner.

## Maintainer

[Vardot](https://www.drupal.org/vardot)

## License

GPL-2.0-or-later
