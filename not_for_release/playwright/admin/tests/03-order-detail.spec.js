// 3. Order detail: customer, delivery address (the unit is a line of it),
//    items, totals and status history show. Change the status with a
//    comment (no customer email), and it saves and shows in the history.
const { test, expect, go, checkPage, submitForm, loginAsAdmin,
  testOrderId, fillText } = require('./helpers');

test('3 order detail and status change', async ({ page }) => {
  await loginAsAdmin(page);
  const oID = await testOrderId(page);
  await go(page, 'orders', `oID=${oID}&action=edit`);

  for (const id of ['#addressCustomer', '#addressDelivery']) {
    await expect(page.locator(id), `${id} shown`).toBeVisible();
    expect((await page.locator(id).innerText()).trim().length,
      `${id} not empty`).toBeGreaterThan(5);
  }
  test.info().annotations.push({ type: 'delivery address',
    description: (await page.locator('#addressDelivery').innerText())
      .replace(/\s+/g, ' ').trim() });
  expect(await page.locator('tr.dataTableRow').count(), 'order items')
    .toBeGreaterThan(0);
  await expect(page.locator('.ot-total-Amount').first(), 'order total')
    .toContainText('$');

  const sel = page.locator('#statusUpdateSelect');
  const current = await sel.inputValue();
  const other = await sel.locator('option').evaluateAll((os, cur) =>
    os.map((o) => o.value).find((v) => v !== '' && v !== cur), current);
  expect(other, 'another order status to change to').toBeTruthy();
  const otherName = (await sel.locator(`option[value="${other}"]`)
    .innerText()).trim();
  const comment = `Playwright admin test ${new Date().toISOString()}`;

  const form = page.locator('form[name=statusUpdateForm]').filter({
    has: page.locator('#statusUpdateSelect') }).first();
  await sel.selectOption(other);
  await fillText(page, '#comments', comment);
  await form.locator('input[name=notify][value="0"]').check(); // no email
  await submitForm(page, form);
  await checkPage(page, 'status-updated');

  await go(page, 'orders', `oID=${oID}&action=edit`);
  await expect(page.locator('body'), 'comment in the status history')
    .toContainText(comment);
  await expect(page.locator('#statusUpdateSelect'), 'new status saved')
    .toHaveValue(other);
  test.info().annotations.push({ type: 'status',
    description: `order ${oID} set to "${otherName}"` });
});
