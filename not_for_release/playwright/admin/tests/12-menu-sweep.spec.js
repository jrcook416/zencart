// 12. Menu sweep: opens every page in the admin menu once and checks it
//     loads cleanly. Pages added to the menu later are covered
//     automatically. Only opens pages; it clicks nothing on them.
const { test, expect, pageProblems, loginAsAdmin } = require('./helpers');

test('12 every admin menu page loads', async ({ page }) => {
  test.setTimeout(20 * 60 * 1000);
  await loginAsAdmin(page);
  const links = await page.locator('nav.navbar .dropdown-menu a[href]')
    .evaluateAll((as) => [...new Set(as.map((a) => a.href))]);
  const todo = links.filter((h) => !/cmd=logoff|[?&]action=/.test(h));
  expect(todo.length, 'pages in the admin menu').toBeGreaterThan(10);

  const failures = [];
  for (const href of todo) {
    const label = 'menu-' + (new URL(href).searchParams.get('cmd') || 'x');
    try {
      const res = await page.goto(href);
      if (!res || res.status() >= 400) {
        failures.push(`HTTP ${res && res.status()} for ${href}`);
      }
      failures.push(...await pageProblems(page, label));
    } catch (e) {
      failures.push(`${href}: ${e.message.split('\n')[0]}`);
    }
  }
  await test.info().attach('menu-pages.txt', { body: todo.join('\n') });
  expect(failures, `${failures.length} of ${todo.length} menu pages `
    + 'had problems').toEqual([]);
});
