# Running the Suite

## Prerequisites

1. A running Drupal site (DDEV, Lando, or any local web server) — Drupal core
   (Standard) or Drupal CMS — with:
   - `canvas` and `canvas_override` available, and the Canvas
     [#3567225](https://www.drupal.org/project/canvas_override) patch applied to
     `drupal/canvas`;
   - the two test recipes applied (they enable the modules, create the content
     types and the default Canvas layout):
     ```bash
     drush recipe modules/contrib/canvas_override/tests/fixtures/marketing_campaign_test_base
     drush recipe modules/contrib/canvas_override/tests/fixtures/department_test_base
     ```
   The `@setup` feature creates the seeded campaign node at
   `/web-design-services` through the browser on the first run.
2. Node.js **>= 20**.

## Install the test dependencies

```bash
cd canvas_override
corepack enable
yarn install
yarn playwright install --with-deps chromium
```

varbase-e2e pins `@cucumber/cucumber` to the 12.x line (Node 20 compatible) via a
`resolutions` entry so the whole suite runs on the same Cucumber instance.

## Point the suite at your site

The base URL comes from the `LAUNCH_URL` environment variable (default
`http://localhost`):

```bash
export LAUNCH_URL="https://canvas-override.ddev.site"
```

The seeded content alias, the enabled bundle and the test users are configured
in `cucumber.shared.js` and can be overridden per run:

```bash
export CANVAS_OVERRIDE_BUNDLE="marketing_campaign"
export CANVAS_OVERRIDE_NODE_ALIAS="/canvas-override-test"
```

## Run

```bash
# Drupal Standard suite (default)
yarn test

# Drupal CMS suite
yarn test:drupalcms

# A specific browser
yarn test:firefox
yarn test:webkit

# A subset by tag
yarn test -- --tags "@smoke"
yarn test -- --tags "@canvas and not @wip"

# A single feature file
yarn test -- tests/features/drupal/03-02-01-reset-layout.feature
```

## Validate without a browser

A `--dry-run` checks that every Gherkin step resolves to a definition without
launching a browser or needing a live site — handy in CI gates and pre-commit:

```bash
VARBASE_E2E_REPORT_DISABLE=1 yarn test -- --dry-run
VARBASE_E2E_REPORT_DISABLE=1 yarn test:drupalcms -- --dry-run
```

`VARBASE_E2E_REPORT_DISABLE=1` stops varbase-e2e's automatic HTML/PDF report hook,
which otherwise looks for the default report path on exit.

## Reports

Generate the HTML and PDF report after a run:

```bash
node ./node_modules/@vardot/varbase-e2e/bin/generate-reports.js \
  --json tests/reports/drupal/cucumber_report.json \
  --out  tests/reports/drupal/cucumber_report.html \
  --format all \
  --pdf-out tests/reports/drupal/cucumber_report.pdf
```

Failure screenshots land in `tests/screenshots/`, failure videos in
`tests/videos/`, and the JSON / HTML / PDF reports in `tests/reports/`. All
three directories are git-ignored except for their README placeholders.
