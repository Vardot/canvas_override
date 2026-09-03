const { Given, Then, When } = require('@cucumber/cucumber');

// Playwright's web-first assertions provide the canonical "smart wait":
// each matcher auto-retries until it passes or the timeout elapses, so no
// sleep-then-check is ever needed.
const { expect } = require('playwright/test');

const {
  friendly,
  gotoUrl,
  waitForPageLoad,
} = require('@vardot/varbase-e2e/tests/step-definitions/varbase-e2e');

/**
 * Run a step body and rethrow any failure as a tester-friendly error.
 *
 * @param {Function} body  - async function performing the step.
 * @param {string} message - human-readable description for failures.
 */
async function attempt(body, message) {
  try {
    await body();
  } catch (err) {
    throw friendly(message, err);
  }
}

/**
 * Provision every non-admin user from worldParameters.users via Drupal's
 * /admin/people/create form. Entries flagged isAdmin: true are skipped (the
 * site-install Webmaster already exists). Idempotent.
 *
 * Example #1: Given I add testing users
 * Example #2: And I add the testing users
 */
Given(/^(?:I |we )?add( the)? testing users$/, async function (theCase) {
  const users = this.parameters.users || {};
  await attempt(async () => {
    for (const [key, info] of Object.entries(users)) {
      if (info.isAdmin) continue;
      await gotoUrl(
        this.page,
        `${this.parameters.launchUrl}/admin/people/create`,
      );
      // Fill values + tick role checkboxes via JS. Drupal CMS's Gin theme
      // wraps the password-confirm widget in an `is-initial` collapsed state
      // that hides the password inputs until an interaction event fires - a
      // normal Playwright fill() then fails actionability. Setting `.value`
      // in the page context bypasses the hidden-input check, and the form
      // posts the assigned values just the same.
      await this.page.evaluate(
        ({ data, sel }) => {
          const set = (s, val) => {
            const el = document.querySelector(s);
            if (el) el.value = val;
          };
          set(sel.name, data.username);
          set(sel.mail, data.email || `${data.username}@example.test`);
          set(sel.pass1, data.password);
          set(sel.pass2, data.password);
          for (const role of data.roles || []) {
            const cb = document.querySelector(`input[name="roles[${role}]"]`);
            if (cb) cb.checked = true;
          }
        },
        {
          data: info,
          sel: {
            name: resolveName(this, 'drupal name field'),
            mail: resolveName(this, 'drupal mail field'),
            pass1: resolveName(this, 'drupal pass1 field'),
            pass2: resolveName(this, 'drupal pass2 field'),
          },
        },
      );
      // JS-click sidesteps the Gin sticky form-actions overlay (Drupal CMS).
      await this.page.evaluate(
        (submit) => {
          const b = document.querySelector(submit);
          if (b) b.click();
        },
        resolveName(this, 'drupal form submit'),
      );
      await waitForPageLoad(this.page);
    }
  }, 'Could not provision the testing users');
});

/**
 * Toggle the Canvas Override "Enable per-content Canvas layout editing"
 * checkbox on a content type's edit form and save.
 *
 * Must be invoked while logged in as a user with both 'administer content
 * types' and 'administer canvas override'. The checkbox lives in the
 * "Canvas layout" vertical-tab fieldset on the node type form; it is present
 * in the DOM even while that tab is collapsed, so the value can be set
 * directly. The submit handler persists the third-party setting and, on
 * first enable, creates the field_canvas_layout field.
 *
 * @param {object} world   - Cucumber world (page + parameters).
 * @param {string} bundle  - The node type machine name.
 * @param {boolean} enable - TRUE to enable, FALSE to disable.
 */
