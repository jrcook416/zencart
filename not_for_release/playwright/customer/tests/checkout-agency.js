// Checkout steps for the IEMS County Agency plugin (v222-iems-dev):
// the unit/agency drop-down (iems_unit_id) on the checkout shipping page,
// IEMS pickup/delivery shipping and the agency payment modules.
// The unit chosen is written to the order's delivery_suburb.
const { expect, go, checkPage, submitForm, loginAsCustomer, findProduct,
  addToCart, emptyCart, mainPageOf } = require('./helpers');

const UNIT_SELECT = '#iems_unit_id';
const MSG_UNIT_REQUIRED =
  'Select an IEMS unit or agency before continuing checkout.';
const SHIP = {
  pickup: 'Pickup at IEMS Logistics',
  iems: 'Indianapolis EMS / Eskenazi Delivery',
  marion: 'Marion County Agency Delivery',
  outOfCounty: 'Out-of-County Agency Delivery',
};
const PAYMENT_CODES = ['iemsinvoice', 'iemsunit'];

// Logs in, puts one product in an empty cart and opens checkout.
async function startCheckout(page) {
  await loginAsCustomer(page);
  await emptyCart(page);
  await findProduct(page);
  await addToCart(page, 1);
  await go(page, 'checkout_shipping');
  expect(mainPageOf(page), 'checkout shipping page opens')
    .toBe('checkout_shipping');
  const n = await page.locator(UNIT_SELECT).count();
  expect(n, 'IEMS unit drop-down is missing from checkout. Is the IEMS '
    + 'County Agency plugin installed and enabled in this store, and does '
    + 'the test customer have a county/agency?').toBe(1);
}

async function unitOptions(page) {
  return page.locator(`${UNIT_SELECT} option`).evaluateAll((opts) =>
    opts.filter((o) => o.value !== '')
      .map((o) => ({ value: o.value, text: o.textContent.trim() })));
}

// Picks ZC_UNIT (matched against the option text) or the first unit.
async function chooseUnit(page) {
  const opts = await unitOptions(page);
  expect(opts.length, 'units offered to the test customer')
    .toBeGreaterThan(0);
  const want = process.env.ZC_UNIT;
  const pick = want ? opts.find((o) => o.text.includes(want)) : opts[0];
  expect(pick, `unit "${want}" in the drop-down`).toBeTruthy();
  const form = page.locator('form[name=checkout_address]');
  await page.locator(UNIT_SELECT).selectOption(pick.value);
  await submitForm(page, form);
  await checkPage(page, 'unit-chosen');
  expect(mainPageOf(page)).toBe('checkout_shipping');
  await expect(page.locator(UNIT_SELECT)).toHaveValue(pick.value);
  return pick;
}

// Shipping choices on the page: [{ value, title, price }].
async function shippingOptions(page) {
  return page.locator('input[name=shipping]').evaluateAll((inputs) =>
    inputs.map((inp) => {
      let title = '';
      if (inp.id) {
        const l = document.querySelector(`label[for="${inp.id}"]`);
        if (l) title = l.textContent.trim();
      }
      // Nearest block holding this choice and a $ amount, and no
      // other choice.
      let el = inp.parentElement;
      let price = null;
      while (el && el !== document.body) {
        const others = el.querySelectorAll('input[name=shipping]').length;
        if (others > 1) break;
        const m = el.textContent.match(/\$\s?[\d,]+\.\d{2}/);
        if (m) { price = Number(m[0].replace(/[$,\s]/g, '')); break; }
        if (!title) title = el.textContent.trim();
        el = el.parentElement;
      }
      return { value: inp.value, title, price };
    }));
}

// From a fresh checkout: picks a unit, an IEMS shipping choice (pickup
// first) and the agency payment option, and stops on the confirm page.
async function goToConfirm(page) {
  await startCheckout(page);
  const unit = await chooseUnit(page);
  const opts = await shippingOptions(page);
  const ship = opts.find((o) => o.value.startsWith('iemspickup_'))
    || opts.find((o) => o.value.startsWith('iemsdelivery_'));
  expect(ship, 'an IEMS shipping choice').toBeTruthy();
  const radio = page.locator(`input[name=shipping][value="${ship.value}"]`);
  if (await radio.getAttribute('type') === 'radio') await radio.check();
  await submitForm(page, page.locator('form[name=checkout_address]'));
  await checkPage(page, 'payment');
  expect(mainPageOf(page), 'payment page').toBe('checkout_payment');

  const codes = await page.locator('form[name=checkout_payment] '
    + 'input[name=payment]').evaluateAll((els) => els.map((e) => e.value));
  const code = PAYMENT_CODES.find((c) => codes.includes(c));
  expect(code, `agency payment option (one of ${PAYMENT_CODES}) in `
    + `[${codes}]`).toBeTruthy();
  const p = page.locator(`input[name=payment][value="${code}"]`);
  if (await p.getAttribute('type') === 'radio') await p.check();
  const cond = page.locator('#conditions');
  if (await cond.count()) await cond.check();
  await submitForm(page, page.locator('form[name=checkout_payment]'));
  await checkPage(page, 'confirm');
  expect(mainPageOf(page), 'confirm page').toBe('checkout_confirmation');
  return { unit, ship, code };
}

module.exports = { goToConfirm, UNIT_SELECT, MSG_UNIT_REQUIRED, SHIP, PAYMENT_CODES,
  startCheckout, unitOptions, chooseUnit, shippingOptions };
