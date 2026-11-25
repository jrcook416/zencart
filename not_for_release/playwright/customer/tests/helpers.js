// Shared pieces for the customer harness.
//
// Every test uses the `test` exported here. It watches the page and
// fails the test on: a browser console error, an uncaught JavaScript
// error, a pop-up dialog, or any HTTP 500+ response. checkPage() also
// fails on a blank page or PHP error text, and saves a screenshot.
const base = require('@playwright/test');

const CUSTOMER = {
  email: process.env.ZC_CUSTOMER_EMAIL || 'jeremiah@jeremiahcook.net',
  password: process.env.ZC_CUSTOMER_PASSWORD || 'iems-test-2026',
};

// PHP's own error format ("Warning: ... in /x.php on line 12") and Zen
// Cart's generic error page.
const PHP_ERROR = new RegExp(
  '(Fatal error|Parse error|Warning|Notice|Deprecated|' +
  'Uncaught [A-Za-z\\\\]+)\\s*:[\\s\\S]{0,400}? on line \\d+' +
  '|An Error occurred, please refresh the page and try again',
  'i'
);

const test = base.test.extend({
  page: async ({ page }, use, testInfo) => {
    const problems = [];
    page.on('console', (msg) => {
      if (msg.type() === 'error') {
        problems.push(`console error on ${page.url()}: ${msg.text()}`);
      }
    });
    page.on('pageerror', (err) => {
      problems.push(`JavaScript error on ${page.url()}: ${err.message}`);
    });
    page.on('dialog', async (dialog) => {
      problems.push(`pop-up dialog on ${page.url()}: ${dialog.message()}`);
      await dialog.dismiss().catch(() => {});
    });
    page.on('response', (res) => {
      if (res.status() >= 500) {
        problems.push(`HTTP ${res.status()} from ${res.url()}`);
      }
    });
    await use(page);
    if (problems.length) {
      await testInfo.attach('problems.txt', { body: problems.join('\n') });
    }
    base.expect(problems, 'page problems (see problems.txt)').toEqual([]);
  },
});
const expect = base.expect;

let shotNo = 0;
// Checks the page that is showing now and saves a screenshot of it.
async function checkPage(page, label) {
  const info = test.info();
  await page.waitForLoadState('load');
  const text = await page.locator('body').innerText().catch(() => '');
  shotNo += 1;
  const name = `${String(shotNo).padStart(2, '0')}-${label}.png`
    .replace(/[^A-Za-z0-9.-]+/g, '-');
  const file = info.outputPath(name);
  await page.screenshot({ path: file, fullPage: true });
  await info.attach(label, { path: file, contentType: 'image/png' });
  expect(text.trim().length, `blank page: ${label} (${page.url()})`)
    .toBeGreaterThan(20);
  const m = text.match(PHP_ERROR);
  expect(m ? m[0] : null, `PHP/Zen Cart error on ${label}`).toBeNull();
}

// Store page by its main_page name, e.g. go(page, 'login').
async function go(page, mainPage, params = '') {
  const url = `index.php?main_page=${mainPage}${params ? '&' + params : ''}`;
  const res = await page.goto(url);
  expect(res, `no response for ${url}`).not.toBeNull();
  expect(res.status(), `HTTP status for ${url}`).toBeLessThan(400);
  await checkPage(page, mainPage);
}

function mainPageOf(page) {
  return new URL(page.url()).searchParams.get('main_page') || 'index';
}

// Submits a form the way a person would: clicks its first submit button,
// then waits for the next page to load.
async function submitForm(page, form) {
  const btn = form.locator('button[type=submit]:visible, '
    + 'input[type=submit]:visible, input[type=image]:visible').first();
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }),
    btn.click(),
  ]);
}

