# Playwright browser tests (v222-iems-dev)

Two separate test sets that drive a real browser (Chromium) against a
**local** copy of the store. They never touch dev or prod. They live in
`not_for_release/`, so they are not part of the store.

| Folder      | What it tests                         | Logs in as                   |
|-------------|---------------------------------------|------------------------------|
| `customer/` | the storefront, as a customer         | jeremiah@jeremiahcook.net    |
| `admin/`    | the admin, as staff                   | testadmin                    |

Each folder has its own settings and is run on its own.

## The order to use them

1. Customer tests pass locally.
2. Admin tests pass locally.
3. Push the branch to GitHub.
4. GitHub Actions runs the existing Unit, Store Feature and Admin
   Feature tests.
5. All green: good to go.

## Every test fails when a page

- shows a PHP error (`Warning: ... on line 12`) or Zen Cart's
  "An Error occurred" page,
- is blank,
- returns HTTP 500 or higher (including background requests),
- logs a browser console error or a JavaScript error, or
- opens a pop-up alert.

A screenshot of every page a test opens is saved in the report.

## Customer tests (`customer/tests`)

1. Home page: logo, header, category links, footer.
2. Browse (logged in; this store shows categories only to logged-in
   customers): a category, then a product: name, price, Add to Cart.
3. Search: a product is found by name; a nonsense word shows
   "There is no product that matches the search criteria" (this store
   shows it on the search page).
4. Login: a bad password is rejected; the test customer logs in (this
   store lands on the home page) and My Account opens.
5. Account: order history lists orders, one order opens, address book
   opens.
6. Cart: add, change the quantity to 3, remove, add again; the subtotal
   is right each time.
7. Checkout unit: the unit/agency drop-down is required (the store
   refuses an empty one) and the chosen unit stays selected.
8. Checkout shipping: IEMS pickup and delivery choices show. Pickup,
   Indianapolis EMS/Eskenazi and Marion County delivery are $0.00;
   Out-of-County delivery has a charge.
9. Checkout payment: the agency option (Invoice Billing to Agency or
   Indianapolis EMS Unit) shows; the confirm page shows
   "IEMS selection for this order". Stops there, no order placed.
10. Logoff: My Account then sends you to the login page.
11. Optional, **off** unless `ZC_PLACE_ORDER=1`: places one order.

Tests 7 to 9 and 11 are for the IEMS County Agency plugin on
v222-iems-dev (`ZC_CHECKOUT_VARIANT=agency`, the default). The live store
branches use the IEMS Legacy Bridge instead; a version of these tests for
those branches would be a new file for `ZC_CHECKOUT_VARIANT=legacy`. It
is not written yet.

## Admin tests (`admin/tests`)

1. Login: a bad password is rejected; testadmin reaches the dashboard.
2. Orders list: loads, the status filter works, order-number search
   opens the order.
3. Order detail: customer and delivery address, items, total. Changes
   the status with a comment (email: No) and checks the history.
4. Edit Orders: totals match the order page; changes the first item's
   quantity, checks the total changes and saves, then puts it back.
5. Packing slip and invoice: show the delivery address lines (the unit
   is one of them) and the items.
6. Customers: search finds the test customer; changes the telephone,
   checks it saved, then puts it back.
7. Catalog: listing loads; one product is saved with no changes
   (Preview, Update); the POSM stock page opens.
8. Shipping Export: skipped. It is part of the Legacy Bridge and is not
   on v222-iems-dev.
9. Configuration: My Store, Shipping/Packaging and Email open
   (view only).
10. Admin users and profiles open (view only).
11. Plugin manager lists Edit Orders and IEMS County Agency (view only).
12. Menu sweep: opens every page in the admin menu once. New menu pages
    are picked up automatically.
13. Logoff: the orders page then asks for login.

The order used by tests 2 to 5 is the newest order, unless `ZC_ORDER_ID`
is set. **What the admin tests change in the local test database:** that
order's status and history (a new comment each run), and the product's
last-modified date. The Edit Orders quantity and the customer's
telephone are put back. Nothing else is saved.

## One-time install (in WSL)

Needs Node.js 18 or newer in WSL (`node -v` shows the version). Run
once in each folder:

```
cd /mnt/c/dev/zencart/not_for_release/playwright/customer
npm install
cd ../admin
npm install
npx playwright install --with-deps chromium
```

- `npm install` puts the Playwright test runner (version 1.63.0) in a
  `node_modules` folder inside that test folder (git ignores it).
- `npx playwright install --with-deps chromium` downloads the Chromium
  browser to `~/.cache/ms-playwright` in WSL, and uses `sudo apt` to add
  the Linux libraries Chromium needs (asks for your password). Both
  folders share that one browser.

## Running