async function setCanvasOverride(world, bundle, enable) {
  await gotoUrl(
    world.page,
    `${world.parameters.launchUrl}/admin/structure/types/manage/${bundle}`,
  );
  await waitForPageLoad(world.page);
  const checkboxSel = resolveName(world, 'canvas override enable checkbox');
  const checkbox = world.page.locator(checkboxSel);
  if ((await checkbox.count()) === 0) {
    throw new Error(
      'Canvas layout checkbox not found - is canvas_override installed and are you an administrator?',
    );
  }
  // Set the checkbox state directly; the node type form carries vertical-tabs
  // JS that can leave the control off-screen.
  await world.page.evaluate(
    ({ on, sel }) => {
      const cb = document.querySelector(sel);
      if (cb) cb.checked = on;
    },
    { on: enable, sel: checkboxSel },
  );
  // JS-click the submit button (sidesteps Gin's sticky form-actions overlay).
  await world.page.evaluate(
    (submit) => {
      const b =
        document.querySelector(submit) ||
        [
          ...document.querySelectorAll(
            'input[type="submit"], button[type="submit"]',
          ),
        ].find((el) => /save/i.test(el.value || el.textContent || ''));
      if (b) b.click();
    },
    resolveName(world, 'drupal form submit'),
  );
  await waitForPageLoad(world.page);
}

/**
 * Enable Canvas Override on a content type through the content type form.
 *
 * Example #1: Given I enable Canvas Override on the "page" content type
 * Example #2: And I enable Canvas Override on the "article" content type
 */
Given(
  /^I enable Canvas Override on the "([^"]*)" content type$/,
  async function (bundle) {
    await attempt(
      () => setCanvasOverride(this, bundle, true),
      `Could not enable Canvas Override on the "${bundle}" content type`,
    );
  },
);

/**
 * Disable Canvas Override on a content type through the content type form.
 *
 * Example: Given I disable Canvas Override on the "page" content type
 */
Given(
  /^I disable Canvas Override on the "([^"]*)" content type$/,
  async function (bundle) {
    await attempt(
      () => setCanvasOverride(this, bundle, false),
      `Could not disable Canvas Override on the "${bundle}" content type`,
    );
  },
);

/**
 * Create the seeded Marketing campaign node through the browser node-add form.
 *
 * The content TYPE, its fields and the default Canvas layout are provided by
 * the `tests/fixtures/marketing_campaign` recipe; this step adds the one
 * published campaign node the suite reaches at the configured path alias. It
 * is idempotent: if a campaign already lives at the alias the step does
 * nothing, so re-runs against a persistent site stay clean.
 *
 * Field values are set in the page context (like "I add testing users") so the
 * step is robust across the Olivero/Claro and Gin node forms, where CKEditor,
 * the autocomplete tags widget and the collapsed URL-alias details would
 * otherwise defeat plain actionability.
 *
 * Example #1: Given I create the Canvas Override test campaign
 * Example #2: And I create the Canvas Override test campaign
 */
Given(
  /^(?:I |we )?create the Canvas Override test campaign$/,
  async function () {
    const co = this.parameters.canvasOverride || {};
    const bundle = co.bundle || 'marketing_campaign';
    const alias = co.nodeAlias || '/canvas-override-test';
    const title = co.nodeTitle || 'Professional Web Design Services';
    await attempt(async () => {
      // Idempotency: if the alias already resolves to the campaign, skip.
      await gotoUrl(this.page, `${this.parameters.launchUrl}${alias}`);
      const heading = await this.page
        .getByRole('heading', { name: title })
        .count();
      if (heading > 0) {
        return;
      }
      await gotoUrl(
        this.page,
        `${this.parameters.launchUrl}/node/add/${bundle}`,
      );
      await waitForPageLoad(this.page);
      await this.page.evaluate(
        ({ data, sel }) => {
          const set = (s, val) => {
            const el = document.querySelector(s);
            if (el) {
              el.value = val;
              el.dispatchEvent(new Event('input', { bubbles: true }));
              el.dispatchEvent(new Event('change', { bubbles: true }));
            }
          };
          set(sel.title, data.title);
          // Free-tagging tags autocomplete - a new term is created on save.
          set(sel.tags, data.tag);
          // Body textarea (present before/under CKEditor).
          set(sel.body, data.body);
          // Drupal CMS ships pathauto with "Generate automatic URL alias"
          // checked, which ignores a manually typed alias. Uncheck it first.
          const pathauto = document.querySelector(sel.pathauto);
          if (pathauto) {
            pathauto.checked = false;
            pathauto.dispatchEvent(new Event('change', { bubbles: true }));
          }
          // URL alias lives in a collapsed details element but is in the DOM.
          set(sel.alias, data.alias);
        },
        {
          data: {
            title,
            tag: co.nodeTag || 'Web Design',
            body: co.nodeBody || '',
            alias,
          },
          sel: {
            title: resolveName(this, 'node title field'),
            tags: resolveName(this, 'node tags field'),
            body: resolveName(this, 'node body field'),
            pathauto: resolveName(this, 'node path pathauto checkbox'),
            alias: resolveName(this, 'node path alias field'),
          },
        },
      );
      // JS submit sidesteps Gin's sticky form-actions overlay (Drupal CMS).
      await this.page.evaluate(
        (submit) => {
          const b = document.querySelector(submit);
          if (b) b.click();
        },
        resolveName(this, 'drupal form submit'),
      );
      await waitForPageLoad(this.page);
    }, 'Could not create the Canvas Override test campaign');
  },
);

