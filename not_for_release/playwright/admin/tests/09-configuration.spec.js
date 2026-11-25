// 9. Configuration: opens My Store, Shipping/Packaging and Email.
//    View only; nothing is saved.
const { test, expect, go, loginAsAdmin } = require('./helpers');

const GROUPS = [
  [1, 'My Store'],
  [7, 'Shipping/Packaging'],
  [12, 'Email'],
];

test('9 configuration groups open (view only)', async ({ page }) => {
  await loginAsAdmin(page);
  for (const [gID, name] of GROUPS) {
    await go(page, 'configuration', `gID=${gID}`);
    await expect(page.locator('h1').first(), `${name} heading`)
      .toContainText(name);
    expect(await page.locator('table tr').count(), `${name} settings`)
      .toBeGreaterThan(1);
  }
});
