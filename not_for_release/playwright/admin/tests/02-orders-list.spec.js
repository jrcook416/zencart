// 2. Orders list: loads, the status filter works, and searching by order
//    number finds the order.
const { test, expect, go, checkPage, loginAsAdmin, cmdOf,
  testOrderId } = require('./helpers');

test('2 orders list, status filter, order number search',
  async ({ page }) => {
    await loginAsAdmin(page);
    const oID = await testOrderId(page); // opens the orders list

    const filter = page.locator('#statusFilterSelect');
    const statuses = await filter.locator('option').evaluateAll((os) =>
      os.filter((o) => o.value !== '').map((o) => o.value));
    expect(statuses.length, 'order statuses in the filter')
      .toBeGreaterThan(0);
    await Promise.all([page.waitForNavigation(),
      filter.selectOption(statuses[0])]);
    await checkPage(page, 'orders-filtered');
    expect(new URL(page.url()).searchParams.get('statusFilterSelect'),
      'status filter applied').toBe(statuses[0]);

    await go(page, 'orders');
    const form = page.locator('form[name=orders]').first();
    await form.locator('input[name=oID]').fill(oID);
    await Promise.all([page.waitForNavigation(),
      form.locator('input[name=oID]').press('Enter')]);
    await checkPage(page, 'order-search');
    expect(cmdOf(page)).toBe('orders');
    expect(new URL(page.url()).searchParams.get('oID'),
      `search for order ${oID}`).toBe(oID);
    await expect(page.locator('#addressDelivery'),
      `order ${oID} opens`).toBeVisible();
  });