/**
 * Resolve a varbase-e2e named selector from the world registry.
 *
 * The registry (`world.__selectorsCss`) is hydrated by varbase-e2e from
 * `cucumber.shared.js`'s `selectors.files` list - see tests/selectors/*.json
 * for the catalog. Throws when the name is unknown so a typo never silently
 * passes through to Playwright as a literal CSS string.
 */
function resolveName(world, name) {
  const css = world.__selectorsCss || {};
  const key = name.trim();
  if (Object.prototype.hasOwnProperty.call(css, key)) {
    return css[key];
  }
  const keys = Object.keys(css);
  let best = null;
  let bestDistance = Infinity;
  for (const candidate of keys) {
    const distance = (function lev(a, b) {
      const m = a.length;
      const n = b.length;
      if (!m) return n;
      if (!n) return m;
      const row = new Array(n + 1);
      for (let j = 0; j <= n; j += 1) row[j] = j;
      for (let i = 1; i <= m; i += 1) {
        let prev = i;
        for (let j = 1; j <= n; j += 1) {
          const cost = a.charCodeAt(i - 1) === b.charCodeAt(j - 1) ? 0 : 1;
          const cur = Math.min(row[j] + 1, prev + 1, row[j - 1] + cost);
          row[j - 1] = prev;
          prev = cur;
        }
        row[n] = prev;
      }
      return row[n];
    })(key, candidate);
    if (distance < bestDistance) {
      bestDistance = distance;
      best = candidate;
    }
  }
  const hint =
    best && bestDistance <= Math.max(4, Math.floor(key.length / 3))
      ? ` Did you mean "${best}"?`
      : '';
  throw new Error(
    `Unknown named selector "${key}".${hint} Run "Then print css selectors" to see all ${keys.length} registered names.`,
  );
}

/**
 * Assert a named element is present (count 1) or absent (count 0). Backs the
 * business-readable tab / link steps below so feature files read in plain
 * language instead of selector counts.
 */
async function assertPresence(world, name, present) {
  const sel = resolveName(world, name);
  const loc = world.page.locator(sel);
  await attempt(
    () => expect(loc).toHaveCount(present ? 1 : 0, { timeout: 10000 }),
    `Expected "${name}" to ${present ? 'be present' : 'be absent'}`,
  );
}

// Business-readable assertions for the Canvas Override surfaces. These let the
// access-matrix feature files read like a product spec a PMO / PO can follow,
// while reusing the same named selectors under the hood.
//
// Example #1: Then the Canvas Override tab is available
// Example #2: Then the Canvas Override tab is not available
// Example #3: Then the Reset Canvas layout tab is available
// Example #4: Then the Reset Canvas layout tab is not available
Then(/^the Canvas Override tab is available$/, async function () {
  await assertPresence(this, 'node canvas override tab', true);
});

