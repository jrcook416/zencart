// 1. Home page: loads, shows the logo, header, categories and footer.
const { test, expect, go } = require('./helpers');

test('1 home page shows logo, header, categories and footer',
  async ({ page }) => {
    await go(page, 'index');
    await expect(page.locator('#headerWrapper'), 'header').toBeVisible();
    const logo = page.locator('#logo img, #logoWrapper img').first();
    await expect(logo, 'logo image').toBeVisible();
    const loaded = await logo.evaluate((img) => img.naturalWidth > 0);
    expect(loaded, 'logo image file loads (not a broken image)').toBe(true);
    expect(await page.locator('a[href*="cPath="]').count(),
      'category links on the home page').toBeGreaterThan(0);
    await expect(page.locator('#footerWrapper'), 'footer').toBeVisible();
  });
