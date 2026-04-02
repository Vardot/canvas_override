# Use Cases

## Overview

Canvas Override is most useful when certain pages need unique layouts while
most content follows a standard template. Below are common scenarios where
per-content layouts add value.

## Landing Pages

**Scenario**: Your marketing team needs campaign-specific landing pages with
unique hero sections, call-to-action blocks, and custom component arrangements.

**How Canvas Override helps**:

- Enable Canvas Override on the "Landing page" content type.
- Create a default ContentTemplate with a standard layout.
- Override individual landing pages with campaign-specific designs.
- Other landing pages continue using the default template.

## Featured Articles

**Scenario**: Most articles use the same layout, but featured or longform
articles need a different presentation with full-width images, pull quotes, and
custom section arrangements.

**How Canvas Override helps**:

- Enable Canvas Override on the "Article" content type.
- The standard template serves regular articles.
- Editors open the Canvas editor on featured articles to create unique layouts.
- When a featured article is no longer highlighted, reset it to the default.

## Product Pages

**Scenario**: An e-commerce site has a standard product layout, but seasonal
promotions or flagship products need enhanced presentations.

**How Canvas Override helps**:

- Enable Canvas Override on the "Product" content type.
- Standard products use the ContentTemplate default.
- Promotional products get custom layouts with additional components.
- After the promotion ends, reset the layout.

## Event Pages

**Scenario**: Recurring events use a standard format, but annual conferences or
special events need custom layouts with speaker grids, schedule components, and
sponsor sections.

**How Canvas Override helps**:

- Enable Canvas Override on the "Event" content type.
- Regular events use the default layout.
- Special events get per-content layouts with event-specific components.

## Homepage or Key Pages

**Scenario**: The homepage needs a completely custom layout that doesn't match
any other page of the same content type.

**How Canvas Override helps**:

- The homepage content gets its own Canvas layout.
- Other content of the same type is unaffected.
- The homepage layout can be updated independently.

## A/B Testing Layouts

**Scenario**: The team wants to test different page layouts to see which
performs better.

**How Canvas Override helps**:

- Create a per-content layout on the test page.
- Swap layouts by editing and resetting.
- Keep the default template unchanged for control group pages.

## Content Type Migration

**Scenario**: You're redesigning a content type's layout but want to migrate
content gradually rather than all at once.

**How Canvas Override helps**:

- Create the new layout as the ContentTemplate default.
- Content that needs the old layout gets per-content overrides preserving it.
- As content is reviewed and updated, reset overrides to adopt the new default.

## Per-Bundle Permission Control

**Scenario**: Different editorial teams manage different content types. You
want the events team to customise event layouts without accessing article
layouts.

**How Canvas Override helps**:

- Grant "Use Canvas Override for Event" to the events team.
- Grant "Use Canvas Override for Article" to the articles team.
- Each team can only override layouts for their content types.

## Best Practices

1. **Start with a strong default template**: A well-designed ContentTemplate
   reduces the need for per-content overrides.
2. **Use overrides sparingly**: Too many per-content layouts create maintenance
   overhead. Reserve them for pages that truly need unique presentations.
3. **Reset when done**: After a promotion or campaign ends, reset the layout to
   reduce complexity.
4. **Use per-bundle permissions**: Limit who can create overrides to prevent
   layout inconsistency across the site.
5. **Document overrides**: Keep track of which pages have per-content layouts so
   the team knows what to update when the default template changes.