Then(/^the Canvas Override tab is not available$/, async function () {
  await assertPresence(this, 'node canvas override tab', false);
});

Then(/^the Reset Canvas layout tab is available$/, async function () {
  await assertPresence(this, 'node canvas reset tab', true);
});

Then(/^the Reset Canvas layout tab is not available$/, async function () {
  await assertPresence(this, 'node canvas reset tab', false);
});

/**
 * Business-readable assertion that the content offers a Canvas action to
 * editors (the operation link in the content list).
 *
 * Example: Then a Canvas action is offered on the content list
 */
Then(/^a Canvas action is offered on the content list$/, async function () {
  const sel = resolveName(this, 'content canvas operation link');
  const loc = this.page.locator(sel);
  await attempt(
    () =>
      expect
        .poll(async () => loc.count(), { timeout: 10000 })
        .toBeGreaterThanOrEqual(1),
    'Expected a Canvas action on the content list',
  );
});

/**
 * Reset the Canvas layout through the HTMX flow and answer the browser's
 * native confirmation (hx-confirm). Pass "accept" to confirm the reset or
 * "dismiss"/"cancel" to keep the layout. Registers the dialog handler before
 * clicking the Reset local task, then waits for the HX-Redirect to settle.
 *
 * Example #1: When I reset the Canvas layout and accept the confirmation
 * Example #2: When I reset the Canvas layout and dismiss the confirmation
 */
When(
  /^(?:I |we )?reset the Canvas layout and (accept|dismiss|cancel) the confirmation$/,
  async function (choice) {
    const accept = choice === 'accept';
    const sel = resolveName(this, 'node canvas reset tab');
    await attempt(async () => {
      // Answer the native confirm dialog that hx-confirm raises on click.
      this.page.once('dialog', (dialog) => {
        if (accept) {
          dialog.accept().catch(() => {});
        } else {
          dialog.dismiss().catch(() => {});
        }
      });
      // The Reset tab can live in a collapsed admin-theme dropdown, so click it
      // in the page context; htmx intercepts and raises the confirm.
      await this.page.evaluate((s) => {
        const el = document.querySelector(s);
        if (el) el.click();
      }, sel);
      await waitForPageLoad(this.page, this.minWaitTime && this.minWaitTime.page);
    }, `Could not reset the Canvas layout (${choice})`);
  },
);

/**
 * Assert a named selector is visible / hidden / attached / focused / enabled /
 * disabled / editable.
 *
 * Example #1: Then the "canvas override enable checkbox" element should be visible
 * Example #2: Then the "node canvas override tab" element should be attached within 5 seconds
 */
Then(
  /^the "([^"]*)" element should be (visible|hidden|attached|focused|enabled|disabled|editable)(?: within (\d+) seconds?)?$/,
  async function (name, state, sec) {
    const sel = resolveName(this, name);
    const loc = this.page.locator(sel).first();
    const timeout = sec ? Number(sec) * 1000 : 10000;
    // Delegate to Playwright's web-first (auto-retrying) assertions.
    const matchers = {
      visible: (l) => expect(l).toBeVisible({ timeout }),
      hidden: (l) => expect(l).toBeHidden({ timeout }),
      attached: (l) => expect(l).toBeAttached({ timeout }),
      focused: (l) => expect(l).toBeFocused({ timeout }),
      enabled: (l) => expect(l).toBeEnabled({ timeout }),
      disabled: (l) => expect(l).toBeDisabled({ timeout }),
      editable: (l) => expect(l).toBeEditable({ timeout }),
    };
    await attempt(
      () => matchers[state](loc),
      `Expected "${name}" (${sel}) to be ${state}`,
    );
  },
);

/**
 * Assert the count of elements matching a named selector.
 *
 * Example #1: Then the "node canvas override tab" element should have a count of 1
 * Example #2: Then the "node canvas override tab" element should have a count of 0
 */
