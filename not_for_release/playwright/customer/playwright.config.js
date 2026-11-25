// Customer (storefront) harness. Run from this folder: npx playwright test
// See ../README.md.
const { defineConfig } = require('@playwright/test');

const base = (process.env.ZC_BASE_URL || 'http://localhost/zencart/')
  .replace(/\/?$/, '/');

module.exports = defineConfig({
  testDir: './tests',
  // One browser, one test at a time: every test uses the same test
  // customer, whose cart is saved in the database.
  workers: 1,
  fullyParallel: false,
  retries: 0,
  timeout: 90 * 1000,
  expect: { timeout: 10 * 1000 },
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: base,
    browserName: 'chromium',
    headless: true,
    viewport: { width: 1280, height: 900 },
    screenshot: 'on',
    trace: 'off',
    ignoreHTTPSErrors: true,
  },
});
