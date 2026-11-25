// 6. Cart: add a product, change the quantity, remove it, add it again.
//    The subtotal updates each time.
const { test, expect, go, checkPage, submitForm, loginAsCustomer,
  findProduct, addToCart, emptyCart, money } = require('./helpers');

async function subtotal(page) {
  const t = page.locator('#cartTotal, #cartSubTotal').first();
  await expect(t, 'cart subtotal').toBeVisible();
  return money(await t.innerText());
}

test('6 cart: add, change quantity, remove, add again', async ({ page }) => {
  await loginAsCustomer(page);
  await emptyCart(page);
  await findProduct(page);
  const productUrl = page.url();

  await addToCart(page, 1);
  const one = await subtotal(page);
  expect(one, 'subtotal after adding 1').toBeGreaterThanOrEqual(0);

  const form = page.locator('form#shoppingCartForm');
  await form.locator('input[name="cart_quantity[]"]').first().fill('3');
  await submitForm(page, form);
  await checkPage(page, 'cart-qty-3');
  await expect(page.locator('input[name="cart_quantity[]"]').first())
    .toHaveValue('3');
  expect(await subtotal(page), 'subtotal for 3 = 3 x subtotal for 1')
    .toBeCloseTo(one * 3, 2);

  const del = page.locator('a[href*="action=remove_product"]:visible').first();
  await Promise.all([page.waitForNavigation(), del.click()]);
  await checkPage(page, 'cart-removed');
  await expect(page.locator('body'))
    .toContainText('Your Shopping Cart is empty');

  await page.goto(productUrl);
  await checkPage(page, 'product-again');
  await addToCart(page, 1);
  expect(await subtotal(page), 'subtotal after adding again')
    .toBeCloseTo(one, 2);
});
