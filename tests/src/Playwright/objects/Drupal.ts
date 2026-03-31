import * as fs from 'node:fs';
import * as nodePath from 'node:path';
import {
  exec,
  execDrush,
  getModuleDir,
  getRootDir,
} from '@drupal-canvas/test-utils';
import { expect } from '@playwright/test';

import type { Page } from '@playwright/test';
import type { DrupalSite } from '../fixtures/DrupalSite';

/**
 * Page object for Drupal interactions in Canvas Override tests.
 *
 * Extends the patterns from the Canvas module's Drupal page object with
 * a setupCanvasOverrideTestSite() method that installs Canvas and
 * canvas_override, creates content types (article with Canvas Override
 * enabled, page without), and enables a test theme.
 */
export class Drupal {
  readonly page: Page;
  readonly drupalSite: DrupalSite;

  constructor({ page, drupalSite }: { page: Page; drupalSite: DrupalSite }) {
    this.page = page;
    this.drupalSite = drupalSite;
  }

  async setTestCookie() {
    const context = await this.page.context();
    const simpletestCookie = {
      name: 'SIMPLETEST_USER_AGENT',
      value: encodeURIComponent(this.drupalSite.userAgent),
      url: this.drupalSite.url,
    };
    await context.addCookies([simpletestCookie]);
  }

  hasDrush() {
    return this.drupalSite.hasDrush;
  }

  async drush(command: string) {
    return await execDrush(command, this.drupalSite);
  }

  /**
   * Sets up a fresh test site with Canvas Override enabled on 'article'.
   *
   * Steps:
   * 1. Enable test extension scanning.
   * 2. Install canvas and canvas_override modules.
   * 3. Create 'article' content type with Canvas Override enabled.
   * 4. Create 'page' content type without Canvas Override (control).
   * 5. Enable a theme.
   */
  async setupCanvasOverrideTestSite() {
    await this.enableTestExtensions();
    await this.installModules(['node', 'field', 'text', 'filter', 'canvas', 'canvas_override']);
    // Create the article content type with Canvas Override enabled.
    await this.drush(
      "php-eval \"" +
      "\\$type = \\Drupal\\node\\Entity\\NodeType::create(['type' => 'article', 'name' => 'Article']); " +
      "\\$type->setThirdPartySetting('canvas_override', 'enabled', TRUE); " +
      "\\$type->save(); " +
      "\\Drupal\\canvas_override\\Hook\\CanvasOverrideHooks::ensureCanvasField('article');\"",
    );
    // Create the page content type without Canvas Override.
    await this.drush(
      "php-eval \"\\Drupal\\node\\Entity\\NodeType::create(['type' => 'page', 'name' => 'Basic page'])->save();\"",
    );
    await this.drush('theme:enable stark');
    await this.drush('config:set system.theme default stark');
    await this.drush('cr');
  }

  async loginAsAdmin() {
    const stdout = await exec(
      `php core/scripts/test-site.php user-login 1 --site-path ${this.drupalSite.sitePath}`,
    );
    await this.page.goto(`${this.drupalSite.url}${stdout.toString()}`);
    await expect(this.page.locator('h1')).toHaveText('admin');
  }

  async login(
    { username, password }: { username: string; password?: string } = {
      username: '',
      password: '',
    },
  ) {
    if (this.drupalSite.hasDrush) {
      const loginUrl = await this.drush(
        `user:login --name=${username} --no-browser`,
      );
      await this.page.goto(loginUrl);
    } else {
      await this.page.goto(`${this.drupalSite.url}/user/login`);
      await this.page.locator('[data-drupal-selector="edit-name"]').fill(username);
      await this.page.locator('[data-drupal-selector="edit-pass"]').fill(password);
      await this.page.locator('[data-drupal-selector="edit-submit"]').click();
    }
    await expect(this.page.locator('h1')).toHaveText(username);
  }

  async logout() {
    await this.page.goto(`${this.drupalSite.url}/user/logout/confirm`);
    await this.page.locator('[data-drupal-selector="edit-submit"]').click();
    await this.page.waitForURL('/');
  }

  async createRole({ name }: { name: string }) {
    if (this.drupalSite.hasDrush) {
      await this.drush(`role:create ${name}`);
    } else {
      const page = this.page;
      await page.goto(`${this.drupalSite.url}/admin/people/roles/add`);
      await page.locator('[data-drupal-selector="edit-label"]').fill(name);
      await page.locator('[data-drupal-selector="edit-submit"]').click();
      await expect(page.locator('//*[@data-drupal-messages]')).toContainText(
        'has been added.',
      );
    }
  }

  async addPermissions({
    role,
    permissions,
  }: {
    role: string;
    permissions: string[];
  }) {
    if (this.drupalSite.hasDrush) {
      await this.drush(`role:perm:add ${role} '${permissions.join(',')}'`);
    } else {
      const page = this.page;
      await page.goto(`${this.drupalSite.url}/admin/people/permissions`);
      for (const permission of permissions) {
        await page
          .locator(
            `[data-drupal-selector="edit-${this.normalizeAttribute(
              role,
            )}-${this.normalizeAttribute(permission)}"]`,
          )
          .check();
      }
      await page.locator('[data-drupal-selector="edit-submit"]').click();
      await expect(page.locator('//*[@data-drupal-messages]')).toContainText(
        'The changes have been saved',
      );
    }
  }

  async createUser({
    username,
    password,
    email,
    roles,
  }: {
    username: string;
    password: string;
    email: string;
    roles: string[];
  }) {
    if (this.drupalSite.hasDrush) {
      await this.drush(
        `user:create ${username} --password=${password} --mail=${email}`,
      );
      for (const role of roles) {
        await this.drush(`user:role:add ${role} ${username}`);
      }
    } else {
      const page = this.page;
      await page.goto(`${this.drupalSite.url}/admin/people/create`);
      await page.locator('[data-drupal-selector="edit-mail"]').fill(email);
      await page.locator('[data-drupal-selector="edit-name"]').fill(username);
      await page
        .locator('[data-drupal-selector="edit-pass-pass1"]')
        .fill(password);
      await page
        .locator('[data-drupal-selector="edit-pass-pass2"]')
        .fill(password);
      for (const role of roles) {
        await page
          .locator(
            `[data-drupal-selector="edit-roles-${this.normalizeAttribute(
              role,
            )}"]`,
          )
          .check();
      }
      await page.locator('[data-drupal-selector="edit-submit"]').click();
      await expect(page.locator('//*[@data-drupal-messages]')).toContainText(
        'Created a new user account for',
      );
    }
  }

  async installModules(modules: string[]) {
    if (this.drupalSite.hasDrush) {
      await this.drush(`pm:enable ${modules.join(' ')}`);
    } else {
      const page = this.page;
      await page.goto(`${this.drupalSite.url}/admin/modules`);
      for (const module of modules) {
        await page
          .locator(
            `[data-drupal-selector="edit-modules-${this.normalizeAttribute(
              module,
            )}-enable"]`,
          )
          .check();
      }
      await page.locator('[data-drupal-selector="edit-submit"]').click();
    }
  }

  async enableTestExtensions() {
    const settingsFile = nodePath.resolve(
      getRootDir(),
      `${this.drupalSite.sitePath}/settings.php`,
    );
    fs.chmodSync(settingsFile, 0o775);
    return await exec(
      `echo '$settings["extension_discovery_scan_tests"] = TRUE;' >> ${settingsFile}`,
    );
  }

  normalizeAttribute(attribute: string) {
    return attribute.replaceAll(' ', '-').replaceAll('_', '-');
  }
}
