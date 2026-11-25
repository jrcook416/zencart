// 5. Account pages: order history lists past orders, one order opens,
//    and the address book opens.
const { test, expect, go, checkPage, loginAsCustomer, mainPageOf } =
  require('./helpers');

test('5 order history, one order, address book', async ({ page }) => {
  await loginAsCustomer(page);

  await go(page, 'account_history');
  const orders = page.locator('a[href*="main_page=account_history_info"]');
  expect(await orders.count(), 'past orders listed').toBeGreaterThan(0);

  await Promise.all([page.waitForNavigation(), orders.first().click()]);
  await checkPage(page, 'order-info');
  expect(mainPageOf(page)).toBe('account_history_info');
  await expect(page.locator('body')).toContainText('$');

  await go(page, 'address_book');
  expect(mainPageOf(page), 'address book opens').toBe('address_book');
  expect(await page.locator('address').count(),
    'at least one saved address').toBeGreaterThan(0);
});
