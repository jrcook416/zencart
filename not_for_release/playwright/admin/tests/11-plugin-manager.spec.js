// 11. Plugin manager: loads and lists installed plugins. View only.
const { test, expect, go, loginAsAdmin } = require('./helpers');

test('11 plugin manager lists plugins (view only)', async ({ page }) => {
  await loginAsAdmin(page);
  await go(page, 'plugin_manager');
  for (const name of ['Edit Orders', 'IEMS County Agency']) {
    await expect(page.locator('body'), `${name} listed`)
      .toContainText(name);
  }
});
