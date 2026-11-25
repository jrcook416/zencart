// Shared pieces for the admin harness.
//
// Every test uses the `test` exported here. It watches the page and
// fails the test on: a browser console error, an uncaught JavaScript
// error, a pop-up dialog, or any HTTP 500+ response. checkPage() also
// fails on a blank page, PHP error text or being logged out, and saves a
// screenshot of the page.
const base = require('@playwright/test');

const ADMIN = {
  user: process.env.ZC_ADMIN_USER || 'testadmin',
  password: process.env.ZC_ADMIN_PASSWORD || 'iems-admin-2026',
};
const CUSTOMER_EMAIL =
  process.env.ZC_CUSTOMER_EMAIL || 'jeremiah@jeremiahcook.net';

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

function cmdOf(page) {
  return new URL(page.url()).searchParams.get('cmd') || '';
}

// Problems on the page showing now, as a list of strings (empty = good).
// Saves a screenshot either way.
let shotNo = 0;
async function pageProblems(page, label) {
  const info = test.info();
  await page.waitForLoadState('load');
  const text = await page.locator('body').innerText().catch(() => '');
  shotNo += 1;
  const name = `${String(shotNo).padStart(3, '0')}-${label}.png`
    .replace(/[^A-Za-z0-9.-]+/g, '-');
  const file = info.outputPath(name);
  await page.screenshot({ path: file, fullPage: true });
  await info.attach(label, { path: file, contentType: 'image/png' });
  const out = [];
  if (text.trim().length <= 20) out.push(`blank page: ${label}`);
  const m = text.match(PHP_ERROR);
  if (m) out.push(`PHP/Zen Cart error on ${label}: ${m[0]}`);
  if (label !== 'login' && cmdOf(page) === 'login') {
    out.push(`sent to the login page (logged out) at ${label}`);
  }
  return out.map((p) => `${p} (${page.url()})`);
}

async function checkPage(page, label) {
  expect(await pageProblems(page, label)).toEqual([]);
}

// Admin page by its cmd name, e.g. go(page, 'orders', 'oID=5').
async function go(page, cmd, params = '') {
  const url = `index.php?cmd=${cmd}${params ? '&' + params : ''}`;
  const res = await page.goto(url);
  expect(res, `no response for ${url}`).not.toBeNull();
  expect(res.status(), `HTTP status for ${url}`).toBeLessThan(400);
  await checkPage(page, cmd);
}

// Clicks the form's first visible submit button and waits for the next
// page to load.
async function submitForm(page, form, button) {
  const btn = button || form.locator('button[type=submit]:visible, '
    + 'input[type=submit]:visible, input[type=image]:visible').first();
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }),
    btn.click(),
  ]);
}

async function login(page, user = ADMIN.user, password = ADMIN.password) {
  await page.goto('index.php?cmd=login');
  await checkPage(page, 'login');
  await page.locator('input[name=admin_name]').fill(user);
  await page.locator('input[name=admin_pass]').fill(password);
  await submitForm(page, page.locator('form').filter({
    has: page.locator('input[name=admin_pass]') }).first());
}

async function loginAsAdmin(page) {
  await login(page);
  await checkPage(page, 'dashboard');
  expect(cmdOf(page), 'testadmin should reach the dashboard '
    + '(check the login in README.md)').not.toBe('login');
  await expect(page.locator('nav.navbar'), 'admin menu').toBeVisible();
}

// Last money amount in a piece of text, as a number. "$1,234.50" -> 1234.5
function money(text) {
  const all = String(text).match(/-?\$\s?[\d,]+\.\d{2}/g);
  expect(all, `no $ amount in "${text}"`).not.toBeNull();
  return Number(all[all.length - 1].replace(/[$,\s]/g, ''));
}

// The order the tests work on: ZC_ORDER_ID, or the newest order.
async function testOrderId(page) {
  if (process.env.ZC_ORDER_ID) return process.env.ZC_ORDER_ID;
  await go(page, 'orders');
  const ids = await page.locator('#orders-table a[href*="oID="]')
    .evaluateAll((as) => as.map((a) => Number(
      new URL(a.href).searchParams.get('oID'))).filter((n) => n > 0));
  expect(ids.length, 'orders in the orders list').toBeGreaterThan(0);
  return String(Math.max(...ids));
}

// Types into a textarea. When the store's HTML editor (TinyMCE or
// CKEditor) has replaced it, the text goes in through the editor instead.
async function fillText(page, selector, text) {
  const box = page.locator(selector).first();
  if (await box.isVisible()) {
    await box.fill(text);
    return;
  }
  await page.evaluate(([sel, t]) => {
    const el = document.querySelector(sel);
    if (window.tinymce && el && window.tinymce.get(el.id)) {
      window.tinymce.get(el.id).setContent(t);
    } else if (window.CKEDITOR && el && window.CKEDITOR.instances[el.id]) {
      window.CKEDITOR.instances[el.id].setData(t);
    }
    if (el) el.value = t;
  }, [selector, text]);
}

// Order total shown on the order's detail page.
async function orderPageTotal(page, oID) {
  await go(page, 'orders', `oID=${oID}&action=edit`);
  return money(await page.locator('.ot-total-Amount').first().innerText());
}

module.exports = {
  test, expect, ADMIN, CUSTOMER_EMAIL, cmdOf, pageProblems, checkPage, go,
  submitForm, login, loginAsAdmin, money, testOrderId, orderPageTotal,
  fillText,
};
