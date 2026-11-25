// 7. Catalog: the category/product listing loads. Open one product's edit
//    page and save it without changes (Preview, then Update). The POSM
//    stock-by-attributes page opens.
const { test, expect, go, checkPage, submitForm, loginAsAdmin, cmdOf } =
  require('./helpers');

test('7 catalog listing, product save, POSM', async ({ page }) => {
  await loginAsAdmin(page);
  await go(page, 'category_product_listing');

  // Walk into categories until a product edit link shows.
  const editSel = 'a[href*="action=new_product"][href*="pID="]';
  const seen = new Set();
  for (let i = 0; i < 15 && !await page.locator(editSel).count(); i++) {
    const next = await page.locator(
      'a[href*="cmd=category_product_listing"][href*="cPath="]'
    ).evaluateAll((as, s) => as.map((a) => a.href)
      .find((h) => !s.includes(h)), [...seen]);
    expect(next, 'a category with products').toBeTruthy();
    seen.add(next);
    await page.goto(next);
    await checkPage(page, 'category');
  }
  const edit = page.locator(editSel).first();
  await Promise.all([page.waitForNavigation(), edit.click()]);
  await checkPage(page, 'product-edit');
  expect(cmdOf(page)).toBe('product');

  const form = page.locator('form[name=new_product]');
  await submitForm(page, form,
    form.locator('button.btn-primary[type=submit]').first());
  await checkPage(page, 'product-preview');
  const save = page.locator('form[action*="action=update_product"]');
  await expect(save, 'Update form on the preview page').toHaveCount(1);
  await submitForm(page, save,
    save.locator('button.btn-primary[type=submit]').first());
  await checkPage(page, 'product-saved');
  expect(cmdOf(page), 'back on the listing after saving')
    .toBe('category_product_listing');

  await go(page, 'products_options_stock');
});
