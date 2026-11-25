// 10. Admin users and profiles: both lists open. View only.
const { test, expect, go, loginAsAdmin, ADMIN } = require('./helpers');

test('10 admin users and profiles (view only)', async ({ page }) => {
  await loginAsAdmin(page);
  await go(page, 'users');
  await expect(page.locator('body'), 'testadmin in the users list')
    .toContainText(ADMIN.user);
  await go(page, 'profiles');
  expect(await page.locator('table tr').count(), 'profiles listed')
    .toBeGreaterThan(1);
});
