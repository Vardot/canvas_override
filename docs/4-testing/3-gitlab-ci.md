# GitLab CI and Local Runner

## Pipeline jobs

`.gitlab-ci.yml` runs four layers on every pipeline:

| Stage | Job | What it does |
|-------|-----|--------------|
| test | `phpunit` (matrix) | DrupalCI kernel + unit suites, group `canvas_override` |
| validate | `cspell`, `eslint` | Spell-check and JS / YAML lint on Node 20 (`allow_failure`) |
| test | `webship-js-test-drupal-core` | Drupal Standard profile + Canvas, runs `cucumber.js` |
| test | `webship-js-test-drupal-cms` | Drupal CMS distribution + Canvas, runs `cucumber.drupalcms.js` |
| .post | `pages` | Builds the MkDocs site for GitLab Pages (default branch only) |

### The Canvas patch

Both the PHPUnit build and the browser builds apply the Canvas
[#3567225](https://www.drupal.org/project/canvas_override) patch that
canvas_override depends on:

- The DrupalCI `composer` job reads `.gitlab-ci/patches.json` via
  `_COMPOSER_PATCHES_FILE`, so every PHPUnit and `webship-js-test-drupal-core` job inherits
  the patched `drupal/canvas`.
- The `webship-js-test-drupal-cms` job builds a fresh `drupal/cms` project and
  registers the same patch through `composer config extra.patches` before
  requiring Canvas.

### How the browser jobs seed the site

The webship-js jobs build the site and apply the two test recipes with
`drush recipe`: `tests/fixtures/marketing_campaign_test_base` (the ready type with Canvas
Override on) and `tests/fixtures/department_test_base` (a plain type with it off).
Everything else — login, user provisioning, creating the seeded campaign node,
and enabling Canvas Override on the Department type — happens inside the
scenarios, through the browser.

Each job uploads `tests/reports/`, `tests/screenshots/` and `tests/videos/` as
artifacts (kept 7 days) and the Cucumber JUnit report.

## Running locally with gitlab-ci-local

The drupalci templates and the browser jobs cannot be reproduced outside
drupalci, so `.gitlab-ci-local.yml` mirrors only the jobs that run anywhere:

```bash
# All reproducible jobs
gitlab-ci-local --file .gitlab-ci-local.yml

# Just the linters
gitlab-ci-local --file .gitlab-ci-local.yml eslint
gitlab-ci-local --file .gitlab-ci-local.yml cspell

# Validate every Gherkin step resolves (no browser, no live site)
gitlab-ci-local --file .gitlab-ci-local.yml webship-js-dry-run

# Build the documentation site exactly as the Pages job does
gitlab-ci-local --file .gitlab-ci-local.yml pages
```

`gitlab-ci-local` needs Docker; each job runs in the same image its real
counterpart uses (`node:20`, `python:3.12-slim`).
