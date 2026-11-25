// 7-9. Checkout for v222-iems-dev (IEMS County Agency plugin).
// Runs when ZC_CHECKOUT_VARIANT is "agency" (the default). A live-branch
// (Legacy Bridge) version would be a sibling file for variant "legacy".
//  7. Unit field: required, and the chosen unit is kept.
//  8. Shipping: the IEMS options show with the right prices.
//  9. Payment: the agency invoice/unit option shows; stops at the
//     confirm page without placing an order.
const { test, expect, checkPage, submitForm, mainPageOf } =
  require('./helpers');
const co = require('./checkout-agency');

test.skip((process.env.ZC_CHECKOUT_VARIANT || 'agency') !== 'agency',
  'checkout variant is not "agency"');

test('7 checkout: unit/agency field is required and saves',
  async ({ page }) => {
    await co.startCheckout(page);
    const sel = page.locator(co.UNIT_SELECT);
    await expect(sel, 'unit drop-down is marked required')
      .toHaveAttribute('required', /.*/);

    // Send the form with no unit (browser check switched off) to prove
    // the store itself refuses it.
    await sel.evaluate((s) => { s.required = false; s.value = ''; });
    await submitForm(page, page.locator('form[name=checkout_address]'));
    await checkPage(page, 'no-unit-sent');
    expect(mainPageOf(page)).toBe('checkout_shipping');
    await expect(page.locator('body')).toContainText(co.MSG_UNIT_REQUIRED);

    const unit = await co.chooseUnit(page);
    expect(await page.locator('input[name=shipping]').count(),
      `shipping choices load after picking "${unit.text}"`)
      .toBeGreaterThan(0);
  });

test('8 checkout: shipping options and prices', async ({ page }) => {
  await co.startCheckout(page);
  const unit = await co.chooseUnit(page);
  const opts = await co.shippingOptions(page);
  await test.info().attach('shipping-options.json',
    { body: JSON.stringify({ unit: unit.text, opts }, null, 2) });

  const iems = opts.filter((o) => /^iems(pickup|delivery)_/.test(o.value));
  expect(iems.length, `IEMS pickup/delivery offered for "${unit.text}"`)
    .toBeGreaterThan(0);
  for (const o of iems) {
    expect(o.price, `price shown for "${o.title}"`).not.toBeNull();
    if (o.title.includes(co.SHIP.outOfCounty)) {
      // flat rate + miles x per-mile rate
      expect(o.price, `${o.title} is charged`).toBeGreaterThan(0);
    } else if ([co.SHIP.pickup, co.SHIP.iems, co.SHIP.marion]
      .some((t) => o.title.includes(t))) {
      expect(o.price, `${o.title} is free`).toBe(0);
    } else {
      throw new Error(`unexpected IEMS shipping title: "${o.title}"`);
    }
  }
});

test('9 checkout: agency payment option, stop at confirm',
  async ({ page }) => {
    const { unit, ship, code } = await co.goToConfirm(page);
    await expect(page.locator('body'), 'unit carried to the order')
      .toContainText('IEMS selection for this order:');
    await expect(page.locator('#btn_submit'), 'Confirm Order button')
      .toBeVisible();
    // Stops here: no order is placed (see 11-place-order.spec.js).
    test.info().annotations.push({ type: 'used', description:
      `unit "${unit.text}", shipping ${ship.value}, payment ${code}` });
  });
