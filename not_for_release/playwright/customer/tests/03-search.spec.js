// 3. Search: a known product is found; a nonsense search shows
//    "no results" without an error.
// Logs in first: the IEMS store only shows products to logged-in
// customers.
const { test, expect, go, checkPage, findProduct, mainPageOf,
  loginAsCustomer } = require('./helpers');

// Uses the search box in the header; if it is not on screen, opens the
// same search results page directly.
async function search(page, words) {
  const form = page.locator('form[name=quick_find_header]').first();
  const box = form.locator('input[name=keyword]');
  if (await box.count() && await box.isVisible()) {
    await box.fill(words);
    await Promise.all([page.waitForNavigation(), box.press('Enter')]);
    await checkPage(page, 'search-results');
  } else {
    await go(page, 'search_result', 'keyword=' + encodeURIComponent(words));
  }
  return mainPageOf(page);
}

test('3 search finds a product and handles no results',
  async ({ page }) => {
    await loginAsCustomer(page);
    await findProduct(page);
    const name = (await page.locator('h1.productName, h1[id$=productName]')
      .first().innerText()).trim();
    const pid = new URL(page.url()).searchParams.get('products_id');

    // With exactly one match the store opens that product directly.
    if (await search(page, name) === 'product_info') {
      expect(new URL(page.url()).searchParams.get('products_id'),
        `search for "${name}" opens product ${pid}`).toBe(pid);
    } else {
      expect(mainPageOf(page)).toBe('search_result');
      expect(await page.locator(`a[href*="products_id=${pid}"]`).count(),
        `"${name}" (id ${pid}) in the search results`).toBeGreaterThan(0);
    }

    expect(await search(page, 'zzqxv-no-such-item-9137'))
      .toBe('search_result');
    await expect(page.locator('body'))
      .toContainText('There is no product that matches the search criteria');
  });
