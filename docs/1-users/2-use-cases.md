# Use Cases

Real-world scenarios where Canvas Override adds value to your Drupal site.

## Overview

Canvas Override is most useful when certain pages need unique layouts while
most content follows a standard template. Below are common scenarios where
per-content layouts solve real problems.

## Landing Pages

**Scenario**: Your marketing team needs campaign-specific landing pages with
unique hero sections, call-to-action blocks, and custom component arrangements.

**How Canvas Override helps**:

1. Enable Canvas Override on the "Landing page" content type.
2. Create a default ContentTemplate with a standard layout.
3. Override individual landing pages with campaign-specific designs.
4. Other landing pages continue using the default template.
5. When a campaign ends, reset the override to revert to the default.

**Benefit**: Marketers get full creative freedom on specific pages without
affecting the rest of the site.

## Featured Articles

**Scenario**: Most articles use the same layout, but featured or longform
articles need a different presentation with full-width images, pull quotes, and
custom section arrangements.

**How Canvas Override helps**:

1. Enable Canvas Override on the "Article" content type.
2. The standard template serves regular articles automatically.
3. Editors open the Canvas tab on featured articles to create unique layouts.
4. When an article is no longer featured, reset it to the default.

**Benefit**: No need to create separate content types for different article
styles. One content type, flexible layouts.

## Product Pages

**Scenario**: An e-commerce site has a standard product layout, but seasonal
promotions or flagship products need enhanced presentations.

**How Canvas Override helps**:

1. Enable Canvas Override on the "Product" content type.
2. Standard products use the ContentTemplate default.
3. Promotional products get custom layouts with additional components
   (countdown timers, comparison tables, video sections).
4. After the promotion ends, reset the layout.

**Benefit**: Promotional layouts are temporary and easy to manage without
affecting other products.

## Event Pages

**Scenario**: Recurring events use a standard format, but annual conferences or
special events need custom layouts with speaker grids, schedule components, and
sponsor sections.

**How Canvas Override helps**:

1. Enable Canvas Override on the "Event" content type.
2. Regular events use the default layout.
3. Special events get per-content layouts with event-specific components.

**Benefit**: Standard events remain low-maintenance while special events get
the visual treatment they deserve.

## Homepage and Key Pages

**Scenario**: The homepage needs a completely custom layout that doesn't match
any other page of the same content type.

**How Canvas Override helps**:

1. The homepage content gets its own Canvas layout.
2. Other content of the same type is unaffected.
3. The homepage layout can be updated independently of the default template.

**Benefit**: One-off pages are easy to manage without creating dedicated
content types.

## A/B Testing Layouts

**Scenario**: The team wants to test different page layouts to see which
performs better.

**How Canvas Override helps**:

1. Create a per-content layout on the test page with a new design.
2. Measure performance against control group pages using the default layout.
3. If the new layout wins, update the ContentTemplate default.
4. Reset the test page override.

**Benefit**: Test layout changes on individual pages without risk to the rest
of the site.

## Content Type Migration

**Scenario**: You are redesigning a content type's layout but want to migrate
content gradually rather than all at once.

**How Canvas Override helps**:

1. Update the ContentTemplate to the new layout design.
2. Content that still needs the old layout gets a per-content override
   preserving it.
3. As content is reviewed and updated, reset overrides to adopt the new
   default.
4. When migration is complete, all overrides are cleared.

**Benefit**: Gradual rollout with full control over which pages get the new
layout first.

## Per-Team Editorial Control

**Scenario**: Different editorial teams manage different content types. You
want the events team to customise event layouts without touching article
layouts.

**How Canvas Override helps**:

1. Grant "Use Canvas Override for Event" to the events team role.
2. Grant "Use Canvas Override for Article" to the articles team role.
3. Each team can only override layouts for their own content types.

**Benefit**: Teams work independently with clear boundaries. See
[Permissions](../2-admins/1-permissions.md) for setup details.

## Best Practices

1. **Start with a strong default template**. A well-designed ContentTemplate
   reduces the number of overrides needed.

2. **Use overrides sparingly**. Too many per-content layouts create
   maintenance overhead. Reserve them for pages that truly need unique
   presentations.

3. **Reset when done**. After a promotion, campaign, or event ends, reset the
   layout to reduce complexity.

4. **Use per-bundle permissions**. Limit who can create overrides to prevent
   layout inconsistency across the site.

5. **Document overrides**. Keep track of which pages have per-content layouts
   so the team knows what to update when the default template changes.

6. **Review regularly**. Periodically check which content items have overrides
   and whether they are still needed.

## Next Steps

- [Edit a per-content layout](1-editing-layouts.md) — Step-by-step editing guide
- [Configure permissions](../2-admins/1-permissions.md) — Control who can
  create overrides
- [Installation](0-installation.md) — Get started if you haven't already
