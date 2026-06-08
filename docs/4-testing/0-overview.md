# Testing Overview

Canvas Override ships two complementary layers of automated tests.

## Test layers

| Layer | Tooling | Location | What it covers |
|-------|---------|----------|----------------|
| **Functional acceptance** | [webship-js](https://www.npmjs.com/package/webship-js) (Playwright + Cucumber-js) | `tests/features/`, `tests/step-definitions/` | Real-browser, black-box behaviour on both Drupal Standard and Drupal CMS: the content type form, the Canvas tabs, the per-content editor redirect, the reset action, tab access control, permission registration and accessibility. |
| **PHPUnit** | DrupalCI | `tests/src/Unit/` | White-box coverage: per-bundle permission generation in isolation. |

The two layers overlap on purpose. PHPUnit drives the module from inside a
known Drupal kernel; webship-js drives a fully-built site the same way a real
editor would, on the actual front-end themes (Olivero / Claro on Standard,
Gin / Mercury on Drupal CMS).

## What the acceptance suite proves

Driven entirely through the browser — no Drush, no shell from inside the
scenarios — the webship-js suite asserts that:

- the **Canvas layout** fieldset appears on the content type form and gates on
  the `administer canvas override` permission;
- enabling Canvas Override creates the `field_canvas_layout` field and shows
  the confirmation message;
- the **Canvas Override** and **Reset Canvas layout** tabs render on enabled
  content and disappear for users without rights;
- a **Canvas** operation link appears in the content list;
- the Canvas Override tab redirects to the per-content Canvas editor
  (`/canvas/editor/node/{nid}`) and the editor mounts with the Page data panel
  hidden;
- the reset action clears the layout and returns the editor to the content with
  the *“Canvas layout reset to the shared default template.”* message;
- the global and per-bundle permissions are registered on the permissions page;
- the key admin surfaces have no serious accessibility violations.

## Requirements

- Node.js **>= 20** (webship-js 2.0 declares `engines.node ">=20"`).
- A running Drupal site with `canvas` and `canvas_override` enabled, the Canvas
  [#3567225](https://www.drupal.org/project/canvas_override) patch applied, and
  a custom "Marketing campaign" content type with Canvas Override turned on
  plus a seeded campaign node reachable at `/canvas-override-test`.

See [Automated Functional Acceptance Testing](1-automated-functional-acceptance-testing.md) for the layout and
[Running the Suite](2-running-tests.md) to execute it.
