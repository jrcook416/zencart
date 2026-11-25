// 13. Logoff: the session ends; an admin page then asks for login.
const { test, expect, checkPage, loginAsAdmin, cmdOf } =
  require('./helpers');

test('13 logoff ends the session', async ({ page }) => {
  await loginAsAdmin(page);
  await page.goto('index.php?cmd=logoff');
  await page.goto('index.php?cmd=orders');
  await checkPage(page, 'login');
  expect(cmdOf(page), 'orders page asks for login after logoff')
    .toBe('login');
});