Your local store must be up (Apache and MariaDB in WSL, store at
http://localhost/zencart/).

```
cd /mnt/c/dev/zencart/not_for_release/playwright/customer
ZC_UNIT=AM001 ZC_PLACE_ORDER=1 npx playwright test

cd /mnt/c/dev/zencart/not_for_release/playwright/admin
npx playwright test
```

`ZC_UNIT=AM001` makes checkout use the unit set up in step 7.
`ZC_PLACE_ORDER=1` places one order in the test database, which the
admin tests then work on.

Good looks like:
- customer: a check mark on every line and `12 passed`.
- admin: `13 passed, 1 skipped`. The skip is Shipping Export, which is
  part of the Legacy Bridge on the live branches and is not in
  v222-iems-dev.

Not checked on this branch, for the same reason: the unit of measure
after the price (Legacy Bridge only). Any `failed` line names the test
and the reason.

To see screenshots and details: `npx playwright show-report` in the same
folder (opens a web page). One test only: `npx playwright test 06-cart`.

## Settings (environment variables)

All optional. Put them before the command, e.g.
`ZC_PLACE_ORDER=1 npx playwright test`.

| Variable              | Default                              |
|-----------------------|--------------------------------------|
| `ZC_BASE_URL`         | `http://localhost/zencart/`          |
| `ZC_ADMIN_URL`        | `<ZC_BASE_URL>wombat/`               |
| `ZC_CUSTOMER_EMAIL`   | `jeremiah@jeremiahcook.net`          |
| `ZC_CUSTOMER_PASSWORD`| `iems-test-2026`                     |
| `ZC_ADMIN_USER`       | `testadmin`                          |
| `ZC_ADMIN_PASSWORD`   | `iems-admin-2026`                    |
| `ZC_PRODUCT_ID`       | none: walks the categories           |
| `ZC_UNIT`             | none: first unit in the drop-down    |
| `ZC_ORDER_ID`         | none: newest order                   |
| `ZC_PLACE_ORDER`      | off; `1` places one order            |
| `ZC_CHECKOUT_VARIANT` | `agency`                             |

These are test-only logins that exist only in the scrubbed test
database.

## The local test database

The tests expect the scrubbed IEMS test database made on dev by
`not_for_release/iems-browser-tests/make-testdb.sh`. It sets the two
test logins above and turns store email off. To load a new copy
(replaces what is in `iems_testdb`):

```
gunzip -c /mnt/c/testdb/<file>.sql.gz | mysql -u iemstest -p iems_testdb
```

## Setup so every test runs (once per fresh test database)

The test database comes from the live IEMS store, which does not use the
IEMS County Agency plugin. These steps add the plugin and give the test
customer a county, agency and unit. They change only `iems_testdb`.

**1. Check the database (read only).** In WSL:

```
mysql -u iemstest -p iems_testdb -e "SHOW TABLES LIKE 'iems\_%';"
mysql -u iemstest -p iems_testdb -e "SHOW TABLES LIKE '%configuration';"
```

Good: the first shows nothing; the second shows exactly
`configuration` (no prefix). If not, stop: the steps below assume that.

**2. Load the county/agency/unit tables.** This creates `iems_counties`
(92), `iems_agencies` (123), `iems_units` (141) and `iems_import_log`.
The file is `zc_install/sql/iems_custom/iems_foundation.sql` in the repo;
on the local store `zc_install` is renamed `valpo`, so:

```
mysql -u iemstest -p iems_testdb \
  < /mnt/c/dev/zencart/valpo/sql/iems_custom/iems_foundation.sql
```

Good: no output. Check:

```
mysql -u iemstest -p iems_testdb -e "SELECT COUNT(*) FROM iems_units;"
```

shows `141`.

**3. Install the plugin.** Admin (http://localhost/zencart/wombat/) >
Modules > Plugin Manager > IEMS County Agency > Install (v1.9.6).
This adds its columns, the customer affiliation table, the menu pages
Customers > IEMS Agencies and Customers > IEMS Units, and Configuration >
IEMS Settings.

**4. Shipping modules.** Modules > Shipping:
- "Pickup at IEMS Logistics": Install, then Edit and fill Pickup
  Recipient `IEMS Logistics`, Pickup Street Address
  `3930 Georgetown Road`, Pickup City `Indianapolis`, Pickup Postcode
  `46254`. Update. (Pickup is hidden until these are filled.)
- "IEMS Agency Delivery": Install.

**5. Payment modules.** Modules > Payment: Install
"Invoice Billing to Agency" and "Indianapolis EMS Unit".

**6. The agency.** Customers > IEMS Agencies > search `IEMS` > Edit
"Indianapolis EMS": Delivery to Location `Enabled`, Shipping Category
`IEMS`, Payment Method `Invoice Billing to Agency`, Status Active. Save.

**7. The unit.** Customers > IEMS Units > search `AM001` > Edit
"AM001 IEMS Ambulance 001": Managed Delivery Address street
`3930 Georgetown Road`, city `Indianapolis`, postcode `46254`. Save.
(Delivery is only offered for a unit with an address.)

**8. The test customer.** Customers > Customers > search
`jeremiah@jeremiahcook.net` > Edit: IEMS County `49 Marion`, IEMS Agency
`49 IEMS Indianapolis EMS`. Update.

**9. Check (read only):**

```
mysql -u iemstest -p iems_testdb -e "
SELECT c.customers_email_address, k.county_number,
       a.agency_identifier, a.delivery_enabled,
       a.shipping_category, a.payment_mode
FROM iems_customer_affiliations f
JOIN customers c ON c.customers_id = f.customer_id
JOIN iems_counties k ON k.county_ID = f.county_ID
JOIN iems_agencies a ON a.agency_ID = f.agency_ID;"
```

Good: one row, `jeremiah@jeremiahcook.net 49 IEMS 1 iems invoice`.

With this setup the checkout offers "Pickup at IEMS Logistics" and
"Indianapolis EMS / Eskenazi Delivery" (both $0.00) and
"Invoice Billing to Agency".

## Adding a test

1. Add a file to `customer/tests` or `admin/tests` named
   `NN-what-it-tests.spec.js`. Files run in name order.
2. Start it with
   `const { test, expect, go, checkPage } = require('./helpers');`
   and use that `test`, so the error checks above apply.
3. Open pages with `go(page, 'name')` (store: `main_page`, admin: `cmd`).
   After a click that loads a new page, call `checkPage(page, 'label')`.
4. Run just your file: `npx playwright test NN-what`.
