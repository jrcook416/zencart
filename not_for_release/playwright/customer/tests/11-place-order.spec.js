// Optional, OFF by default: places one real order in the LOCAL test
// database so the admin tests have a fresh order to work on.
// Turn on with ZC_PLACE_ORDER=1.
const { test, expect, checkPage, submitForm, mainPageOf } =
  require('./helpers');
const co = require('./checkout-agency');

test.skip(process.env.ZC_PLACE_ORDER !== '1',
  'placing an order is off (set ZC_PLACE_ORDER=1 to turn it on)');
test.skip((process.env.ZC_CHECKOUT_VARIANT || 'agency') !== 'agency',
  'checkout variant is not "agency"');

test('11 place one order (optional)', async ({ page }) => {
  await co.goToConfirm(page);
  await submitForm(page, page.locator('form[name=checkout_confirmation]'));
  await checkPage(page, 'order-placed');
  expect(mainPageOf(page), 'order placed').toBe('checkout_success');
});
