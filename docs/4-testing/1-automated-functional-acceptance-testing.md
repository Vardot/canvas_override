# Automated Functional Acceptance Testing

The functional acceptance suite uses [varbase-e2e](https://www.npmjs.com/package/varbase-e2e)
**2.0.x** — a thin Behaviour-Driven Development layer over Playwright and
Cucumber-js. Scenarios are written in plain Gherkin and driven through a real
Chromium / Firefox / WebKit browser.

## Layout

```text
canvas_override/
├── cucumber.js                 # Drupal Standard suite config (default)
├── cucumber.drupalcms.js       # Drupal CMS suite config
├── cucumber.shared.js          # worldParameters shared by both suites
├── playwright.config.ts        # browser launch + context options
├── tsconfig.json
└── tests/
    ├── features/
    │   ├── drupal/             # Drupal Standard profile scenarios
    │   └── drupalcms/          # Drupal CMS distribution scenarios
    ├── step-definitions/
    │   └── canvas_override.steps.js   # custom Canvas Override steps
    ├── selectors/
    │   ├── canvas_override.json        # Canvas Override named selectors
    │   ├── cms-drupal-core-claro.json  # generic Claro admin selectors
    │   └── cms-drupal-cms-gin.json     # generic Gin admin selectors
    ├── reports/                # generated JSON / HTML / PDF (git-ignored)
    ├── screenshots/            # failure screenshots (git-ignored)
    └── videos/                 # failure videos (git-ignored)
```

The two suites are split by Drupal flavour so reports, screenshots and videos
never collide:

- `cucumber.js` loads only `tests/features/drupal/**` (Drupal Standard).
- `cucumber.drupalcms.js` loads only `tests/features/drupalcms/**` (Drupal CMS).

Both pull their `worldParameters` from `cucumber.shared.js` and layer their own
report / screenshot / video subdirectories on top.

## Test fixture: two Drupal recipes

The suite is built around the marketing-team use case (see
[Use Cases](../1-users/2-use-cases.md)) and ships two Drupal recipes under
`tests/fixtures/`, applied with `drush recipe <path>`:

| Recipe | Provides |
|--------|----------|
| `tests/fixtures/marketing_campaign_test_base` | The **ready** custom *Marketing campaign* content type with image / body / tags fields, a nice non-empty default full-content Canvas layout, and Canvas Override **already activated**. This is the type the suite reaches at `/canvas-override-test`. |
| `tests/fixtures/department_test_base` | A plain *Department* content type with Canvas Override **not activated** — the suite turns it on through the content type form to prove the enable flow creates the Canvas Layout field. |

Both recipes apply cleanly on **Drupal core (Standard)** and **Drupal CMS**:
field storages the profile already ships are kept; the ones it does not are
created. The ready recipe enables Canvas Override through a config action
*after* the bundle and fields exist, so canvas_override's field/display setup
runs cleanly.

The one seeded campaign node is created through the browser by the `@setup`
feature (no PHP fixture). Override the bundle, label and alias per run with the
`CANVAS_OVERRIDE_BUNDLE`, `CANVAS_OVERRIDE_BUNDLE_LABEL` and
`CANVAS_OVERRIDE_NODE_ALIAS` environment variables (defaults in
`cucumber.shared.js`).

## Scenario coverage

| Feature file | Tags | Asserts |
|--------------|------|---------|
| `01-01-01-users-login` | `@setup` | Webmaster login, creating the seeded campaign through the browser, provisioning per-role users |
| `01-02-01-canvas-override-smoke` | `@smoke` | Seeded content and home page load with no JS errors |
| `02-01-01-content-type-form` | `@admin @form` | The Canvas layout fieldset, the enable checkbox, field creation |
| `02-02-01-canvas-tabs-and-operations` | `@admin` | The Canvas Override / Reset tabs and the content-list operation link |
| `03-01-01-canvas-editor` | `@canvas @editor` | Tab redirect to `/canvas/editor/node/{nid}`, editor mount, hidden Page data panel |
| `03-02-01-reset-layout` | `@reset` | Reset clears the layout and returns to the content with the success message |
| `04-01-01-access-control` | `@access` | The full Canvas + Reset tab access matrix, one scenario per permission tier (admin, global, per-bundle, reset, template, content editor, authenticated) |
| `04-02-01-permissions` | `@permissions` | Global and per-bundle permissions are registered |
| `04-03-01-access-control-routes` | `@access @routes` | Direct `/canvas` and `/canvas/reset` routes return Access denied for unauthorised tiers |
| `05-01-01-accessibility` | `@accessibility` | No serious accessibility violations on key surfaces |

The permission tiers come from the `canvas_override_roles_test_base` recipe —
one role per Canvas Override permission. The Drupal CMS suite mirrors a
representative slice of the same behaviours on the Gin admin theme.

## Named selectors

Scenarios never embed raw CSS. Every selector is registered by name in
`tests/selectors/canvas_override.json` and resolved at run time. A typo surfaces
a one-line *“Did you mean …?”* hint instead of a silent miss. Run
`Then print css selectors` in any scenario to dump the full catalogue.

## Custom steps

`tests/step-definitions/canvas_override.steps.js` adds the Canvas-Override-specific
vocabulary on top of varbase-e2e's built-in library:

- `Given I am a logged in user with the "<role>" user`
- `Given I add testing users`
- `Given I enable Canvas Override on the "<bundle>" content type`
- `Given I disable Canvas Override on the "<bundle>" content type`
- `Given I am on the Canvas Override test content`
- `Then the "<name>" element should be visible | have a count of N | …`

Everything else (navigation, accessibility audits, JS-error capture, the
`url should match` assertion) comes straight from varbase-e2e.
