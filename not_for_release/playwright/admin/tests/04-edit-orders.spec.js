// 4. Edit Orders: open an order; with no changes the totals match the
//    order page and Edit Orders offers no Update (it only offers one after
//    a change). Change a quantity: the totals recalculate and save. The
//    quantity is then put back the same way.
const { test, expect, go, checkPage, loginAsAdmin, money, testOrderId,
  orderPageTotal } = require('./helpers');

async function eoTotal(page) {
  const row = page.locator('#products-listing tr.eo-ot').last();
  return money(await row.locator('td').last().innerText());
}

// Changes the first product's quantity to qty and saves the order.
async function setQty(page, oID, qty) {
  await go(page, 'edit_orders', `oID=${oID}`);
  const before = await eoTotal(page);
  await page.locator('button.eo-btn-prod-edit').first().click();
  const q = page.locator('#prod-qty');
  await expect(q, 'quantity box in the product pop-up').toBeVisible();
  await q.fill(String(qty));
  await page.locator('#eo-prod-update').click();
  await expect(q, 'product pop-up closes').toBeHidden();
  await expect(page.locator('#update-verify'), 'Update button appears')
    .toBeVisible();
  const recalced = await eoTotal(page);
  await page.locator('#update-verify').click();
  const commit = page.locator('#commit-changes');
  await expect(commit, 'confirm-changes pop-up').toBeVisible();
  await Promise.all([page.waitForNavigation(), commit.click()]);
  await checkPage(page, `eo-saved-qty-${qty}`);
  return { before, recalced };
}

test('4 Edit Orders: no-change totals, quantity change saves',
  async ({ page }) => {
    await loginAsAdmin(page);
    const oID = await testOrderId(page);
    const shown = await orderPageTotal(page, oID);

    await go(page, 'edit_orders', `oID=${oID}`);
    expect(await eoTotal(page), 'Edit Orders total = order page total')
      .toBeCloseTo(shown, 2);
    await expect(page.locator('#update-verify'),
      'no Update offered without changes').toBeHidden();

    await page.locator('button.eo-btn-prod-edit').first().click();
    const startQty = Number(await page.locator('#prod-qty').inputValue());
    // (the pop-up is left open; setQty() reloads the page)
    expect(startQty, 'first product quantity').toBeGreaterThan(0);

    const up = await setQty(page, oID, startQty + 1);
    expect(up.recalced, 'total changes when the quantity changes')
      .not.toBeCloseTo(up.before, 2);
    expect(await orderPageTotal(page, oID), 'new total saved')
      .toBeCloseTo(up.recalced, 2);

    const back = await setQty(page, oID, startQty);
    expect(back.recalced, 'total back to the start').toBeCloseTo(shown, 2);
    expect(await orderPageTotal(page, oID), 'original total saved')
      .toBeCloseTo(shown, 2);
  });
