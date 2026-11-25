// 5. Packing slip and invoice: both open for an order and show the
//    delivery address (which holds the unit) and the items.
const { test, expect, go, loginAsAdmin, testOrderId } =
  require('./helpers');

test('5 packing slip and invoice', async ({ page }) => {
  await loginAsAdmin(page);
  const oID = await testOrderId(page);
  await go(page, 'orders', `oID=${oID}&action=edit`);
  const lines = (await page.locator('#addressDelivery').innerText())
    .split('\n').map((l) => l.trim()).filter((l) => l.length > 2);
  const names = await page.locator('tr.dataTableRow')
    .evaluateAll((rows) => rows.map((r) => {
      const td = r.querySelectorAll('td')[1];
      return td ? td.innerText.split('\n')[0].trim() : '';
    }).filter(Boolean));
  expect(names.length, 'items on the order').toBeGreaterThan(0);

  for (const cmd of ['packingslip', 'invoice']) {
    await go(page, cmd, `oID=${oID}`);
    const text = await page.locator('body').innerText();
    for (const l of lines) {
      expect(text, `${cmd} shows delivery line "${l}"`).toContain(l);
    }
    for (const n of names) {
      expect(text, `${cmd} shows item "${n}"`).toContain(n);
    }
  }
});
