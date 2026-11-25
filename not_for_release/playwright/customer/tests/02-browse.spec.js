// 2. Browse: open a category, then a product page. Name, price and the
//    Add to Cart button show.
const { test, expect, findProduct } = require('./helpers');

test('2 browse to a product: name, price, Add to Cart',
  async ({ page }) => {
    await findProduct(page);
    await expect(page.locator('h1.productName, h1[id$=productName]').first(),
      'product name').not.toBeEmpty();
    const price = page.locator(
      '.productPriceTopPrice, .productPriceBottomPrice, [id*=productPrices]'
    ).first();
    await expect(price, 'product price').toContainText('$');
    await expect(page.locator('form[name=cart_quantity]').locator(
      'button[type=submit], input[type=submit], input[type=image]'
    ).first(), 'Add to Cart button').toBeVisible();
    // Unit of measure after the price comes from the IEMS Legacy Bridge,
    // which is not on v222-iems-dev, so it is not checked here.
    test.info().annotations.push({ type: 'not checked',
      description: 'unit of measure (Legacy Bridge only, not on '
        + 'v222-iems-dev)' });
  });
