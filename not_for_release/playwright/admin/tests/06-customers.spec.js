// 6. Customers: the list loads and search finds the test customer. Open
//    them, edit a field (telephone), save, and check it saved. The old
//    telephone is then put back.
const { test, expect, go, checkPage, submitForm, loginAsAdmin,
  CUSTOMER_EMAIL } = require('./helpers');

async function saveTelephone(page, cID, phone) {
  await go(page, 'customers', `cID=${cID}&action=edit`);
  const form = page.locator('form[name=customers]');
  const tel = form.locator('input[name=customers_telephone]');
  const old = await tel.inputValue();
  await tel.fill(phone);
  await submitForm(page, form,
    form.locator('button.btn-primary[type=submit]').first());
  await checkPage(page, 'customer-saved');
  await go(page, 'customers', `cID=${cID}&action=edit`);
  await expect(page.locator('input[name=customers_telephone]'),
    'telephone saved').toHaveValue(phone);
  return old;
}

test('6 customers: list, search, edit and save', async ({ page }) => {
  await loginAsAdmin(page);
  await go(page, 'customers');
  await go(page, 'customers',
    'search=' + encodeURIComponent(CUSTOMER_EMAIL));
  await expect(page.locator('body'), 'test customer found')
    .toContainText(CUSTOMER_EMAIL);
  const cID = await page.locator('a[href*="cID="]').evaluateAll((as) =>
    as.map((a) => new URL(a.href).searchParams.get('cID'))
      .find((id) => id && /^\d+$/.test(id)));
  expect(cID, 'customer id from the search results').toBeTruthy();

  const old = await saveTelephone(page, cID, '555-0199');
  await saveTelephone(page, cID, old === '555-0199' ? '555-0100' : old);
});
