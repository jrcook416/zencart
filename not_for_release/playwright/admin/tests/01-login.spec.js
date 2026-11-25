// 1. Login: a bad password is rejected. testadmin logs in and the
//    dashboard loads.
const { test, expect, ADMIN, login, loginAsAdmin, cmdOf, checkPage } =
  require('./helpers');

test('1a bad password is rejected', async ({ page }) => {
  await login(page, ADMIN.user, 'wrong-password-xyz');
  await checkPage(page, 'login');
  expect(cmdOf(page), 'still on the login page').toBe('login');
  await expect(page.locator('body'))
    .toContainText('You entered the wrong username or password.');
});

test('1b testadmin logs in and the dashboard loads', async ({ page }) => {
  await loginAsAdmin(page);
});
