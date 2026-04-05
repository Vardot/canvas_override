# Editing Per-Content Layouts

Learn how to use the Canvas editor to create unique layouts for individual content items.

## Overview

Once Canvas Override is enabled for a content type, editors can create unique
Canvas layouts for individual content items. Each content item gets its own
layout stored in a dedicated field, while the shared ContentTemplate default
remains unchanged.

**Key concept**: Canvas Override separates **layout** from **content**. The
Canvas editor handles visual layout (component arrangement, styling,
structure). Content fields (body, images, custom fields) are edited through the
standard Drupal content edit form.

## Opening the Canvas Editor

### From the Content Page

1. Navigate to the content item you want to customise.
2. Click the **Canvas** tab in the local tasks area.
3. The Canvas visual editor opens with the content's layout.

### From Content Listings

1. Go to **Content** (`/admin/content`).
2. Find the content item in the list.
3. Click the **Canvas** operation link in the operations column.

### Direct URL

Navigate to `/node/{id}/canvas` where `{id}` is the content item ID.

## First-Time Editing

When you open the Canvas editor on a content item for the first time, the
editor loads with a copy of the content type's shared ContentTemplate layout.
This gives you a starting point that matches the default look of the content
type.

From here you can:

- **Rearrange** existing components by dragging them to new positions.
- **Add** new components from the component library.
- **Remove** components you don't need.
- **Link** component properties to entity fields.
- **Style** components using the Canvas editor controls.

Changes are saved to the content item's own `field_canvas_layout` field and do
**not** affect the ContentTemplate default or other content items.

## Editing Workflow

### Step 1: Open the Canvas Tab

Navigate to the content item and click the **Canvas** tab.

### Step 2: Modify the Layout

Use the Canvas drag-and-drop editor to customise the page:

- **Add components**: Open the Library panel to browse available components and
  drag them onto the canvas.
- **Move components**: Drag components to reorder or reposition them within
  sections.
- **Configure components**: Select a component and use the properties panel to
  adjust settings.
- **Remove components**: Select a component and delete it.

### Step 3: Save

Click **Save** in the Canvas editor to persist the layout. The per-content
layout is saved to the content item's `field_canvas_layout` field.

### Step 4: Preview

Navigate to the content item's view page to see the per-content layout
rendered on the front end.

## Editing Content Fields

Content fields (body text, images, taxonomy terms, custom fields) are **not**
edited inside the Canvas editor. Canvas Override hides entity fields from the
Canvas editor's Page data panel to keep the editing experience focused on
layout.

To update field values:

1. Navigate to the content item.
2. Click the **Edit** tab (the standard Drupal content edit form).
3. Update fields as usual.
4. Save.

Field values are reflected in the Canvas layout through **field linking**.

### Field Linking

Editors can link component properties to entity fields. For example:

- A text component's content property linked to the **Body** field.
- An image component's source property linked to an **Image** field.
- A heading component linked to a custom **Subtitle** field.

When field values are updated through the edit form, the Canvas layout reflects
the changes automatically. This keeps content dynamic while giving the layout
its own visual structure.

## Resetting to the Default Layout

To discard a per-content layout and revert to the content type's shared
ContentTemplate default:

### Via the UI

1. Navigate to the content item.
2. Click the **Reset to default layout** tab.
3. Confirm the reset.

### Via Direct URL

Navigate to `/node/{id}/canvas/reset`.

### What Reset Does

- Clears the `field_canvas_layout` field value.
- The content reverts to the ContentTemplate default for its content type.
- The next time an editor opens the Canvas tab, the ContentTemplate layout is
  copied again as a fresh starting point.
- Other content items are not affected.

## Routes Reference

| Path | Description |
|------|-------------|
| `/node/{id}/canvas` | Open the per-content Canvas editor |
| `/node/{id}/canvas/reset` | Reset layout to the ContentTemplate default |

## Best Practices

### Start From the Default

The first time you open the editor, the ContentTemplate layout is copied for
you. Modify it rather than starting from scratch — this ensures consistency
with the rest of the site.

### Use Field Linking

Link component properties to entity fields so content updates are reflected
automatically. This avoids having to update content in two places.

### Keep Overrides Intentional

Only create per-content layouts for pages that truly need a unique
presentation. Too many overrides make it harder to maintain consistency when
the default template changes.

### Test Before Publishing

Preview the content to verify the layout looks correct before publishing.
Check both desktop and mobile viewports.

### Reset When Done

After a promotion, campaign, or event ends, reset the layout to reduce
maintenance overhead. The content reverts to the shared default.

## Troubleshooting

### Canvas Tab Not Visible

Check that:
1. Canvas Override is enabled on the content type.
2. Your user account has at least one Canvas Override permission.
3. The cache is cleared: `drush cr`

See [Troubleshooting](../2-admins/2-troubleshooting.md) for more details.

### Changes Not Appearing on the Front End

- Verify you clicked **Save** in the Canvas editor.
- Clear the Drupal cache: `drush cr`
- Check that the content is using the `full` view mode.

### Required Field Errors When Saving

Canvas Override handles required field validation automatically. If you see
errors, ensure both Canvas and Canvas Override are updated to the latest
version. See [Troubleshooting](../2-admins/2-troubleshooting.md).

## Next Steps

- [Use Cases](2-use-cases.md) — Real-world scenarios for per-content layouts
- [Permissions](../2-admins/1-permissions.md) — Understanding who can edit
  per-content layouts
- [Configuration](../2-admins/0-configuration.md) — Managing Canvas Override
  settings