// Opens My Account while logged out (the store sends you to the login
// page), logs in, and returns the page the store lands on.
async function login(page, email = CUSTOMER.email,
  password = CUSTOMER.password) {
  await page.goto('index.php?main_page=account');
  await checkPage(page, 'login-page');
  expect(mainPageOf(page), 'logged-out My Account should go to login')
    .toBe('login');
  const form = page.locator('form[name=loginForm]:visible').first();
  await form.locator('input[name=email_address]').fill(email);
  await form.locator('input[name=password]').fill(password);
  await submitForm(page, form);
  await checkPage(page, 'after-login');
  return mainPageOf(page);
}

// Logs in as the test customer. This store sends a customer to the home
// page after login (not My Account), so this checks the Log Out link
// shows, then opens My Account.
async function loginAsCustomer(page) {
  await login(page);
  expect(await page.locator('a[href*="main_page=logoff"]').count(),
    'logged in: Log Out link shows (check the login in README.md)')
    .toBeGreaterThan(0);
  await go(page, 'account');
  expect(mainPageOf(page), 'My Account opens').toBe('account');
}

// Last money amount in a piece of text, as a number. "$1,234.50" -> 1234.5
function money(text) {
  const all = String(text).match(/-?\$\s?[\d,]+\.\d{2}/g);
  expect(all, `no $ amount in "${text}"`).not.toBeNull();
  return Number(all[all.length - 1].replace(/[$,\s]/g, ''));
}

// Finds a product page that has an Add to Cart button. Uses ZC_PRODUCT_ID
// when set; otherwise walks the categories from the home page.
async function findProduct(page) {
  if (process.env.ZC_PRODUCT_ID) {
    await go(page, 'product_info', `products_id=${process.env.ZC_PRODUCT_ID}`);
    return;
  }
  const seen = new Set();
  const queue = [];
  await go(page, 'index');
  const hrefs = async (sel) => page.locator(sel).evaluateAll(
    (els) => [...new Set(els.map((a) => a.href))]
  );
  queue.push(...await hrefs('a[href*="cPath="]'));
  while (queue.length && seen.size < 15) {
    const url = queue.shift();
    if (seen.has(url)) continue;
    seen.add(url);
    await page.goto(url);
    await checkPage(page, 'category');
    const products = await hrefs(
      '.centerColumn a[href*="products_id="], ' +
      '#productsListing a[href*="products_id="]'
    );
    for (const p of products.slice(0, 5)) {
      if (!/main_page=product_info/.test(p)) continue;
      await page.goto(p);
      if (await addToCartForm(page).count()) {
        await checkPage(page, 'product');
        return;
      }
    }
    queue.unshift(...await hrefs('.centerColumn a[href*="cPath="]'));
  }
  throw new Error('No product with an Add to Cart button was found by '
    + 'walking the categories. Set ZC_PRODUCT_ID to a product id.');
}

function addToCartForm(page) {
  return page.locator('form[name=cart_quantity]').filter({
    has: page.locator(
      'button[type=submit], input[type=submit], input[type=image]'
    ),
  });
}

// On a product page: adds qty to the cart, then opens the cart.
async function addToCart(page, qty = 1) {
  const form = addToCartForm(page).first();
  const q = form.locator('input[name=cart_quantity]');
  if (await q.count() && await q.first().isVisible()) {
    await q.first().fill(String(qty));
  }
  await submitForm(page, form);
  await checkPage(page, 'after-add-to-cart');
  if (mainPageOf(page) !== 'shopping_cart') await go(page, 'shopping_cart');
}

async function emptyCart(page) {
  await go(page, 'shopping_cart');
  for (let i = 0; i < 30; i++) {
    const del = page.locator('a[href*="action=remove_product"]').first();
    if (!await del.count()) break;
    await Promise.all([page.waitForNavigation(), del.click()]);
  }
  await expect(page.locator('a[href*="action=remove_product"]'))
    .toHaveCount(0);
}

module.exports = {
  test, expect, CUSTOMER, checkPage, go, mainPageOf, submitForm, login,
  loginAsCustomer, money, findProduct, addToCart, emptyCart,
};
