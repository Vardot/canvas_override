# Editing Per-Content Layouts

## Overview

Once Canvas Override is enabled for a content type, editors can create unique
Canvas layouts for individual content items. This guide covers the editor
workflow from opening the Canvas editor to resetting a layout.

## Opening the Canvas Editor

### From the Content Page

1. Navigate to the content item you want to edit.
2. Click the **Canvas** tab in the local tasks area.
3. The Canvas visual editor opens with the content's layout.

### From Content Listings

1. Go to **Content** (`/admin/content`).
2. Find the content item in the list.
3. Click the **Canvas** operation link in the operations column.

### Direct URL

Navigate to `/node/{id}/canvas` where `{id}` is the content ID.

## First-Time Editing

When you open the Canvas editor on a content item for the first time, the
editor loads with a copy of the content type's shared ContentTemplate layout.
This gives you a starting point that matches the default look of your content
type.

From here you can:

- Rearrange existing components.
- Add new components from the component library.
- Remove components you don't need.
- Link component properties to entity fields.

Changes are saved to the content item's own `field_canvas_layout` field and do
not affect the ContentTemplate default or other content.

## Editing Content Fields

Canvas Override separates **layout** from **content**:

- **Layout** is edited in the Canvas visual editor (component arrangement,
  styling, structure).
- **Content fields** (body, images, custom fields) are edited through the
  standard Drupal content edit form.

The Canvas editor hides the Page data panel for entity fields. Use the
content's **Edit** tab to update field values.

### Field Linking

Editors can link component properties to entity fields. For example, a text
component's content property can be linked to the body field. When the body
field is updated through the edit form, the Canvas layout reflects the change
automatically.

## Saving Changes

Click **Save** in the Canvas editor to persist your layout. The per-content
layout is saved to the content item's `field_canvas_layout` field.

## Resetting to the Default Layout

To discard a per-content layout and revert to the content type's shared
ContentTemplate default:

1. Navigate to the content item.
2. Click the **Reset to default layout** tab.
3. Confirm the reset.

This clears the `field_canvas_layout` value. The content will render using the
ContentTemplate default until a new per-content layout is created.

You can also reset by visiting `/node/{id}/canvas/reset`.

## Routes Reference

| Path | Description |
|------|-------------|
| `/node/{id}/canvas` | Open the per-content Canvas editor |
| `/node/{id}/canvas/reset` | Reset layout to the ContentTemplate default |

## Tips

- **Start from the default**: The first time you open the editor, the
  ContentTemplate layout is copied for you. Modify it rather than starting from
  scratch.
- **Use field linking**: Link component properties to entity fields so content
  updates are reflected automatically.
- **Test before publishing**: Preview the content to verify the layout looks
  correct before publishing.
- **Reset if needed**: You can always reset to the default layout and start
  over.

## Next Steps

- [Use Cases](2-use-cases.md) - Real-world scenarios for per-content layouts.
- [Permissions](../2-admins/1-permissions.md) - Understanding who can edit
  per-content layouts.
