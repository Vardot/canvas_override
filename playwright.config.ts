import { defineConfig, devices } from '@playwright/test';

import 'dotenv-defaults/config';

/**
 * Playwright configuration for Canvas Override tests.
 *
 * @see https://playwright.dev/docs/test-configuration
 */

const browserProjects = [
  {
    name: 'chromium',
    use: {
      ...devices['Desktop Chrome'],
      deviceScaleFactor: 1,
      viewport: { width: 1920, height: 1080 },
    },
    dependencies: ['setup'],
  },
  {
    name: 'firefox',
    use: {
      ...devices['Desktop Firefox'],
      deviceScaleFactor: 1,
      viewport: { width: 1920, height: 1080 },
    },
    dependencies: ['setup'],
  },
  {
    name: 'webkit',
    use: {
      ...devices['Desktop Safari'],
      deviceScaleFactor: 1,
      viewport: { width: 1920, height: 1080 },
    },
    dependencies: ['setup'],
  },
];

// In CI, each job runs exactly one browser.
const activeBrowserProjects = process.env.BROWSER
  ? browserProjects.filter((p) => p.name === process.env.BROWSER)
  : browserProjects;

export default defineConfig({
  testDir: './tests/src/Playwright',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  maxFailures: 999999,
  workers: '50%',
  reporter: [
    ['list'],
    ['junit', { outputFile: 'test-results/playwright.xml' }],
    ['html', { host: '0.0.0.0', open: 'never' }],
  ],
  timeout: process.env.CI ? 120_000 : 30_000,
  expect: { timeout: 10_000 },
  globalTimeout: 3_600_000,
  use: {
    baseURL: process.env.DRUPAL_TEST_BASE_URL,
    ignoreHTTPSErrors: true,
    testIdAttribute: 'data-testid',
    trace: 'on-first-retry',
    screenshot: {
      mode: 'only-on-failure',
      fullPage: true,
    },
    video: 'retain-on-failure',
  },
  projects: [
    {
      name: 'setup',
      testMatch: /_global\.setup\.ts/,
    },
    ...activeBrowserProjects,
  ],
});