Then(
  /^the "([^"]*)" element should have a count of (\d+)(?: within (\d+) seconds?)?$/,
  async function (name, expected, sec) {
    const sel = resolveName(this, name);
    const target = Number(expected);
    const timeout = sec ? Number(sec) * 1000 : 10000;
    // Playwright's locator engine resolves selector extensions like
    // `:has-text('X')` (used for table-row content matches); toHaveCount
    // auto-retries until the count matches or the timeout elapses.
    const loc = this.page.locator(sel);
    await attempt(
      () => expect(loc).toHaveCount(target, { timeout }),
      `Expected "${name}" (${sel}) count to be ${target}`,
    );
  },
);

/**
 * Assert the count of elements matching a named selector is at least N.
 *
 * Example: Then the "content canvas operation link" element should have a count of at least 1
 */
Then(
  /^the "([^"]*)" element should have a count of at least (\d+)(?: within (\d+) seconds?)?$/,
  async function (name, expected, sec) {
    const sel = resolveName(this, name);
    const target = Number(expected);
    const timeout = sec ? Number(sec) * 1000 : 10000;
    const loc = this.page.locator(sel);
    // expect.poll auto-retries the live count until the predicate holds.
    await attempt(
      () =>
        expect
          .poll(async () => loc.count(), { timeout })
          .toBeGreaterThanOrEqual(target),
      `Expected "${name}" (${sel}) count to be at least ${target}`,
    );
  },
);

/**
 * Assert the first element matching a named selector carries a CSS class.
 *
 * Example: Then the "canvas override enable checkbox" element should have class "form-checkbox"
 */
Then(
  /^the "([^"]*)" element should have class "([^"]*)"(?: within (\d+) seconds?)?$/,
  async function (name, cls, sec) {
    const sel = resolveName(this, name);
    const timeout = sec ? Number(sec) * 1000 : 10000;
    const loc = this.page.locator(sel).first();
    // toHaveClass with a word-boundary regex auto-retries until the class
    // is present (the element's class attribute may carry many classes).
    const escaped = cls.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    await attempt(
      () =>
        expect(loc).toHaveClass(new RegExp(`(^|\\s)${escaped}(\\s|$)`), {
          timeout,
        }),
      `Expected "${name}" (${sel}) to have class "${cls}"`,
    );
  },
);

/**
 * Assert the first element matching a named selector contains the given text.
 *
 * Example: Then the "drupal status messages" element should contain text "Canvas layout reset"
 */
Then(
  /^the "([^"]*)" element should contain text "([^"]*)"(?: within (\d+) seconds?)?$/,
  async function (name, text, sec) {
    const sel = resolveName(this, name);
    const timeout = sec ? Number(sec) * 1000 : 10000;
    const loc = this.page.locator(sel).first();
    // toContainText auto-retries until the substring appears.
    await attempt(
      () => expect(loc).toContainText(text, { timeout }),
      `Expected "${name}" (${sel}) to contain text "${text}"`,
    );
  },
);

/**
 * Click the first element matching a named selector. Falls back to a JS
 * `.click()` after a failed Playwright actionability attempt so Gin's sticky
 * form-actions overlay on Drupal CMS does not stall the click.
 *
 * Example: When I click the "node canvas override tab" element
 */
When(
  /^(?:I |we )?click(?: on)?(?: the)? "([^"]*)" element$/,
  async function (name) {
    const sel = resolveName(this, name);
    await attempt(async () => {
      const loc = this.page.locator(sel).first();
      // Wait only for attachment, not visibility: on Drupal CMS the per-content
      // Canvas tabs live inside the Gin top-bar dropdown and stay 0x0 until the
      // dropdown is expanded. A JS .click() drives them regardless of visibility.
      await loc.waitFor({ state: 'attached', timeout: 10000 });
      let clicked = false;
      if (await loc.isVisible().catch(() => false)) {
        try {
          await loc.click({ timeout: 4000 });
          clicked = true;
        } catch (e) {
          clicked = false;
        }
      }
      if (!clicked) {
        // Sticky overlays (Gin) or collapsed dropdowns: JS click bypasses
        // Playwright actionability and works on hidden-but-attached elements.
        await this.page.evaluate((s) => {
          const el = document.querySelector(s);
          if (el) el.click();
        }, sel);
      }
      // Settle is best-effort and time-boxed. Clicking a link into a heavy
      // React SPA (the Canvas editor) keeps the network busy far longer than
      // waitForPageLoad's own budget, which would blow the Cucumber step
      // timeout. Cap the settle at 8s and let the following web-first
      // assertion (which auto-retries) do the real waiting.
      const settle = waitForPageLoad(
        this.page,
        this.minWaitTime && this.minWaitTime.page,
      ).catch(() => {});
      const cap = new Promise((resolve) => {
        setTimeout(resolve, 8000);
      });
      await Promise.race([settle, cap]);
    }, `Could not click the "${name}" element`);
  },
);

