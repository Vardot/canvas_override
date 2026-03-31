import { expect } from '@playwright/test';

import type { Page } from '@playwright/test';

const initializedReadyPreviewIframeSelector =
  '[data-test-canvas-content-initialized="true"][data-canvas-swap-active="true"]';

/**
 * Page object for Canvas editor interactions in Canvas Override tests.
 *
 * Provides helpers for waiting on the Canvas UI, opening panels,
 * adding components, and publishing changes.
 */
export class CanvasEditor {
  readonly page: Page;

  constructor({ page }: { page: Page }) {
    this.page = page;
  }

  async getSettings() {
    return await this.page.evaluate(() => {
      return window.drupalSettings;
    });
  }

  async getEditorPath() {
    const bodyClass = await this.page.locator('body').getAttribute('class');
    const hasCanvasPageClass = bodyClass?.includes('canvas-page');
    const drupalSettings = await this.getSettings();
    if (hasCanvasPageClass) {
      return `${drupalSettings.path.baseUrl}canvas/editor/canvas_${drupalSettings.path.currentPath}`;
    } else {
      return `${drupalSettings.path.baseUrl}canvas/editor/${drupalSettings.path.currentPath}`;
    }
  }

  async waitForCanvasUi() {
    await expect(this.page.getByTestId('canvas-side-menu')).toBeAttached();
    await expect(this.page.getByTestId('canvas-topbar')).toBeAttached();
  }

  async waitForEditorUi() {
    await this.waitForCanvasUi();
    await this.waitForEditorFrame();
  }

  async waitForEditorUINoContextualPanel() {
    await this.waitForCanvasUi();
    await this.waitForEditorFrame();
  }

  async waitForEditorFrame() {
    await expect(
      this.page.locator('.canvasEditorFrameScalingContainer'),
    ).toHaveCSS('opacity', '1');

    await expect(
      this.page.locator(initializedReadyPreviewIframeSelector),
    ).toBeAttached();

    await this.page.waitForFunction(
      (selector: string) => {
        const el = document.querySelector(selector);
        return el && !!(el as HTMLIFrameElement).contentDocument;
      },
      initializedReadyPreviewIframeSelector,
      { timeout: 15_000 },
    );
  }

  async goToEditor() {
    const path = await this.getEditorPath();
    const response = await this.page.goto(path);
    if (!response || response.status() !== 200) {
      throw new Error(
        "Editor didn't load. Ensure the page can be edited by Canvas.",
      );
    }
    await this.waitForEditorUi();
  }

  async getActivePreviewFrame() {
    await this.waitForEditorUINoContextualPanel();
    return this.page
      .locator(
        '[data-testid="canvas-editor-frame-scaling"] iframe[data-canvas-swap-active="true"]',
      )
      .contentFrame();
  }

  async openLibraryPanel() {
    await this.page
      .getByTestId('canvas-side-menu')
      .getByLabel('Library')
      .click();

    await expect(
      this.page.getByTestId('canvas-components-library-loading'),
    ).not.toBeVisible();
    await expect(
      this.page.getByRole('heading', { name: 'Library' }),
    ).toBeVisible();

    // Ensure we are on the Components tab.
    await this.page.getByTestId('canvas-library-components-tab-select').click();
  }

  async openLayersPanel() {
    await this.page
      .getByTestId('canvas-side-menu')
      .getByLabel('Layers')
      .click();
    await expect(
      this.page.getByRole('heading', { name: 'Layers' }),
    ).toBeVisible();
  }

  async clickPreviewComponent(componentId: string) {
    const component = this.page.locator(
      `#canvasPreviewOverlay [data-canvas-component-id="${componentId}"]`,
    );
    await component.evaluate((el) => {
      el.scrollIntoView({
        behavior: 'instant',
        block: 'center',
        inline: 'center',
      });
      const mousedownEvent = new MouseEvent('mousedown', {
        view: window,
        bubbles: true,
        cancelable: true,
        button: 0,
        buttons: 1,
      });
      const mouseupEvent = new MouseEvent('mouseup', {
        view: window,
        bubbles: true,
        cancelable: true,
        button: 0,
        buttons: 0,
      });
      const clickEvent = new MouseEvent('click', {
        view: window,
        bubbles: true,
        cancelable: true,
        button: 0,
        buttons: 0,
      });
      el.dispatchEvent(mousedownEvent);
      el.dispatchEvent(mouseupEvent);
      el.dispatchEvent(clickEvent);
    });
  }

  async publishAllChanges(expectedTitles: string[] = []) {
    await this.page
      .getByRole('button', { name: /Review \d+ changes?/ })
      .click();
    await expect(async () => {
      await this.page.getByLabel('Select all changes', { exact: true }).click();
      if (expectedTitles.length > 0) {
        await Promise.all(
          expectedTitles.map(async (title: string) =>
            expect(
              await this.page.getByLabel(`Select change ${title}`),
            ).toBeChecked(),
          ),
        );
      }
      await this.page
        .getByRole('button', { name: /Publish \d+ selected?/ })
        .click();
      await expect(this.page.getByText('All changes published!')).toBeVisible();
    }).toPass({
      intervals: [1_000, 2_000, 10_000],
      timeout: 60_000,
    });
  }
}
