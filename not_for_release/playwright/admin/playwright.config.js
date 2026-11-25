// Admin harness. Run from this folder: npx playwright test
// See ../README.md.
const { defineConfig } = require('@playwright/test');

const store = (process.env.ZC_BASE_URL || 'http://localhost/zencart/')
  .replace(/\/?$/, '/');
// Admin folder on the local store is named wombat (as on prod).
const admin = (process.env.ZC_ADMIN_URL || store + 'wombat/')
  .replace(/\/?$/, '/');

module.exports = defineConfig({
  testDir: './tests',
  // One browser, one test at a time: the tests share one admin login and
  // the same orders in the test database.
  workers: 1,
  fullyParallel: false,
  retries: 0,
  timeout: 120 * 1000,
  expect: { timeout: 10 * 1000 },
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: admin,
    browserName: 'chromium',
    headless: true,
    viewport: { width: 1400, height: 900 },
    screenshot: 'on',
    trace: 'off',
    ignoreHTTPSErrors: true,
  },
});
