// 10. Logoff: the session ends, and My Account redirects to login.
const { test, expect, go, checkPage, loginAsCustomer, mainPageOf } =
  require('./helpers');

test('10 logoff ends the session', async ({ page }) => {
  await loginAsCustomer(page);
  await go(page, 'logoff');
  await page.goto('index.php?main_page=account');
  await checkPage(page, 'account-after-logoff');
  expect(mainPageOf(page), 'My Account sends a logged-off visitor to login')
    .toBe('login');
});