/**
 * Navigate to the seeded Canvas Override article (via its path alias).
 *
 * Example: Given I am on the Canvas Override test content
 */
Given(/^I am on the Canvas Override test content$/, async function () {
  const alias =
    (this.parameters.canvasOverride &&
      this.parameters.canvasOverride.nodeAlias) ||
    '/canvas-override-test';
  await attempt(async () => {
    await gotoUrl(this.page, `${this.parameters.launchUrl}${alias}`);
    await waitForPageLoad(this.page, this.minWaitTime && this.minWaitTime.page);
  }, `Could not open the Canvas Override test content at "${alias}"`);
});

/**
 * Resolve the seeded test content's node id, then navigate to one of its
 * Canvas Override routes (canvas | canvas/reset | canvas/default). Used by the
 * access-matrix scenarios to hit the route directly and assert a 403 / access
 * denied page for roles that must not reach it, independently of whether the
 * local-task tab is shown.
 *
 * The node id is read from `[data-history-node-id]` (present on rendered node
 * pages) so the step never has to hard-code an id.
 *
 * Example #1: Given I go to the Canvas Override test content "canvas" route
 * Example #2: Given I go to the Canvas Override test content "canvas/reset" route
 */
Given(
  /^(?:I |we )?go to the Canvas Override test content "([^"]*)" route$/,
  async function (suffix) {
    const alias =
      (this.parameters.canvasOverride &&
        this.parameters.canvasOverride.nodeAlias) ||
      '/canvas-override-test';
    await attempt(async () => {
      await gotoUrl(this.page, `${this.parameters.launchUrl}${alias}`);
      await waitForPageLoad(this.page);
      const nid = await this.page.evaluate(() => {
        const fromPath = (s) => {
          const m = s && s.match(/(?:^|\/)node\/(\d+)/);
          return m ? m[1] : null;
        };
        // drupalSettings.path.currentPath is "node/{nid}" on a node page and is
        // present for every user who can view the node, regardless of edit
        // rights or theme.
        try {
          if (
            window.drupalSettings &&
            window.drupalSettings.path &&
            window.drupalSettings.path.currentPath
          ) {
            const n = fromPath(window.drupalSettings.path.currentPath);
            if (n) return n;
          }
        } catch (e) {
          /* fall through */
        }
        // The canonical / shortlink <link> elements carry /node/{nid} in the
        // document head on every node page.
        const linkEl = document.querySelector(
          "link[rel='shortlink'], link[rel='canonical']",
        );
        if (linkEl) {
          const n = fromPath(linkEl.getAttribute('href'));
          if (n) return n;
        }
        const el = document.querySelector('[data-history-node-id]');
        if (el) return el.getAttribute('data-history-node-id');
        const m = document.body.className.match(/page-node-(\d+)/);
        if (m) return m[1];
        const link = document.querySelector("a[href*='/node/']");
        return link ? fromPath(link.getAttribute('href')) : null;
      });
      if (!nid) {
        throw new Error(
          'Could not resolve the test content node id from the page',
        );
      }
      await gotoUrl(
        this.page,
        `${this.parameters.launchUrl}/node/${nid}/${suffix}`,
      );
      await waitForPageLoad(this.page);
    }, `Could not open the Canvas Override test content "${suffix}" route`);
  },
);
