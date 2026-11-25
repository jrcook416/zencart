// 4. Login: a bad password is rejected with a message. The test customer
//    logs in and lands on My Account.
const { test, expect, CUSTOMER, login, loginAsCustomer, mainPageOf } =
  require('./helpers');

test('4a bad password is rejected', async ({ page }) => {
  await login(page, CUSTOMER.email, 'wrong-password-xyz');
  expect(mainPageOf(page), 'still on the login page').toBe('login');
  await expect(page.locator('body')).toContainText(
    'Sorry, there is no match for that email address and/or password');
});

test('4b test customer logs in and lands on My Account',
  async ({ page }) => {
    await loginAsCustomer(page);
  });
