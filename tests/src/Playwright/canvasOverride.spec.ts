import { expect } from '@playwright/test';

import { test } from './fixtures/DrupalSite';
import { Drupal } from './objects/Drupal';

import type { Page } from '@playwright/test';
import type { CanvasEditor } from './objects/CanvasEditor';

/**
 * Tests Canvas Override per-node layout editing.
 *
 * Verifies that enabling Canvas Override on a content type:
 * - adds the Canvas tab to nodes of that type,
 * - hides the tab for users without proper permissions,
 * - opens the Canvas editor when navigating to the Canvas tab,
 * - allows adding components and publishing changes.
 */
test.describe('Canvas Override', () => {
  test.beforeAll(
    'Setup test site with Canvas Override enabled on Article',
    async ({ browser, drupalSite }) => {
      const page = await browser.newPage();
      const drupal: Drupal = new Drupal({ page, drupalSite });
      await drupal.setupCanvasOverrideTestSite();
      await page.close();
    },
  );

  test('Canvas tab is visible for admin on an enabled content type', async ({
    page,
    drupal,
  }) => {
    await drupal.loginAsAdmin();
    // Create an article node.
    await drupal.drush(
      "php-eval \"\\Drupal\\node\\Entity\\Node::create(['type' => 'article', 'title' => 'Override Test', 'status' => 1, 'uid' => 1])->save();\"",
    );
    await page.goto('/admin/content');
    await page.getByRole('link', { name: 'Override Test' }).click();
    // The Canvas local task tab should be present.
    await expect(page.getByRole('link', { name: 'Canvas' })).toBeVisible();
  });

  test('Canvas tab is hidden for user without Canvas Override permission', async ({
    page,
    drupal,
  }) => {
    await drupal.loginAsAdmin();
    // Create a role with content editing but no canvas override permissions.
    await drupal.createRole({ name: 'content_editor' });
    await drupal.addPermissions({
      role: 'content_editor',
      permissions: [
        'access content',
        'create article content',
        'edit any article content',
      ],
    });
    const user = {
      username: 'editor_no_canvas',
      password: 'TestPassword123!',
      email: 'editor_no_canvas@example.com',
      roles: ['content_editor'],
    };
    await drupal.createUser(user);
    await drupal.logout();
    await drupal.login(user);

    // Visit the article node.
    await page.goto('/admin/content');
    await page.getByRole('link', { name: 'Override Test' }).click();

    // Canvas tab should NOT be visible.
    await expect(
      page.getByRole('link', { name: 'Canvas' }),
    ).not.toBeAttached();
  });

  test('Canvas tab visible with per-bundle permission', async ({
    page,
    drupal,
  }) => {
    await drupal.loginAsAdmin();
    // Create a role with the per-bundle permission only.
    await drupal.createRole({ name: 'article_canvas_editor' });
    await drupal.addPermissions({
      role: 'article_canvas_editor',
      permissions: [
        'access content',
        'create article content',
        'edit any article content',
        'view the administration theme',
        'use canvas override for article',
      ],
    });
    const user = {
      username: 'article_canvas_user',
      password: 'TestPassword123!',
      email: 'article_canvas@example.com',
      roles: ['article_canvas_editor'],
    };
    await drupal.createUser(user);
    await drupal.logout();
    await drupal.login(user);

    await page.goto('/admin/content');
    await page.getByRole('link', { name: 'Override Test' }).click();

    // Canvas tab should be visible with per-bundle permission.
    await expect(page.getByRole('link', { name: 'Canvas' })).toBeVisible();
  });

  test('Canvas tab not visible for disabled content type', async ({
    page,
    drupal,
  }) => {
    await drupal.loginAsAdmin();
    // Create a node of type 'page' (Canvas Override not enabled).
    await drupal.drush(
      "php-eval \"\\Drupal\\node\\Entity\\Node::create(['type' => 'page', 'title' => 'No Override Page', 'status' => 1, 'uid' => 1])->save();\"",
    );
    await page.goto('/admin/content');
    await page.getByRole('link', { name: 'No Override Page' }).click();

    // Canvas tab should NOT be present.
    await expect(
      page.getByRole('link', { name: 'Canvas' }),
    ).not.toBeAttached();
  });

  test('Canvas editor loads when clicking Canvas tab', async ({
    page,
    drupal,
    canvasEditor,
  }) => {
    await drupal.loginAsAdmin();
    await page.goto('/admin/content');
    await page.getByRole('link', { name: 'Override Test' }).click();

    // Click the Canvas tab.
    await page.getByRole('link', { name: 'Canvas' }).click();

    // Should be redirected to the Canvas editor.
    await page.waitForURL(/canvas\/editor\/node\/\d+/);
    await canvasEditor.waitForEditorUINoContextualPanel();
  });

  test('Can add a component to per-node canvas and publish', async ({
    page,
    drupal,
    canvasEditor,
  }) => {
    await drupal.loginAsAdmin();
    await page.goto('/admin/content');
    await page.getByRole('link', { name: 'Override Test' }).click();
    await page.getByRole('link', { name: 'Canvas' }).click();
    await page.waitForURL(/canvas\/editor\/node\/\d+/);
    await canvasEditor.waitForEditorUINoContextualPanel();

    // Open the library and add a component.
    await canvasEditor.openLibraryPanel();

    // Add the heading component (available in most Canvas test setups).
    const headingComponent = page
      .getByTestId('canvas-primary-panel')
      .locator('[data-canvas-type="component"]')
      .first();
    const componentName = await headingComponent.getAttribute(
      'data-canvas-name',
    );

    await headingComponent.hover();
    await headingComponent.getByLabel('Open contextual menu').click();
    await page.getByText('Insert').click();

    // Should show a pending change.
    await expect(page.getByText(/Review \d+ change/)).toBeVisible();

    // Publish all changes.
    await canvasEditor.publishAllChanges();
  });
});
